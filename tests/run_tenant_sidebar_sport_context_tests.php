<?php
/**
 * Test Suite: Tenant Sidebar / Sport Context
 * Focuses on P1 #1 Architecture:
 * - Proper sport context resolution priority (route explicit -> request attribute -> primary active sport -> null).
 * - Multi-sport support (sidebar follows current sport context).
 * - Strict tenant isolation (cannot access inactive or cross-tenant sports).
 * - Consistent sidebar rendering for all sport-scoped navigation items:
 *   Seasons, Teams & Squads, Players, Fixtures, Results.
 * - Graceful degradation when no active sport exists (no crash, sport links hidden, org links present).
 * - Legacy non-sport route compatibility without undefined $sport notices.
 * - No hard-coded sport IDs.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\TenantContext;
use Benchero\Core\Ulid;
use Benchero\Middleware\TenantMiddleware;
use Benchero\Services\OrganizationService;
use Benchero\Controllers\Tenant\DashboardController;
use Benchero\Controllers\Tenant\LegacyRouteController;

$db = Database::getConnection();

$passed = 0;
$failed = 0;

function assertTest(string $description, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  \e[32m[PASS]\e[0m {$description}\n";
    } else {
        $failed++;
        echo "  \e[31m[FAIL]\e[0m {$description}";
        if ($details) {
            echo " — {$details}";
        }
        echo "\n";
    }
}

echo "=================================================================\n";
echo "BENCHERO P1 #1: TENANT SIDEBAR / SPORT CONTEXT TEST SUITE\n";
echo "=================================================================\n\n";

// Setup unique test data
$testRunId = strtolower(substr(Ulid::generate(), -6));
$userAId = Ulid::generate();
$userBId = Ulid::generate();

$db->prepare("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES (?, ?, ?, ?, NOW())")
   ->execute([$userAId, 'Tenant A Owner', "owner-a-{$testRunId}@example.com", password_hash('secret123', PASSWORD_DEFAULT)]);

$db->prepare("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES (?, ?, ?, ?, NOW())")
   ->execute([$userBId, 'Tenant B Owner', "owner-b-{$testRunId}@example.com", password_hash('secret123', PASSWORD_DEFAULT)]);

$orgService = new OrganizationService();
$orgASlug = $orgService->createOrganization("Club Alpha {$testRunId}", "club-alpha-{$testRunId}", 'KE', 'Africa/Nairobi', $userAId);
$orgBSlug = $orgService->createOrganization("Club Beta {$testRunId}", "club-beta-{$testRunId}", 'KE', 'Africa/Nairobi', $userBId);
$orgA = $orgService->getOrganizationBySlug($orgASlug);
$orgB = $orgService->getOrganizationBySlug($orgBSlug);

// Ensure standard catalog sports exist with valid ULIDs
$football = $db->query("SELECT * FROM sports WHERE slug = 'football'")->fetch(PDO::FETCH_ASSOC);
if (!$football) {
    $fbId = Ulid::generate();
    $db->exec("INSERT INTO sports (id, name, slug) VALUES ('{$fbId}', 'Football', 'football')");
    $football = $db->query("SELECT * FROM sports WHERE slug = 'football'")->fetch(PDO::FETCH_ASSOC);
}

$basketball = $db->query("SELECT * FROM sports WHERE slug = 'basketball'")->fetch(PDO::FETCH_ASSOC);
if (!$basketball) {
    $bbId = Ulid::generate();
    $db->exec("INSERT INTO sports (id, name, slug) VALUES ('{$bbId}', 'Basketball', 'basketball')");
    $basketball = $db->query("SELECT * FROM sports WHERE slug = 'basketball'")->fetch(PDO::FETCH_ASSOC);
}

$rugby = $db->query("SELECT * FROM sports WHERE slug = 'rugby'")->fetch(PDO::FETCH_ASSOC);
if (!$rugby) {
    $rbId = Ulid::generate();
    $db->exec("INSERT INTO sports (id, name, slug) VALUES ('{$rbId}', 'Rugby', 'rugby')");
    $rugby = $db->query("SELECT * FROM sports WHERE slug = 'rugby'")->fetch(PDO::FETCH_ASSOC);
}

$middleware = new TenantMiddleware();

try {
    echo "Test Group 1: Sport Context Resolution & Priority\n";
    echo "--------------------------------------------------\n";

    // 1. Tenant dashboard receives active sport context
    $_SESSION['_user_id'] = $userAId;
    $dashReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/dashboard"
    ], [], []);
    $dashCapturedReq = null;
    $dashResp = $middleware->handle($dashReq, function(Request $req) use (&$dashCapturedReq) {
        $dashCapturedReq = $req;
        return new Response('200 OK', 200);
    });

    assertTest("1. Tenant dashboard receives active sport context", 
        $dashCapturedReq && $dashCapturedReq->getAttribute('sport') !== null &&
        $dashCapturedReq->getAttribute('sport')['slug'] === 'football'
    );

    // 2. Non-sport-scoped tenant route resolves the organization's active primary sport
    $staffReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/staff"
    ], [], []);
    $staffCapturedReq = null;
    $staffResp = $middleware->handle($staffReq, function(Request $req) use (&$staffCapturedReq) {
        $staffCapturedReq = $req;
        return new Response('200 OK', 200);
    });

    assertTest("2. Non-sport-scoped tenant route resolves the organization's active primary sport",
        $staffCapturedReq && $staffCapturedReq->getAttribute('sport') !== null &&
        $staffCapturedReq->getAttribute('sport')['slug'] === 'football' &&
        TenantContext::getSport()['slug'] === 'football'
    );

    // 3. Sport-scoped Football route resolves Football
    $fbReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/s/football/teams"
    ], [], []);
    $fbCapturedReq = null;
    $fbResp = $middleware->handle($fbReq, function(Request $req) use (&$fbCapturedReq) {
        $fbCapturedReq = $req;
        return new Response('200 OK', 200);
    });

    assertTest("3. Sport-scoped Football route resolves Football",
        $fbResp->getStatusCode() === 200 &&
        $fbCapturedReq && $fbCapturedReq->getAttribute('sport')['slug'] === 'football'
    );

    // 4. Sport-scoped Basketball route resolves Basketball when assigned
    // Activate Basketball for Org A as well
    $db->prepare("INSERT INTO organization_sports (organization_id, sport_id, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE is_active = 1")
       ->execute([$orgA['id'], $basketball['id']]);

    $bbReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/s/basketball/teams"
    ], [], []);
    $bbCapturedReq = null;
    $bbResp = $middleware->handle($bbReq, function(Request $req) use (&$bbCapturedReq) {
        $bbCapturedReq = $req;
        return new Response('200 OK', 200);
    });

    assertTest("4. Sport-scoped Basketball route resolves Basketball when assigned",
        $bbResp->getStatusCode() === 200 &&
        $bbCapturedReq && $bbCapturedReq->getAttribute('sport')['slug'] === 'basketball' &&
        TenantContext::getSport()['slug'] === 'basketball'
    );

    // 5. Current sport remains correct when multiple sports are active
    // When visiting Football route, context must be Football, NOT Basketball
    $fbReq2 = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/s/football/fixtures"
    ], [], []);
    $fbCapturedReq2 = null;
    $middleware->handle($fbReq2, function(Request $req) use (&$fbCapturedReq2) {
        $fbCapturedReq2 = $req;
        return new Response('200 OK', 200);
    });

    // When visiting Basketball route, context must be Basketball, NOT Football
    $bbReq2 = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/s/basketball/fixtures"
    ], [], []);
    $bbCapturedReq2 = null;
    $middleware->handle($bbReq2, function(Request $req) use (&$bbCapturedReq2) {
        $bbCapturedReq2 = $req;
        return new Response('200 OK', 200);
    });

    assertTest("5. Current sport remains correct when multiple sports are active",
        $fbCapturedReq2->getAttribute('sport')['slug'] === 'football' &&
        $bbCapturedReq2->getAttribute('sport')['slug'] === 'basketball'
    );

    echo "\nTest Group 2: Tenant Isolation & Security\n";
    echo "-----------------------------------------\n";

    // 6. Inactive sport cannot become current context
    // Deactivate Rugby for Org A
    $db->prepare("INSERT INTO organization_sports (organization_id, sport_id, is_active) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE is_active = 0")
       ->execute([$orgA['id'], $rugby['id']]);

    $inactiveReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/s/rugby/teams"
    ], [], []);
    $inactiveResp = $middleware->handle($inactiveReq, function(Request $req) {
        return new Response('200 OK', 200);
    });

    assertTest("6. Inactive sport cannot become current context (404 returned)",
        $inactiveResp->getStatusCode() === 404
    );

    // 7. Unknown sport cannot become current context
    $unknownReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/s/curling-unknown-xyz/teams"
    ], [], []);
    $unknownResp = $middleware->handle($unknownReq, function(Request $req) {
        return new Response('200 OK', 200);
    });

    assertTest("7. Unknown sport cannot become current context (404 returned)",
        $unknownResp->getStatusCode() === 404
    );

    // 8. Sport from organization A cannot be used for organization B
    // Org B only has Football (default). Org A has Basketball. Org B tries to access Basketball.
    $_SESSION['_user_id'] = $userBId;
    $crossSportReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgB['slug']}/s/basketball/teams"
    ], [], []);
    $crossSportResp = $middleware->handle($crossSportReq, function(Request $req) {
        return new Response('200 OK', 200);
    });

    assertTest("8. Sport from organization A cannot be used for organization B (404 returned)",
        $crossSportResp->getStatusCode() === 404
    );

    echo "\nTest Group 3: Sidebar Navigation Links & Multi-Sport URLs\n";
    echo "---------------------------------------------------------\n";

    // 9. Sidebar renders Teams link with correct context
    $_SESSION['_user_id'] = $userAId;
    // Visit Basketball route to ensure sidebar renders Basketball links
    $bbPageReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/s/basketball/teams"
    ], [], []);
    $_SERVER['REQUEST_URI'] = "/o/{$orgA['slug']}/s/basketball/teams";
    $bbPageCaptured = null;
    $middleware->handle($bbPageReq, function(Request $req) use (&$bbPageCaptured) {
        $bbPageCaptured = $req;
        return new Response('200 OK', 200);
    });

    $dashController = new DashboardController();
    ob_start();
    $bbDashResp = $dashController->index($bbPageCaptured, $orgA['slug']);
    $echoed = ob_get_clean();
    $bbHtml = $bbDashResp->getContent() . $echoed;

    assertTest("9. Sidebar renders Teams link with correct context (/s/basketball/teams)",
        str_contains($bbHtml, "/o/{$orgA['slug']}/s/basketball/teams")
    );

    // 10. Sidebar renders Players link with correct context
    assertTest("10. Sidebar renders Players link with correct context (/s/basketball/players)",
        str_contains($bbHtml, "/o/{$orgA['slug']}/s/basketball/players")
    );

    // 11. Sidebar renders Squad/Roster link if the feature exists
    // Teams & Squads navigation item represents both teams and squad/roster management
    assertTest("11. Sidebar renders Squad/Roster link ('Teams & Squads')",
        str_contains($bbHtml, 'Teams & Squads')
    );

    // 12. Sidebar renders Fixtures link
    assertTest("12. Sidebar renders Fixtures link (/s/basketball/fixtures)",
        str_contains($bbHtml, "/o/{$orgA['slug']}/s/basketball/fixtures")
    );

    // 13. Sidebar renders Results link
    assertTest("13. Sidebar renders Results link (/s/basketball/results)",
        str_contains($bbHtml, "/o/{$orgA['slug']}/s/basketball/results")
    );

    // 14. Sidebar renders Seasons link
    assertTest("14. Sidebar renders Seasons link (/s/basketball/seasons)",
        str_contains($bbHtml, "/o/{$orgA['slug']}/s/basketball/seasons")
    );

    echo "\nTest Group 4: Legacy Routes & Zero Active Sport Safety\n";
    echo "------------------------------------------------------\n";

    // 15. Legacy tenant route does not produce an undefined $sport
    // Test legacy route controller redirection with resolved sport context
    $legacyReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgA['slug']}/teams"
    ], [], []);
    $legacyCaptured = null;
    $middleware->handle($legacyReq, function(Request $req) use (&$legacyCaptured) {
        $legacyCaptured = $req;
        return new Response('200 OK', 200);
    });

    $legacyController = new LegacyRouteController();
    $legacyResp = $legacyController->teams($legacyCaptured, $orgA['slug']);

    assertTest("15. Legacy tenant route does not produce an undefined \$sport and redirects to sport route",
        $legacyResp->getStatusCode() === 302 &&
        str_contains($legacyResp->getHeader('Location'), "/o/{$orgA['slug']}/s/") &&
        str_contains($legacyResp->getHeader('Location'), "/teams")
    );

    // 16. Organization with no active sport does not crash
    // Create an organization and explicitly deactivate all sports
    $userCId = Ulid::generate();
    $db->prepare("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES (?, ?, ?, ?, NOW())")
       ->execute([$userCId, 'Tenant C Owner', "owner-c-{$testRunId}@example.com", password_hash('secret123', PASSWORD_DEFAULT)]);
    $orgCSlug = $orgService->createOrganization("Club Gamma {$testRunId}", "club-gamma-{$testRunId}", 'KE', 'Africa/Nairobi', $userCId);
    $orgC = $orgService->getOrganizationBySlug($orgCSlug);
    $db->prepare("UPDATE organization_sports SET is_active = 0 WHERE organization_id = ?")->execute([$orgC['id']]);

    $_SESSION['_user_id'] = $userCId;
    $_SERVER['REQUEST_URI'] = "/o/{$orgC['slug']}/dashboard";
    $noSportReq = new Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => "/o/{$orgC['slug']}/dashboard"
    ], [], []);
    $noSportCaptured = null;
    $middleware->handle($noSportReq, function(Request $req) use (&$noSportCaptured) {
        $noSportCaptured = $req;
        return new Response('200 OK', 200);
    });

    ob_start();
    $noSportDashResp = $dashController->index($noSportCaptured, $orgC['slug']);
    $noSportEchoed = ob_get_clean();
    $noSportHtml = $noSportDashResp->getContent() . $noSportEchoed;

    assertTest("16. Organization with no active sport does not crash (HTTP 200)",
        $noSportDashResp->getStatusCode() === 200 &&
        !str_contains($noSportHtml, '/s/football/teams') && // sport links hidden
        str_contains($noSportHtml, "/o/{$orgC['slug']}/dashboard") && // general tenant links intact
        str_contains($noSportHtml, "/o/{$orgC['slug']}/sports")
    );

    // 17. No hard-coded sport ID is used
    $resolvedPrimary = TenantContext::resolvePrimarySportForOrg($orgA['id']);
    assertTest("17. No hard-coded sport ID is used (sport ID is valid 26-char ULID)",
        $resolvedPrimary !== null &&
        strlen($resolvedPrimary['id']) === 26 &&
        $resolvedPrimary['id'] !== '1' &&
        $resolvedPrimary['id'] !== 1
    );

    echo "\nTest Group 5: Existing Suite Regressions\n";
    echo "----------------------------------------\n";

    // 18. Existing onboarding sport activation tests still pass
    exec('/opt/lampp/bin/php ' . escapeshellarg(__DIR__ . '/run_onboarding_sport_activation_tests.php'), $onbOut, $onbCode);
    assertTest("18. Existing onboarding sport activation tests still pass (exit code 0)",
        $onbCode === 0,
        "Exit code: {$onbCode}"
    );

    // 19. Existing tenant security tests still pass
    exec('/opt/lampp/bin/php ' . escapeshellarg(__DIR__ . '/run_task7_tenant_security_tests.php'), $secOut, $secCode);
    assertTest("19. Existing tenant security tests still pass (exit code 0)",
        $secCode === 0,
        "Exit code: {$secCode}"
    );

    // 20. Existing team/player/season/fixture tests still pass
    exec('/opt/lampp/bin/php ' . escapeshellarg(__DIR__ . '/run_task8_sports_security_tests.php'), $t8Out, $t8Code);
    exec('/opt/lampp/bin/php ' . escapeshellarg(__DIR__ . '/run_task9_seasons_tests.php'), $t9Out, $t9Code);
    exec('/opt/lampp/bin/php ' . escapeshellarg(__DIR__ . '/run_task10_team_tests.php'), $t10Out, $t10Code);
    exec('/opt/lampp/bin/php ' . escapeshellarg(__DIR__ . '/run_task11_player_tests.php'), $t11Out, $t11Code);
    exec('/opt/lampp/bin/php ' . escapeshellarg(__DIR__ . '/run_task12_fixture_tests.php'), $t12Out, $t12Code);
    $allSubsuitesPassed = ($t8Code === 0 && $t9Code === 0 && $t10Code === 0 && $t11Code === 0 && $t12Code === 0);

    assertTest("20. Existing team/player/season/fixture tests still pass",
        $allSubsuitesPassed,
        "Exit codes: Task8={$t8Code}, Task9={$t9Code}, Task10={$t10Code}, Task11={$t11Code}, Task12={$t12Code}"
    );

} finally {
    // Clean up test data
    $orgIds = [$orgA['id'] ?? null, $orgB['id'] ?? null, $orgC['id'] ?? null];
    $userIds = [$userAId, $userBId, $userCId ?? null];

    foreach (array_filter($orgIds) as $oid) {
        $db->exec("DELETE FROM organization_sports WHERE organization_id = '{$oid}'");
        $db->exec("DELETE FROM subscriptions WHERE organization_id = '{$oid}'");
        $db->exec("DELETE FROM organization_user WHERE organization_id = '{$oid}'");
        $db->exec("DELETE FROM organizations WHERE id = '{$oid}'");
    }

    foreach (array_filter($userIds) as $uid) {
        $db->exec("DELETE FROM users WHERE id = '{$uid}'");
    }
}

echo "\n=================================================================\n";
echo "SUMMARY: Total: " . ($passed + $failed) . " | Passed: {$passed} | Failed: {$failed}\n";
echo "=================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
