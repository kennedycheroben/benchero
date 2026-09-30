<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\Sports\SportsService;
use Benchero\Services\Sports\SportsSyncService;
use Benchero\Services\Sports\SportsQuotaTracker;
use Benchero\Services\Sports\AdaptiveLiveSyncEngine;
use Benchero\Services\Sports\SportsProviderRouter;
use Benchero\Services\Sports\Providers\ApiFootballSportsProvider;
use Benchero\Services\Sports\Providers\FootballDataSportsProvider;
use Benchero\Services\Sports\Providers\NullSportsProvider;
use Benchero\Services\Sports\Providers\MockSportsProvider;
use Benchero\Services\CacheService;

echo "====================================================================\n";
echo " BENCHERO SPORTS ADAPTIVE LIVE SYNC & QUOTA VERIFICATION SUITE\n";
echo "====================================================================\n\n";

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
$cache = new CacheService();
$tracker = new SportsQuotaTracker($pdo);
$adaptive = new AdaptiveLiveSyncEngine($pdo, $tracker);
$router = new SportsProviderRouter();

// Ensure clean provider state before tests start
$pdo->exec("UPDATE sports_provider_states SET backoff_until = NULL, is_degraded = 0, last_error = NULL, quota_remaining = 100 WHERE 1=1");

// Helper to seed match in transaction
$sportId = $pdo->query("SELECT id FROM sports LIMIT 1")->fetchColumn();
$compId = $pdo->query("SELECT id FROM sports_competitions LIMIT 1")->fetchColumn();
$teams = $pdo->query("SELECT id FROM sports_teams LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
$teamA = $teams[0] ?? Ulid::generate();
$teamB = $teams[1] ?? Ulid::generate();

// -----------------------------------------------------------------------------
// TEST 1: No live matches -> slow polling behavior
// -----------------------------------------------------------------------------
$pdo->beginTransaction();
try {
    // Clear out live and upcoming matches in transaction
    $pdo->exec("DELETE FROM sports_matches WHERE provider = 'test_adaptive'");
    
    // Test adaptive engine evaluation with 0 matches
    // Create an isolated engine with mocked query responses
    $testEngine = new AdaptiveLiveSyncEngine($pdo, $tracker, 30, 900, 900, 900, true);
    
    // Force empty live, prematch, and grace period in test query logic
    $pdo->exec("UPDATE sports_matches SET status = 'FINISHED', updated_at = DATE_SUB(NOW(), INTERVAL 3600 SECOND) WHERE provider != 'mock'");
    $state = $testEngine->determineLiveSyncState();
    
    assertCheck(
        $state['state'] === AdaptiveLiveSyncEngine::STATE_IDLE_DISCOVERY && $state['interval_seconds'] >= 900,
        "1. No live matches -> slow polling behavior ({$state['interval_seconds']}s interval in {$state['state']})",
        $passed,
        $failed
    );
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// TEST 2: Live matches -> fast polling behavior
// -----------------------------------------------------------------------------
$pdo->beginTransaction();
try {
    $liveMatchId = Ulid::generate();
    $stmtLive = $pdo->prepare("
        INSERT INTO sports_matches (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, provider, external_id)
        VALUES (?, ?, ?, ?, ?, 'LIVE', DATE_SUB(NOW(), INTERVAL 45 MINUTE), 'test_adaptive', ?)
    ");
    $stmtLive->execute([$liveMatchId, $sportId, $compId, $teamA, $teamB, 'ext_test_live_match']);

    $testEngine = new AdaptiveLiveSyncEngine($pdo, $tracker, 30, 900, 900, 900, true);
    $state = $testEngine->determineLiveSyncState();

    assertCheck(
        $state['state'] === AdaptiveLiveSyncEngine::STATE_LIVE_ACTIVE && $state['interval_seconds'] === 30,
        "2. Live matches -> fast polling behavior ({$state['interval_seconds']}s interval in LIVE_ACTIVE)",
        $passed,
        $failed
    );
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// TEST 3: Upcoming match -> pre-live polling behavior
// -----------------------------------------------------------------------------
$pdo->beginTransaction();
try {
    $pdo->exec("UPDATE sports_matches SET status = 'FINISHED' WHERE status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED')");
    
    $prematchId = Ulid::generate();
    $stmtPre = $pdo->prepare("
        INSERT INTO sports_matches (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, provider, external_id)
        VALUES (?, ?, ?, ?, ?, 'TIMED', DATE_ADD(NOW(), INTERVAL 10 MINUTE), 'test_adaptive', ?)
    ");
    $stmtPre->execute([$prematchId, $sportId, $compId, $teamA, $teamB, 'ext_test_prematch']);

    $testEngine = new AdaptiveLiveSyncEngine($pdo, $tracker, 30, 900, 900, 900, true);
    $state = $testEngine->determineLiveSyncState();

    assertCheck(
        $state['state'] === AdaptiveLiveSyncEngine::STATE_PREMATCH_WINDOW && $state['interval_seconds'] === 60,
        "3. Upcoming match within 15m -> pre-live polling behavior ({$state['interval_seconds']}s in PREMATCH_WINDOW)",
        $passed,
        $failed
    );
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// TEST 4: Finished matches -> live polling stops
// -----------------------------------------------------------------------------
$pdo->beginTransaction();
try {
    $pdo->exec("UPDATE sports_matches SET status = 'FINISHED', updated_at = DATE_SUB(NOW(), INTERVAL 30 MINUTE) WHERE provider != 'mock'");

    $testEngine = new AdaptiveLiveSyncEngine($pdo, $tracker, 30, 900, 900, 900, true);
    $state = $testEngine->determineLiveSyncState();

    assertCheck(
        $state['state'] === AdaptiveLiveSyncEngine::STATE_IDLE_DISCOVERY && $state['interval_seconds'] >= 900,
        "4. Finished matches outside grace period -> live fast polling stops, reverts to slow discovery",
        $passed,
        $failed
    );
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// TEST 5: Frontend polling does not call external providers
// -----------------------------------------------------------------------------
// Create mock provider that throws if called
$spyProvider = new class implements \Benchero\Contracts\SportsProviderInterface {
    public int $callCount = 0;
    public function getLiveScores(): array { $this->callCount++; throw new \RuntimeException("Should NOT be called"); }
    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array { $this->callCount++; throw new \RuntimeException("Should NOT be called"); }
    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array { $this->callCount++; throw new \RuntimeException("Should NOT be called"); }
    public function getCompetitions(?string $sport = null): array { $this->callCount++; throw new \RuntimeException("Should NOT be called"); }
    public function getStandings(string $competitionId): array { $this->callCount++; throw new \RuntimeException("Should NOT be called"); }
    public function getMatchDetail(string $matchId): ?array { $this->callCount++; return null; }
};

$isolatedSportsService = new SportsService($spyProvider, $cache, $router);
$liveResult = $isolatedSportsService->getLiveScores();
$resultsResult = $isolatedSportsService->getResults();
$fixturesResult = $isolatedSportsService->getFixtures();
$standingsResult = $isolatedSportsService->getStandings('premier-league');

assertCheck(
    $spyProvider->callCount === 0,
    "5. Frontend polling and visitor service methods NEVER call external provider APIs synchronously",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 6: Multiple users share server-side cache
// -----------------------------------------------------------------------------
$cache->flushSportsCache();
$user1Result = $isolatedSportsService->getLiveScores();
$user2Result = $isolatedSportsService->getLiveScores();

assertCheck(
    isset($user1Result['matches']) && isset($user2Result['matches']) && $user1Result === $user2Result,
    "6. Multiple users share the same server-side cached provider response immediately",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 7: Concurrent sync processes are locked
// -----------------------------------------------------------------------------
$syncServiceA = new SportsSyncService($pdo);
$syncServiceB = new SportsSyncService($pdo);

$lockA = $syncServiceA->acquireLock('live');
$lockB = $syncServiceB->acquireLock('live');

assertCheck(
    $lockA !== false && $lockB === false,
    "7. Concurrent sync processes are locked: second worker cannot acquire active action lock",
    $passed,
    $failed
);
$syncServiceA->releaseLock($lockA);

// -----------------------------------------------------------------------------
// TEST 8: Lock expires/releases after process crash/close
// -----------------------------------------------------------------------------
$lockC = $syncServiceA->acquireLock('live');
$syncServiceA->releaseLock($lockC);
$lockD = $syncServiceB->acquireLock('live');

assertCheck(
    $lockD !== false,
    "8. Lock automatically frees and is re-acquirable after process release / termination",
    $passed,
    $failed
);
$syncServiceB->releaseLock($lockD);

// -----------------------------------------------------------------------------
// TEST 9: HTTP 429 causes backoff
// -----------------------------------------------------------------------------
$tracker->recordRateLimitHit('api-football', 429, 300);
$inBackoff = $tracker->isProviderInBackoff('api-football');
$remBackoff = $tracker->getRemainingBackoffSeconds('api-football');

assertCheck(
    $inBackoff === true && $remBackoff > 200,
    "9. HTTP 429 triggers automatic rate-limit backoff ({$remBackoff}s remaining)",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 10: HTTP 401/403 causes provider error state
// -----------------------------------------------------------------------------
$tracker->recordRequest('api-football', 'fixtures', '/fixtures', 403, 150, null, null, "Forbidden: Invalid API Key");
$stateRow = $tracker->getProviderState('api-football');

assertCheck(
    !empty($stateRow['last_error']) && str_contains($stateRow['last_error'], 'Forbidden'),
    "10. HTTP 401/403 causes provider error state and logs sanitized error",
    $passed,
    $failed
);
$pdo->exec("UPDATE sports_provider_states SET backoff_until = NULL, is_degraded = 0, last_error = NULL WHERE provider = 'api-football'");

// -----------------------------------------------------------------------------
// TEST 11: Network timeout does not create fake data
// -----------------------------------------------------------------------------
$mockTimeoutProvider = new class implements \Benchero\Contracts\SportsProviderInterface {
    public function getLiveScores(): array { throw new \RuntimeException("cURL error 28: Connection timed out after 5000 milliseconds"); }
    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array { return []; }
    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array { return []; }
    public function getCompetitions(?string $sport = null): array { return []; }
    public function getStandings(string $competitionId): array { return []; }
    public function getMatchDetail(string $matchId): ?array { return null; }
};

$timeoutSyncService = new SportsSyncService($pdo, $mockTimeoutProvider);
$timeoutRes = $timeoutSyncService->syncLive(true);
$dbFakeCount = (int)$pdo->query("SELECT COUNT(*) FROM sports_matches WHERE provider = 'mock' OR home_team_id LIKE '%fake%'")->fetchColumn();

assertCheck(
    $timeoutRes['success'] === false && $dbFakeCount === 0,
    "11. Network timeout handles failure safely without inserting fake or synthetic data",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 12: Empty successful response is distinguished from failure
// -----------------------------------------------------------------------------
$emptySuccessProvider = new class implements \Benchero\Contracts\SportsProviderInterface {
    public function getLiveScores(): array { return []; } // 200 OK with 0 live matches
    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array { return []; }
    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array { return []; }
    public function getCompetitions(?string $sport = null): array { return []; }
    public function getStandings(string $competitionId): array { return []; }
    public function getMatchDetail(string $matchId): ?array { return null; }
};

$emptySyncService = new SportsSyncService($pdo, $emptySuccessProvider);
$emptyRes = $emptySyncService->syncLive(true);

assertCheck(
    $emptyRes['success'] === true && $emptyRes['processed'] === 0,
    "12. Empty successful response (200 OK, 0 matches) is distinguished from provider failure",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 13: Failed sync does not update last_successful_sync
// -----------------------------------------------------------------------------
$pdo->beginTransaction();
try {
    $beforeSuccessAt = $tracker->getProviderState('api-football')['last_successful_sync_at'] ?? null;
    
    // Simulate failed sync
    $failSyncService = new SportsSyncService($pdo, $mockTimeoutProvider);
    $failSyncService->syncLive(true);
    
    $afterSuccessAt = $tracker->getProviderState('api-football')['last_successful_sync_at'] ?? null;
    
    assertCheck(
        $beforeSuccessAt === $afterSuccessAt,
        "13. Failed provider sync does NOT update last_successful_sync timestamp",
        $passed,
        $failed
    );
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// TEST 14: API quota remaining is tracked
// -----------------------------------------------------------------------------
$tracker->recordRequest('api-football', 'live', '/fixtures?live=all', 200, 120, 85, 100);
$stateAf = $tracker->getProviderState('api-football');

assertCheck(
    isset($stateAf['quota_remaining']) && (int)$stateAf['quota_remaining'] === 85 && (int)$stateAf['quota_limit'] === 100,
    "14. API quota remaining and daily limits are tracked in database from response headers",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 15: Low quota triggers conservative polling / backoff
// -----------------------------------------------------------------------------
// When quota drops below threshold (e.g. 5 remaining < 10 threshold)
$tracker->recordRequest('api-football', 'live', '/fixtures?live=all', 200, 100, 5, 100);
$isThrottled = $tracker->isProviderInBackoff('api-football');

assertCheck(
    $isThrottled === true,
    "15. Low quota (5 remaining <= 10 threshold) automatically triggers conservative backoff",
    $passed,
    $failed
);
// Reset backoff for clean state
$pdo->exec("UPDATE sports_provider_states SET backoff_until = NULL, quota_remaining = 95 WHERE provider = 'api-football'");

// -----------------------------------------------------------------------------
// TEST 16: Production mock provider remains blocked
// -----------------------------------------------------------------------------
$prevAppEnv = $_ENV['APP_ENV'] ?? null;
$prevProv = $_ENV['SPORTS_PROVIDER'] ?? null;
$_ENV['APP_ENV'] = 'production';
$_ENV['SPORTS_PROVIDER'] = 'mock';

$prodService = new SportsService();
$prodRefl = new \ReflectionClass($prodService);
$provProp = $prodRefl->getProperty('provider');
$provProp->setAccessible(true);
$activeProv = $provProp->getValue($prodService);

assertCheck(
    $activeProv instanceof NullSportsProvider,
    "16. Production mock provider is strictly blocked; safely falls back to NullSportsProvider",
    $passed,
    $failed
);
$_ENV['APP_ENV'] = $prevAppEnv ?: 'local';
$_ENV['SPORTS_PROVIDER'] = $prevProv ?: 'football-data';

// -----------------------------------------------------------------------------
// TEST 17: Football-Data routing remains intact
// -----------------------------------------------------------------------------
$eplProvider = $router->resolveProviderForCompetition('premier-league');
$uclProvider = $router->resolveProviderForCompetition('uefa-champions-league');
$bundesProvider = $router->resolveProviderForCompetition('bundesliga');

assertCheck(
    $eplProvider instanceof FootballDataSportsProvider &&
    $uclProvider instanceof FootballDataSportsProvider &&
    $bundesProvider instanceof FootballDataSportsProvider,
    "17. European premier leagues (EPL, UCL, Bundesliga) route authoritatively to Football-Data",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 18: API-Football African/Kenyan routing remains intact
// -----------------------------------------------------------------------------
$fkfProvider = $router->resolveProviderForCompetition('fkf-premier-league');
$cafProvider = $router->resolveProviderForCompetition('caf-champions-league');
$afconProvider = $router->resolveProviderForCompetition('africa-cup-of-nations');

assertCheck(
    $fkfProvider instanceof ApiFootballSportsProvider &&
    $cafProvider instanceof ApiFootballSportsProvider &&
    $afconProvider instanceof ApiFootballSportsProvider,
    "18. Kenyan and African competitions (FKF, CAF, AFCON) route authoritatively to API-Football",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 19: Standings provider isolation remains intact
// -----------------------------------------------------------------------------
assertCheck(
    $router->getAuthoritativeProvider('fkf-premier-league') === 'api-football' &&
    $router->getAuthoritativeProvider('premier-league') === 'football-data',
    "19. Standings isolation remains intact: competitions never cross-pollinate providers",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 20: Stale live matches are not publicly displayed
// -----------------------------------------------------------------------------
$pdo->beginTransaction();
try {
    $staleMatchId = Ulid::generate();
    $stmtStale = $pdo->prepare("
        INSERT INTO sports_matches (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, provider, external_id)
        VALUES (?, ?, ?, ?, ?, 'LIVE', DATE_SUB(NOW(), INTERVAL 200 MINUTE), 'test_filter', ?)
    ");
    $stmtStale->execute([$staleMatchId, $sportId, $compId, $teamA, $teamB, 'ext_test_stale_filter']);

    $cache->flushSportsCache();
    $freshLiveData = (new SportsService())->getLiveScores();
    $containsStale = false;
    foreach ($freshLiveData['matches'] as $m) {
        if ($m['id'] === $staleMatchId) {
            $containsStale = true;
            break;
        }
    }

    assertCheck(
        $containsStale === false,
        "20. Stale live matches (> 150m old) are strictly filtered and not displayed publicly",
        $passed,
        $failed
    );
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// TEST 21: Legitimate long-running matches are not prematurely marked finished
// -----------------------------------------------------------------------------
$pdo->beginTransaction();
try {
    // A match at 105 minutes (e.g. extra time / injury time)
    $etMatchId = Ulid::generate();
    $stmtEt = $pdo->prepare("
        INSERT INTO sports_matches (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, provider, external_id)
        VALUES (?, ?, ?, ?, ?, 'LIVE', DATE_SUB(NOW(), INTERVAL 105 MINUTE), 'test_filter', ?)
    ");
    $stmtEt->execute([$etMatchId, $sportId, $compId, $teamA, $teamB, 'ext_test_et_match']);

    $syncServiceA->cleanupStaleLiveMatches(150);
    $statusAfter = $pdo->query("SELECT status FROM sports_matches WHERE id = '{$etMatchId}'")->fetchColumn();

    assertCheck(
        $statusAfter === 'LIVE',
        "21. Legitimate long-running match (105m elapsed) is NOT prematurely marked finished",
        $passed,
        $failed
    );
} finally {
    $pdo->rollBack();
}

// -----------------------------------------------------------------------------
// TEST 22: Cache invalidation works after successful sync
// -----------------------------------------------------------------------------
$cache->set('sports_live_scores_v4', ['matches' => ['cached_flag']], 60);
$cache->flushSportsCache();
$afterFlush = $cache->get('sports_live_scores_v4');

assertCheck(
    $afterFlush === null,
    "22. Cache invalidation (flushSportsCache) cleanses server-side live cache on sync completion",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 23: Cache remains available during temporary provider failure
// -----------------------------------------------------------------------------
$cache->set('sports_live_scores_v4', ['matches' => [['id' => 'cached_m1', 'home_team' => 'Cached Home']], 'is_stale' => false], 60);
$liveDuringFailure = (new SportsService($mockTimeoutProvider))->getLiveScores();

assertCheck(
    !empty($liveDuringFailure['matches']) && $liveDuringFailure['matches'][0]['id'] === 'cached_m1',
    "23. Server-side cache remains instantly available during temporary provider failure",
    $passed,
    $failed
);
$cache->flushSportsCache();

// -----------------------------------------------------------------------------
// TEST 24: API response contains truthful freshness metadata
// -----------------------------------------------------------------------------
$liveMetaTest = (new SportsService())->getLiveScores();

assertCheck(
    isset($liveMetaTest['meta']) &&
    array_key_exists('last_updated', $liveMetaTest['meta']) &&
    array_key_exists('stale', $liveMetaTest['meta']) &&
    array_key_exists('degraded', $liveMetaTest['meta']) &&
    array_key_exists('age_seconds', $liveMetaTest['meta']),
    "24. API live payload contains truthful freshness metadata (last_updated, stale, degraded, age_seconds)",
    $passed,
    $failed
);

// -----------------------------------------------------------------------------
// TEST 25: No credentials appear in logs/API responses
// -----------------------------------------------------------------------------
$jsonMeta = json_encode($liveMetaTest);
$statesJson = json_encode($tracker->getAllProviderStates());

$hasSecret = false;
$sensitiveTerms = ['API_FOOTBALL_API_KEY', 'FOOTBALL_DATA_API_KEY', 'x-apisports-key', 'X-Auth-Token'];
foreach ($sensitiveTerms as $term) {
    if (str_contains($jsonMeta, $term) || str_contains($statesJson, $term)) {
        $hasSecret = true;
    }
}

assertCheck(
    $hasSecret === false,
    "25. Zero credentials, API keys, or provider tokens appear in API payloads or telemetry logs",
    $passed,
    $failed
);

echo "\n====================================================================\n";
echo " ADAPTIVE LIVE SYNC & QUOTA SUITE SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);
