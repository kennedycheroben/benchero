<?php
require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Middleware\TenantMiddleware;
use Benchero\Services\OrganizationService;
use Benchero\Services\TeamService;
use Benchero\Core\Ulid;

$db = Database::getConnection();
$db->exec("DELETE FROM teams");
$db->exec("DELETE FROM organization_sports");
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

// 2. Test Team Creation and Slug Gen
$teamService = new TeamService();
$team1 = $teamService->createTeam($orgA, $footballId, 'Senior Men', 'Main squad', 'Senior');
$slug1 = $db->query("SELECT slug FROM teams WHERE id = '$team1'")->fetchColumn();
logResult('Create Team', $slug1 === 'senior-men', 'Slug generated correctly');

// 3. Duplicate Slug Prevention within org/sport
try {
    $teamService->createTeam($orgA, $footballId, 'Senior Men', 'Another one', 'Senior');
    logResult('Duplicate Slug Prevented', false, 'Allowed duplicate slug');
} catch (Exception $e) {
    logResult('Duplicate Slug Prevented', true, 'Correctly threw exception');
}

// 4. Same Slug Different Sport
try {
    $team2 = $teamService->createTeam($orgA, $basketballId, 'Senior Men', 'Bball squad', 'Senior');
    logResult('Same Slug Different Sport', true, 'Allowed successfully');
} catch (Exception $e) {
    logResult('Same Slug Different Sport', false, $e->getMessage());
}

// 5. Same Slug Different Org
try {
    $team3 = $teamService->createTeam($orgB, $footballId, 'Senior Men', 'Org B squad', 'Senior');
    logResult('Same Slug Different Org', true, 'Allowed successfully');
} catch (Exception $e) {
    logResult('Same Slug Different Org', false, $e->getMessage());
}

// 6. Cross Tenant IDOR Protection via Service/Repo (Simulated)
// The middleware normally isolates this, but if a malicious user passes another org's ID:
try {
    $teamService->updateTeam($team3, $orgA, $footballId, 'Hacked Name', null, null);
    logResult('IDOR Protection in Service', false, 'Allowed cross-tenant update');
} catch (Exception $e) {
    logResult('IDOR Protection in Service', true, 'Prevented cross-tenant update');
}

// 7. Test Archive
$teamService->archiveTeam($team1, $orgA, $footballId);
$deletedAt = $db->query("SELECT deleted_at FROM teams WHERE id = '$team1'")->fetchColumn();
$isActive = $db->query("SELECT is_active FROM teams WHERE id = '$team1'")->fetchColumn();
logResult('Archive Team', $deletedAt !== null && $isActive == 0, 'Soft deleted and set inactive');

// 8. Archived teams excluded from active list
$repo = new \Benchero\Repositories\TeamRepository();
$activeTeams = $repo->findActiveByOrgAndSport($orgA, $footballId);
logResult('Archived Team Excluded', count($activeTeams) === 0, 'Archived team not in active list');

echo "\nAll Task 10 tests passed.\n";
