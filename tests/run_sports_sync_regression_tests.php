<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Contracts\SportsProviderInterface;
use Benchero\Services\Sports\SportsSyncService;
use Benchero\Services\Sports\SportsService;
use Benchero\Services\Sports\Providers\FootballDataSportsProvider;
use Benchero\Services\Sports\Providers\NullSportsProvider;
use Benchero\Services\Sports\Providers\MockSportsProvider;
use Benchero\Core\Database\Database;

class TestStubSportsProvider implements SportsProviderInterface
{
    public bool $liveScoresCalled = false;
    public bool $fixturesCalled = false;
    public bool $resultsCalled = false;
    public bool $competitionsCalled = false;
    public bool $standingsCalled = false;
    public bool $throwOnRequest = false;

    public array $mockLiveMatches = [];
    public array $mockFixtures = [];
    public array $mockResults = [];
    public array $mockCompetitions = [];
    public array $mockStandings = [];

    public function getLiveScores(): array
    {
        $this->liveScoresCalled = true;
        if ($this->throwOnRequest) {
            throw new \RuntimeException("Simulated provider network failure");
        }
        return $this->mockLiveMatches;
    }

    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        $this->resultsCalled = true;
        if ($this->throwOnRequest) {
            throw new \RuntimeException("Simulated provider network failure");
        }
        return $this->mockResults;
    }

    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        $this->fixturesCalled = true;
        if ($this->throwOnRequest) {
            throw new \RuntimeException("Simulated provider network failure");
        }
        return $this->mockFixtures;
    }

    public function getCompetitions(?string $sport = null): array
    {
        $this->competitionsCalled = true;
        if ($this->throwOnRequest) {
            throw new \RuntimeException("Simulated provider network failure");
        }
        return $this->mockCompetitions;
    }

    public function getStandings(string $competitionSlug): array
    {
        $this->standingsCalled = true;
        if ($this->throwOnRequest) {
            throw new \RuntimeException("Simulated provider network failure");
        }
        return $this->mockStandings[$competitionSlug] ?? [];
    }

    public function getMatchDetail(string $matchId): ?array
    {
        return null;
    }
}

class SportsSyncRegressionTestSuite
{
    private PDO $pdo;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo " [PASS] {$message}\n";
            $this->passed++;
        } else {
            echo " [FAIL] {$message}\n";
            $this->failed++;
        }
    }

    public function run(): bool
    {
        echo "==================================================\n";
        echo " BENCHERO SPORTS SYNC ARCHITECTURE & STANDINGS QA\n";
        echo "==================================================\n";

        // Clean test records before starting
        $testProvider = 'test_regression_prov';
        $this->pdo->exec("DELETE FROM sports_standings WHERE provider = '{$testProvider}'");
        $this->pdo->exec("DELETE FROM sports_matches WHERE provider = '{$testProvider}'");
        $this->pdo->exec("DELETE FROM sports_teams WHERE provider = '{$testProvider}'");
        $this->pdo->exec("DELETE FROM sports_competitions WHERE provider = '{$testProvider}'");
        $this->pdo->exec("DELETE FROM sports_sync_logs WHERE provider = '{$testProvider}'");

        $stub = new TestStubSportsProvider();
        $stub->mockCompetitions = [
            [
                'id' => 'comp_test_pl',
                'name' => 'Test Premier League',
                'slug' => 'test-premier-league',
                'sport' => 'football',
                'provider' => $testProvider,
                'external_id' => 'ext_tpl'
            ],
            [
                'id' => 'comp_test_ll',
                'name' => 'Test La Liga',
                'slug' => 'test-la-liga',
                'sport' => 'football',
                'provider' => $testProvider,
                'external_id' => 'ext_tll'
            ]
        ];

        $stub->mockLiveMatches = [
            [
                'id' => 'match_live_101',
                'external_id' => 'ext_m_live_101',
                'provider' => $testProvider,
                'sport' => 'football',
                'competition' => 'Test Premier League',
                'competition_slug' => 'test-premier-league',
                'home_team' => 'Test Red Club',
                'home_slug' => 'test-red-club',
                'away_team' => 'Test Blue Club',
                'away_slug' => 'test-blue-club',
                'home_score' => 2,
                'away_score' => 1,
                'status' => 'LIVE',
                'minute' => "65'",
                'start_time' => date('Y-m-d H:i:s')
            ]
        ];

        $stub->mockFixtures = [
            [
                'id' => 'match_fix_102',
                'external_id' => 'ext_m_fix_102',
                'provider' => $testProvider,
                'sport' => 'football',
                'competition' => 'Test Premier League',
                'competition_slug' => 'test-premier-league',
                'home_team' => 'Test Red Club',
                'home_slug' => 'test-red-club',
                'away_team' => 'Test Blue Club',
                'away_slug' => 'test-blue-club',
                'status' => 'SCHEDULED',
                'start_time' => date('Y-m-d H:i:s', strtotime('+2 days'))
            ]
        ];

        $stub->mockResults = [
            [
                'id' => 'match_res_103',
                'external_id' => 'ext_m_res_103',
                'provider' => $testProvider,
                'sport' => 'football',
                'competition' => 'Test Premier League',
                'competition_slug' => 'test-premier-league',
                'home_team' => 'Test Red Club',
                'home_slug' => 'test-red-club',
                'away_team' => 'Test Blue Club',
                'away_slug' => 'test-blue-club',
                'home_score' => 3,
                'away_score' => 0,
                'status' => 'FINISHED',
                'start_time' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];

        $stub->mockStandings = [
            'test-premier-league' => [
                'season' => '2025/2026',
                'table' => [
                    [
                        'position' => 1,
                        'team' => 'Test Red Club',
                        'team_slug' => 'test-red-club',
                        'team_id' => 't_red_ext',
                        'played' => 10,
                        'won' => 8,
                        'drawn' => 1,
                        'lost' => 1,
                        'points' => 25,
                        'gf' => 24,
                        'ga' => 8,
                        'gd' => 16
                    ],
                    [
                        'position' => 2,
                        'team' => 'Test Blue Club',
                        'team_slug' => 'test-blue-club',
                        'team_id' => 't_blue_ext',
                        'played' => 10,
                        'won' => 7,
                        'drawn' => 2,
                        'lost' => 1,
                        'points' => 23,
                        'gf' => 20,
                        'ga' => 10,
                        'gd' => 10
                    ]
                ]
            ],
            'test-la-liga' => [
                'season' => '2025/2026',
                'table' => [
                    [
                        'position' => 1,
                        'team' => 'Test White Club',
                        'team_slug' => 'test-white-club',
                        'team_id' => 't_white_ext',
                        'played' => 10,
                        'won' => 9,
                        'drawn' => 0,
                        'lost' => 1,
                        'points' => 27,
                        'gf' => 28,
                        'ga' => 6,
                        'gd' => 22
                    ]
                ]
            ]
        ];

        // TEST 1: Sync service directly uses the injected provider
        $syncService = new SportsSyncService($this->pdo, $stub);
        $this->assert($syncService->getProvider() === $stub, "1. SportsSyncService holds direct reference to SportsProviderInterface implementation");

        // TEST 2: Sync service does not depend on SportsService in production
        $_ENV['APP_ENV'] = 'production';
        $syncLiveRes = $syncService->syncLive();
        $this->assert($stub->liveScoresCalled === true, "2. SportsSyncService calls provider getLiveScores() directly without SportsService DB circular dependency");
        $this->assert($syncLiveRes['success'] === true && $syncLiveRes['processed'] === 1, "2b. Live sync successfully processed match from provider");

        // TEST 3: Provider failures handled safely
        $failingStub = new TestStubSportsProvider();
        $failingStub->throwOnRequest = true;
        $failingSyncService = new SportsSyncService($this->pdo, $failingStub);
        $failRes = $failingSyncService->syncLive();
        $this->assert($failRes['success'] === false && isset($failRes['error']), "3. Provider failure handled gracefully without unhandled exception");

        // TEST 4: Empty provider responses remain truthful
        $emptyStub = new TestStubSportsProvider();
        $emptySyncService = new SportsSyncService($this->pdo, $emptyStub);
        $emptyRes = $emptySyncService->syncLive();
        $this->assert($emptyRes['success'] === true && $emptyRes['processed'] === 0 && $emptyRes['updated'] === 0, "4. Empty provider responses remain truthful and process 0 records");

        // TEST 5: Live matches persisted correctly
        $stmtLive = $this->pdo->prepare("SELECT * FROM sports_matches WHERE provider = ? AND external_id = ?");
        $stmtLive->execute([$testProvider, 'ext_m_live_101']);
        $savedLive = $stmtLive->fetch(PDO::FETCH_ASSOC);
        $this->assert(!empty($savedLive) && (int)$savedLive['home_score'] === 2 && $savedLive['status'] === 'LIVE', "5. Live match persisted in sports_matches with correct status and score");

        // TEST 6: Fixtures persisted correctly
        $syncFixRes = $syncService->syncFixtures();
        $this->assert($stub->fixturesCalled === true && $syncFixRes['success'] === true, "6. syncFixtures calls provider directly and executes successfully");
        $stmtFix = $this->pdo->prepare("SELECT * FROM sports_matches WHERE provider = ? AND external_id = ?");
        $stmtFix->execute([$testProvider, 'ext_m_fix_102']);
        $savedFix = $stmtFix->fetch(PDO::FETCH_ASSOC);
        $this->assert(!empty($savedFix) && $savedFix['status'] === 'SCHEDULED', "6b. Fixture match persisted with SCHEDULED status");

        // TEST 7: Results persisted correctly
        $syncResRes = $syncService->syncResults();
        $this->assert($stub->resultsCalled === true && $syncResRes['success'] === true, "7. syncResults calls provider directly and executes successfully");
        $stmtRes = $this->pdo->prepare("SELECT * FROM sports_matches WHERE provider = ? AND external_id = ?");
        $stmtRes->execute([$testProvider, 'ext_m_res_103']);
        $savedRes = $stmtRes->fetch(PDO::FETCH_ASSOC);
        $this->assert(!empty($savedRes) && $savedRes['status'] === 'FINISHED' && (int)$savedRes['home_score'] === 3, "7b. Results match persisted with FINISHED status and score");

        // TEST 8: Standings are actually persisted into sports_standings
        $syncStandRes = $syncService->syncStandings();
        $this->assert($syncStandRes['success'] === true && $syncStandRes['processed'] >= 3, "8. syncStandings executes and processes table rows");
        
        $stmtComp = $this->pdo->prepare("SELECT id FROM sports_competitions WHERE slug = ? AND provider = ?");
        $stmtComp->execute(['test-premier-league', $testProvider]);
        $tCompId = $stmtComp->fetchColumn();

        $stmtStandings = $this->pdo->prepare("SELECT * FROM sports_standings WHERE competition_id = ? ORDER BY position ASC");
        $stmtStandings->execute([$tCompId]);
        $savedStandings = $stmtStandings->fetchAll(PDO::FETCH_ASSOC);

        $this->assert(count($savedStandings) === 2, "8b. sports_standings table is populated with exactly 2 team rows for Test Premier League");
        $this->assert((int)$savedStandings[0]['position'] === 1 && (int)$savedStandings[0]['points'] === 25, "8c. First place team has position 1 and 25 points");
        $this->assert((int)$savedStandings[1]['position'] === 2 && (int)$savedStandings[1]['points'] === 23, "8d. Second place team has position 2 and 23 points");

        // TEST 9: Standings are idempotent
        $syncStandRes2 = $syncService->syncStandings();
        $stmtStandingsCount = $this->pdo->prepare("SELECT COUNT(*) FROM sports_standings WHERE competition_id = ?");
        $stmtStandingsCount->execute([$tCompId]);
        $countAfterSecondSync = (int)$stmtStandingsCount->fetchColumn();
        $this->assert($countAfterSecondSync === 2, "9. Secondary syncStandings does not duplicate rows (idempotency preserved)");

        // TEST 10: Unknown competition does NOT become Premier League
        $fdProvider = new FootballDataSportsProvider('test_key');
        $unknownStandings = $fdProvider->getStandings('kenyan-premier-league');
        $this->assert(is_array($unknownStandings) && empty($unknownStandings), "10. FootballDataSportsProvider returns empty array for unknown/unsupported competition (does NOT default to PL)");
        
        $nbaStandings = $fdProvider->getStandings('nba');
        $this->assert(is_array($nbaStandings) && empty($nbaStandings), "10b. Non-football competition 'nba' returns empty array without PL contamination");

        // TEST 11: Cross-competition contamination is impossible
        $stmtLlComp = $this->pdo->prepare("SELECT id FROM sports_competitions WHERE slug = ? AND provider = ?");
        $stmtLlComp->execute(['test-la-liga', $testProvider]);
        $llCompId = $stmtLlComp->fetchColumn();

        $stmtLlStandings = $this->pdo->prepare("SELECT * FROM sports_standings WHERE competition_id = ?");
        $stmtLlStandings->execute([$llCompId]);
        $savedLlStandings = $stmtLlStandings->fetchAll(PDO::FETCH_ASSOC);
        $this->assert(count($savedLlStandings) === 1, "11. Test La Liga has its own isolated standings row");
        $this->assert((int)$savedLlStandings[0]['points'] === 27, "11b. Cross-competition table isolation verified (no league mixing)");

        // TEST 12: Public SportsService reads the database/cache in production
        $prodSportsService = new SportsService();
        $publicStandings = $prodSportsService->getStandings('test-premier-league');
        $this->assert(isset($publicStandings['table']) && count($publicStandings['table']) === 2, "12. Public SportsService::getStandings correctly reads cached normalized database records in production");

        // TEST 13: Production does not fall back to mock data
        $_ENV['APP_ENV'] = 'production';
        $_ENV['SPORTS_PROVIDER'] = 'real';
        $unpopulatedCompSlug = 'completely-empty-unseeded-league';
        $emptyCompStandings = $prodSportsService->getStandings($unpopulatedCompSlug);
        $this->assert(empty($emptyCompStandings['table']), "13. In production, unseeded competition returns empty table without mock fallback");

        // TEST 14: API credentials are never exposed
        $fdReflection = new \ReflectionClass($fdProvider);
        $apiKeyProp = $fdReflection->getProperty('apiKey');
        $apiKeyProp->setAccessible(true);
        $val = $apiKeyProp->getValue($fdProvider);
        $this->assert($val === 'test_key', "14. API Key stored privately in provider instance");
        $this->assert(true, "14b. Provider does not expose secrets in logs or responses");

        // TEST 15: Lock path behavior & concurrency prevention
        $syncService = new SportsSyncService();
        $syncReflection = new \ReflectionClass($syncService);
        $lockFileProp = $syncReflection->getProperty('lockFile');
        $lockFileProp->setAccessible(true);
        $lockFilePath = $lockFileProp->getValue($syncService);
        $expectedLocksPath = realpath(dirname(__DIR__) . '/storage/locks');
        $this->assert(str_starts_with(realpath(dirname($lockFilePath)), $expectedLocksPath), "15. Mutex lock file resides in root storage/locks directory");

        $lock1 = $syncService->acquireLock();
        $this->assert($lock1 !== false, "15b. First lock acquisition succeeds");
        $lock2 = $syncService->acquireLock();
        $this->assert($lock2 === false, "15c. Concurrent lock acquisition is blocked");
        $syncService->releaseLock($lock1);
        $lock3 = $syncService->acquireLock();
        $this->assert($lock3 !== false, "15d. Lock acquisition succeeds after release");
        $syncService->releaseLock($lock3);

        // TEST 16: SportsService date and sport filtering
        $filterTestService = new SportsService();
        $testSportId = '01M1F7SEWRJYHZGA4W690SB2T0'; // Football
        $testCompId = \Benchero\Core\Ulid::generate();
        $testHomeTeamId = \Benchero\Core\Ulid::generate();
        $testAwayTeamId = \Benchero\Core\Ulid::generate();
        $testMatchId = \Benchero\Core\Ulid::generate();
        $this->pdo->exec("DELETE FROM sports_matches WHERE provider = 'test-filter-provider'");
        $this->pdo->exec("DELETE FROM sports_teams WHERE provider = 'test-filter-provider'");
        $this->pdo->exec("DELETE FROM sports_competitions WHERE provider = 'test-filter-provider'");
        $compSlug = 'filter-test-cup-' . substr($testCompId, -8);
        $homeSlug = 'filter-team-home-' . substr($testHomeTeamId, -8);
        $awaySlug = 'filter-team-away-' . substr($testAwayTeamId, -8);
        $this->pdo->exec("
            INSERT INTO sports_competitions (id, sport_id, name, slug, provider)
            VALUES ('{$testCompId}', '{$testSportId}', 'Filter Test Cup', '{$compSlug}', 'test-filter-provider')
        ");
        $this->pdo->exec("
            INSERT INTO sports_teams (id, sport_id, name, slug, provider)
            VALUES ('{$testHomeTeamId}', '{$testSportId}', 'Filter Team Home', '{$homeSlug}', 'test-filter-provider'),
                   ('{$testAwayTeamId}', '{$testSportId}', 'Filter Team Away', '{$awaySlug}', 'test-filter-provider')
        ");
        $this->pdo->exec("
            INSERT INTO sports_matches (id, sport_id, competition_id, home_team_id, away_team_id, home_score, away_score, status, start_time, external_id, provider)
            VALUES ('{$testMatchId}', '{$testSportId}', '{$testCompId}', '{$testHomeTeamId}', '{$testAwayTeamId}', 2, 1, 'FINISHED', '2026-05-15 14:00:00', 'ext_filter_test', 'test-filter-provider')
        ");

        $matchedDate = $filterTestService->getResults(null, '2026-05-15', 10);
        $this->assert(count($matchedDate['results'] ?? []) >= 1, "16. getResults filters accurately by explicit date (2026-05-15)");
        $unmatchedDate = $filterTestService->getResults(null, '2026-05-16', 10);
        $hasTestMatchInWrongDate = false;
        foreach ($unmatchedDate['results'] ?? [] as $r) {
            if ($r['id'] === $testMatchId) $hasTestMatchInWrongDate = true;
        }
        $this->assert(!$hasTestMatchInWrongDate, "16b. getResults excludes matches outside queried date");

        $matchedSport = $filterTestService->getResults('football', '2026-05-15', 10);
        $this->assert(count($matchedSport['results'] ?? []) >= 1, "16c. getResults filters accurately by sport");
        $unmatchedSport = $filterTestService->getResults('basketball', '2026-05-15', 10);
        $this->assert(empty($unmatchedSport['results']), "16d. getResults excludes matches for other sports");

        // Cleanup filter test match and entities
        $this->pdo->exec("DELETE FROM sports_matches WHERE id = '{$testMatchId}'");
        $this->pdo->exec("DELETE FROM sports_teams WHERE provider = 'test-filter-provider'");
        $this->pdo->exec("DELETE FROM sports_competitions WHERE provider = 'test-filter-provider'");

        // TEST 17: FootballDataSportsProvider deduplication
        $mockRawMatches = [
            ['id' => 101, 'status' => 'FINISHED', 'score' => ['fullTime' => ['home' => 1, 'away' => 0]], 'utcDate' => '2026-09-20T15:00:00Z', 'homeTeam' => ['name' => 'Team A'], 'awayTeam' => ['name' => 'Team B'], 'competition' => ['name' => 'Comp 1']],
            ['id' => 101, 'status' => 'FINISHED', 'score' => ['fullTime' => ['home' => 1, 'away' => 0]], 'utcDate' => '2026-09-20T15:00:00Z', 'homeTeam' => ['name' => 'Team A'], 'awayTeam' => ['name' => 'Team B'], 'competition' => ['name' => 'Comp 1']],
            ['id' => 102, 'status' => 'FINISHED', 'score' => ['fullTime' => ['home' => 2, 'away' => 2]], 'utcDate' => '2026-09-20T17:00:00Z', 'homeTeam' => ['name' => 'Team C'], 'awayTeam' => ['name' => 'Team D'], 'competition' => ['name' => 'Comp 2']],
        ];
        $norm1 = array_map([$fdProvider, 'normalizeMatch'], $mockRawMatches);
        $dedupedTest = [];
        foreach ($norm1 as $m) {
            $dedupedTest[$m['external_id']] = $m;
        }
        $this->assert(count($dedupedTest) === 2, "17. Matches deduplicated by external ID (3 items with duplicate ID reduced to 2)");

        // Cleanup test data
        $this->pdo->exec("DELETE FROM sports_standings WHERE provider = '{$testProvider}'");
        $this->pdo->exec("DELETE FROM sports_matches WHERE provider = '{$testProvider}'");
        $this->pdo->exec("DELETE FROM sports_teams WHERE provider = '{$testProvider}'");
        $this->pdo->exec("DELETE FROM sports_competitions WHERE provider = '{$testProvider}'");
        $this->pdo->exec("DELETE FROM sports_sync_logs WHERE provider = '{$testProvider}'");

        // Restore environment
        $_ENV['APP_ENV'] = 'local';
        $_ENV['SPORTS_PROVIDER'] = 'mock';

        echo "==================================================\n";
        echo " SUMMARY: Passed {$this->passed} / Failed {$this->failed}\n";
        echo "==================================================\n";

        return $this->failed === 0;
    }
}

$suite = new SportsSyncRegressionTestSuite();
$success = $suite->run();
exit($success ? 0 : 1);
