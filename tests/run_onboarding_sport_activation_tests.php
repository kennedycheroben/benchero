<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Middleware\TenantMiddleware;
use Benchero\Services\OrganizationService;
use Benchero\Services\TeamService;
use Benchero\Core\Ulid;

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertTest(string $description, bool $condition, string $detail = '') {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] {$description}" . ($detail ? " — {$detail}" : "") . "\n";
    } else {
        $failedTests++;
        echo "  [FAIL] {$description}" . ($detail ? " — {$detail}" : "") . "\n";
    }
}

echo "=================================================================\n";
echo "BENCHERO P0 #2: ONBOARDING SPORT ACTIVATION TEST SUITE\n";
echo "=================================================================\n\n";

$db = Database::getConnection();

// Ensure core sports exist
$footballStmt = $db->prepare("SELECT id FROM sports WHERE slug = 'football'");
$footballStmt->execute();
$footballId = $footballStmt->fetchColumn();

if (!$footballId) {
    $footballId = Ulid::generate();
    $db->prepare("INSERT INTO sports (id, name, slug, is_active) VALUES (?, 'Football', 'football', 1)")->execute([$footballId]);
}

$basketballStmt = $db->prepare("SELECT id FROM sports WHERE slug = 'basketball'");
$basketballStmt->execute();
$basketballId = $basketballStmt->fetchColumn();

if (!$basketballId) {
    $basketballId = Ulid::generate();
    $db->prepare("INSERT INTO sports (id, name, slug, is_active) VALUES (?, 'Basketball', 'basketball', 1)")->execute([$basketballId]);
}

// Track IDs created during tests for surgical cleanup
$createdUserIds = [];
$createdOrgIds = [];

try {
    // -------------------------------------------------------------
    // Test Scenario 1: Fresh User & Fresh Organization Onboarding (Default Football)
    // -------------------------------------------------------------
    echo "Scenario 1: Fresh Onboarding Activates Default Sport (Football)\n";
    echo "-------------------------------------------------------------\n";

    $userId = Ulid::generate();
    $createdUserIds[] = $userId;
    $userEmail = 'onboarding_test_' . substr($userId, 0, 8) . '@benchero.test';

    // 1. Create fresh authenticated test user
    $insertUser = $db->prepare("INSERT INTO users (id, name, email, password_hash, role, email_verified_at, created_at, updated_at) VALUES (?, ?, ?, ?, 'user', NOW(), NOW(), NOW())");
    $insertUser->execute([$userId, 'Test Owner', $userEmail, password_hash('Secret123!', PASSWORD_BCRYPT)]);

    $userRecord = $db->query("SELECT * FROM users WHERE id = '{$userId}'")->fetch(PDO::FETCH_ASSOC);
    assertTest('Test user created and email verified', !empty($userRecord) && !empty($userRecord['email_verified_at']));

    // 2. Run organization onboarding via OrganizationService::createOrganization (NO PRE-SEEDED organization_sports)
    $orgService = new OrganizationService();
    $orgName = 'AFC Leopard Onboarding Test ' . substr($userId, 0, 4);
    $orgSlugCandidate = 'afc-onboarding-' . strtolower(substr($userId, 0, 6));

    $createdSlug = $orgService->createOrganization($orgName, $orgSlugCandidate, 'KE', 'Africa/Nairobi', $userId);
    
    // 3. Confirm organization exists
    $orgRecord = $db->query("SELECT * FROM organizations WHERE slug = '{$createdSlug}'")->fetch(PDO::FETCH_ASSOC);
    assertTest('Organization created in database', !empty($orgRecord), "Org ID: {$orgRecord['id']}, Slug: {$createdSlug}");
    $orgId = $orgRecord['id'] ?? null;
    if ($orgId) {
        $createdOrgIds[] = $orgId;
    }

    // 4. Confirm owner membership exists
    $membershipStmt = $db->prepare("SELECT * FROM organization_user WHERE organization_id = ? AND user_id = ?");
    $membershipStmt->execute([$orgId, $userId]);
    $membership = $membershipStmt->fetch(PDO::FETCH_ASSOC);
    assertTest('Organization owner membership created', !empty($membership) && ($membership['role'] ?? '') === 'owner');

    // Confirm user role upgraded to owner
    $userAfterRole = $db->query("SELECT role FROM users WHERE id = '{$userId}'")->fetchColumn();
    assertTest('User role updated to owner in users table', $userAfterRole === 'owner');

    // 5. Confirm trial subscription exists
    $subStmt = $db->prepare("SELECT * FROM subscriptions WHERE organization_id = ?");
    $subStmt->execute([$orgId]);
    $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
    $subStatus = (new \Benchero\Services\SubscriptionService())->getSubscriptionStatus($orgId);
    assertTest('14-day trial subscription initialized', !empty($subscription) && ($subscription['status'] ?? '') === 'trialing' && $subStatus['status'] === \Benchero\Services\SubscriptionService::STATUS_TRIAL);

    // 6. Query organization_sports directly — MUST NOT BE EMPTY
    $orgSportsStmt = $db->prepare("SELECT os.*, s.name as sport_name, s.slug as sport_slug FROM organization_sports os JOIN sports s ON os.sport_id = s.id WHERE os.organization_id = ?");
    $orgSportsStmt->execute([$orgId]);
    $orgSports = $orgSportsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Confirm exactly ONE active default sport exists
    assertTest('Exactly 1 active sport record exists in organization_sports', count($orgSports) === 1, "Found: " . count($orgSports) . " row(s)");
    $activeSport = $orgSports[0] ?? null;
    assertTest('Active sport is Football (slug: football)', ($activeSport['sport_slug'] ?? '') === 'football');
    assertTest('Active sport flag is 1 (active)', ((int)($activeSport['is_active'] ?? 0)) === 1);
    assertTest('Sport ID is valid 26-char ULID (not hard-coded 1)', strlen($activeSport['sport_id'] ?? '') === 26 && ($activeSport['sport_id'] ?? '') !== '1', "Sport ID: " . ($activeSport['sport_id'] ?? ''));

    // 8. Test sport resolution via TenantMiddleware mechanism
    $resStmt = $db->prepare("
        SELECT s.* FROM sports s 
        JOIN organization_sports os ON s.id = os.sport_id 
        WHERE s.slug = :sport_slug 
        AND os.organization_id = :org_id 
        AND os.is_active = 1
    ");
    $resStmt->execute(['sport_slug' => 'football', 'org_id' => $orgId]);
    $resolvedSport = $resStmt->fetch(PDO::FETCH_ASSOC);
    assertTest('TenantMiddleware sport resolution query finds active Football sport', !empty($resolvedSport) && $resolvedSport['slug'] === 'football');

    // 9. Execute TenantMiddleware directly on GET /o/{slug}/s/football/teams
    $_SESSION['_user_id'] = $userId;
    $middleware = new TenantMiddleware();
    $request = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$createdSlug}/s/football/teams"
    ], [], []);

    $capturedRequest = null;
    $response = $middleware->handle($request, function(Request $req) use (&$capturedRequest) {
        $capturedRequest = $req;
        return new Response('200 OK', 200);
    });

    assertTest('GET /o/{slug}/s/football/teams resolves with HTTP 200 (not 404)', $response->getStatusCode() === 200);
    $reqSport = $capturedRequest ? $capturedRequest->getAttribute('sport') : null;
    assertTest('TenantMiddleware sets sport attribute on request', !empty($reqSport) && ($reqSport['slug'] ?? '') === 'football');

    // Confirm that accessing an unactivated sport (e.g. basketball) returns 404
    $unactivatedReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$createdSlug}/s/basketball/teams"
    ], [], []);
    $unactivatedResp = $middleware->handle($unactivatedReq, function(Request $req) {
        return new Response('200 OK', 200);
    });
    assertTest('GET /o/{slug}/s/basketball/teams strictly rejected with 404 (not activated)', $unactivatedResp->getStatusCode() === 404);

    // 10. Confirm dashboard route sets default sport context on request
    $dashReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$createdSlug}/dashboard"
    ], [], []);
    $dashCapturedReq = null;
    $dashResp = $middleware->handle($dashReq, function(Request $req) use (&$dashCapturedReq) {
        $dashCapturedReq = $req;
        return new Response('200 OK', 200);
    });
    assertTest('GET /o/{slug}/dashboard resolves with HTTP 200', $dashResp->getStatusCode() === 200);
    $dashSport = $dashCapturedReq ? $dashCapturedReq->getAttribute('sport') : null;
    assertTest('GET /o/{slug}/dashboard injects default active sport context', !empty($dashSport) && ($dashSport['slug'] ?? '') === 'football');

    // 11. Create first team through normal service path
    $teamService = new TeamService();
    $teamId = $teamService->createTeam($orgId, $activeSport['sport_id'], 'Senior Squad', 'First team created after onboarding', 'Senior');
    assertTest('First team created successfully through TeamService', !empty($teamId));

    $teamRecord = $db->query("SELECT * FROM teams WHERE id = '{$teamId}'")->fetch(PDO::FETCH_ASSOC);
    assertTest('First team stored correctly in teams table', !empty($teamRecord) && $teamRecord['organization_id'] === $orgId && $teamRecord['sport_id'] === $activeSport['sport_id']);

    // -------------------------------------------------------------
    // Test Scenario 2: Idempotency & Duplicate Sport Prevention
    // -------------------------------------------------------------
    echo "\nScenario 2: Idempotency and Duplicate Safety\n";
    echo "-------------------------------------------------------------\n";

    // Call activateSport again for the same organization and same sport
    $reActivateResult = $orgService->activateSport($orgId, $activeSport['sport_id']);
    assertTest('Re-activating an already active sport returns true', $reActivateResult === true);

    $countStmt = $db->prepare("SELECT COUNT(*) FROM organization_sports WHERE organization_id = ? AND sport_id = ?");
    $countStmt->execute([$orgId, $activeSport['sport_id']]);
    $sportRowCount = (int)$countStmt->fetchColumn();
    assertTest('Duplicate activation prevented: Exactly 1 row in organization_sports', $sportRowCount === 1);

    // Total rows for the org remains 1
    $totalOrgSports = (int)$db->query("SELECT COUNT(*) FROM organization_sports WHERE organization_id = '{$orgId}'")->fetchColumn();
    assertTest('Total sports for organization remains exactly 1', $totalOrgSports === 1);

    // -------------------------------------------------------------
    // Test Scenario 3: Selected Sport Onboarding (e.g. Basketball)
    // -------------------------------------------------------------
    echo "\nScenario 3: Explicit Sport Selection (Basketball)\n";
    echo "-------------------------------------------------------------\n";

    $user2Id = Ulid::generate();
    $createdUserIds[] = $user2Id;
    $user2Email = 'bball_test_' . substr($user2Id, 0, 8) . '@benchero.test';

    $insertUser->execute([$user2Id, 'Basketball Club Owner', $user2Email, password_hash('Secret123!', PASSWORD_BCRYPT)]);

    $bballOrgSlug = $orgService->createOrganization('Hoops Club', 'hoops-club-' . strtolower(substr($user2Id, 0, 5)), 'KE', 'Africa/Nairobi', $user2Id, 'basketball');
    $bballOrg = $db->query("SELECT * FROM organizations WHERE slug = '{$bballOrgSlug}'")->fetch(PDO::FETCH_ASSOC);
    $bballOrgId = $bballOrg['id'];
    $createdOrgIds[] = $bballOrgId;

    $bballOrgSports = $db->query("
        SELECT os.*, s.slug as sport_slug 
        FROM organization_sports os 
        JOIN sports s ON os.sport_id = s.id 
        WHERE os.organization_id = '{$bballOrgId}'
    ")->fetchAll(PDO::FETCH_ASSOC);

    assertTest('Selected sport onboarding activates Basketball', count($bballOrgSports) === 1 && ($bballOrgSports[0]['sport_slug'] ?? '') === 'basketball');

    // TenantMiddleware resolves basketball for bball org
    $_SESSION['_user_id'] = $user2Id;
    $bballReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$bballOrgSlug}/s/basketball/teams"
    ], [], []);
    $bballResp = $middleware->handle($bballReq, function(Request $req) {
        return new Response('200 OK', 200);
    });
    assertTest('Basketball org accesses /s/basketball/teams with 200 OK', $bballResp->getStatusCode() === 200);

    // Football is NOT active for bball org
    $bballFootballReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$bballOrgSlug}/s/football/teams"
    ], [], []);
    $bballFootballResp = $middleware->handle($bballFootballReq, function(Request $req) {
        return new Response('200 OK', 200);
    });
    assertTest('Basketball org cannot access unactivated /s/football/teams (404)', $bballFootballResp->getStatusCode() === 404);

    // -------------------------------------------------------------
    // Test Scenario 4: Atomicity Verification (Rollback on failure)
    // -------------------------------------------------------------
    echo "\nScenario 4: Atomicity & Transaction Rollback\n";
    echo "-------------------------------------------------------------\n";

    $badUserId = Ulid::generate(); // Non-existent user
    $atomicFailed = false;
    try {
        $orgService->createOrganization('Ghost Org', 'ghost-org', 'KE', 'Africa/Nairobi', $badUserId);
    } catch (\Throwable $e) {
        $atomicFailed = true;
    }
    assertTest('Creation fails cleanly when user does not exist', $atomicFailed);

    $ghostOrgCount = (int)$db->query("SELECT COUNT(*) FROM organizations WHERE slug LIKE 'ghost-org%'")->fetchColumn();
    assertTest('Transaction rolled back: No organization row created', $ghostOrgCount === 0);

    // -------------------------------------------------------------
    // Scenario 5: Complete Controller Flow (Onboarding -> Dashboard -> Team Creation)
    // -------------------------------------------------------------
    echo "\nScenario 5: Complete Controller Flow (Onboarding -> Dashboard -> First Team)\n";
    echo "-------------------------------------------------------------\n";

    $user3Id = Ulid::generate();
    $createdUserIds[] = $user3Id;
    $user3Email = 'controller_flow_' . substr($user3Id, 0, 8) . '@benchero.test';

    $insertUser->execute([$user3Id, 'Controller Club Owner', $user3Email, password_hash('Secret123!', PASSWORD_BCRYPT)]);

    $_SESSION['_user_id'] = $user3Id;

    // Simulate Onboarding Form POST
    $onboardController = new \Benchero\Controllers\OnboardingController();
    $onboardReq = new Request([
        'name' => 'Kariobangi FC',
        'slug' => 'kariobangi-fc-' . strtolower(substr($user3Id, 0, 5)),
        'country' => 'KE',
        'timezone' => 'Africa/Nairobi'
    ], [], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/onboarding'
    ], [], []);

    $onboardResp = $onboardController->store($onboardReq);
    assertTest('OnboardingController::store redirects with HTTP 302', $onboardResp->getStatusCode() === 302);
    $redirectHeader = $onboardResp->getHeaders()['Location'] ?? '';
    assertTest('Redirects to /o/{slug}/dashboard', str_contains($redirectHeader, '/dashboard'), "Location: {$redirectHeader}");

    $kariobangiOrg = $db->query("SELECT * FROM organizations WHERE slug LIKE 'kariobangi-fc%' ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    assertTest('Organization created by OnboardingController', !empty($kariobangiOrg));
    $kariobangiOrgId = $kariobangiOrg['id'];
    $kariobangiSlug = $kariobangiOrg['slug'];
    $createdOrgIds[] = $kariobangiOrgId;

    // Verify organization_sports was populated
    $kSports = $db->query("SELECT * FROM organization_sports WHERE organization_id = '{$kariobangiOrgId}'")->fetchAll(PDO::FETCH_ASSOC);
    assertTest('organization_sports populated during OnboardingController store', count($kSports) === 1);

    // Call Dashboard via TenantMiddleware & DashboardController
    $dashReq3 = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$kariobangiSlug}/dashboard"
    ], [], []);
    $dashCapturedReq3 = null;
    $middleware->handle($dashReq3, function(Request $req) use (&$dashCapturedReq3) {
        $dashCapturedReq3 = $req;
        return new Response('200 OK', 200);
    });

    $dashController = new \Benchero\Controllers\Tenant\DashboardController();
    $dashViewResp = $dashController->index($dashCapturedReq3, $kariobangiSlug);
    assertTest('DashboardController::index renders successfully with HTTP 200', $dashViewResp->getStatusCode() === 200);
    $dashHtml = $dashViewResp->getContent();
    assertTest('Dashboard HTML contains Football navigation', str_contains($dashHtml, 'Football'));
    assertTest('Dashboard HTML contains dynamic link to Teams (/s/football/teams)', str_contains($dashHtml, "/o/{$kariobangiSlug}/s/football/teams"));
    assertTest('Dashboard HTML contains dynamic link to Players (/s/football/players)', str_contains($dashHtml, "/o/{$kariobangiSlug}/s/football/players"));
    assertTest('Dashboard HTML contains dynamic link to Seasons (/s/football/seasons)', str_contains($dashHtml, "/o/{$kariobangiSlug}/s/football/seasons"));
    assertTest('Dashboard HTML contains dynamic link to Fixtures (/s/football/fixtures)', str_contains($dashHtml, "/o/{$kariobangiSlug}/s/football/fixtures"));

    // Call TeamController::create via TenantMiddleware
    $teamCreateReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$kariobangiSlug}/s/football/teams/create"
    ], [], []);
    $teamCreateCaptured = null;
    $middleware->handle($teamCreateReq, function(Request $req) use (&$teamCreateCaptured) {
        $teamCreateCaptured = $req;
        return new Response('200 OK', 200);
    });

    $teamController = new \Benchero\Controllers\Tenant\TeamController();
    $createViewResp = $teamController->create($teamCreateCaptured);
    assertTest('TeamController::create returns HTTP 200', $createViewResp->getStatusCode() === 200);

    // Create Team via TeamService
    $teamService = new TeamService();
    $createdTeamId = $teamService->createTeam($kariobangiOrgId, $kSports[0]['sport_id'], 'Senior Boys', 'A-team squad', 'Senior');
    assertTest('Team created for newly onboarded organization', !empty($createdTeamId));

    $finalTeamsCount = (int)$db->query("SELECT COUNT(*) FROM teams WHERE organization_id = '{$kariobangiOrgId}'")->fetchColumn();
    assertTest('Teams table reflects exactly 1 team for this organization', $finalTeamsCount === 1);


} finally {
    // Surgical cleanup of test data
    echo "\nCleaning up test artifacts...\n";
    foreach ($createdOrgIds as $oid) {
        $db->exec("DELETE FROM teams WHERE organization_id = '{$oid}'");
        $db->exec("DELETE FROM organization_sports WHERE organization_id = '{$oid}'");
        $db->exec("DELETE FROM subscriptions WHERE organization_id = '{$oid}'");
        $db->exec("DELETE FROM organization_user WHERE organization_id = '{$oid}'");
        $db->exec("DELETE FROM organizations WHERE id = '{$oid}'");
    }
    foreach ($createdUserIds as $uid) {
        $db->exec("DELETE FROM users WHERE id = '{$uid}'");
    }
    echo "Cleanup complete.\n";
}

echo "\n=================================================================\n";
echo "SUMMARY: Total: {$totalTests} | Passed: {$passedTests} | Failed: {$failedTests}\n";
echo "=================================================================\n";

if ($failedTests > 0) {
    exit(1);
}
exit(0);
