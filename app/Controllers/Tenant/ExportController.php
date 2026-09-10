<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\EntitlementService;
use Benchero\Services\OrganizationService;
use PDO;

class ExportController extends Controller
{
    private PDO $db;
    private OrganizationService $orgService;
    private EntitlementService $entitlementService;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getConnection();
        $this->orgService = new OrganizationService();
        $this->entitlementService = new EntitlementService($this->db);
    }

    public function export(Request $request, string $slug, string $type): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        // Check Pro Entitlement for Data Export
        if (!$this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_DATA_EXPORT)) {
            return $this->error('Data export requires a Benchero Pro subscription plan.', 403);
        }

        $format = strtolower($request->get('format', 'csv'));
        $data = [];
        $filename = "{$org['slug']}_{$type}_export_" . date('Y-m-d');

        switch ($type) {
            case 'players':
                $stmt = $this->db->prepare("
                    SELECT id, display_name, first_name, last_name, position, jersey_number, date_of_birth, nationality, preferred_foot, status, created_at
                    FROM players WHERE organization_id = ? AND deleted_at IS NULL ORDER BY display_name ASC
                ");
                $stmt->execute([$org['id']]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'teams':
                $stmt = $this->db->prepare("
                    SELECT id, name, slug, gender, age_group, description, is_active, created_at
                    FROM teams WHERE organization_id = ? AND deleted_at IS NULL ORDER BY name ASC
                ");
                $stmt->execute([$org['id']]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'staff':
                $stmt = $this->db->prepare("
                    SELECT id, name, role, email, phone, bio, display_order, created_at
                    FROM staff WHERE organization_id = ? ORDER BY display_order ASC
                ");
                $stmt->execute([$org['id']]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'fixtures':
                $stmt = $this->db->prepare("
                    SELECT f.id, f.fixture_date, f.venue, f.status, f.home_score, f.away_score, ht.name as home_team, at.name as away_team
                    FROM fixtures f
                    LEFT JOIN teams ht ON f.home_team_id = ht.id
                    LEFT JOIN teams at ON f.away_team_id = at.id
                    WHERE f.organization_id = ? AND f.deleted_at IS NULL ORDER BY f.fixture_date DESC
                ");
                $stmt->execute([$org['id']]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'news':
                $stmt = $this->db->prepare("
                    SELECT id, title, category, excerpt, published_at, created_at
                    FROM news_articles WHERE organization_id = ? ORDER BY published_at DESC
                ");
                $stmt->execute([$org['id']]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            default:
                return $this->error('Invalid export type requested', 400);
        }

        if ($format === 'json') {
            header('Content-Type: application/json');
            header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
            echo json_encode(['organization' => $org['name'], 'exported_at' => date('Y-m-d H:i:s'), 'type' => $type, 'count' => count($data), 'records' => $data], JSON_PRETTY_PRINT);
            exit;
        }

        // CSV format fallback
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");

        $output = fopen('php://output', 'w');
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ['No records found']);
        }
        fclose($output);
        exit;
    }
}
