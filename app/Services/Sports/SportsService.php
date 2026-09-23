<?php

namespace Benchero\Services\Sports;

use Benchero\Contracts\SportsProviderInterface;
use Benchero\Services\Sports\Providers\MockSportsProvider;
use Benchero\Services\Sports\Providers\FootballDataSportsProvider;
use Benchero\Services\Sports\Providers\NullSportsProvider;
use Benchero\Services\CacheService;
use Benchero\Core\Database\Database;
use PDO;

class SportsService
{
    private SportsProviderInterface $provider;
    private CacheService $cache;
    private string $providerType;
    private bool $isProduction;
    private PDO $pdo;

    public function __construct(?SportsProviderInterface $provider = null, ?CacheService $cache = null)
    {
        $this->pdo = Database::getConnection();
        $this->cache = $cache ?? new CacheService();
        $this->isProduction = (env('APP_ENV') === 'production');
        $this->providerType = strtolower((string)($_ENV['SPORTS_PROVIDER'] ?? env('SPORTS_PROVIDER', 'mock')));
        $this->provider = $provider ?? $this->resolveProvider();
    }

    private function resolveProvider(): SportsProviderInterface
    {
        if ($this->isProduction && $this->providerType === 'mock') {
            error_log("SPORTS_PROVIDER=mock configured in production environment. Falling back to NullSportsProvider.");
            return new NullSportsProvider();
        }

        return match ($this->providerType) {
            'api-football', 'apifootball' => new \Benchero\Services\Sports\Providers\ApiFootballSportsProvider(),
            'real', 'football-data', 'football' => new FootballDataSportsProvider(),
            'mock' => $this->isProduction ? new NullSportsProvider() : new MockSportsProvider(),
            default => $this->isProduction ? new NullSportsProvider() : new MockSportsProvider()
        };
    }

    public function getLiveScores(): array
    {
        $cacheKey = 'sports_live_scores_v3';
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Query Benchero's local normalized database cache
        try {
            $stmt = $this->pdo->query("
                SELECT m.*, 
                       c.name as competition_name, c.slug as competition_slug,
                       ht.name as home_team_name, ht.slug as home_slug,
                       at.name as away_team_name, at.slug as away_slug
                FROM sports_matches m
                LEFT JOIN sports_competitions c ON m.competition_id = c.id
                LEFT JOIN sports_teams ht ON m.home_team_id = ht.id
                LEFT JOIN sports_teams at ON m.away_team_id = at.id
                WHERE m.status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED') AND m.provider != 'mock'
                ORDER BY m.start_time DESC LIMIT 20
            ");
            $dbMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $lastSync = $this->pdo->query("SELECT created_at FROM sports_sync_logs WHERE status = 'success' AND operation = 'sync-live' ORDER BY id DESC LIMIT 1")->fetchColumn();
            $isStale = false;
            if (!$lastSync || (abs(time() - strtotime($lastSync)) > 900)) {
                $isStale = true;
            }

            $matches = array_map(function($r) {
                return [
                    'id' => $r['id'],
                    'external_id' => $r['external_id'],
                    'provider' => $r['provider'],
                    'competition' => $r['competition_name'] ?? 'League',
                    'home_team' => $r['home_team_name'] ?? 'Home Team',
                    'away_team' => $r['away_team_name'] ?? 'Away Team',
                    'home_score' => (int)($r['home_score'] ?? 0),
                    'away_score' => (int)($r['away_score'] ?? 0),
                    'status' => $r['status'],
                    'minute' => $r['minute'] ?? null,
                    'start_time' => $r['start_time']
                ];
            }, $dbMatches);

            // In non-production, fall back to direct provider call if DB is empty or stale
            if ((empty($matches) || $isStale) && !$this->isProduction) {
                try {
                    $data = $this->provider->getLiveScores();
                    $res = [
                        'matches' => $data ?? [],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'is_stale' => false
                    ];
                    $this->cache->set($cacheKey, $res, 30);
                    return $res;
                } catch (\Throwable $provEx) {
                    error_log("SportsService getLiveScores provider fallback error: " . $provEx->getMessage());
                }
            }

            $result = [
                'matches' => $matches,
                'updated_at' => $lastSync ?: date('Y-m-d H:i:s'),
                'is_stale' => $isStale
            ];
            $this->cache->set($cacheKey, $result, 30);
            return $result;
        } catch (\Throwable $e) {
            error_log("SportsService getLiveScores error: " . $e->getMessage());

            if (!$this->isProduction) {
                try {
                    $data = $this->provider->getLiveScores();
                    $res = [
                        'matches' => $data ?? [],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'is_stale' => false
                    ];
                    $this->cache->set($cacheKey, $res, 30);
                    return $res;
                } catch (\Throwable $provEx) {
                    error_log("SportsService getLiveScores fallback error: " . $provEx->getMessage());
                }
            }

            return [
                'matches' => [],
                'updated_at' => date('Y-m-d H:i:s'),
                'is_stale' => true,
                'notice' => 'Live scores temporarily unavailable.'
            ];
        }
    }

    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20, ?string $competitionId = null): array
    {
        $cacheKey = "sports_results_{$sport}_{$date}_{$limit}_{$competitionId}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Query local normalized database table FIRST
        try {
            $sql = "
                SELECT m.*, 
                       c.name as competition_name, c.slug as competition_slug,
                       ht.name as home_team_name, ht.slug as home_slug,
                       at.name as away_team_name, at.slug as away_slug
                FROM sports_matches m
                LEFT JOIN sports_competitions c ON m.competition_id = c.id
                LEFT JOIN sports_teams ht ON m.home_team_id = ht.id
                LEFT JOIN sports_teams at ON m.away_team_id = at.id
                LEFT JOIN sports s ON m.sport_id = s.id
                WHERE m.status IN ('FINISHED', 'FT', 'AET', 'PEN') AND m.provider != 'mock'
            ";
            $params = [];
            if ($competitionId !== null) {
                $sql .= " AND (m.competition_id = ? OR c.slug = ?)";
                $params[] = $competitionId;
                $params[] = $competitionId;
            }
            if ($sport !== null) {
                $sql .= " AND (s.slug = ? OR s.name = ?)";
                $params[] = $sport;
                $params[] = $sport;
            }
            if ($date !== null) {
                $sql .= " AND DATE(m.start_time) = ?";
                $params[] = $date;
            }
            $sql .= " ORDER BY m.start_time DESC LIMIT " . (int)$limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($dbRows)) {
                $results = array_map(function($r) {
                    return [
                        'id' => $r['id'],
                        'external_id' => $r['external_id'],
                        'provider' => $r['provider'],
                        'competition' => $r['competition_name'] ?? 'League',
                        'competition_slug' => $r['competition_slug'] ?? 'league',
                        'home_team' => $r['home_team_name'] ?? 'Home Team',
                        'away_team' => $r['away_team_name'] ?? 'Away Team',
                        'home_score' => (int)($r['home_score'] ?? 0),
                        'away_score' => (int)($r['away_score'] ?? 0),
                        'status' => $r['status'],
                        'start_time' => $r['start_time']
                    ];
                }, $dbRows);

                $res = ['results' => $results, 'updated_at' => date('Y-m-d H:i:s')];
                $this->cache->set($cacheKey, $res, 600);
                return $res;
            }
        } catch (\Throwable $dbEx) {
            error_log("SportsService getResults DB error: " . $dbEx->getMessage());
        }

        if ($this->isProduction) {
            return ['results' => [], 'updated_at' => date('Y-m-d H:i:s')];
        }

        // Fallback in dev/testing mode
        try {
            $data = $this->provider->getResults($sport, $date, $limit);
            $res = ['results' => $data ?? [], 'updated_at' => date('Y-m-d H:i:s')];
            $this->cache->set($cacheKey, $res, 300);
            return $res;
        } catch (\Throwable $e) {
            error_log("SportsService getResults provider error: " . $e->getMessage());
            return ['results' => [], 'updated_at' => date('Y-m-d H:i:s')];
        }
    }

    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20, ?string $competitionId = null): array
    {
        $cacheKey = "sports_fixtures_{$sport}_{$date}_{$limit}_{$competitionId}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Query local normalized database table FIRST
        try {
            $sql = "
                SELECT m.*, 
                       c.name as competition_name, c.slug as competition_slug,
                       ht.name as home_team_name, ht.slug as home_slug,
                       at.name as away_team_name, at.slug as away_slug
                FROM sports_matches m
                LEFT JOIN sports_competitions c ON m.competition_id = c.id
                LEFT JOIN sports_teams ht ON m.home_team_id = ht.id
                LEFT JOIN sports_teams at ON m.away_team_id = at.id
                LEFT JOIN sports s ON m.sport_id = s.id
                WHERE m.status IN ('NS', 'SCHEDULED', 'TIMED', 'POSTPONED') AND m.provider != 'mock'
            ";
            $params = [];
            if ($competitionId !== null) {
                $sql .= " AND (m.competition_id = ? OR c.slug = ?)";
                $params[] = $competitionId;
                $params[] = $competitionId;
            }
            if ($sport !== null) {
                $sql .= " AND (s.slug = ? OR s.name = ?)";
                $params[] = $sport;
                $params[] = $sport;
            }
            if ($date !== null) {
                $sql .= " AND DATE(m.start_time) = ?";
                $params[] = $date;
            } else {
                $sql .= " AND m.start_time >= NOW()";
            }
            $sql .= " ORDER BY m.start_time ASC LIMIT " . (int)$limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($dbRows)) {
                $fixtures = array_map(function($r) {
                    return [
                        'id' => $r['id'],
                        'external_id' => $r['external_id'],
                        'provider' => $r['provider'],
                        'competition' => $r['competition_name'] ?? 'League',
                        'competition_slug' => $r['competition_slug'] ?? 'league',
                        'home_team' => $r['home_team_name'] ?? 'Home Team',
                        'away_team' => $r['away_team_name'] ?? 'Away Team',
                        'status' => $r['status'],
                        'start_time' => $r['start_time']
                    ];
                }, $dbRows);

                $res = ['fixtures' => $fixtures, 'updated_at' => date('Y-m-d H:i:s')];
                $this->cache->set($cacheKey, $res, 600);
                return $res;
            }
        } catch (\Throwable $dbEx) {
            error_log("SportsService getFixtures DB error: " . $dbEx->getMessage());
        }

        if ($this->isProduction) {
            return ['fixtures' => [], 'updated_at' => date('Y-m-d H:i:s')];
        }

        // Fallback in dev/testing mode
        try {
            $data = $this->provider->getFixtures($sport, $date, $limit);
            $res = ['fixtures' => $data ?? [], 'updated_at' => date('Y-m-d H:i:s')];
            $this->cache->set($cacheKey, $res, 300);
            return $res;
        } catch (\Throwable $e) {
            error_log("SportsService getFixtures provider error: " . $e->getMessage());
            return ['fixtures' => [], 'updated_at' => date('Y-m-d H:i:s')];
        }
    }

    public function getCompetitions(?string $sport = null): array
    {
        $cacheKey = "sports_competitions_{$sport}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Query local database FIRST
        try {
            $stmt = $this->pdo->query("
                SELECT c.*, s.slug as sport, s.name as sport_name
                FROM sports_competitions c
                LEFT JOIN sports s ON c.sport_id = s.id
                WHERE c.provider != 'mock'
                ORDER BY c.name ASC
            ");
            $comps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($comps)) {
                $this->cache->set($cacheKey, $comps, 3600);
                return $comps;
            }
        } catch (\Throwable $dbEx) {
            error_log("SportsService getCompetitions DB error: " . $dbEx->getMessage());
        }

        if ($this->isProduction) {
            return [];
        }

        try {
            $data = $this->provider->getCompetitions($sport);
            $this->cache->set($cacheKey, $data, 3600);
            return $data;
        } catch (\Throwable $e) {
            error_log("SportsService getCompetitions provider error: " . $e->getMessage());
            return $cached ?? [];
        }
    }

    /**
     * Get deterministic curated/featured competitions for landing page discovery.
     * Capped at 6-8 items, prioritized by recognized domestic & international leagues.
     */
    public function getFeaturedCompetitions(int $limit = 8, ?string $sport = null): array
    {
        $cacheKey = "sports_featured_competitions_{$sport}_{$limit}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Deterministic priority ordering: Kenyan domestic top league, premier international tournaments, major European domestic leagues
            $priorityOrder = [
                'fkf-premier-league',
                'kenyan-premier-league',
                'premier-league',
                'uefa-champions-league',
                'la-liga',
                'serie-a',
                'bundesliga',
                'caf-champions-league',
                'ligue-1',
                'championship',
                'eredivisie',
                'primeira-liga',
                'copa-libertadores',
                'campeonato-brasileiro-s-rie-a'
            ];

            $allComps = $this->getCompetitions($sport);
            if (!empty($allComps)) {
                usort($allComps, function ($a, $b) use ($priorityOrder) {
                    $slugA = $a['slug'] ?? '';
                    $slugB = $b['slug'] ?? '';

                    $posA = array_search($slugA, $priorityOrder, true);
                    $posB = array_search($slugB, $priorityOrder, true);

                    $rankA = ($posA !== false) ? $posA : 999;
                    $rankB = ($posB !== false) ? $posB : 999;

                    if ($rankA !== $rankB) {
                        return $rankA <=> $rankB;
                    }

                    $featA = !empty($a['is_featured']) ? 0 : 1;
                    $featB = !empty($b['is_featured']) ? 0 : 1;
                    if ($featA !== $featB) {
                        return $featA <=> $featB;
                    }

                    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
                });

                $featured = array_slice($allComps, 0, $limit);
                $this->cache->set($cacheKey, $featured, 3600);
                return $featured;
            }
        } catch (\Throwable $e) {
            error_log("SportsService getFeaturedCompetitions error: " . $e->getMessage());
        }

        return [];
    }

    public function getStandings(string $competitionSlug): array
    {
        $cacheKey = "sports_standings_{$competitionSlug}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Query local database FIRST
        try {
            $stmtComp = $this->pdo->prepare("SELECT * FROM sports_competitions WHERE slug = ? AND provider != 'mock'");
            $stmtComp->execute([$competitionSlug]);
            $comp = $stmtComp->fetch(PDO::FETCH_ASSOC);

            if ($comp) {
                $stmtSt = $this->pdo->prepare("
                    SELECT s.*, t.name as team_name, t.slug as team_slug, t.logo as team_logo
                    FROM sports_standings s
                    LEFT JOIN sports_teams t ON s.team_id = t.id
                    WHERE s.competition_id = ?
                    ORDER BY s.position ASC
                ");
                $stmtSt->execute([$comp['id']]);
                $table = $stmtSt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($table)) {
                    $res = [
                        'competition' => [
                            'id' => $comp['id'],
                            'name' => $comp['name'],
                            'slug' => $comp['slug']
                        ],
                        'season' => date('Y') . '/' . (date('Y') + 1),
                        'table' => $table
                    ];
                    $this->cache->set($cacheKey, $res, 1800);
                    return $res;
                }
            }
        } catch (\Throwable $dbEx) {
            error_log("SportsService getStandings DB error: " . $dbEx->getMessage());
        }

        if ($this->isProduction) {
            return [
                'competition' => ['name' => ucfirst(str_replace('-', ' ', $competitionSlug)), 'slug' => $competitionSlug],
                'season' => date('Y') . '/' . (date('Y') + 1),
                'table' => []
            ];
        }

        try {
            $data = $this->provider->getStandings($competitionSlug);
            $res = [
                'competition' => ['name' => ucfirst(str_replace('-', ' ', $competitionSlug)), 'slug' => $competitionSlug],
                'season' => date('Y') . '/' . (date('Y') + 1),
                'table' => $data
            ];
            $this->cache->set($cacheKey, $res, 1800);
            return $res;
        } catch (\Throwable $e) {
            error_log("SportsService getStandings provider error: " . $e->getMessage());
            return $cached ?? [
                'competition' => ['name' => ucfirst(str_replace('-', ' ', $competitionSlug)), 'slug' => $competitionSlug],
                'season' => date('Y') . '/' . (date('Y') + 1),
                'table' => []
            ];
        }
    }

    public function getMatchDetail(string $matchId): ?array
    {
        try {
            return $this->provider->getMatchDetail($matchId);
        } catch (\Throwable $e) {
            error_log("SportsService getMatchDetail error: " . $e->getMessage());
            return null;
        }
    }
}
