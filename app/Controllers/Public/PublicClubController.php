<?php

namespace Benchero\Controllers\Public;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Repositories\PlayerRepository;
use Benchero\Repositories\StaffRepository;
use Benchero\Services\ContentService;
use Benchero\Services\StandingsService;
use Benchero\Services\SubscriptionService;
use PDO;

class PublicClubController extends Controller
{
    public function show(Request $request, array $params): Response
    {
        $slug = $params['slug'] ?? '';
        $db = Database::getConnection();

        // 1. Fetch public organization details (including extended profile fields)
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

        // Parse social links JSON
        $socialLinks = [];
        if (!empty($org['social_links'])) {
            $decoded = json_decode($org['social_links'], true);
            if (is_array($decoded)) {
                $socialLinks = array_filter($decoded);
            }
        }

        // 3. Fetch active sports
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

        // 4. Fetch teams
        $teamsStmt = $db->prepare("
            SELECT id, name, slug, description, team_type
            FROM teams
            WHERE organization_id = ? AND deleted_at IS NULL
            ORDER BY display_order ASC, name ASC
        ");
        $teamsStmt->execute([$org['id']]);
        $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Fetch Public Squad (Players)
        $playerRepo = new PlayerRepository();
        $squad = $playerRepo->findPublicSquadByOrg($org['id']);

        // 6. Fetch Staff / Management
        $staffRepo = new StaffRepository($db);
        $staff = $staffRepo->getByOrganization($org['id']);

        // 7. Fetch Fixtures & Results
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
        $fixturesStmt->execute([$org['id']]);
        $allFixtures = $fixturesStmt->fetchAll(PDO::FETCH_ASSOC);

        $upcomingFixtures = array_filter($allFixtures, fn($f) => $f['status'] !== 'completed');
        $completedResults = array_reverse(array_filter($allFixtures, fn($f) => $f['status'] === 'completed'));

        // 8. Compute League Standings (if primary sport exists)
        $standings = [];
        if (!empty($primarySport['id'])) {
            $standingsService = new StandingsService();
            $standings = $standingsService->getStandings($org['id'], $primarySport['id']);
        }

        // 9. Fetch Content: News, Gallery, Sponsors
        $contentService = new ContentService();
        $news = $contentService->getNews($org['id'], 6);
        $gallery = $contentService->getGallery($org['id']);
        $sponsors = $contentService->getSponsors($org['id']);

        // 10. Sport-specific Terminology Map
        $sportTerminology = $this->getSportTerminology($primarySport['slug']);

        return $this->render('public/club', [
            'title' => htmlspecialchars($org['name']) . ' — Official Sports Website',
            'org' => $org,
            'socialLinks' => $socialLinks,
            'sports' => $sports,
            'primarySport' => $primarySport,
            'teams' => $teams,
            'squad' => $squad,
            'staff' => $staff,
            'upcomingFixtures' => array_slice($upcomingFixtures, 0, 6),
            'completedResults' => array_slice($completedResults, 0, 6),
            'standings' => $standings,
            'news' => $news,
            'gallery' => $gallery,
            'sponsors' => $sponsors,
            'sportTerminology' => $sportTerminology
        ]);
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
