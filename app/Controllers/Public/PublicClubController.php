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
        $slug = $params['slug'] ?? '';
        $db = Database::getConnection();

        // Fetch public organization details
        $stmt = $db->prepare("
            SELECT id, name, slug, country, timezone, created_at
            FROM organizations
            WHERE slug = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$slug]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$org) {
            return $this->render('errors/404', ['title' => 'Club Not Found'], 404);
        }

        // Check backend subscription public profile visibility
        $subService = new \Benchero\Services\SubscriptionService();
        if (!$subService->isPublicProfileVisible($org['id'])) {
            return $this->render('public/locked_profile', ['org' => $org]);
        }

        // Fetch active sports for this organization (collated to prevent MySQL 1267 mix error)
        $sportsStmt = $db->prepare("
            SELECT s.id, s.name, s.slug
            FROM sports s
            JOIN organization_sports os ON os.sport_id COLLATE utf8mb4_unicode_ci = s.id COLLATE utf8mb4_unicode_ci
            WHERE os.organization_id COLLATE utf8mb4_unicode_ci = ? AND os.is_active = 1 AND s.is_active = 1
        ");
        $sportsStmt->execute([$org['id']]);
        $sports = $sportsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch recent public fixtures with scores (collated to prevent MySQL 1267 mix error)
        $fixturesStmt = $db->prepare("
            SELECT f.id, f.scheduled_at, f.venue_name, f.competition_type, f.competition_name, f.status, f.home_score, f.away_score,
                   ht.name as home_team_name, at.name as away_team_name, s.name as sport_name
            FROM fixtures f
            JOIN teams ht ON f.home_team_id COLLATE utf8mb4_unicode_ci = ht.id COLLATE utf8mb4_unicode_ci
            JOIN teams at ON f.away_team_id COLLATE utf8mb4_unicode_ci = at.id COLLATE utf8mb4_unicode_ci
            JOIN sports s ON f.sport_id COLLATE utf8mb4_unicode_ci = s.id COLLATE utf8mb4_unicode_ci
            WHERE f.organization_id COLLATE utf8mb4_unicode_ci = ? AND f.deleted_at IS NULL
            ORDER BY f.scheduled_at DESC
            LIMIT 15
        ");
        $fixturesStmt->execute([$org['id']]);
        $fixtures = $fixturesStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('public/club', [
            'title' => htmlspecialchars($org['name']) . ' — Benchero Public Club Page',
            'org' => $org,
            'sports' => $sports,
            'fixtures' => $fixtures
        ]);
    }
}
