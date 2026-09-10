<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\FixtureService;

// Setup test environment
$db = Database::getConnection();
$db->exec("DELETE FROM fixtures");
$db->exec("DELETE FROM roster_assignments");
$db->exec("DELETE FROM players");
$db->exec("DELETE FROM seasons");
$db->exec("DELETE FROM teams");
$db->exec("DELETE FROM organization_sports");
$db->exec("DELETE FROM payments");
$db->exec("DELETE FROM subscriptions");
$db->exec("DELETE FROM organization_user");
$db->exec("DELETE FROM organizations");
$db->exec("DELETE FROM users");

$fixtureService = new FixtureService();

use Benchero\Services\OrganizationService;
use Benchero\Services\TeamService;
use Benchero\Services\SeasonService;

// Create users
$userA = Ulid::generate();
$userB = Ulid::generate();
$db->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$userA', 'User A', 'a@a.com', 'pwd', NOW()), ('$userB', 'User B', 'b@b.com', 'pwd', NOW())");

$orgService = new OrganizationService();
$orgService->createOrganization('Org 1', 'org-1', 'KE', 'Africa/Nairobi', $userA);
$orgService->createOrganization('Org 2', 'org-2', 'KE', 'Africa/Nairobi', $userB);

$org1 = $db->query("SELECT id FROM organizations WHERE slug = 'org-1'")->fetchColumn();
$org2 = $db->query("SELECT id FROM organizations WHERE slug = 'org-2'")->fetchColumn();

$sport1 = $db->query("SELECT id FROM sports WHERE slug = 'football'")->fetchColumn();
if (!$sport1) {
    $sport1 = Ulid::generate();
    $db->exec("INSERT INTO sports (id, name, slug) VALUES ('$sport1', 'Football', 'football')");
}
$db->exec("INSERT INTO organization_sports (organization_id, sport_id) VALUES ('$org1', '$sport1'), ('$org2', '$sport1')");

// Create seasons
$seasonService = new SeasonService();
$season1 = $seasonService->createSeason($org1, $sport1, 'Season 1', '2026-01-01', '2026-12-31');
$season2 = $seasonService->createSeason($org2, $sport1, 'Season 2', '2026-01-01', '2026-12-31');

// Create Teams
$teamService = new TeamService();
$teamA = $teamService->createTeam($org1, $sport1, 'Team A', 'team-a', 'Senior');
$teamB = $teamService->createTeam($org1, $sport1, 'Team B', 'team-b', 'Senior');
$teamC = $teamService->createTeam($org2, $sport1, 'Team C', 'team-c', 'Senior');

echo "Testing Fixtures Foundation...\n\n";

$passed = 0;
$failed = 0;

function assertTest($name, $condition, &$passed, &$failed) {
    if ($condition) {
        echo "✅ PASS: $name\n";
        $passed++;
    } else {
        echo "❌ FAIL: $name\n";
        $failed++;
    }
}

// 1. Basic Creation
try {
    $fixtureId = $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamB, 
        '2026-10-01 15:00:00', 'Test Stadium', 'league', null
    );
    assertTest("Valid fixture creation", true, $passed, $failed);
} catch (\Exception $e) {
    assertTest("Valid fixture creation", false, $passed, $failed);
    echo "Exception: " . $e->getMessage() . "\n";
}

// 2. Same Home and Away Team validation
try {
    $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamA, 
        '2026-10-01 15:00:00'
    );
    assertTest("Prevent same home and away team", false, $passed, $failed);
} catch (\Exception $e) {
    assertTest("Prevent same home and away team", true, $passed, $failed);
}

// 3. Cross-Tenant IDOR: Team belongs to different org
try {
    $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamC, 
        '2026-10-01 15:00:00'
    );
    assertTest("Cross-tenant team IDOR protection", false, $passed, $failed);
} catch (\Exception $e) {
    assertTest("Cross-tenant team IDOR protection", true, $passed, $failed);
}

// 4. Cross-Tenant IDOR: Season belongs to different org
try {
    $fixtureService->createFixture(
        $org1, $sport1, $season2, $teamA, $teamB, 
        '2026-10-01 15:00:00'
    );
    assertTest("Cross-tenant season IDOR protection", false, $passed, $failed);
} catch (\Exception $e) {
    assertTest("Cross-tenant season IDOR protection", true, $passed, $failed);
}

// 5. Status Transitions
try {
    $fixtureService->updateStatus($fixtureId, $org1, $sport1, 'completed');
    
    // Fetch directly to verify
    $stmt = $db->prepare("SELECT status FROM fixtures WHERE id = ?");
    $stmt->execute([$fixtureId]);
    $status = $stmt->fetchColumn();
    
    assertTest("Update status to completed", $status === 'completed', $passed, $failed);
} catch (\Exception $e) {
    assertTest("Update status to completed", false, $passed, $failed);
}

// 6. Cannot freely edit completed fixture
try {
    $fixtureService->updateFixture(
        $fixtureId, $org1, $sport1, $season1, $teamB, $teamA, '2026-10-02 15:00:00'
    );
    assertTest("Cannot edit completed fixture details", false, $passed, $failed);
} catch (\Exception $e) {
    assertTest("Cannot edit completed fixture details", true, $passed, $failed);
}

// 7. Soft Deletion Tests
try {
    $fixtureService->deleteFixture($fixtureId, $org1, $sport1);
    
    // Ensure it's soft deleted
    $stmt = $db->prepare("SELECT deleted_at FROM fixtures WHERE id = ?");
    $stmt->execute([$fixtureId]);
    $deletedAt = $stmt->fetchColumn();
    
    assertTest("Fixture is soft-deleted in DB", $deletedAt !== null && $deletedAt !== false, $passed, $failed);
    
    // Ensure normal fetch fails
    $repo = new \Benchero\Repositories\FixtureRepository();
    $fetched = $repo->findById($fixtureId, $org1, $sport1);
    assertTest("Soft-deleted fixture is excluded from normal queries", $fetched === null, $passed, $failed);
    
} catch (\Exception $e) {
    assertTest("Fixture soft deletion", false, $passed, $failed);
}

// 8. Cross tenant soft delete protection
try {
    // Create another fixture for org1
    $fixtureId2 = $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamB, 
        '2026-11-01 15:00:00'
    );
    // Org2 tries to delete it
    $fixtureService->deleteFixture($fixtureId2, $org2, $sport1);
    assertTest("Cross-tenant soft delete protection", false, $passed, $failed);
} catch (\Exception $e) {
    assertTest("Cross-tenant soft delete protection", true, $passed, $failed);
}

// 9. Public Read Access Tests
try {
    // We already have a soft deleted fixture ($fixtureId) and an active one ($fixtureId2) in $org1/$sport1
    // Add another active one just in case
    $fixtureId3 = $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamB, 
        '2026-12-01 15:00:00', 'Public Venue', 'league', 'Public Comp'
    );
    
    $repo = new \Benchero\Repositories\FixtureRepository();
    $publicFixtures = $repo->findPublicBySeason($season1, $org1, $sport1);
    
    // Ensure active fixture is visible
    $foundActive = false;
    // Ensure soft-deleted fixture is NOT visible
    $foundDeleted = false;
    
    foreach ($publicFixtures as $f) {
        if ($f['id'] === $fixtureId3) $foundActive = true;
        if ($f['id'] === $fixtureId) $foundDeleted = true;
    }
    
    assertTest("Public query retrieves active fixtures", $foundActive, $passed, $failed);
    assertTest("Public query hides soft-deleted fixtures", !$foundDeleted, $passed, $failed);
    
    // Ensure no private fields are leaked
    $sample = $publicFixtures[0];
    $hasPrivateFields = isset($sample['notes']) || isset($sample['created_at']) || isset($sample['updated_at']);
    assertTest("Public endpoint exposes no private tenant fields", !$hasPrivateFields, $passed, $failed);

    // Cross-tenant public check
    $publicFixturesOrg2 = $repo->findPublicBySeason($season1, $org2, $sport1);
    $leaked = false;
    foreach ($publicFixturesOrg2 as $f) {
        if ($f['id'] === $fixtureId3) $leaked = true;
    }
    assertTest("Public query cannot retrieve another organization's fixture", !$leaked, $passed, $failed);
    
    // Ensure unauthenticated visitor cannot access authenticated endpoint
    // This is tested by manually simulating a request without session
    $_SESSION = [];
    $stmt = $db->prepare("SELECT slug FROM organizations WHERE id = ?");
    $stmt->execute([$org1]);
    $org1Slug = $stmt->fetchColumn();

    $request = new \Benchero\Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/o/{$org1Slug}/s/football/fixtures"], [], []);
    $middleware = new \Benchero\Middleware\TenantMiddleware();
    $response = $middleware->handle($request, function($req) {
        return new \Benchero\Core\Http\Response('OK');
    });
    assertTest("Public visitor cannot access authenticated tenant endpoints", $response->getStatusCode() === 302, $passed, $failed);
    
} catch (\Exception $e) {
    assertTest("Public access tests failed: " . $e->getMessage(), false, $passed, $failed);
}

echo "\nTests Completed: $passed Passed, $failed Failed\n";
if ($failed > 0) {
    exit(1);
}
exit(0);
