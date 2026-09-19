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
            throw new \RuntimeException('Mock sports provider is strictly prohibited in production.');
        }

        return match ($this->providerType) {
            'real', 'football-data' => new FootballDataSportsProvider(),
            'mock' => $this->isProduction ? throw new \RuntimeException('Mock sports provider is strictly prohibited in production.') : new MockSportsProvider(),
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

        if ($this->providerType === 'mock') {
            try {
                $data = $this->provider->getLiveScores();
                return ['matches' => $data, 'updated_at' => date('Y-m-d H:i:s'), 'is_stale' => false];
            } catch (\Throwable $e) {
                return ['matches' => [], 'updated_at' => date('Y-m-d H:i:s'), 'is_stale' => true, 'notice' => 'Live scores unavailable.'];
            }
        }

        // Production / Real mode: Query Benchero's local normalized database cache
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
                WHERE m.status IN ('LIVE', 'IN_PLAY', 'PAUSED') AND m.provider != 'mock'
                ORDER BY m.start_time DESC LIMIT 20
            ");
            $dbMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $lastSync = $this->pdo->query("SELECT created_at FROM sports_sync_logs WHERE status = 'success' AND operation = 'sync-live' ORDER BY id DESC LIMIT 1")->fetchColumn();
            $isStale = false;
            if (empty($matches) || !$lastSync || (time() - strtotime($lastSync) > 120)) {
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
                    'start_time' => $r['start_time']
                ];
            }, $dbMatches);

            $result = [
                'matches' => $matches,
                'updated_at' => $lastSync ?: date('Y-m-d H:i:s'),
                'is_stale' => $isStale
            ];
            $this->cache->set($cacheKey, $result, 30);
            return $result;
        } catch (\Throwable $e) {
            error_log("SportsService getLiveScores error: " . $e->getMessage());
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

        try {
            $data = $this->provider->getResults($sport, $date, $limit);
            if ($competitionId !== null && !empty($data)) {
                $data = array_values(array_filter($data, function($m) use ($competitionId) {
                    return ($m['competition_slug'] ?? '') === $competitionId || ($m['competition'] ?? '') === $competitionId;
                }));
            }

            $result = [
                'results' => $data,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->cache->set($cacheKey, $result, 600);
            return $result;
        } catch (\Throwable $e) {
            error_log("SportsService getResults error: " . $e->getMessage());

            // Database fallback
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
                    WHERE m.status IN ('FINISHED', 'FT', 'AET', 'PEN') AND m.provider != 'mock'
                ";
                $params = [];
                if ($competitionId !== null) {
                    $sql .= " AND (m.competition_id = ? OR c.slug = ?)";
                    $params[] = $competitionId;
                    $params[] = $competitionId;
                }
                $sql .= " ORDER BY m.start_time DESC LIMIT " . (int)$limit;

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

                return ['results' => $results, 'updated_at' => date('Y-m-d H:i:s'), 'is_stale' => true];
            } catch (\Throwable $dbEx) {
                return $cached ?? ['results' => [], 'updated_at' => date('Y-m-d H:i:s')];
            }
        }
    }

    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20, ?string $competitionId = null): array
    {
        $cacheKey = "sports_fixtures_{$sport}_{$date}_{$limit}_{$competitionId}";
        $cached = $this->cache->get($cacheKey);

        try {
            $data = $this->provider->getFixtures($sport, $date, $limit);
            if ($competitionId !== null && !empty($data)) {
                $data = array_values(array_filter($data, function($m) use ($competitionId) {
                    return ($m['competition_slug'] ?? '') === $competitionId || ($m['competition'] ?? '') === $competitionId;
                }));
            }

            $result = [
                'fixtures' => $data,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->cache->set($cacheKey, $result, 600);
            return $result;
        } catch (\Throwable $e) {
            error_log("SportsService getFixtures error: " . $e->getMessage());

            // Database fallback
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
                    WHERE m.status IN ('SCHEDULED', 'TIMED', 'POSTPONED') AND m.provider != 'mock'
                ";
                $params = [];
                if ($competitionId !== null) {
                    $sql .= " AND (m.competition_id = ? OR c.slug = ?)";
                    $params[] = $competitionId;
                    $params[] = $competitionId;
                }
                $sql .= " ORDER BY m.start_time ASC LIMIT " . (int)$limit;

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

                return ['fixtures' => $fixtures, 'updated_at' => date('Y-m-d H:i:s'), 'is_stale' => true];
            } catch (\Throwable $dbEx) {
                return $cached ?? ['fixtures' => [], 'updated_at' => date('Y-m-d H:i:s')];
            }
        }
    }

    public function getCompetitions(?string $sport = null): array
    {
        $cacheKey = "sports_competitions_{$sport}";
        $cached = $this->cache->get($cacheKey);

        try {
            $data = $this->provider->getCompetitions($sport);
            $this->cache->set($cacheKey, $data, 3600);
            return $data;
        } catch (\Throwable $e) {
            error_log("SportsService getCompetitions error: " . $e->getMessage());

            try {
                $stmt = $this->pdo->query("SELECT * FROM sports_competitions WHERE provider != 'mock' ORDER BY name ASC");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $dbEx) {
                return $cached ?? [];
            }
        }
    }

    public function getStandings(string $competitionSlug): array
    {
        $cacheKey = "sports_standings_{$competitionSlug}";
        $cached = $this->cache->get($cacheKey);

        try {
            $data = $this->provider->getStandings($competitionSlug);
            $this->cache->set($cacheKey, $data, 1800);
            return $data;
        } catch (\Throwable $e) {
            error_log("SportsService getStandings error: " . $e->getMessage());

            try {
                $stmtComp = $this->pdo->prepare("SELECT * FROM sports_competitions WHERE slug = ?");
                $stmtComp->execute([$competitionSlug]);
                $comp = $stmtComp->fetch(PDO::FETCH_ASSOC);

                if (!$comp) {
                    return [
                        'competition' => ['name' => ucfirst(str_replace('-', ' ', $competitionSlug)), 'slug' => $competitionSlug],
                        'season' => date('Y') . '/' . (date('Y') + 1),
                        'table' => []
                    ];
                }

                $stmtSt = $this->pdo->prepare("
                    SELECT s.*, t.name as team_name, t.slug as team_slug, t.logo as team_logo
                    FROM standings s
                    LEFT JOIN sports_teams t ON s.team_id = t.id
                    WHERE s.competition_id = ?
                    ORDER BY s.position ASC
                ");
                $stmtSt->execute([$comp['id']]);
                $table = $stmtSt->fetchAll(PDO::FETCH_ASSOC);

                return [
                    'competition' => [
                        'id' => $comp['id'],
                        'name' => $comp['name'],
                        'slug' => $comp['slug']
                    ],
                    'season' => date('Y') . '/' . (date('Y') + 1),
                    'table' => $table
                ];
            } catch (\Throwable $dbEx) {
                return $cached ?? [
                    'competition' => ['name' => ucfirst(str_replace('-', ' ', $competitionSlug)), 'slug' => $competitionSlug],
                    'season' => date('Y') . '/' . (date('Y') + 1),
                    'table' => []
                ];
            }
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
