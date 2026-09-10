<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Ulid;
use Benchero\Controllers\Public\PublicClubController;
use Benchero\Services\WebsiteService;
use Benchero\Services\MediaService;
use Benchero\Repositories\HistoryRepository;
use Benchero\Repositories\PageRepository;

echo "==================================================\n";
echo "  BENCHERO CLUB WEBSITE BUILDER TEST SUITE\n";
echo "==================================================\n\n";

$pdo = Database::getConnection();
$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $title) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] {$title}\n";
        $passed++;
    } else {
        echo " [FAIL] {$title}\n";
        $failed++;
    }
}

// 1. Create Test Organization
$orgId = Ulid::generate();
$orgSlug = 'test-club-website-' . rand(1000, 9999);
$pdo->exec("
    INSERT INTO organizations (id, name, slug, timezone, created_at, updated_at)
    VALUES ('{$orgId}', 'Test Cheetahs FC', '{$orgSlug}', 'Africa/Nairobi', NOW(), NOW())
");

// Create active subscription for org
$subId = Ulid::generate();
$pdo->exec("
    INSERT INTO subscriptions (id, organization_id, plan_id, status, starts_at, expires_at, created_at, updated_at)
    VALUES ('{$subId}', '{$orgId}', 2, 'ACTIVE', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), NOW(), NOW())
");

echo "--- 1. Testing WebsiteService Settings & Readiness ---\n";
$webService = new WebsiteService($pdo);
$settings = $webService->getSettings($orgId);

assertTest(!empty($settings['id']), 'Default settings row initialized');
assertTest($settings['theme_id'] === 'modern_sport', 'Default theme is modern_sport');

// Update customization
$updated = $webService->updateSettings($orgId, [
    'theme_id' => 'dynamic_athletic',
    'primary_color' => '#ff0000',
    'hero_title' => 'CHEETAHS FC REIGN'
]);
assertTest($updated === true, 'Settings update succeeded');

$newSettings = $webService->getSettings($orgId);
assertTest($newSettings['theme_id'] === 'dynamic_athletic', 'Updated theme persisted');
assertTest($newSettings['primary_color'] === '#ff0000', 'Updated primary color persisted');

// Readiness calculation
$orgData = ['id' => $orgId, 'name' => 'Test Cheetahs FC', 'slug' => $orgSlug, 'logo_url' => '/logo.png', 'description' => 'Test Club'];
$readiness = $webService->calculateCompletionScore($orgId, $orgData);
assertTest(is_int($readiness['score']) && $readiness['score'] > 0, 'Website readiness score calculated');

echo "\n--- 2. Testing HistoryRepository Timeline ---\n";
$historyRepo = new HistoryRepository($pdo);
$milestoneId = $historyRepo->create([
    'organization_id' => $orgId,
    'year_date' => '2022',
    'title' => 'League Champions',
    'description' => 'Won the national cup.',
    'category' => 'Championship'
]);
assertTest(!empty($milestoneId), 'Timeline milestone created');

$historyList = $historyRepo->getByOrganization($orgId);
assertTest(count($historyList) === 1, 'History milestone retrieved for org');
assertTest($historyList[0]['title'] === 'League Champions', 'Milestone title matches');

echo "\n--- 3. Testing Public Multi-Page Website Rendering ---\n";
$publicCtrl = new PublicClubController();
$req = new Request([], [], [], [], ['REQUEST_METHOD' => 'GET']);

// Home
$resHome = $publicCtrl->show($req, ['slug' => $orgSlug]);
assertTest($resHome->getStatusCode() === 200, 'GET /club/{slug} Home page returns 200');
assertTest(str_contains($resHome->getContent(), 'Test Cheetahs FC'), 'Home page contains club name');

// About
$resAbout = $publicCtrl->about($req, ['slug' => $orgSlug]);
assertTest($resAbout->getStatusCode() === 200, 'GET /club/{slug}/about returns 200');
assertTest(str_contains($resAbout->getContent(), 'League Champions'), 'About page contains timeline milestone');

// Teams
$resTeams = $publicCtrl->teams($req, ['slug' => $orgSlug]);
assertTest($resTeams->getStatusCode() === 200, 'GET /club/{slug}/teams returns 200');

// Players
$resPlayers = $publicCtrl->players($req, ['slug' => $orgSlug]);
assertTest($resPlayers->getStatusCode() === 200, 'GET /club/{slug}/players returns 200');

// Staff
$resStaff = $publicCtrl->staff($req, ['slug' => $orgSlug]);
assertTest($resStaff->getStatusCode() === 200, 'GET /club/{slug}/staff returns 200');

// Fixtures
$resFixtures = $publicCtrl->fixtures($req, ['slug' => $orgSlug]);
assertTest($resFixtures->getStatusCode() === 200, 'GET /club/{slug}/fixtures returns 200');

// Results
$resResults = $publicCtrl->results($req, ['slug' => $orgSlug]);
assertTest($resResults->getStatusCode() === 200, 'GET /club/{slug}/results returns 200');

// Standings
$resStandings = $publicCtrl->standings($req, ['slug' => $orgSlug]);
assertTest($resStandings->getStatusCode() === 200, 'GET /club/{slug}/standings returns 200');

// News
$resNews = $publicCtrl->news($req, ['slug' => $orgSlug]);
assertTest($resNews->getStatusCode() === 200, 'GET /club/{slug}/news returns 200');

// Gallery
$resGallery = $publicCtrl->gallery($req, ['slug' => $orgSlug]);
assertTest($resGallery->getStatusCode() === 200, 'GET /club/{slug}/gallery returns 200');

// History
$resHistory = $publicCtrl->history($req, ['slug' => $orgSlug]);
assertTest($resHistory->getStatusCode() === 200, 'GET /club/{slug}/history returns 200');

// Sponsors
$resSponsors = $publicCtrl->sponsors($req, ['slug' => $orgSlug]);
assertTest($resSponsors->getStatusCode() === 200, 'GET /club/{slug}/sponsors returns 200');

// Contact
$resContact = $publicCtrl->contact($req, ['slug' => $orgSlug]);
assertTest($resContact->getStatusCode() === 200, 'GET /club/{slug}/contact returns 200');

echo "\n--- 4. Testing Multi-Tenant Data Isolation & Subscription Locking ---\n";
// Org B
$orgIdB = Ulid::generate();
$orgSlugB = 'test-org-isolation-' . rand(1000, 9999);
$pdo->exec("
    INSERT INTO organizations (id, name, slug, timezone, created_at, updated_at)
    VALUES ('{$orgIdB}', 'Org B Isolation', '{$orgSlugB}', 'Africa/Nairobi', NOW(), NOW())
");

$historyB = $historyRepo->getByOrganization($orgIdB);
assertTest(count($historyB) === 0, 'Org B history is isolated from Org A');

// Subscription locking check
$pdo->exec("
    INSERT INTO subscriptions (id, organization_id, plan_id, status, starts_at, expires_at, created_at, updated_at)
    VALUES ('" . Ulid::generate() . "', '{$orgIdB}', 1, 'EXPIRED', NOW(), DATE_SUB(NOW(), INTERVAL 10 DAY), NOW(), NOW())
");

$resLocked = $publicCtrl->show($req, ['slug' => $orgSlugB]);
assertTest(str_contains($resLocked->getContent(), 'unavailable') || str_contains($resLocked->getContent(), 'subscription'), 'Expired club website profile renders locked status page');

// Cleanup test orgs and subscriptions
$pdo->exec("DELETE FROM subscriptions WHERE organization_id IN ('{$orgId}', '{$orgIdB}')");
$pdo->exec("DELETE FROM organizations WHERE id IN ('{$orgId}', '{$orgIdB}')");

echo "\n==================================================\n";
echo "  TEST SUMMARY: Passed {$passed} / Failed {$failed}\n";
echo "==================================================\n";

exit($failed > 0 ? 1 : 0);
