<?php

namespace Benchero\Services\Sports;

use Benchero\Contracts\SportsProviderInterface;
use Benchero\Services\Sports\Providers\MockSportsProvider;
use Benchero\Services\Sports\Providers\FootballDataSportsProvider;
use Benchero\Services\Sports\Providers\ApiFootballSportsProvider;
use Benchero\Services\Sports\Providers\NullSportsProvider;
use Benchero\Services\Sports\SportsProviderRouter;
use Benchero\Services\CacheService;
use Benchero\Core\Database\Database;
use PDO;

class SportsService
{
    private SportsProviderInterface $provider;
    private CacheService $cache;
    private SportsProviderRouter $router;
    private string $providerType;
    private bool $isProduction;
    private PDO $pdo;

    public function __construct(
        ?SportsProviderInterface $provider = null,
        ?CacheService $cache = null,
        ?SportsProviderRouter $router = null
    ) {
        $this->pdo = Database::getConnection();
        $this->cache = $cache ?? new CacheService();
        $this->router = $router ?? new SportsProviderRouter();
        $this->isProduction = (env('APP_ENV') === 'production');
        $this->providerType = strtolower((string)($_ENV['SPORTS_PROVIDER'] ?? env('SPORTS_PROVIDER', 'mock')));
        $this->provider = $provider ?? $this->resolveProvider();
    }

    public function getLatestSuccessfulSyncTimestamp(string $operation): ?string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT created_at FROM sports_sync_logs WHERE status = 'success' AND operation = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$operation]);
            $ts = $stmt->fetchColumn();
            return $ts ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveProvider(): SportsProviderInterface
    {
        if ($this->isProduction && $this->providerType === 'mock') {
            error_log("SPORTS_PROVIDER=mock configured in production environment. Falling back to NullSportsProvider.");
            return new NullSportsProvider();
        }

        return match ($this->providerType) {
            'api-football', 'apifootball' => new ApiFootballSportsProvider(),
            'real', 'football-data', 'football' => new FootballDataSportsProvider(),
            'mock' => $this->isProduction ? new NullSportsProvider() : new MockSportsProvider(),
            default => $this->isProduction ? new NullSportsProvider() : new MockSportsProvider()
        };
    }

    /**
     * Get per-provider data freshness and quota status from database.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getProviderFreshness(): array
    {
        $freshness = [];
        try {
            $stmt = $this->pdo->query("
                SELECT provider, requests_today, daily_limit, requests_remaining, 
                       backoff_until, last_successful_sync, last_successful_live_sync,
                       last_error_message, updated_at
                FROM sports_provider_states
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $p = $row['provider'];
                $lastLiveSync = $row['last_successful_live_sync'] ?? $row['last_successful_sync'];
                $age = $lastLiveSync ? max(0, time() - strtotime($lastLiveSync)) : null;
                $isBackoff = !empty($row['backoff_until']) && (strtotime($row['backoff_until']) > time());
                
                $freshness[$p] = [
                    'provider' => $p,
                    'last_successful_sync' => $row['last_successful_sync'],
                    'last_successful_live_sync' => $row['last_successful_live_sync'],
                    'data_age_seconds' => $age,
                    'requests_today' => (int)($row['requests_today'] ?? 0),
                    'requests_remaining' => isset($row['requests_remaining']) ? (int)$row['requests_remaining'] : null,
                    'daily_limit' => isset($row['daily_limit']) ? (int)$row['daily_limit'] : null,
                    'degraded' => $isBackoff || !empty($row['last_error_message']),
                    'backoff' => $isBackoff
                ];
            }
        } catch (\Throwable) {
            // Table might not exist in early tests or migration check
        }

        return $freshness;
    }

    public function getLiveScores(): array
    {
        $cacheKey = 'sports_live_scores_v4';
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Query Benchero's local normalized database cache with strict live window protection (<= 150 minutes)
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
                WHERE m.status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED')
                  AND m.provider != 'mock'
                  AND m.start_time >= DATE_SUB(NOW(), INTERVAL 150 MINUTE)
                  AND m.start_time <= DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                ORDER BY m.start_time DESC LIMIT 20
            ");
            $dbMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $lastSync = $this->getLatestSuccessfulSyncTimestamp('sync-live');
            $maxStaleSeconds = (int)($_ENV['SPORTS_LIVE_MAX_STALE_SECONDS'] ?? env('SPORTS_LIVE_MAX_STALE_SECONDS', 120));
            if ($maxStaleSeconds <= 0) {
                $maxStaleSeconds = 120;
            }

            $dataAge = $lastSync ? max(0, time() - strtotime($lastSync)) : null;
            $isStale = ($dataAge === null || $dataAge > $maxStaleSeconds);

            $providerFreshness = $this->getProviderFreshness();
            $isDegraded = false;
            foreach ($providerFreshness as $pf) {
                if (!empty($pf['degraded'])) {
                    $isDegraded = true;
                    break;
                }
            }

            $matches = array_map(function($r) {
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
                    'minute' => $r['minute'] ?? null,
                    'start_time' => $r['start_time'],
                    'venue' => $r['venue'] ?? null
                ];
            }, $dbMatches);

            $latestMatchUpdate = !empty($dbMatches) ? max(array_map(fn($r) => $r['updated_at'] ?? $r['start_time'], $dbMatches)) : null;
            $updatedAt = $lastSync ?: ($latestMatchUpdate ?: date('Y-m-d H:i:s'));

            $result = [
                'matches' => $matches,
                'updated_at' => $updatedAt,
                'last_updated' => $updatedAt,
                'is_stale' => $isStale,
                'stale' => $isStale,
                'degraded' => $isDegraded,
                'age_seconds' => $dataAge,
                'data_age_seconds' => $dataAge,
                'last_successful_sync' => $lastSync,
                'provider' => $this->providerType,
                'providers' => $providerFreshness,
                'meta' => [
                    'last_updated' => $updatedAt,
                    'age_seconds' => $dataAge,
                    'stale' => $isStale,
                    'degraded' => $isDegraded,
                    'live_count' => count($matches),
                    'provider' => $this->providerType
                ]
            ];

            // Cache for 15 seconds to serve multiple concurrent visitors without re-querying
            $this->cache->set($cacheKey, $result, 15);
            return $result;
        } catch (\Throwable $e) {
            error_log("SportsService getLiveScores error: " . $e->getMessage());

            $lastSync = $this->getLatestSuccessfulSyncTimestamp('sync-live');
            $dataAge = $lastSync ? max(0, time() - strtotime($lastSync)) : null;

            return [
                'matches' => [],
                'updated_at' => $lastSync ?: date('Y-m-d H:i:s'),
                'last_updated' => $lastSync ?: date('Y-m-d H:i:s'),
                'is_stale' => true,
                'stale' => true,
                'degraded' => true,
                'age_seconds' => $dataAge,
                'data_age_seconds' => $dataAge,
                'last_successful_sync' => $lastSync,
                'provider' => $this->providerType,
                'providers' => [],
                'notice' => 'Live scores temporarily unavailable.',
                'meta' => [
                    'last_updated' => $lastSync ?: date('Y-m-d H:i:s'),
                    'age_seconds' => $dataAge,
                    'stale' => true,
                    'degraded' => true,
                    'live_count' => 0,
                    'provider' => $this->providerType
                ]
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

                $lastSync = $this->getLatestSuccessfulSyncTimestamp('sync-results');
                $latestDbTime = max(array_map(fn($r) => $r['updated_at'] ?? $r['created_at'] ?? $r['start_time'], $dbRows));
                $updatedAt = $lastSync ?: $latestDbTime;

                $res = ['results' => $results, 'updated_at' => $updatedAt];
                $this->cache->set($cacheKey, $res, 600);
                return $res;
            }
        } catch (\Throwable $dbEx) {
            error_log("SportsService getResults DB error: " . $dbEx->getMessage());
        }

        $lastSync = $this->getLatestSuccessfulSyncTimestamp('sync-results');
        return ['results' => [], 'updated_at' => $lastSync ?: date('Y-m-d H:i:s')];
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
                       at.name as away_team_name, at.slug as away_team_slug
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

                $lastSync = $this->getLatestSuccessfulSyncTimestamp('sync-fixtures');
                $latestDbTime = max(array_map(fn($r) => $r['updated_at'] ?? $r['created_at'] ?? $r['start_time'], $dbRows));
                $updatedAt = $lastSync ?: $latestDbTime;

                $res = ['fixtures' => $fixtures, 'updated_at' => $updatedAt];
                $this->cache->set($cacheKey, $res, 600);
                return $res;
            }
        } catch (\Throwable $dbEx) {
            error_log("SportsService getFixtures DB error: " . $dbEx->getMessage());
        }

        $lastSync = $this->getLatestSuccessfulSyncTimestamp('sync-fixtures');
        return ['fixtures' => [], 'updated_at' => $lastSync ?: date('Y-m-d H:i:s')];
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

        return [];
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

        return [
            'competition' => ['name' => ucfirst(str_replace('-', ' ', $competitionSlug)), 'slug' => $competitionSlug],
            'season' => date('Y') . '/' . (date('Y') + 1),
            'table' => []
        ];
    }

    public function getMatchDetail(string $matchId): ?array
    {
        try {
            if (str_starts_with($matchId, 'af_') || str_starts_with($matchId, 'af_m_')) {
                $afKey = env('API_FOOTBALL_API_KEY');
                if (!empty($afKey)) {
                    return (new ApiFootballSportsProvider())->getMatchDetail($matchId);
                }
            }
            if (str_starts_with($matchId, 'fd_') || str_starts_with($matchId, 'fd_m_')) {
                $fdKey = env('FOOTBALL_DATA_API_KEY');
                if (!empty($fdKey)) {
                    return (new FootballDataSportsProvider())->getMatchDetail($matchId);
                }
            }
            return $this->provider->getMatchDetail($matchId);
        } catch (\Throwable $e) {
            error_log("SportsService getMatchDetail error: " . $e->getMessage());
            return null;
        }
    }
}
