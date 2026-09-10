<?php

namespace Benchero\Controllers\Public;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Database\Database;
use Benchero\Repositories\FixtureRepository;
use Benchero\Repositories\SeasonRepository;
use DateTime;
use DateTimeZone;

class FixtureController extends Controller
{
    private FixtureRepository $repo;
    private SeasonRepository $seasonRepo;
    private \PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new FixtureRepository();
        $this->seasonRepo = new SeasonRepository();
        $this->db = Database::getConnection();
    }

    public function index(Request $request, array $params): Response
    {
        try {
            $orgSlug = $params['org_slug'] ?? '';
            $sportSlug = $params['sport_slug'] ?? '';
    
            // 1. Resolve Organization
            $stmt = $this->db->prepare("SELECT id, name, slug, timezone FROM organizations WHERE slug = :slug AND deleted_at IS NULL");
            $stmt->execute(['slug' => $orgSlug]);
            $org = $stmt->fetch(\PDO::FETCH_ASSOC);
    
            if (!$org) {
                return new Response('404 Not Found - Organization not found', 404);
            }
    
            // Check backend subscription public profile visibility
            $subService = new \Benchero\Services\SubscriptionService();
            if (!$subService->isPublicProfileVisible($org['id'])) {
                return $this->render('public/locked_profile', ['org' => $org]);
            }
    
            // 2. Resolve Sport
            $stmt = $this->db->prepare("
                SELECT s.id, s.name, s.slug 
                FROM sports s 
                JOIN organization_sports os ON s.id = os.sport_id 
                WHERE s.slug = :sport_slug 
                AND os.organization_id = :org_id 
                AND s.is_active = 1 
                AND os.is_active = 1
            ");
            $stmt->execute(['sport_slug' => $sportSlug, 'org_id' => $org['id']]);
            $sport = $stmt->fetch(\PDO::FETCH_ASSOC);
    
            if (!$sport) {
                return new Response('404 Not Found - Sport not found', 404);
            }
    
            // 3. Resolve Season
            $seasonId = $_GET['season_id'] ?? null;
            $seasons = $this->seasonRepo->findActiveByOrgAndSport($org['id'], $sport['id']);
    
            if (!$seasonId && !empty($seasons)) {
                $seasonId = $seasons[0]['id'];
            }
    
            $fixtures = [];
            if ($seasonId) {
                $fixtures = $this->repo->findPublicBySeason($seasonId, $org['id'], $sport['id']);
                
                // Convert UTC to Organization Timezone
                $tz = new DateTimeZone($org['timezone'] ?? 'UTC');
                $utcTz = new DateTimeZone('UTC');
                foreach ($fixtures as &$f) {
                    $dt = new DateTime($f['scheduled_at'], $utcTz);
                    $dt->setTimezone($tz);
                    $f['scheduled_at_local'] = $dt->format('l, F j, Y - H:i');
                }
            }
    
            return $this->render('public/fixtures', [
                'org' => $org,
                'sport' => $sport,
                'seasons' => $seasons,
                'seasonId' => $seasonId,
                'fixtures' => $fixtures,
                'title' => $org['name'] . ' ' . $sport['name'] . ' Fixtures'
            ]);
        } catch (\Throwable $e) {
            return new Response("HTTP 500 Debug Info:\n\n" . $e->getMessage() . "\n\n" . $e->getTraceAsString(), 500, ['Content-Type' => 'text/plain']);
        }
    }
}
