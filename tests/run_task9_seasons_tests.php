<?php
require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Middleware\TenantMiddleware;
use Benchero\Services\OrganizationService;
use Benchero\Services\SeasonService;
use Benchero\Core\Ulid;

$db = Database::getConnection();
$db->exec("DELETE FROM seasons");
$db->exec("DELETE FROM organization_sports");
$db->exec("DELETE FROM teams");
$db->exec("DELETE FROM payments");
$db->exec("DELETE FROM subscriptions");
$db->exec("DELETE FROM organization_user");
$db->exec("DELETE FROM organizations");
$db->exec("DELETE FROM users");

function logResult($testName, $passed, $message = '') {
    $status = $passed ? "\e[32mPASS\e[0m" : "\e[31mFAIL\e[0m";
    echo "[$status] $testName" . ($message ? " - $message" : "") . "\n";
    if (!$passed) exit(1);
}

// 1. Setup users and organizations
$userA = Ulid::generate();
$userB = Ulid::generate();
$db->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$userA', 'User A', 'a@a.com', 'pwd', NOW())");
$db->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$userB', 'User B', 'b@b.com', 'pwd', NOW())");

$orgService = new OrganizationService();
$orgService->createOrganization('Org A', 'org-a', 'KE', 'Africa/Nairobi', $userA);
$orgService->createOrganization('Org B', 'org-b', 'US', 'America/New_York', $userB);

$orgA = $db->query("SELECT id FROM organizations WHERE slug = 'org-a'")->fetchColumn();
$orgB = $db->query("SELECT id FROM organizations WHERE slug = 'org-b'")->fetchColumn();
$footballId = $db->query("SELECT id FROM sports WHERE slug = 'football'")->fetchColumn();
$basketballId = $db->query("SELECT id FROM sports WHERE slug = 'basketball'")->fetchColumn();

$db->exec("INSERT INTO organization_sports (organization_id, sport_id) VALUES ('$orgA', '$footballId')");
$db->exec("INSERT INTO organization_sports (organization_id, sport_id) VALUES ('$orgA', '$basketballId')");
$db->exec("INSERT INTO organization_sports (organization_id, sport_id) VALUES ('$orgB', '$footballId')");

// 2. Test Season Creation and is_current logic
$seasonService = new SeasonService();
$season1 = $seasonService->createSeason($orgA, $footballId, '2025/2026', '2025-08-01', '2026-05-30', true);
$isCurrent1 = $db->query("SELECT is_current FROM seasons WHERE id = '$season1'")->fetchColumn();
logResult('Create Current Season', $isCurrent1 == 1, 'is_current correctly set');

$season2 = $seasonService->createSeason($orgA, $footballId, '2026/2027', '2026-08-01', '2027-05-30', true);
$isCurrent1_after = $db->query("SELECT is_current FROM seasons WHERE id = '$season1'")->fetchColumn();
$isCurrent2 = $db->query("SELECT is_current FROM seasons WHERE id = '$season2'")->fetchColumn();
logResult('Set Current Season Trx', $isCurrent1_after == 0 && $isCurrent2 == 1, 'Previous season unset correctly');

// 3. Test duplicate slug prevention within org/sport
try {
    $seasonService->createSeason($orgA, $footballId, '2026/2027', '2026-09-01', '2027-04-30');
    logResult('Duplicate Slug Prevented', false, 'Allowed duplicate slug');
} catch (Exception $e) {
    logResult('Duplicate Slug Prevented', true, 'Correctly threw exception');
}

// 4. Test same slug different sport
try {
    $season3 = $seasonService->createSeason($orgA, $basketballId, '2026/2027', '2026-10-01', '2027-06-30');
    logResult('Same Slug Different Sport', true, 'Allowed successfully');
} catch (Exception $e) {
    logResult('Same Slug Different Sport', false, $e->getMessage());
}

// 5. Test Invalid Dates
try {
    $seasonService->createSeason($orgA, $basketballId, 'Bad Season', '2027-10-01', '2026-06-30');
    logResult('Invalid Dates Prevented', false, 'Allowed end date before start date');
} catch (Exception $e) {
    logResult('Invalid Dates Prevented', true, 'Correctly threw exception');
}

// 6. Test Cross Tenant Protection inside Service
// We enforce this implicitly by Controller extracting orgId/sportId from middleware, 
// but let's test if middleware blocks an Org B user hitting an Org A route.
$middleware = new TenantMiddleware();
$request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/o/org-a/s/football/seasons"], [], []);
$_SESSION['_user_id'] = $userB;
$res = $middleware->handle($request, function($req) { return new Response('200 OK', 200); });
logResult('User B -> Org A Football Seasons', $res->getStatusCode() === 403, 'Blocked with 403 cross-tenant');

// 7. Test Archive
$seasonService->archiveSeason($season2, $orgA, $footballId);
$deletedAt = $db->query("SELECT deleted_at FROM seasons WHERE id = '$season2'")->fetchColumn();
$isCurrent2_after = $db->query("SELECT is_current FROM seasons WHERE id = '$season2'")->fetchColumn();
logResult('Archive Season', $deletedAt !== null && $isCurrent2_after == 0, 'Soft deleted and is_current unset');

echo "\nAll Task 9 tests passed.\n";
