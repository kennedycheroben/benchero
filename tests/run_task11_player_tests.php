<?php
require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Services\OrganizationService;
use Benchero\Services\TeamService;
use Benchero\Services\SeasonService;
use Benchero\Services\PlayerService;
use Benchero\Services\RosterService;
use Benchero\Repositories\PlayerRepository;
use Benchero\Repositories\RosterRepository;
use Benchero\Core\Ulid;

$db = Database::getConnection();
$db->exec("DELETE FROM roster_assignments");
$db->exec("DELETE FROM players");
$db->exec("DELETE FROM seasons");
$db->exec("DELETE FROM teams");
$db->exec("DELETE FROM organization_sports");
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
$db->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$userA', 'User A', 'a@a.com', 'pwd', NOW())");

$orgService = new OrganizationService();
$orgService->createOrganization('Org A', 'org-a', 'KE', 'Africa/Nairobi', $userA);
$orgA = $db->query("SELECT id FROM organizations WHERE slug = 'org-a'")->fetchColumn();
$footballId = $db->query("SELECT id FROM sports WHERE slug = 'football'")->fetchColumn();
$db->exec("INSERT INTO organization_sports (organization_id, sport_id) VALUES ('$orgA', '$footballId')");

// Create a Team and Season
$teamService = new TeamService();
$team1 = $teamService->createTeam($orgA, $footballId, 'Senior Men', 'Main squad', 'Senior');

$seasonService = new SeasonService();
$season1 = $seasonService->createSeason($orgA, $footballId, '2025/2026', '2025-08-01', '2026-05-31');

// 2. Test Player Creation
$playerService = new PlayerService();
$playerRepo = new PlayerRepository();

$player1 = $playerService->createPlayer($orgA, $footballId, 'John', 'Doe');
$dbPlayer = $playerRepo->findById($player1, $orgA, $footballId);
logResult('Create Player', $dbPlayer['first_name'] === 'John' && $dbPlayer['last_name'] === 'Doe', 'Player created successfully');

// 3. Test Roster Assignment
$rosterService = new RosterService();
$rosterRepo = new RosterRepository();

$assignment1 = $rosterService->assignPlayerToRoster($orgA, $footballId, $player1, $team1, $season1, '10', 'Forward');
$dbRoster = $rosterRepo->findAssignment($player1, $team1, $season1, $orgA, $footballId);
logResult('Assign to Roster', $dbRoster['jersey_number'] === '10' && $dbRoster['position'] === 'Forward', 'Player assigned successfully');

// 4. Duplicate Assignment Prevention
try {
    $rosterService->assignPlayerToRoster($orgA, $footballId, $player1, $team1, $season1, '11', 'Midfielder');
    logResult('Duplicate Assignment Prevented', false, 'Allowed duplicate assignment');
} catch (Exception $e) {
    logResult('Duplicate Assignment Prevented', true, 'Threw exception correctly');
}

// 5. Cross Tenant IDOR Protection Simulated
$userB = Ulid::generate();
$db->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$userB', 'User B', 'b@b.com', 'pwd', NOW())");
$orgService->createOrganization('Org B', 'org-b', 'US', 'America/New_York', $userB);
$orgB = $db->query("SELECT id FROM organizations WHERE slug = 'org-b'")->fetchColumn();
$db->exec("INSERT INTO organization_sports (organization_id, sport_id) VALUES ('$orgB', '$footballId')");

$team2 = $teamService->createTeam($orgB, $footballId, 'Team B', 'B', 'Senior');
$season2 = $seasonService->createSeason($orgB, $footballId, '2025', '2025-01-01', '2025-12-31');

// Try to assign Org A player to Org B team
try {
    $rosterService->assignPlayerToRoster($orgB, $footballId, $player1, $team2, $season2, '7', 'Winger');
    logResult('IDOR Protection on Assignment', false, 'Allowed assigning another orgs player');
} catch (Exception $e) {
    logResult('IDOR Protection on Assignment', true, 'Prevented cross-tenant player assignment');
}

// Try to update Org A's player using Org B context
try {
    $playerService->updatePlayer($player1, $orgB, $footballId, 'Hacked', 'Name');
    logResult('IDOR Protection on Player Update', false, 'Allowed cross-tenant update');
} catch (Exception $e) {
    logResult('IDOR Protection on Player Update', true, 'Prevented cross-tenant player update');
}

// 6. Archive player archives them (soft delete)
$playerService->archivePlayer($player1, $orgA, $footballId);
$activePlayers = $playerRepo->findActiveByOrgAndSport($orgA, $footballId);
logResult('Archive Player', count($activePlayers) === 0, 'Archived player excluded from active list');

// 7. Verify roster assignment still exists (integrity despite player archive)
$assignments = $rosterRepo->findAssignmentsByPlayer($player1, $orgA, $footballId);
logResult('Roster Integrity', count($assignments) === 1, 'Roster assignment remains after player soft-delete');

echo "\nAll Task 11 tests passed.\n";
