<?php

namespace Benchero\Controllers\Public;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use PDO;

class PublicClubController extends Controller
{
    public function show(Request $request, array $params): Response
    {
        $logFile = __DIR__ . '/../../../storage/logs/debug_trace.log';
        $log = function ($msg) use ($logFile) {
            $entry = date('[Y-m-d H:i:s] ') . $msg . "\n";
            error_log('[BENCHERO_TRACE] ' . $msg);
            @file_put_contents($logFile, $entry, FILE_APPEND);
        };

        try {
            $log('Step 1: Entry into PublicClubController::show()');
            $slug = $params['slug'] ?? '';
            $db = Database::getConnection();

            $log('Step 2: Fetching organization details for slug=' . $slug);
            $stmt = $db->prepare("
                SELECT id, name, slug, country, timezone, created_at
                FROM organizations
                WHERE slug = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$slug]);
            $org = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$org) {
                $log('Step 2a: Organization not found, returning 404');
                return $this->render('errors/404', ['title' => 'Club Not Found'], 404);
            }
            $log('Step 2b: Organization found ID=' . $org['id']);

            $log('Step 3: Checking isPublicProfileVisible()');
            $subService = new \Benchero\Services\SubscriptionService();
            if (!$subService->isPublicProfileVisible($org['id'])) {
                $log('Step 3a: Profile locked');
                return $this->render('public/locked_profile', ['org' => $org]);
            }
            $log('Step 3b: Profile visible');

            $log('Step 4: Fetching organization active sports');
            $sportsStmt = $db->prepare("
                SELECT s.id, s.name, s.slug
                FROM sports s
                JOIN organization_sports os ON os.sport_id = s.id
                WHERE os.organization_id = ? AND os.is_active = 1 AND s.is_active = 1
            ");
            $sportsStmt->execute([$org['id']]);
            $sports = $sportsStmt->fetchAll(PDO::FETCH_ASSOC);
            $log('Step 4b: Sports count=' . count($sports));

            $log('Step 5: Fetching recent public fixtures');
            $fixturesStmt = $db->prepare("
                SELECT f.id, f.scheduled_at, f.venue_name, f.competition_type, f.competition_name, f.status, f.home_score, f.away_score,
                       ht.name as home_team_name, at.name as away_team_name, s.name as sport_name
                FROM fixtures f
                JOIN teams ht ON f.home_team_id = ht.id
                JOIN teams at ON f.away_team_id = at.id
                JOIN sports s ON f.sport_id = s.id
                WHERE f.organization_id = ? AND f.deleted_at IS NULL
                ORDER BY f.scheduled_at DESC
                LIMIT 15
            ");
            $fixturesStmt->execute([$org['id']]);
            $fixtures = $fixturesStmt->fetchAll(PDO::FETCH_ASSOC);
            $log('Step 5b: Fixtures count=' . count($fixtures));

            $log('Step 6: Rendering view public/club');
            $res = $this->render('public/club', [
                'title' => htmlspecialchars($org['name']) . ' — Benchero Public Club Page',
                'org' => $org,
                'sports' => $sports,
                'fixtures' => $fixtures
            ]);
            $log('Step 6b: View rendered successfully');
            return $res;
        } catch (\Throwable $e) {
            $errDetail = "EXCEPTION: " . get_class($e) . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString();
            $log($errDetail);
            // Return generic 500 error page to browser, NEVER display stack trace to user
            return new Response("Internal Server Error", 500);
        }
    }
}
