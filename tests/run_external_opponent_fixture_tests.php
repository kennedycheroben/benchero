<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\FixtureService;
use Benchero\Services\OrganizationService;
use Benchero\Services\TeamService;
use Benchero\Services\SeasonService;
use Benchero\Repositories\FixtureRepository;

echo "====================================================\n";
echo "BENCHERO EXTERNAL OPPONENT FIXTURE TEST SUITE (P0 #4)\n";
echo "====================================================\n\n";

$db = Database::getConnection();

// Reset test data
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
$fixtureRepo = new FixtureRepository();
$orgService = new OrganizationService();
$teamService = new TeamService();
$seasonService = new SeasonService();

// Create test users
$userA = Ulid::generate();
$userB = Ulid::generate();
$db->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$userA', 'User A', 'usera@example.com', 'pwd', NOW()), ('$userB', 'User B', 'userb@example.com', 'pwd', NOW())");

// Create organizations
$orgService->createOrganization('Nairobi Cheetahs Club', 'nairobi-cheetahs', 'KE', 'Africa/Nairobi', $userA);
$orgService->createOrganization('Rival Organization', 'rival-org', 'KE', 'Africa/Nairobi', $userB);

$org1 = $db->query("SELECT id FROM organizations WHERE slug = 'nairobi-cheetahs'")->fetchColumn();
$org2 = $db->query("SELECT id FROM organizations WHERE slug = 'rival-org'")->fetchColumn();

$sport1 = $db->query("SELECT id FROM sports WHERE slug = 'football'")->fetchColumn();
if (!$sport1) {
    $sport1 = Ulid::generate();
    $db->exec("INSERT INTO sports (id, name, slug) VALUES ('$sport1', 'Football', 'football')");
}
$db->exec("INSERT INTO organization_sports (organization_id, sport_id, is_active) VALUES ('$org1', '$sport1', 1), ('$org2', '$sport1', 1) ON DUPLICATE KEY UPDATE is_active = 1");

// Create seasons
$season1 = $seasonService->createSeason($org1, $sport1, '2026 Season', '2026-01-01', '2026-12-31');
$season2 = $seasonService->createSeason($org2, $sport1, '2026 Season', '2026-01-01', '2026-12-31');

// Create Teams
$teamA = $teamService->createTeam($org1, $sport1, 'Cheetahs Senior Team', 'cheetahs-senior', 'Senior');
$teamB = $teamService->createTeam($org1, $sport1, 'Cheetahs Academy U20', 'cheetahs-u20', 'Youth');
$teamC = $teamService->createTeam($org2, $sport1, 'Rival Strikers', 'rival-strikers', 'Senior');

$passed = 0;
$failed = 0;

function assertTest($number, $name, $condition, &$passed, &$failed, $details = '') {
    if ($condition) {
        echo "✅ PASS [Test {$number}]: {$name}\n";
        $passed++;
    } else {
        echo "❌ FAIL [Test {$number}]: {$name}\n";
        if ($details) {
            echo "   Details: {$details}\n";
        }
        $failed++;
    }
}

// ----------------------------------------------------
// 1. Existing Internal Fixture: Team A vs Team B succeeds
// ----------------------------------------------------
try {
    $internalFixtureId = $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamB,
        '2026-10-01 15:00:00', 'Cheetahs Arena', 'league', 'Premier Division'
    );
    $f = $fixtureRepo->findById($internalFixtureId, $org1, $sport1);
    $ok = $f && $f['home_team_id'] === $teamA && $f['away_team_id'] === $teamB
        && $f['home_opponent_name'] === null && $f['away_opponent_name'] === null
        && $f['home_team_name'] === 'Cheetahs Senior Team'
        && $f['away_team_name'] === 'Cheetahs Academy U20';
    assertTest(1, "Existing internal fixture (Team A vs Team B succeeds)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(1, "Existing internal fixture (Team A vs Team B succeeds)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 2. Internal team home + external away: Team A vs AFC Leopards succeeds
// ----------------------------------------------------
try {
    $externalAwayFixtureId = $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, null,
        '2026-10-05 16:00:00', 'Cheetahs Arena', 'friendly', 'Pre-season Friendly',
        'scheduled', 'First test match', null, 'AFC Leopards'
    );
    $f = $fixtureRepo->findById($externalAwayFixtureId, $org1, $sport1);
    // Verify no fake team record was created for AFC Leopards
    $fakeTeamCount = (int)$db->query("SELECT COUNT(*) FROM teams WHERE name = 'AFC Leopards'")->fetchColumn();
    $ok = $f && $f['home_team_id'] === $teamA && $f['away_team_id'] === null
        && $f['home_opponent_name'] === null && $f['away_opponent_name'] === 'AFC Leopards'
        && $f['home_team_name'] === 'Cheetahs Senior Team'
        && $f['away_team_name'] === 'AFC Leopards'
        && $fakeTeamCount === 0;
    assertTest(2, "Internal team home + external away (Team A vs AFC Leopards succeeds without fake team)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(2, "Internal team home + external away (Team A vs AFC Leopards succeeds without fake team)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 3. External home + internal team away: AFC Leopards vs Team A succeeds
// ----------------------------------------------------
try {
    $externalHomeFixtureId = $fixtureService->createFixture(
        $org1, $sport1, $season1, null, $teamA,
        '2026-10-10 15:00:00', 'Nyayo National Stadium', 'cup', 'GOtv Shield',
        'scheduled', null, 'AFC Leopards', null
    );
    $f = $fixtureRepo->findById($externalHomeFixtureId, $org1, $sport1);
    $ok = $f && $f['home_team_id'] === null && $f['away_team_id'] === $teamA
        && $f['home_opponent_name'] === 'AFC Leopards' && $f['away_opponent_name'] === null
        && $f['home_team_name'] === 'AFC Leopards'
        && $f['away_team_name'] === 'Cheetahs Senior Team';
    assertTest(3, "External home + internal team away (AFC Leopards vs Team A succeeds)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(3, "External home + internal team away (AFC Leopards vs Team A succeeds)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 4. External vs external: Must fail
// ----------------------------------------------------
try {
    $fixtureService->createFixture(
        $org1, $sport1, $season1, null, null,
        '2026-10-15 15:00:00', 'Neutral Ground', 'friendly', null,
        'scheduled', null, 'AFC Leopards', 'Gor Mahia'
    );
    assertTest(4, "External vs external (Must fail)", false, $passed, $failed, "Did not reject external vs external");
} catch (\Exception $e) {
    assertTest(4, "External vs external (Must fail)", str_contains($e->getMessage(), 'at least one internal Benchero team'), $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 5. Same internal team: Must fail
// ----------------------------------------------------
try {
    $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamA,
        '2026-10-20 15:00:00'
    );
    assertTest(5, "Same internal team (Must fail)", false, $passed, $failed, "Did not reject Team A vs Team A");
} catch (\Exception $e) {
    assertTest(5, "Same internal team (Must fail)", str_contains($e->getMessage(), 'cannot be the same'), $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 6. Empty external opponent: Must fail
// ----------------------------------------------------
try {
    $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, null,
        '2026-10-25 15:00:00', null, 'friendly', null,
        'scheduled', null, null, "   "
    );
    assertTest(6, "Empty/whitespace external opponent (Must fail)", false, $passed, $failed, "Did not reject whitespace opponent name");
} catch (\Exception $e) {
    assertTest(6, "Empty/whitespace external opponent (Must fail)", str_contains($e->getMessage(), 'must specify either an internal team or an external opponent'), $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 7. Internal + external on same side: Must fail
// ----------------------------------------------------
try {
    $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamB,
        '2026-10-30 15:00:00', null, 'league', null,
        'scheduled', null, 'Duplicate Opponent', null
    );
    assertTest(7, "Internal + external on same side (Must fail)", false, $passed, $failed, "Did not reject both internal team and external opponent on same side");
} catch (\Exception $e) {
    assertTest(7, "Internal + external on same side (Must fail)", str_contains($e->getMessage(), 'cannot have both an internal team and an external opponent'), $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 8. Cross-tenant team: Must fail
// ----------------------------------------------------
try {
    $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamC,
        '2026-11-01 15:00:00'
    );
    assertTest(8, "Cross-tenant team (Must fail)", false, $passed, $failed, "Did not reject cross-tenant team");
} catch (\Exception $e) {
    assertTest(8, "Cross-tenant team (Must fail)", str_contains($e->getMessage(), 'Invalid away team context') || str_contains($e->getMessage(), 'belong to this organization'), $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 9. Editing internal → external: Succeeds and clears stale team ID
// ----------------------------------------------------
try {
    // Start with internal fixture: Team A vs Team B
    $editableFixtureId = $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, $teamB,
        '2026-11-05 14:00:00'
    );
    // Update away side from Team B (internal) to Tusker FC (external)
    $fixtureService->updateFixture(
        $editableFixtureId, $org1, $sport1, $season1,
        $teamA, null, '2026-11-05 14:00:00', 'Cheetahs Arena', 'friendly', 'Friendly',
        'Updated to external opponent', null, 'Tusker FC'
    );
    $f = $fixtureRepo->findById($editableFixtureId, $org1, $sport1);
    $ok = $f && $f['home_team_id'] === $teamA && $f['away_team_id'] === null
        && $f['away_opponent_name'] === 'Tusker FC'
        && $f['away_team_name'] === 'Tusker FC';
    assertTest(9, "Editing internal → external (Succeeds and clears stale team ID)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(9, "Editing internal → external (Succeeds and clears stale team ID)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 10. Editing external → internal: Succeeds and clears stale opponent name
// ----------------------------------------------------
try {
    // Now switch away side back from Tusker FC (external) to Team B (internal)
    $fixtureService->updateFixture(
        $editableFixtureId, $org1, $sport1, $season1,
        $teamA, $teamB, '2026-11-05 14:00:00', 'Cheetahs Arena', 'league', 'League Match',
        'Updated back to internal', null, null
    );
    $f = $fixtureRepo->findById($editableFixtureId, $org1, $sport1);
    $ok = $f && $f['home_team_id'] === $teamA && $f['away_team_id'] === $teamB
        && $f['away_opponent_name'] === null
        && $f['away_team_name'] === 'Cheetahs Academy U20';
    assertTest(10, "Editing external → internal (Succeeds and clears stale opponent name)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(10, "Editing external → internal (Succeeds and clears stale opponent name)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 11. Result recording: External fixture accepts score and notes
// ----------------------------------------------------
try {
    $resultNotes = "Scorers: Cheetahs striker 15', Leopards 78'";
    $fixtureService->recordResult($externalAwayFixtureId, $org1, $sport1, 2, 1, $resultNotes);
    $f = $fixtureRepo->findById($externalAwayFixtureId, $org1, $sport1);
    $ok = $f && $f['status'] === 'completed'
        && (int)$f['home_score'] === 2 && (int)$f['away_score'] === 1
        && $f['result_notes'] === $resultNotes;
    assertTest(11, "Result recording (External fixture accepts score and notes)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(11, "Result recording (External fixture accepts score and notes)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 12. Result display: Both participant names appear correctly
// ----------------------------------------------------
try {
    $f = $fixtureRepo->findById($externalAwayFixtureId, $org1, $sport1);
    $allSeason = $fixtureRepo->findBySeason($season1, $org1, $sport1);
    $foundInSeason = false;
    foreach ($allSeason as $item) {
        if ($item['id'] === $externalAwayFixtureId) {
            $foundInSeason = ($item['home_team_name'] === 'Cheetahs Senior Team' && $item['away_team_name'] === 'AFC Leopards');
            break;
        }
    }
    $ok = $f && $f['home_team_name'] === 'Cheetahs Senior Team'
        && $f['away_team_name'] === 'AFC Leopards'
        && (int)$f['home_score'] === 2
        && (int)$f['away_score'] === 1
        && $foundInSeason;
    assertTest(12, "Result display (Both participant names appear correctly in result and season listing)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(12, "Result display (Both participant names appear correctly)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 13. Public fixture display: External opponent appears correctly
// ----------------------------------------------------
try {
    $publicFixtures = $fixtureRepo->findPublicBySeason($season1, $org1, $sport1);
    $foundPublicAway = false;
    $foundPublicHome = false;
    foreach ($publicFixtures as $pf) {
        if ($pf['id'] === $externalAwayFixtureId) {
            $foundPublicAway = ($pf['home_team_name'] === 'Cheetahs Senior Team' && $pf['away_team_name'] === 'AFC Leopards');
        }
        if ($pf['id'] === $externalHomeFixtureId) {
            $foundPublicHome = ($pf['home_team_name'] === 'AFC Leopards' && $pf['away_team_name'] === 'Cheetahs Senior Team');
        }
    }
    $ok = $foundPublicAway && $foundPublicHome;
    assertTest(13, "Public fixture display (External opponent appears correctly in public club queries)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(13, "Public fixture display (External opponent appears correctly in public club queries)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 14. Tenant isolation: Organization A cannot access Organization B fixture
// ----------------------------------------------------
try {
    $fixtureInOrg2 = $fixtureService->createFixture(
        $org2, $sport1, $season2, $teamC, null,
        '2026-11-20 15:00:00', 'Rival Grounds', 'friendly', null,
        'scheduled', null, null, 'Bandari FC'
    );
    // Org 1 tries to find Org 2's fixture
    $leak = $fixtureRepo->findById($fixtureInOrg2, $org1, $sport1);
    assertTest(14, "Tenant isolation (Organization A cannot access Organization B fixture)", $leak === null, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(14, "Tenant isolation (Organization A cannot access Organization B fixture)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 15. Delete protection: Cross-tenant fixture deletion remains blocked
// ----------------------------------------------------
try {
    $fixtureService->deleteFixture($fixtureInOrg2, $org1, $sport1);
    assertTest(15, "Delete protection (Cross-tenant fixture deletion remains blocked)", false, $passed, $failed, "Allowed Org 1 to delete Org 2 fixture");
} catch (\Exception $e) {
    // Ensure Org 2's fixture was NOT deleted
    $fCheck = $fixtureRepo->findById($fixtureInOrg2, $org2, $sport1);
    $ok = str_contains($e->getMessage(), 'Fixture not found') && $fCheck !== null;
    assertTest(15, "Delete protection (Cross-tenant fixture deletion remains blocked)", $ok, $passed, $failed);
}

// ----------------------------------------------------
// 16. Existing fixture regression: Existing internal fixtures continue to work
// ----------------------------------------------------
try {
    // Record result for internal fixture
    $fixtureService->recordResult($internalFixtureId, $org1, $sport1, 3, 2, 'Great derby');
    $f = $fixtureRepo->findById($internalFixtureId, $org1, $sport1);
    $ok = $f && $f['status'] === 'completed'
        && (int)$f['home_score'] === 3 && (int)$f['away_score'] === 2
        && $f['home_team_name'] === 'Cheetahs Senior Team'
        && $f['away_team_name'] === 'Cheetahs Academy U20';
    assertTest(16, "Existing fixture regression (Internal fixtures continue to record results and display)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(16, "Existing fixture regression (Internal fixtures continue to record results and display)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 17. Database migration: Existing fixture rows survive migration unchanged
// ----------------------------------------------------
try {
    // Verify columns exist and internal fixture has NULL opponent names
    $stmt = $db->prepare("SELECT home_team_id, away_team_id, home_opponent_name, away_opponent_name FROM fixtures WHERE id = ?");
    $stmt->execute([$internalFixtureId]);
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    $ok = $row
        && $row['home_team_id'] === $teamA
        && $row['away_team_id'] === $teamB
        && $row['home_opponent_name'] === null
        && $row['away_opponent_name'] === null;
    assertTest(17, "Database migration integrity (Existing fixture rows survive with NULL opponent columns)", $ok, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(17, "Database migration integrity (Existing fixture rows survive with NULL opponent columns)", false, $passed, $failed, $e->getMessage());
}

// ----------------------------------------------------
// 18. Validation: Maximum external opponent length (255 characters)
// ----------------------------------------------------
try {
    $valid255 = str_repeat('A', 255);
    $fix255 = $fixtureService->createFixture(
        $org1, $sport1, $season1, $teamA, null,
        '2026-12-01 15:00:00', null, 'friendly', null,
        'scheduled', null, null, $valid255
    );
    $f255 = $fixtureRepo->findById($fix255, $org1, $sport1);
    $validPass = $f255 && $f255['away_opponent_name'] === $valid255;

    $invalid256 = str_repeat('B', 256);
    $invalidPass = false;
    try {
        $fixtureService->createFixture(
            $org1, $sport1, $season1, $teamA, null,
            '2026-12-02 15:00:00', null, 'friendly', null,
            'scheduled', null, null, $invalid256
        );
    } catch (\Exception $e) {
        $invalidPass = str_contains($e->getMessage(), 'cannot exceed 255');
    }

    assertTest(18, "Validation (Accepts 255 chars, rejects >255 chars for external opponent)", $validPass && $invalidPass, $passed, $failed);
} catch (\Throwable $e) {
    assertTest(18, "Validation (Accepts 255 chars, rejects >255 chars for external opponent)", false, $passed, $failed, $e->getMessage());
}

echo "\n----------------------------------------------------\n";
echo "External Opponent Fixture Tests Summary:\n";
echo "Total Passed: {$passed}\n";
echo "Total Failed: {$failed}\n";
echo "----------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
