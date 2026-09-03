<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Database\Database;

class SportController
{
    public function index(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $db = Database::getConnection();

        // Get all global sports
        $stmt = $db->prepare("SELECT * FROM sports ORDER BY name ASC");
        $stmt->execute();
        $globalSports = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get organization's active sports
        $stmt = $db->prepare("SELECT sport_id FROM organization_sports WHERE organization_id = :org_id AND is_active = 1");
        $stmt->execute(['org_id' => $tenant['id']]);
        $activeSportIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $sports = array_map(function($sport) use ($activeSportIds) {
            $sport['org_active'] = in_array($sport['id'], $activeSportIds);
            return $sport;
        }, $globalSports);

        ob_start();
        require __DIR__ . '/../../../views/tenant/sports/index.php';
        return new Response(ob_get_clean());
    }

    public function toggle(Request $request): Response
    {
        // Security checks
        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $role = $request->getAttribute('tenant_role');

        if ($role !== 'owner' && $role !== 'admin') {
            return new Response('403 Forbidden - Insufficient permissions', 403);
        }

        $sportId = $_POST['sport_id'] ?? null;
        $action = $_POST['action'] ?? null; // 'activate' or 'deactivate'

        if (!$sportId || !in_array($action, ['activate', 'deactivate'])) {
            return new Response('400 Bad Request', 400);
        }

        $db = Database::getConnection();

        if ($action === 'activate') {
            $stmt = $db->prepare("
                INSERT INTO organization_sports (organization_id, sport_id, is_active) 
                VALUES (:org_id, :sport_id, 1) 
                ON DUPLICATE KEY UPDATE is_active = 1
            ");
        } else {
            $stmt = $db->prepare("
                UPDATE organization_sports 
                SET is_active = 0 
                WHERE organization_id = :org_id AND sport_id = :sport_id
            ");
        }

        $stmt->execute([
            'org_id' => $tenant['id'],
            'sport_id' => $sportId
        ]);

        return Response::redirect("/o/{$tenant['slug']}/sports");
    }
}
