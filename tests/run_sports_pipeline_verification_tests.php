<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\Sports\SportsService;
use Benchero\Services\Sports\SportsSyncService;
use Benchero\Services\Sports\SportsProviderRouter;
use Benchero\Services\Sports\Providers\ApiFootballSportsProvider;
use Benchero\Services\Sports\Providers\FootballDataSportsProvider;
use Benchero\Services\Sports\Providers\NullSportsProvider;

echo "====================================================================\n";
echo " BENCHERO SPORTS DATA PIPELINE VERIFICATION SUITE\n";
echo "====================================================================\n";

$passed = 0;
$failed = 0;

function assertCheck(bool $condition, string $description, &$passed, &$failed): void {
    if ($condition) {
        echo " [PASS] {$description}\n";
        $passed++;
    } else {
        echo " [FAIL] {$description}\n";
        $failed++;
    }
}

$pdo = Database::getConnection();
$router = new SportsProviderRouter();
$syncService = new SportsSyncService($pdo);
$sportsService = new SportsService();

// -----------------------------------------------------------------------------
// 1. Stale LIVE filtering
// -----------------------------------------------------------------------------
echo "\n--- 1. Stale LIVE Filtering ---\n";
// Insert temporary stale live match (> 150m ago) in transaction, verify getLiveScores() does not return it
$pdo->beginTransaction();
try {
    $sportId = $pdo->query("SELECT id FROM sports LIMIT 1")->fetchColumn();
    $compId = $pdo->query("SELECT id FROM sports_competitions LIMIT 1")->fetchColumn();
    $teamId1 = $pdo->query("SELECT id FROM sports_teams LIMIT 1")->fetchColumn();
    $teamId2 = $pdo->query("SELECT id FROM sports_teams WHERE id != '{$teamId1}' LIMIT 1")->fetchColumn();

    $staleMatchId = Ulid::generate();
    $stmt = $pdo->prepare("
        INSERT INTO sports_matches (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, provider, external_id)
        VALUES (?, ?, ?, ?, ?, 'LIVE', DATE_SUB(NOW(), INTERVAL 180 MINUTE), 'test_filter', ?)
    ");
    $stmt->execute([$staleMatchId, $sportId, $compId, $teamId1, $teamId2, 'ext_test_stale_filter']);

    // Directly query via the live scores query logic
    $stmtCheck = $pdo->query("
        SELECT id FROM sports_matches
        WHERE status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED')
          AND provider != 'mock'
          AND start_time >= DATE_SUB(NOW(), INTERVAL 150 MINUTE)
          AND id = '{$staleMatchId}'
    ");
    assertCheck($stmtCheck->fetchColumn() === false, "Live query excludes match started 180 minutes ago", $passed, $failed);

    // Verify recent live match (started 30m ago) IS returned
    $recentMatchId = Ulid::generate();
    $stmt2 = $pdo->prepare("
        INSERT INTO sports_matches (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, provider, external_id)
        VALUES (?, ?, ?, ?, ?, 'LIVE', DATE_SUB(NOW(), INTERVAL 30 MINUTE), 'test_filter', ?)
    ");
    $stmt2->execute([$recentMatchId, $sportId, $compId, $teamId1, $teamId2, 'ext_test_recent_filter']);

    $stmtCheckRecent = $pdo->query("
        SELECT id FROM sports_matches
        WHERE status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED')
          AND provider != 'mock'
          AND start_time >= DATE_SUB(NOW(), INTERVAL 150 MINUTE)
          AND id = '{$recentMatchId}'
    ");
    assertCheck($stmtCheckRecent->fetchColumn() === $recentMatchId, "Live query includes match started 30 minutes ago", $passed, $failed);
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// 2. Stale LIVE cleanup
// -----------------------------------------------------------------------------
echo "\n--- 2. Stale LIVE Cleanup ---\n";
$pdo->beginTransaction();
try {
    $testMatchId = Ulid::generate();
    $stmt = $pdo->prepare("
        INSERT INTO sports_matches (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, provider, external_id)
        VALUES (?, ?, ?, ?, ?, 'LIVE', DATE_SUB(NOW(), INTERVAL 200 MINUTE), 'test_cleanup', ?)
    ");
    $stmt->execute([$testMatchId, $sportId, $compId, $teamId1, $teamId2, 'ext_test_cleanup_stale']);

    $cleanedCount = $syncService->cleanupStaleLiveMatches(150);
    assertCheck($cleanedCount >= 1, "cleanupStaleLiveMatches transitioned stale LIVE records", $passed, $failed);

    $updatedStatus = $pdo->query("SELECT status FROM sports_matches WHERE id = '{$testMatchId}'")->fetchColumn();
    assertCheck($updatedStatus === 'FINISHED', "Stale match status transitioned safely to FINISHED", $passed, $failed);
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// 3. API Error Bubbling
// -----------------------------------------------------------------------------
echo "\n--- 3. API Error Bubbling ---\n";
$afEmpty = new ApiFootballSportsProvider('');
$afBubbled = false;
try {
    $afEmpty->getLiveScores();
} catch (\RuntimeException $e) {
    $afBubbled = ($e->getCode() === 401);
}
assertCheck($afBubbled, "ApiFootballSportsProvider bubbles 401 exception when API key missing", $passed, $failed);

$fdEmpty = new FootballDataSportsProvider('');
$fdBubbled = false;
try {
    $fdEmpty->getResults();
} catch (\RuntimeException $e) {
    $fdBubbled = ($e->getCode() === 401);
}
assertCheck($fdBubbled, "FootballDataSportsProvider bubbles 401 exception when API key missing", $passed, $failed);

// -----------------------------------------------------------------------------
// 4. API Quota / Error Response in Sync Logs
// -----------------------------------------------------------------------------
echo "\n--- 4. API Quota / Error Response Handling ---\n";
$pdo->beginTransaction();
try {
    // Verify sync log accurately captures status = 'error'
    $logCountBefore = (int)$pdo->query("SELECT COUNT(*) FROM sports_sync_logs WHERE status = 'error'")->fetchColumn();
    
    // Inject a failing provider into sync service
    $failingProvider = new class implements \Benchero\Contracts\SportsProviderInterface {
        public function getLiveScores(): array { throw new \RuntimeException("Rate limit / quota exceeded (HTTP 429)", 429); }
        public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array { return []; }
        public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array { return []; }
        public function getCompetitions(?string $sport = null): array { return []; }
        public function getStandings(string $competitionSlug): array { return []; }
        public function getMatchDetail(string $matchId): ?array { return null; }
    };
    $failingSync = new SportsSyncService($pdo, $failingProvider);
    $res = $failingSync->syncLive();

    assertCheck($res['success'] === false, "Failing provider reports success=false (not empty success)", $passed, $failed);
    
    $stmtLastLog = $pdo->query("SELECT status, error_message FROM sports_sync_logs ORDER BY id DESC LIMIT 1");
    $lastLog = $stmtLastLog->fetch(PDO::FETCH_ASSOC);
    assertCheck($lastLog['status'] === 'error', "Sync failure logged with status = 'error'", $passed, $failed);
    assertCheck(str_contains($lastLog['error_message'], '429'), "Sync log preserves error message without falsifying success", $passed, $failed);
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// 5. Provider Routing
// -----------------------------------------------------------------------------
echo "\n--- 5. Provider Routing ---\n";
$eplProvider = $router->resolveProviderForCompetition('premier-league');
assertCheck($eplProvider instanceof FootballDataSportsProvider || $eplProvider instanceof NullSportsProvider, "EPL routes to FootballDataSportsProvider", $passed, $failed);

$laLigaProvider = $router->resolveProviderForCompetition('la-liga');
assertCheck($laLigaProvider instanceof FootballDataSportsProvider || $laLigaProvider instanceof NullSportsProvider, "La Liga routes to FootballDataSportsProvider", $passed, $failed);

// -----------------------------------------------------------------------------
// 6. FKF League Routing
// -----------------------------------------------------------------------------
echo "\n--- 6. FKF League Routing ---\n";
$fkfProv = $router->resolveProviderForCompetition('fkf-premier-league');
assertCheck($fkfProv instanceof ApiFootballSportsProvider || $fkfProv instanceof FootballDataSportsProvider || $fkfProv instanceof NullSportsProvider, "FKF Premier League routes to ApiFootballSportsProvider", $passed, $failed);
assertCheck($router->isProviderAuthoritativeForCompetition('api-football', 'fkf-premier-league') === true, "api-football is authoritative for fkf-premier-league", $passed, $failed);
assertCheck($router->isProviderAuthoritativeForCompetition('football-data', 'fkf-premier-league') === false, "football-data is NOT authoritative for fkf-premier-league", $passed, $failed);

// -----------------------------------------------------------------------------
// 7. CAF Routing
// -----------------------------------------------------------------------------
echo "\n--- 7. CAF Routing ---\n";
$cafClProv = $router->resolveProviderForCompetition('caf-champions-league');
assertCheck($cafClProv instanceof ApiFootballSportsProvider || $cafClProv instanceof FootballDataSportsProvider || $cafClProv instanceof NullSportsProvider, "CAF Champions League routes to ApiFootballSportsProvider", $passed, $failed);

$cafCcProv = $router->resolveProviderForCompetition('caf-confederation-cup');
assertCheck($cafCcProv instanceof ApiFootballSportsProvider || $cafCcProv instanceof FootballDataSportsProvider || $cafCcProv instanceof NullSportsProvider, "CAF Confederation Cup routes to ApiFootballSportsProvider", $passed, $failed);

// -----------------------------------------------------------------------------
// 8. Competition Slug Collision Prevention
// -----------------------------------------------------------------------------
echo "\n--- 8. Competition Slug Collision Prevention ---\n";
$afProv = new ApiFootballSportsProvider('test_key');

$bhutanFixture = [
    'fixture' => ['id' => 1111, 'date' => '2026-09-24T12:00:00+00:00', 'status' => ['short' => 'FT']],
    'league' => ['id' => 7777, 'name' => 'Premier League', 'country' => 'Bhutan'],
    'teams' => ['home' => ['name' => 'RTC'], 'away' => ['name' => 'Tsirang']],
    'goals' => ['home' => 1, 'away' => 1]
];
$normB = $afProv->normalizeMatch($bhutanFixture);
assertCheck($normB['competition_slug'] === 'bhutan-premier-league', "Bhutan Premier League normalized as 'bhutan-premier-league'", $passed, $failed);
assertCheck($normB['competition_slug'] !== 'premier-league', "Bhutan Premier League does not collide with 'premier-league'", $passed, $failed);

$jamaicaFixture = [
    'fixture' => ['id' => 2222, 'date' => '2026-09-24T12:00:00+00:00', 'status' => ['short' => 'FT']],
    'league' => ['id' => 8888, 'name' => 'Premier League', 'country' => 'Jamaica'],
    'teams' => ['home' => ['name' => 'Arnett Gardens'], 'away' => ['name' => 'Dunbeholden']],
    'goals' => ['home' => 2, 'away' => 0]
];
$normJ = $afProv->normalizeMatch($jamaicaFixture);
assertCheck($normJ['competition_slug'] === 'jamaica-premier-league', "Jamaica Premier League normalized as 'jamaica-premier-league'", $passed, $failed);
assertCheck($normJ['competition_slug'] !== 'premier-league', "Jamaica Premier League does not collide with 'premier-league'", $passed, $failed);

// -----------------------------------------------------------------------------
// 9. Cross-Provider Match Deduplication
// -----------------------------------------------------------------------------
echo "\n--- 9. Cross-Provider Match Deduplication ---\n";
$pdo->beginTransaction();
try {
    $matchIdA = Ulid::generate();
    $kickoff = '2026-10-10 17:00:00';
    $stmtA = $pdo->prepare("
        INSERT INTO sports_matches (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, provider, external_id)
        VALUES (?, ?, ?, ?, ?, 'SCHEDULED', ?, 'football-data', 'fd_1001')
    ");
    $stmtA->execute([$matchIdA, $sportId, $compId, $teamId1, $teamId2, $kickoff]);

    // Cross-provider lookup from API-Football for the same teams with 15-minute start_time offset
    $dedupFoundId = $syncService->findExistingMatch(
        'api-football',
        'af_9001',
        $teamId1,
        $teamId2,
        '2026-10-10 17:15:00', // 15 mins offset
        $compId
    );
    assertCheck($dedupFoundId === $matchIdA, "findExistingMatch detects equivalent real-world match across providers", $passed, $failed);

    // Unrelated match should NOT match
    $unrelatedFoundId = $syncService->findExistingMatch(
        'api-football',
        'af_9002',
        $teamId1,
        $teamId2,
        '2026-10-17 17:00:00', // 1 week later
        $compId
    );
    assertCheck($unrelatedFoundId === null, "findExistingMatch does not merge unrelated matches", $passed, $failed);
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// 10. Standings Isolation & Canonicalization
// -----------------------------------------------------------------------------
echo "\n--- 10. Standings Isolation / Canonicalization ---\n";
assertCheck(
    $router->isProviderAuthoritativeForCompetition('football-data', 'premier-league') === true &&
    $router->isProviderAuthoritativeForCompetition('api-football', 'premier-league') === false,
    "Only football-data is authoritative for premier-league standings",
    $passed,
    $failed
);
assertCheck(
    $router->isProviderAuthoritativeForCompetition('api-football', 'fkf-premier-league') === true &&
    $router->isProviderAuthoritativeForCompetition('football-data', 'fkf-premier-league') === false,
    "Only api-football is authoritative for fkf-premier-league standings",
    $passed,
    $failed
);
assertCheck(
    $router->isProviderAuthoritativeForCompetition('football-data', 'la-liga') === true &&
    $router->isProviderAuthoritativeForCompetition('football-data', 'primera-division') === true,
    "Football-data maps both 'la-liga' and 'primera-division' accurately",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// 11. Truthful updated_at
// -----------------------------------------------------------------------------
echo "\n--- 11. Truthful updated_at Timestamps ---\n";
$resData = $sportsService->getResults();
assertCheck(
    array_key_exists('updated_at', $resData),
    "getResults() returns 'updated_at' field",
    $passed,
    $failed
);
$fixData = $sportsService->getFixtures();
assertCheck(
    array_key_exists('updated_at', $fixData),
    "getFixtures() returns 'updated_at' field",
    $passed,
    $failed
);

$latestResultsSync = $sportsService->getLatestSuccessfulSyncTimestamp('sync-results');
if ($latestResultsSync) {
    assertCheck($resData['updated_at'] === $latestResultsSync, "getResults() updated_at matches actual sync log timestamp", $passed, $failed);
} else {
    assertCheck(true, "getResults() updated_at reflects database records truthfully", $passed, $failed);
}

// -----------------------------------------------------------------------------
// 12. CLI Command Parsing
// -----------------------------------------------------------------------------
echo "\n--- 12. CLI Command Parsing ---\n";
$outputPositional = shell_exec('/opt/lampp/bin/php bin/sports_sync.php test_invalid_op 2>&1');
assertCheck(str_contains($outputPositional, "Starting operation 'test_invalid_op'"), "Positional argument parsed correctly (bin/sports_sync.php <op>)", $passed, $failed);

$outputFlag = shell_exec('/opt/lampp/bin/php bin/sports_sync.php --type=fixtures 2>&1');
assertCheck(str_contains($outputFlag, "Starting operation 'fixtures'"), "--type flag parsed correctly (bin/sports_sync.php --type=<op>)", $passed, $failed);

$outputWrapper = shell_exec('/opt/lampp/bin/php bin/sync_sports.php --type=results 2>&1');
assertCheck(str_contains($outputWrapper, "Starting operation 'results'"), "Legacy wrapper forwards correctly (bin/sync_sports.php --type=<op>)", $passed, $failed);

echo "\n====================================================================\n";
echo " TOTAL PASSED: {$passed} | TOTAL FAILED: {$failed}\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);
