<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\SubscriptionService;
use PDO;

class DashboardController
{
    public function index(Request $request, string $slug): Response
    {
        $tenant = $request->getAttribute('tenant');
        $role = $request->getAttribute('tenant_role');
        
        if (!$tenant) {
            return new Response('404 Not Found', 404);
        }

        $db = Database::getConnection();

        // 1. Fetch Real Counts
        $teamsCount = (int)$db->query("SELECT COUNT(*) FROM teams WHERE organization_id = {$db->quote($tenant['id'])} AND deleted_at IS NULL")->fetchColumn();
        $playersCount = (int)$db->query("SELECT COUNT(*) FROM players WHERE organization_id = {$db->quote($tenant['id'])} AND deleted_at IS NULL")->fetchColumn();
        $staffCount = (int)$db->query("SELECT COUNT(*) FROM staff WHERE organization_id = {$db->quote($tenant['id'])} AND deleted_at IS NULL")->fetchColumn();
        $fixturesCount = (int)$db->query("SELECT COUNT(*) FROM fixtures WHERE organization_id = {$db->quote($tenant['id'])} AND deleted_at IS NULL")->fetchColumn();

        // 2. Fetch Recent Fixtures
        $recentFixturesStmt = $db->prepare("
            SELECT f.id, f.scheduled_at, f.status, f.home_score, f.away_score, f.venue_name,
                   ht.name as home_team_name, at.name as away_team_name, s.name as sport_name
            FROM fixtures f
            JOIN teams ht ON f.home_team_id = ht.id
            JOIN teams at ON f.away_team_id = at.id
            JOIN sports s ON f.sport_id = s.id
            WHERE f.organization_id = ? AND f.deleted_at IS NULL
            ORDER BY f.scheduled_at DESC
            LIMIT 5
        ");
        $recentFixturesStmt->execute([$tenant['id']]);
        $recentFixtures = $recentFixturesStmt->fetchAll(PDO::FETCH_ASSOC);

        $subService = new SubscriptionService();
        $subscriptionStatus = $subService->getSubscriptionStatus($tenant['id']);

        return Response::view('tenant/dashboard', [
            'tenant' => $tenant,
            'role' => $role,
            'subscription' => $subService->getSubscription($tenant['id']),
            'subscriptionStatus' => $subscriptionStatus,
            'stats' => [
                'teams' => $teamsCount,
                'players' => $playersCount,
                'staff' => $staffCount,
                'fixtures' => $fixturesCount
            ],
            'recentFixtures' => $recentFixtures
        ]);
    }
}
