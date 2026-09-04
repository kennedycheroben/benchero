<?php

namespace Benchero\Controllers\Public;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Repositories\HistoryRepository;
use Benchero\Repositories\PageRepository;
use Benchero\Repositories\PlayerRepository;
use Benchero\Repositories\StaffRepository;
use Benchero\Services\ContentService;
use Benchero\Services\StandingsService;
use Benchero\Services\SubscriptionService;
use Benchero\Services\WebsiteService;
use PDO;

class PublicClubController extends Controller
{
    private function getPublicContext(string $slug, string $activeRoute): array|Response
    {
        $db = Database::getConnection();

        // 1. Fetch public organization details
        $stmt = $db->prepare("
            SELECT * FROM organizations
            WHERE slug = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$slug]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$org) {
            return $this->render('errors/404', ['title' => 'Club Not Found'], 404);
        }

        // 2. Subscription visibility check
        $subService = new SubscriptionService();
        if (!$subService->isPublicProfileVisible($org['id'])) {
            return $this->render('public/locked_profile', ['org' => $org]);
        }

        // 3. Website Settings & Navigation Visibility
        $websiteService = new WebsiteService($db);
        $settings = $websiteService->getSettings($org['id']);

        // 4. Parse Social Links
        $socialLinks = [];
        if (!empty($org['social_links'])) {
            $decoded = json_decode($org['social_links'], true);
            if (is_array($decoded)) {
                $socialLinks = array_filter($decoded);
            }
        }

        // 5. Fetch Active Sports
        $sportsStmt = $db->prepare("
            SELECT s.id, s.name, s.slug, s.description
            FROM sports s
            JOIN organization_sports os ON os.sport_id = s.id
            WHERE os.organization_id = ? AND os.is_active = 1 AND s.is_active = 1
            ORDER BY s.name ASC
        ");
        $sportsStmt->execute([$org['id']]);
        $sports = $sportsStmt->fetchAll(PDO::FETCH_ASSOC);

        $primarySport = $sports[0] ?? ['id' => '', 'name' => 'General Sports', 'slug' => 'sports'];
        $sportTerminology = $this->getSportTerminology($primarySport['slug']);

        // 6. Sponsors for Footer
        $contentService = new ContentService();
        $sponsors = $contentService->getSponsors($org['id']);

        return [
            'org' => $org,
            'settings' => $settings,
            'sports' => $sports,
            'primarySport' => $primarySport,
            'sportTerminology' => $sportTerminology,
            'socialLinks' => $socialLinks,
            'sponsors' => $sponsors,
            'activeRoute' => $activeRoute
        ];
    }

    public function show(Request $request, array $params): Response
    {
        return $this->index($request, $params);
    }

    public function index(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'home');
        if ($ctx instanceof Response) return $ctx;

        $db = Database::getConnection();
        $orgId = $ctx['org']['id'];

        $websiteService = new WebsiteService($db);
        $sections = $websiteService->getHomepageSections($orgId);

        // Fetch teams
        $teamsStmt = $db->prepare("SELECT * FROM teams WHERE organization_id = ? AND deleted_at IS NULL ORDER BY display_order ASC, name ASC");
        $teamsStmt->execute([$orgId]);
        $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch squad
        $playerRepo = new PlayerRepository();
        $squad = $playerRepo->findPublicSquadByOrg($orgId);

        // Fetch staff
        $staffRepo = new StaffRepository($db);
        $staff = $staffRepo->getByOrganization($orgId);

        // Fetch fixtures & results
        $fixturesStmt = $db->prepare("
            SELECT f.id, f.scheduled_at, f.venue_name, f.competition_type, f.competition_name, f.status, f.home_score, f.away_score, f.result_notes,
                   ht.name as home_team_name, at.name as away_team_name, s.name as sport_name
            FROM fixtures f
            JOIN teams ht ON f.home_team_id = ht.id
            JOIN teams at ON f.away_team_id = at.id
            JOIN sports s ON f.sport_id = s.id
            WHERE f.organization_id = ? AND f.deleted_at IS NULL
            ORDER BY f.scheduled_at ASC
        ");
        $fixturesStmt->execute([$orgId]);
        $allFixtures = $fixturesStmt->fetchAll(PDO::FETCH_ASSOC);

        $upcomingFixtures = array_values(array_filter($allFixtures, fn($f) => $f['status'] !== 'completed'));
        $completedResults = array_reverse(array_values(array_filter($allFixtures, fn($f) => $f['status'] === 'completed')));

        // Standings
        $standings = [];
        if (!empty($ctx['primarySport']['id'])) {
            $standingsService = new StandingsService();
            $standings = $standingsService->getStandings($orgId, $ctx['primarySport']['id']);
        }

        // News & Gallery
        $contentService = new ContentService();
        $news = $contentService->getNews($orgId, 3);
        $gallery = $contentService->getGallery($orgId);

        // History
        $historyRepo = new HistoryRepository($db);
        $history = $historyRepo->getByOrganization($orgId);

        return $this->render('public/club_website/home', array_merge($ctx, [
            'pageTitle' => htmlspecialchars($ctx['org']['name']) . ' — Official Website',
            'sections' => $sections,
            'teams' => $teams,
            'squad' => array_slice($squad, 0, 8),
            'staff' => array_slice($staff, 0, 6),
            'upcomingFixtures' => array_slice($upcomingFixtures, 0, 4),
            'completedResults' => array_slice($completedResults, 0, 4),
            'standings' => $standings,
            'news' => $news,
            'gallery' => array_slice($gallery, 0, 6),
            'history' => array_slice($history, 0, 4)
        ]));
    }

    public function about(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'about');
        if ($ctx instanceof Response) return $ctx;

        $db = Database::getConnection();
        $historyRepo = new HistoryRepository($db);
        $history = $historyRepo->getByOrganization($ctx['org']['id']);

        return $this->render('public/club_website/about', array_merge($ctx, [
            'pageTitle' => 'About Us — ' . htmlspecialchars($ctx['org']['name']),
            'history' => $history
        ]));
    }

    public function teams(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'teams');
        if ($ctx instanceof Response) return $ctx;

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM teams WHERE organization_id = ? AND deleted_at IS NULL ORDER BY display_order ASC, name ASC");
        $stmt->execute([$ctx['org']['id']]);
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('public/club_website/teams', array_merge($ctx, [
            'pageTitle' => 'Teams — ' . htmlspecialchars($ctx['org']['name']),
            'teams' => $teams
        ]));
    }

    public function teamDetail(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $teamSlug = $params['team_slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'teams');
        if ($ctx instanceof Response) return $ctx;

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM teams WHERE organization_id = ? AND (slug = ? OR id = ?) AND deleted_at IS NULL");
        $stmt->execute([$ctx['org']['id'], $teamSlug, $teamSlug]);
        $team = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$team) {
            return $this->render('errors/404', ['title' => 'Team Not Found'], 404);
        }

        // Roster / Players for team
        $playersStmt = $db->prepare("
            SELECT p.*, ra.jersey_number, ra.position, ra.is_captain, ra.is_vice_captain
            FROM roster_assignments ra
            JOIN players p ON ra.player_id = p.id
            WHERE ra.team_id = ? AND ra.deleted_at IS NULL AND p.deleted_at IS NULL
            ORDER BY ra.position ASC, ra.jersey_number ASC
        ");
        $playersStmt->execute([$team['id']]);
        $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Staff assigned to team
        $staffStmt = $db->prepare("SELECT * FROM staff WHERE team_id = ? AND organization_id = ?");
        $staffStmt->execute([$team['id'], $ctx['org']['id']]);
        $staff = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('public/club_website/team_detail', array_merge($ctx, [
            'pageTitle' => htmlspecialchars($team['name']) . ' — ' . htmlspecialchars($ctx['org']['name']),
            'team' => $team,
            'players' => $players,
            'staff' => $staff
        ]));
    }

    public function players(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'players');
        if ($ctx instanceof Response) return $ctx;

        $playerRepo = new PlayerRepository();
        $squad = $playerRepo->findPublicSquadByOrg($ctx['org']['id']);

        return $this->render('public/club_website/players', array_merge($ctx, [
            'pageTitle' => 'Player Roster — ' . htmlspecialchars($ctx['org']['name']),
            'squad' => $squad
        ]));
    }

    public function playerDetail(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $playerSlug = $params['player_slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'players');
        if ($ctx instanceof Response) return $ctx;

        $playerRepo = new PlayerRepository();
        $player = $playerRepo->findPublicPlayerBySlugOrId($ctx['org']['id'], $playerSlug);

        if (!$player) {
            return $this->render('errors/404', ['title' => 'Player Profile Not Found'], 404);
        }

        return $this->render('public/club_website/player_detail', array_merge($ctx, [
            'pageTitle' => htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) . ' — Player Profile',
            'player' => $player
        ]));
    }

    public function staff(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'staff');
        if ($ctx instanceof Response) return $ctx;

        $db = Database::getConnection();
        $staffRepo = new StaffRepository($db);
        $staff = $staffRepo->getByOrganization($ctx['org']['id']);

        return $this->render('public/club_website/staff', array_merge($ctx, [
            'pageTitle' => 'Staff & Management — ' . htmlspecialchars($ctx['org']['name']),
            'staff' => $staff
        ]));
    }

    public function fixtures(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'fixtures');
        if ($ctx instanceof Response) return $ctx;

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT f.*, ht.name as home_team_name, at.name as away_team_name, s.name as sport_name
            FROM fixtures f
            JOIN teams ht ON f.home_team_id = ht.id
            JOIN teams at ON f.away_team_id = at.id
            JOIN sports s ON f.sport_id = s.id
            WHERE f.organization_id = ? AND f.deleted_at IS NULL AND f.status != 'completed'
            ORDER BY f.scheduled_at ASC
        ");
        $stmt->execute([$ctx['org']['id']]);
        $fixtures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('public/club_website/fixtures', array_merge($ctx, [
            'pageTitle' => 'Match Schedule & Fixtures — ' . htmlspecialchars($ctx['org']['name']),
            'fixtures' => $fixtures
        ]));
    }

    public function results(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'results');
        if ($ctx instanceof Response) return $ctx;

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT f.*, ht.name as home_team_name, at.name as away_team_name, s.name as sport_name
            FROM fixtures f
            JOIN teams ht ON f.home_team_id = ht.id
            JOIN teams at ON f.away_team_id = at.id
            JOIN sports s ON f.sport_id = s.id
            WHERE f.organization_id = ? AND f.deleted_at IS NULL AND f.status = 'completed'
            ORDER BY f.scheduled_at DESC
        ");
        $stmt->execute([$ctx['org']['id']]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('public/club_website/results', array_merge($ctx, [
            'pageTitle' => 'Match Results — ' . htmlspecialchars($ctx['org']['name']),
            'results' => $results
        ]));
    }

    public function standings(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'standings');
        if ($ctx instanceof Response) return $ctx;

        $standings = [];
        if (!empty($ctx['primarySport']['id'])) {
            $standingsService = new StandingsService();
            $standings = $standingsService->getStandings($ctx['org']['id'], $ctx['primarySport']['id']);
        }

        return $this->render('public/club_website/standings', array_merge($ctx, [
            'pageTitle' => 'League Standings — ' . htmlspecialchars($ctx['org']['name']),
            'standings' => $standings
        ]));
    }

    public function news(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'news');
        if ($ctx instanceof Response) return $ctx;

        $contentService = new ContentService();
        $news = $contentService->getNews($ctx['org']['id'], 20);

        return $this->render('public/club_website/news', array_merge($ctx, [
            'pageTitle' => 'Latest News — ' . htmlspecialchars($ctx['org']['name']),
            'news' => $news
        ]));
    }

    public function newsDetail(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $articleSlug = $params['article_slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'news');
        if ($ctx instanceof Response) return $ctx;

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM news_articles WHERE organization_id = ? AND (slug = ? OR id = ?)");
        $stmt->execute([$ctx['org']['id'], $articleSlug, $articleSlug]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$article) {
            return $this->render('errors/404', ['title' => 'Article Not Found'], 404);
        }

        // Related articles
        $relStmt = $db->prepare("SELECT * FROM news_articles WHERE organization_id = ? AND id != ? ORDER BY published_at DESC LIMIT 3");
        $relStmt->execute([$ctx['org']['id'], $article['id']]);
        $related = $relStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('public/club_website/news_detail', array_merge($ctx, [
            'pageTitle' => htmlspecialchars($article['title']) . ' — News',
            'article' => $article,
            'relatedNews' => $related
        ]));
    }

    public function gallery(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'gallery');
        if ($ctx instanceof Response) return $ctx;

        $contentService = new ContentService();
        $gallery = $contentService->getGallery($ctx['org']['id']);

        return $this->render('public/club_website/gallery', array_merge($ctx, [
            'pageTitle' => 'Photo Gallery — ' . htmlspecialchars($ctx['org']['name']),
            'gallery' => $gallery
        ]));
    }

    public function history(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'history');
        if ($ctx instanceof Response) return $ctx;

        $db = Database::getConnection();
        $historyRepo = new HistoryRepository($db);
        $history = $historyRepo->getByOrganization($ctx['org']['id']);

        return $this->render('public/club_website/history', array_merge($ctx, [
            'pageTitle' => 'Club History & Milestones — ' . htmlspecialchars($ctx['org']['name']),
            'history' => $history
        ]));
    }

    public function sponsors(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'sponsors');
        if ($ctx instanceof Response) return $ctx;

        $contentService = new ContentService();
        $sponsors = $contentService->getSponsors($ctx['org']['id']);

        return $this->render('public/club_website/sponsors', array_merge($ctx, [
            'pageTitle' => 'Official Sponsors & Partners — ' . htmlspecialchars($ctx['org']['name']),
            'sponsors' => $sponsors
        ]));
    }

    public function contact(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'contact');
        if ($ctx instanceof Response) return $ctx;

        return $this->render('public/club_website/contact', array_merge($ctx, [
            'pageTitle' => 'Contact Us — ' . htmlspecialchars($ctx['org']['name']),
            'success' => $request->get('sent') === '1'
        ]));
    }

    public function contactSubmit(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $ctx = $this->getPublicContext($slug, 'contact');
        if ($ctx instanceof Response) return $ctx;

        // Basic contact form processing & sanitization
        $name = trim(filter_var($request->post('name', ''), FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim(filter_var($request->post('email', ''), FILTER_SANITIZE_EMAIL));
        $subject = trim(filter_var($request->post('subject', ''), FILTER_SANITIZE_SPECIAL_CHARS));
        $message = trim(filter_var($request->post('message', ''), FILTER_SANITIZE_SPECIAL_CHARS));

        if (empty($name) || empty($email) || empty($message)) {
            return $this->render('public/club_website/contact', array_merge($ctx, [
                'pageTitle' => 'Contact Us — ' . htmlspecialchars($ctx['org']['name']),
                'error' => 'Please complete all required fields.',
                'formData' => ['name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message]
            ]));
        }

        // Redirect back with success status
        return $this->redirect("/club/{$slug}/contact?sent=1");
    }

    private function getSportTerminology(string $sportSlug): array
    {
        switch ($sportSlug) {
            case 'basketball':
                return [
                    'score_label' => 'Points',
                    'positions' => ['Guard', 'Point Guard', 'Shooting Guard', 'Forward', 'Small Forward', 'Power Forward', 'Center'],
                    'stats' => ['Points', 'Rebounds', 'Assists', 'Steals']
                ];
            case 'volleyball':
                return [
                    'score_label' => 'Sets',
                    'positions' => ['Setter', 'Outside Hitter', 'Opposite Hitter', 'Middle Blocker', 'Libero', 'Defensive Specialist'],
                    'stats' => ['Sets Won', 'Points', 'Aces', 'Blocks']
                ];
            case 'rugby':
                return [
                    'score_label' => 'Points',
                    'positions' => ['Prop', 'Hooker', 'Lock', 'Flanker', 'Number 8', 'Scrum-half', 'Fly-half', 'Centre', 'Wing', 'Full-back'],
                    'stats' => ['Tries', 'Conversions', 'Penalties', 'Tackles']
                ];
            case 'football':
            default:
                return [
                    'score_label' => 'Goals',
                    'positions' => ['Goalkeeper', 'Defender', 'Center Back', 'Full Back', 'Midfielder', 'Central Midfielder', 'Winger', 'Forward', 'Striker'],
                    'stats' => ['Matches', 'Goals', 'Assists', 'Cards']
                ];
        }
    }
}
