<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Routing\Router;
use Benchero\Core\Http\Request;
use Benchero\Services\Sports\SportsService;
use Benchero\Services\Sports\NewsService;
use Benchero\Services\Sports\SportsSyncService;
use Benchero\Services\CacheService;
use Benchero\Core\Database\Database;

class SportsPlatformTestSuite
{
    private int $passed = 0;
    private int $failed = 0;
    private PDO $pdo;
    private Router $router;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->router = require __DIR__ . '/../config/routes.php';
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

    private function simulateGet(string $path, array $query = []): \Benchero\Core\Http\Response
    {
        $request = new Request($query, [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path], [], []);
        return $this->router->dispatch($request);
    }

    public function run(): bool
    {
        echo "==================================================\n";
        echo " BENCHERO SPORTS PLATFORM QA SUITE\n";
        echo "==================================================\n";

        // 1. SportsService & MockSportsProvider
        $mockProvider = new \Benchero\Services\Sports\Providers\MockSportsProvider();
        $mockLive = $mockProvider->getLiveScores();
        $this->assert(is_array($mockLive) && count($mockLive) > 0, "MockSportsProvider provides live matches data");
        $firstMatch = $mockLive[0] ?? [];
        $this->assert(isset($firstMatch['home_team'], $firstMatch['away_team'], $firstMatch['home_score'], $firstMatch['away_score'], $firstMatch['status']), "Live match has normalized fields");

        $sportsService = new SportsService();
        $liveData = $sportsService->getLiveScores();
        $this->assert(isset($liveData['matches']) && is_array($liveData['matches']), "SportsService returns live matches payload");

        $resultsData = $sportsService->getResults();
        $this->assert(isset($resultsData['results']) && is_array($resultsData['results']), "SportsService returns finished results payload");

        $fixturesData = $sportsService->getFixtures();
        $this->assert(isset($fixturesData['fixtures']) && is_array($fixturesData['fixtures']), "SportsService returns upcoming fixtures payload");

        $competitions = $sportsService->getCompetitions();
        $this->assert(is_array($competitions), "SportsService provides featured competitions list");

        $standings = $sportsService->getStandings('premier-league');
        $this->assert(count($standings) > 0, "SportsService returns standings for Premier League");

        // 2. NewsService & MockNewsProvider
        $newsService = new NewsService();
        $latestNews = $newsService->getLatestNews();
        $this->assert(count($latestNews) > 0, "NewsService provides news articles");

        $firstNews = $latestNews[0] ?? [];
        $this->assert(isset($firstNews['title'], $firstNews['summary'], $firstNews['source'], $firstNews['published_at']), "News item has complete metadata");

        $singleNews = $newsService->getNewsBySlug($firstNews['slug'] ?? '');
        $this->assert($singleNews !== null && $singleNews['title'] === $firstNews['title'], "NewsService retrieves single news article by slug");

        // 3. CacheService Integration
        $cache = new CacheService();
        $testKey = 'sports_test_key_' . time();
        $cache->set($testKey, ['test' => true], 60);
        $cachedVal = $cache->get($testKey);
        $this->assert($cachedVal !== null && isset($cachedVal['test']) && $cachedVal['test'] === true, "CacheService stores and retrieves sports data");
        $cache->forget($testKey);

        // 4. Background Sync Service & Database Persistence
        $syncService = new SportsSyncService($this->pdo);
        $liveSyncRes = $syncService->syncLive();
        $this->assert($liveSyncRes['success'] === true, "SportsSyncService syncLive executes successfully");

        $newsSyncRes = $syncService->syncNews();
        $this->assert($newsSyncRes['success'] === true, "SportsSyncService syncNews executes successfully");

        $stmtMatches = $this->pdo->query("SELECT COUNT(*) FROM sports_matches");
        $this->assert((int)$stmtMatches->fetchColumn() > 0, "sports_matches table populated");

        $stmtNews = $this->pdo->query("SELECT COUNT(*) FROM sports_news");
        $this->assert((int)$stmtNews->fetchColumn() > 0, "sports_news table populated");

        $stmtLogs = $this->pdo->query("SELECT COUNT(*) FROM sports_sync_logs WHERE status = 'success'");
        $this->assert((int)$stmtLogs->fetchColumn() > 0, "sports_sync_logs table populated");

        // 5. Route & View Rendering Simulation (Full HTTP 200 & Output Verification)
        echo "\n --- Testing All Page & API HTTP Routes ---\n";

        $resHome = $this->simulateGet('/sports');
        $this->assert($resHome->getStatusCode() === 200 && str_contains($resHome->getContent(), 'Benchero Sports'), "GET /sports renders 200 OK");

        $resLive = $this->simulateGet('/sports/live');
        $this->assert($resLive->getStatusCode() === 200 && str_contains($resLive->getContent(), 'Live Sports Match Center'), "GET /sports/live renders 200 OK");

        $resResults = $this->simulateGet('/sports/results', ['sport' => 'football']);
        $this->assert($resResults->getStatusCode() === 200 && str_contains($resResults->getContent(), 'Sports Match Results'), "GET /sports/results renders 200 OK with query parameters");

        $resFixtures = $this->simulateGet('/sports/fixtures', ['sport' => 'football']);
        $this->assert($resFixtures->getStatusCode() === 200 && str_contains($resFixtures->getContent(), 'Upcoming Match Fixtures'), "GET /sports/fixtures renders 200 OK with query parameters");

        $resNews = $this->simulateGet('/sports/news');
        $this->assert($resNews->getStatusCode() === 200 && str_contains($resNews->getContent(), 'Sports News & Headlines'), "GET /sports/news renders 200 OK");

        $resNewsDetail = $this->simulateGet('/sports/news/' . ($firstNews['slug'] ?? 'gor-mahia-extend-kpl-lead-derby-victory'));
        $this->assert($resNewsDetail->getStatusCode() === 200 || $resNewsDetail->getStatusCode() === 404, "GET /sports/news/{slug} renders valid response (200/404)");

        $resComps = $this->simulateGet('/sports/competitions');
        $this->assert($resComps->getStatusCode() === 200 && str_contains($resComps->getContent(), 'Sports Leagues & Competitions'), "GET /sports/competitions renders 200 OK");

        $resCompDetail = $this->simulateGet('/sports/c/premier-league');
        $this->assert($resCompDetail->getStatusCode() === 200 && str_contains($resCompDetail->getContent(), 'English Premier League'), "GET /sports/c/{slug} renders 200 OK");

        $resClubs = $this->simulateGet('/sports/clubs');
        $this->assert($resClubs->getStatusCode() === 200 && str_contains($resClubs->getContent(), 'Benchero Club Directory'), "GET /sports/clubs renders 200 OK");

        // APIs
        $resApiLive = $this->simulateGet('/api/sports/live');
        $this->assert($resApiLive->getStatusCode() === 200 && str_contains($resApiLive->getContent(), '"success":true'), "GET /api/sports/live returns 200 JSON");

        $resApiResults = $this->simulateGet('/api/sports/results');
        $this->assert($resApiResults->getStatusCode() === 200 && str_contains($resApiResults->getContent(), '"success":true'), "GET /api/sports/results returns 200 JSON");

        $resApiFixtures = $this->simulateGet('/api/sports/fixtures');
        $this->assert($resApiFixtures->getStatusCode() === 200 && str_contains($resApiFixtures->getContent(), '"success":true'), "GET /api/sports/fixtures returns 200 JSON");

        $resApiNews = $this->simulateGet('/api/sports/news');
        $this->assert($resApiNews->getStatusCode() === 200 && str_contains($resApiNews->getContent(), '"success":true'), "GET /api/sports/news returns 200 JSON");

        echo "==================================================\n";
        echo " SUMMARY: Passed {$this->passed} / Failed {$this->failed}\n";
        echo "==================================================\n";

        return $this->failed === 0;
    }
}

$suite = new SportsPlatformTestSuite();
$success = $suite->run();
exit($success ? 0 : 1);
