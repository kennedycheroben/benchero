<?php

namespace Benchero\Controllers\Public;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\Sports\SportsService;
use Benchero\Services\Sports\NewsService;
use Benchero\Core\Database\Database;
use PDO;

class SportsController extends Controller
{
    private SportsService $sportsService;
    private NewsService $newsService;

    public function __construct()
    {
        parent::__construct();
        $this->sportsService = new SportsService();
        $this->newsService = new NewsService();
    }

    public function index(Request $request, array $vars = []): Response
    {
        $liveData = $this->sportsService->getLiveScores();
        $resultsData = $this->sportsService->getResults(null, null, 6);
        $fixturesData = $this->sportsService->getFixtures(null, null, 6);
        $newsFeed = $this->newsService->getLatestNews(6);
        $competitions = $this->sportsService->getCompetitions();

        return $this->render('sports/index', [
            'title' => 'Benchero Sports — Live Scores, Results, Fixtures & Sports News',
            'description' => 'Follow live sports scores, match results, upcoming fixtures, league standings, and latest sports news on Benchero Sports.',
            'liveMatches' => $liveData['matches'] ?? [],
            'isStale' => $liveData['is_stale'] ?? false,
            'recentResults' => $resultsData['results'] ?? [],
            'upcomingFixtures' => $fixturesData['fixtures'] ?? [],
            'latestNews' => $newsFeed,
            'competitions' => $competitions
        ]);
    }

    public function live(Request $request, array $vars = []): Response
    {
        $liveData = $this->sportsService->getLiveScores();

        return $this->render('sports/live', [
            'title' => 'Live Sports Scores — Benchero Sports',
            'description' => 'Real-time live scores for Football, Basketball, Rugby and local Kenyan sports.',
            'liveMatches' => $liveData['matches'] ?? [],
            'updatedAt' => $liveData['updated_at'] ?? date('Y-m-d H:i:s'),
            'isStale' => $liveData['is_stale'] ?? false
        ]);
    }

    public function results(Request $request, array $vars = []): Response
    {
        $rawSport = $request->query('sport');
        $rawDate = $request->query('date');
        $sport = is_string($rawSport) && !empty($rawSport) ? $rawSport : null;
        $date = is_string($rawDate) && !empty($rawDate) ? $rawDate : null;

        $resultsData = $this->sportsService->getResults($sport, $date, 30);
        $competitions = $this->sportsService->getCompetitions();

        return $this->render('sports/results', [
            'title' => 'Sports Results & Recent Scores — Benchero Sports',
            'description' => 'Full archives of recent sports match results across football, basketball, rugby, and local leagues.',
            'results' => $resultsData['results'] ?? [],
            'activeSport' => $sport,
            'activeDate' => $date,
            'competitions' => $competitions
        ]);
    }

    public function fixtures(Request $request, array $vars = []): Response
    {
        $rawSport = $request->query('sport');
        $rawDate = $request->query('date');
        $sport = is_string($rawSport) && !empty($rawSport) ? $rawSport : null;
        $date = is_string($rawDate) && !empty($rawDate) ? $rawDate : null;

        $fixturesData = $this->sportsService->getFixtures($sport, $date, 30);
        $competitions = $this->sportsService->getCompetitions();

        return $this->render('sports/fixtures', [
            'title' => 'Upcoming Fixtures & Schedules — Benchero Sports',
            'description' => 'Check upcoming match fixtures, kick-off times, and venues for major sports leagues.',
            'fixtures' => $fixturesData['fixtures'] ?? [],
            'activeSport' => $sport,
            'activeDate' => $date,
            'competitions' => $competitions
        ]);
    }

    public function news(Request $request, array $vars = []): Response
    {
        $rawSport = $request->query('sport');
        $sport = is_string($rawSport) && !empty($rawSport) ? $rawSport : null;

        $newsFeed = $this->newsService->getLatestNews(20, $sport);

        return $this->render('sports/news', [
            'title' => 'Latest Sports News & Headlines — Benchero Sports',
            'description' => 'Stay updated with the latest headlines, match summaries, and sports news.',
            'newsArticles' => $newsFeed,
            'activeSport' => $sport
        ]);
    }

    public function newsDetail(Request $request, array $vars = []): Response
    {
        $slug = is_array($vars) ? ($vars['slug'] ?? '') : (string)$vars;
        $article = $this->newsService->getNewsBySlug($slug);

        if (!$article) {
            return $this->error('News article not found.', 404);
        }

        $relatedNews = $this->newsService->getLatestNews(4, $article['sport'] ?? null);

        return $this->render('sports/news_detail', [
            'title' => $article['title'] . ' — Benchero Sports',
            'description' => $article['summary'],
            'article' => $article,
            'relatedNews' => array_filter($relatedNews, fn($a) => $a['slug'] !== $slug)
        ]);
    }

    public function competitions(Request $request, array $vars = []): Response
    {
        $competitions = $this->sportsService->getCompetitions();

        return $this->render('sports/competitions', [
            'title' => 'Featured Leagues & Competitions — Benchero Sports',
            'description' => 'Browse premier leagues, cups, tournaments and local competitions.',
            'competitions' => $competitions
        ]);
    }

    public function competitionDetail(Request $request, array $vars = []): Response
    {
        $slug = is_array($vars) ? ($vars['slug'] ?? '') : (string)$vars;
        $competitions = $this->sportsService->getCompetitions();
        $competition = null;

        foreach ($competitions as $comp) {
            if ($comp['slug'] === $slug) {
                $competition = $comp;
                break;
            }
        }

        if (!$competition) {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM sports_competitions WHERE slug = ?");
            $stmt->execute([$slug]);
            $competition = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$competition) {
            return $this->error('Competition not found.', 404);
        }

        $standings = $this->sportsService->getStandings($slug);
        $results = $this->sportsService->getResults($competition['sport'] ?? null, null, 10, $competition['id'] ?? $slug);
        $fixtures = $this->sportsService->getFixtures($competition['sport'] ?? null, null, 10, $competition['id'] ?? $slug);

        return $this->render('sports/competition_detail', [
            'title' => $competition['name'] . ' Standings, Fixtures & Results — Benchero Sports',
            'description' => "Complete coverage of {$competition['name']} including standings, results, fixtures and news.",
            'competition' => $competition,
            'standings' => $standings,
            'recentResults' => $results['results'] ?? [],
            'upcomingFixtures' => $fixtures['fixtures'] ?? []
        ]);
    }

    public function clubs(Request $request, array $vars = []): Response
    {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT o.name, o.slug, o.logo_url, o.country, s.name as plan_name
            FROM organizations o
            LEFT JOIN subscriptions sub ON o.id = sub.organization_id AND sub.status = 'active'
            LEFT JOIN plans s ON sub.plan_id = s.id
            WHERE o.deleted_at IS NULL
            ORDER BY o.created_at DESC
            LIMIT 24
        ");
        $clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('sports/clubs', [
            'title' => 'Discover Sports Clubs — Benchero Sports',
            'description' => 'Explore sports clubs, academies, and teams powered by Benchero.',
            'clubs' => $clubs
        ]);
    }

    // --- JSON REST API ENDPOINTS ---

    public function apiLive(Request $request, array $vars = []): Response
    {
        $liveData = $this->sportsService->getLiveScores();
        return $this->json([
            'success' => true,
            'data' => $liveData['matches'] ?? [],
            'meta' => [
                'updated_at' => $liveData['updated_at'] ?? date('Y-m-d H:i:s'),
                'is_stale' => $liveData['is_stale'] ?? false,
                'source' => env('SPORTS_PROVIDER', 'mock')
            ]
        ]);
    }

    public function apiResults(Request $request, array $vars = []): Response
    {
        $rawSport = $request->query('sport');
        $rawDate = $request->query('date');
        $sport = is_string($rawSport) && !empty($rawSport) ? $rawSport : null;
        $date = is_string($rawDate) && !empty($rawDate) ? $rawDate : null;

        $resultsData = $this->sportsService->getResults($sport, $date);

        return $this->json([
            'success' => true,
            'data' => $resultsData['results'] ?? [],
            'meta' => [
                'updated_at' => $resultsData['updated_at'] ?? date('Y-m-d H:i:s'),
                'source' => env('SPORTS_PROVIDER', 'mock')
            ]
        ]);
    }

    public function apiFixtures(Request $request, array $vars = []): Response
    {
        $rawSport = $request->query('sport');
        $rawDate = $request->query('date');
        $sport = is_string($rawSport) && !empty($rawSport) ? $rawSport : null;
        $date = is_string($rawDate) && !empty($rawDate) ? $rawDate : null;

        $fixturesData = $this->sportsService->getFixtures($sport, $date);

        return $this->json([
            'success' => true,
            'data' => $fixturesData['fixtures'] ?? [],
            'meta' => [
                'updated_at' => $fixturesData['updated_at'] ?? date('Y-m-d H:i:s'),
                'source' => env('SPORTS_PROVIDER', 'mock')
            ]
        ]);
    }

    public function apiNews(Request $request, array $vars = []): Response
    {
        $rawSport = $request->query('sport');
        $sport = is_string($rawSport) && !empty($rawSport) ? $rawSport : null;

        $newsFeed = $this->newsService->getLatestNews(15, $sport);

        return $this->json([
            'success' => true,
            'data' => $newsFeed,
            'meta' => [
                'updated_at' => date('Y-m-d H:i:s'),
                'source' => env('NEWS_PROVIDER', 'mock')
            ]
        ]);
    }
}
