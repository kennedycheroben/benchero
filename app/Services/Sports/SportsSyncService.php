<?php

namespace Benchero\Services\Sports;

use Benchero\Contracts\SportsProviderInterface;
use Benchero\Contracts\NewsProviderInterface;
use Benchero\Services\Sports\Providers\FootballDataSportsProvider;
use Benchero\Services\Sports\Providers\MockSportsProvider;
use Benchero\Services\Sports\Providers\NullSportsProvider;
use Benchero\Services\Sports\Providers\RSSNewsProvider;
use Benchero\Services\Sports\Providers\MockNewsProvider;
use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use PDO;

class SportsSyncService
{
    private PDO $pdo;
    private SportsProviderInterface $provider;
    private NewsProviderInterface $newsProvider;
    private string $lockFile;

    public function __construct(?PDO $pdo = null, ?SportsProviderInterface $provider = null, ?NewsProviderInterface $newsProvider = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->provider = $provider ?? $this->resolveProvider();
        $this->newsProvider = $newsProvider ?? $this->resolveNewsProvider();
        
        $locksDir = dirname(__DIR__, 3) . '/storage/locks';
        if (!is_dir($locksDir)) {
            @mkdir($locksDir, 0755, true);
        }
        $this->lockFile = $locksDir . '/sports_sync.lock';
    }

    private function resolveProvider(): SportsProviderInterface
    {
        $providerType = strtolower((string)($_ENV['SPORTS_PROVIDER'] ?? env('SPORTS_PROVIDER', 'mock')));
        $isProduction = (env('APP_ENV') === 'production');

        if ($isProduction && $providerType === 'mock') {
            error_log("SPORTS_PROVIDER=mock configured in production. Falling back to NullSportsProvider in SportsSyncService.");
            return new NullSportsProvider();
        }

        return match ($providerType) {
            'api-football', 'apifootball' => new \Benchero\Services\Sports\Providers\ApiFootballSportsProvider(),
            'real', 'football-data', 'football' => new FootballDataSportsProvider(),
            'mock' => $isProduction ? new NullSportsProvider() : new MockSportsProvider(),
            default => $isProduction ? new NullSportsProvider() : new MockSportsProvider()
        };
    }

    private function resolveNewsProvider(): NewsProviderInterface
    {
        $providerType = strtolower((string)($_ENV['NEWS_PROVIDER'] ?? env('NEWS_PROVIDER', 'mock')));
        $isProduction = (env('APP_ENV') === 'production');

        if ($isProduction && $providerType === 'mock') {
            return new RSSNewsProvider();
        }

        return match ($providerType) {
            'real', 'rss' => new RSSNewsProvider(),
            'mock' => $isProduction ? new RSSNewsProvider() : new MockNewsProvider(),
            default => $isProduction ? new RSSNewsProvider() : new MockNewsProvider()
        };
    }

    public function getProvider(): SportsProviderInterface
    {
        return $this->provider;
    }

    public function getNewsProvider(): NewsProviderInterface
    {
        return $this->newsProvider;
    }

    public function getProviderName(): string
    {
        if ($this->provider instanceof \Benchero\Services\Sports\Providers\ApiFootballSportsProvider) {
            return 'api-football';
        }
        if ($this->provider instanceof FootballDataSportsProvider) {
            return 'football-data';
        }
        if ($this->provider instanceof MockSportsProvider) {
            return 'mock';
        }
        if ($this->provider instanceof NullSportsProvider) {
            return 'null';
        }
        $envProvider = env('SPORTS_PROVIDER');
        if ($envProvider && $envProvider !== 'mock') {
            return $envProvider;
        }
        return 'mock';
    }

    public function acquireLock(): mixed
    {
        $dir = dirname($this->lockFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $fp = @fopen($this->lockFile, 'w+');
        if (!$fp) {
            return false;
        }

        if (!@flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return false;
        }

        return $fp;
    }

    public function releaseLock(mixed $fp): void
    {
        if (is_resource($fp)) {
            @flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public function syncLive(): array
    {
        $startTime = microtime(true);
        $providerName = $this->getProviderName();
        $processed = 0;
        $updated = 0;

        try {
            $matches = $this->provider->getLiveScores();
            $processed = count($matches);

            foreach ($matches as $match) {
                $sportId = $this->resolveSportId($match['sport'] ?? 'football');
                $compId = $this->ensureCompetition(
                    $match['competition'] ?? 'League',
                    $match['competition_slug'] ?? 'league',
                    $sportId,
                    $match['provider'] ?? $providerName
                );
                $homeId = $this->ensureTeam(
                    $match['home_team'] ?? 'Home Team',
                    $match['home_slug'] ?? 'home-team',
                    $sportId,
                    $match['provider'] ?? $providerName,
                    null,
                    $match['home_logo'] ?? null
                );
                $awayId = $this->ensureTeam(
                    $match['away_team'] ?? 'Away Team',
                    $match['away_slug'] ?? 'away-team',
                    $sportId,
                    $match['provider'] ?? $providerName,
                    null,
                    $match['away_logo'] ?? null
                );

                $stmtCheck = $this->pdo->prepare("SELECT id FROM sports_matches WHERE provider = ? AND external_id = ?");
                $stmtCheck->execute([$match['provider'] ?? $providerName, $match['external_id'] ?? $match['id']]);
                $existingId = $stmtCheck->fetchColumn();

                if ($existingId) {
                    $stmtUpdate = $this->pdo->prepare("
                        UPDATE sports_matches
                        SET home_score = ?, away_score = ?, status = ?, minute = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([
                        $match['home_score'],
                        $match['away_score'],
                        $match['status'],
                        $match['minute'] ?? null,
                        $existingId
                    ]);
                    $updated++;
                } else {
                    $stmtInsert = $this->pdo->prepare("
                        INSERT INTO sports_matches
                        (id, sport_id, competition_id, home_team_id, away_team_id, home_score, away_score, status, minute, start_time, venue, round, external_id, provider)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsert->execute([
                        Ulid::generate(),
                        $sportId,
                        $compId,
                        $homeId,
                        $awayId,
                        $match['home_score'],
                        $match['away_score'],
                        $match['status'],
                        $match['minute'] ?? null,
                        $match['start_time'] ?? date('Y-m-d H:i:s'),
                        $match['venue'] ?? null,
                        $match['round'] ?? null,
                        $match['external_id'] ?? $match['id'],
                        $match['provider'] ?? $providerName
                    ]);
                    $updated++;
                }
            }

            // Transition stale LIVE matches from this provider that are no longer active to FINISHED
            $liveExtIds = array_filter(array_map(function($m) {
                return (string)($m['external_id'] ?? $m['id'] ?? '');
            }, $matches));

            if (!empty($liveExtIds)) {
                $placeholders = implode(',', array_fill(0, count($liveExtIds), '?'));
                $stmtCleanup = $this->pdo->prepare("
                    UPDATE sports_matches
                    SET status = 'FINISHED', updated_at = NOW()
                    WHERE provider = ?
                      AND status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED')
                      AND external_id NOT IN ($placeholders)
                      AND start_time < DATE_SUB(NOW(), INTERVAL 135 MINUTE)
                ");
                $stmtCleanup->execute(array_merge([$providerName], $liveExtIds));
            } else {
                $stmtCleanup = $this->pdo->prepare("
                    UPDATE sports_matches
                    SET status = 'FINISHED', updated_at = NOW()
                    WHERE provider = ?
                      AND status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED')
                      AND start_time < DATE_SUB(NOW(), INTERVAL 135 MINUTE)
                ");
                $stmtCleanup->execute([$providerName]);
            }

            // Invalidate live scores cache
            (new \Benchero\Services\CacheService())->forget('sports_live_scores_v3');

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-live', 'success', $duration, $processed, $updated);

            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-live', 'error', $duration, $processed, $updated, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncFixtures(): array
    {
        $startTime = microtime(true);
        $providerName = $this->getProviderName();
        $processed = 0;
        $updated = 0;

        try {
            $matches = $this->provider->getFixtures();
            $processed = count($matches);

            foreach ($matches as $match) {
                $sportId = $this->resolveSportId($match['sport'] ?? 'football');
                $compId = $this->ensureCompetition(
                    $match['competition'] ?? 'League',
                    $match['competition_slug'] ?? 'league',
                    $sportId,
                    $match['provider'] ?? $providerName
                );
                $homeId = $this->ensureTeam(
                    $match['home_team'] ?? 'Home Team',
                    $match['home_slug'] ?? 'home-team',
                    $sportId,
                    $match['provider'] ?? $providerName,
                    null,
                    $match['home_logo'] ?? null
                );
                $awayId = $this->ensureTeam(
                    $match['away_team'] ?? 'Away Team',
                    $match['away_slug'] ?? 'away-team',
                    $sportId,
                    $match['provider'] ?? $providerName,
                    null,
                    $match['away_logo'] ?? null
                );

                $stmtCheck = $this->pdo->prepare("SELECT id FROM sports_matches WHERE provider = ? AND external_id = ?");
                $stmtCheck->execute([$match['provider'] ?? $providerName, $match['external_id'] ?? $match['id']]);
                $existingId = $stmtCheck->fetchColumn();

                if ($existingId) {
                    $stmtUpdate = $this->pdo->prepare("
                        UPDATE sports_matches
                        SET status = ?, start_time = ?, venue = ?, round = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([
                        $match['status'] ?? 'SCHEDULED',
                        $match['start_time'] ?? date('Y-m-d H:i:s'),
                        $match['venue'] ?? null,
                        $match['round'] ?? null,
                        $existingId
                    ]);
                    $updated++;
                } else {
                    $stmtInsert = $this->pdo->prepare("
                        INSERT INTO sports_matches
                        (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, venue, round, external_id, provider)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsert->execute([
                        Ulid::generate(), $sportId, $compId, $homeId, $awayId,
                        $match['status'] ?? 'SCHEDULED',
                        $match['start_time'] ?? date('Y-m-d H:i:s'),
                        $match['venue'] ?? null,
                        $match['round'] ?? null,
                        $match['external_id'] ?? $match['id'],
                        $match['provider'] ?? $providerName
                    ]);
                    $updated++;
                }
            }

            // Invalidate fixtures cache
            (new \Benchero\Services\CacheService())->forget('sports_fixtures_v3');

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-fixtures', 'success', $duration, $processed, $updated);
            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-fixtures', 'error', $duration, $processed, $updated, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncResults(): array
    {
        $startTime = microtime(true);
        $providerName = $this->getProviderName();
        $processed = 0;
        $updated = 0;

        try {
            $matches = $this->provider->getResults();
            $processed = count($matches);

            foreach ($matches as $match) {
                $sportId = $this->resolveSportId($match['sport'] ?? 'football');
                $compId = $this->ensureCompetition(
                    $match['competition'] ?? 'League',
                    $match['competition_slug'] ?? 'league',
                    $sportId,
                    $match['provider'] ?? $providerName
                );
                $homeId = $this->ensureTeam(
                    $match['home_team'] ?? 'Home Team',
                    $match['home_slug'] ?? 'home-team',
                    $sportId,
                    $match['provider'] ?? $providerName,
                    null,
                    $match['home_logo'] ?? null
                );
                $awayId = $this->ensureTeam(
                    $match['away_team'] ?? 'Away Team',
                    $match['away_slug'] ?? 'away-team',
                    $sportId,
                    $match['provider'] ?? $providerName,
                    null,
                    $match['away_logo'] ?? null
                );

                $stmtCheck = $this->pdo->prepare("SELECT id FROM sports_matches WHERE provider = ? AND external_id = ?");
                $stmtCheck->execute([$match['provider'] ?? $providerName, $match['external_id'] ?? $match['id']]);
                $existingId = $stmtCheck->fetchColumn();

                if ($existingId) {
                    $stmtUpdate = $this->pdo->prepare("
                        UPDATE sports_matches
                        SET home_score = ?, away_score = ?, status = ?, venue = ?, round = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([
                        $match['home_score'],
                        $match['away_score'],
                        $match['status'] ?? 'FINISHED',
                        $match['venue'] ?? null,
                        $match['round'] ?? null,
                        $existingId
                    ]);
                    $updated++;
                } else {
                    $stmtInsert = $this->pdo->prepare("
                        INSERT INTO sports_matches
                        (id, sport_id, competition_id, home_team_id, away_team_id, home_score, away_score, status, start_time, venue, round, external_id, provider)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsert->execute([
                        Ulid::generate(), $sportId, $compId, $homeId, $awayId,
                        $match['home_score'], $match['away_score'],
                        $match['status'] ?? 'FINISHED',
                        $match['start_time'] ?? date('Y-m-d H:i:s'),
                        $match['venue'] ?? null,
                        $match['round'] ?? null,
                        $match['external_id'] ?? $match['id'],
                        $match['provider'] ?? $providerName
                    ]);
                    $updated++;
                }
            }

            // Invalidate results cache
            (new \Benchero\Services\CacheService())->forget('sports_results_v3');

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-results', 'success', $duration, $processed, $updated);
            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-results', 'error', $duration, $processed, $updated, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncStandings(): array
    {
        $startTime = microtime(true);
        $providerName = $this->getProviderName();
        $processed = 0;
        $updated = 0;

        try {
            $comps = $this->provider->getCompetitions('football');
            if (empty($comps)) {
                $stmt = $this->pdo->prepare("SELECT * FROM sports_competitions WHERE provider = ?");
                $stmt->execute([$providerName]);
                $comps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            foreach ($comps as $comp) {
                $slug = $comp['slug'] ?? '';
                if (empty($slug)) {
                    continue;
                }

                $sportId = $this->resolveSportId($comp['sport'] ?? 'football');
                $compId = $this->ensureCompetition(
                    $comp['name'] ?? 'Competition',
                    $slug,
                    $sportId,
                    $comp['provider'] ?? $providerName,
                    $comp['external_id'] ?? null,
                    $comp['logo'] ?? null
                );

                try {
                    $rawStandings = $this->provider->getStandings($slug);
                } catch (\Throwable $standingsEx) {
                    error_log("SportsSyncService standings error for {$slug}: " . $standingsEx->getMessage());
                    continue;
                }

                if (empty($rawStandings)) {
                    continue;
                }

                $table = isset($rawStandings['table']) ? $rawStandings['table'] : $rawStandings;
                if (!is_array($table) || empty($table)) {
                    continue;
                }

                $season = $rawStandings['season'] ?? (date('Y') . '/' . (date('Y') + 1));

                foreach ($table as $row) {
                    $pos = (int)($row['position'] ?? 0);
                    $teamName = $row['team'] ?? $row['team_name'] ?? 'Team';
                    $teamSlug = $row['team_slug'] ?? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $teamName), '-'));
                    $teamExtId = $row['team_id'] ?? $row['external_id'] ?? null;
                    $teamLogo = $row['team_logo'] ?? $row['logo'] ?? null;

                    $teamId = $this->ensureTeam(
                        $teamName,
                        $teamSlug,
                        $sportId,
                        $comp['provider'] ?? $providerName,
                        $teamExtId,
                        $teamLogo
                    );

                    $played = (int)($row['played'] ?? $row['played_games'] ?? 0);
                    $won = (int)($row['won'] ?? 0);
                    $drawn = (int)($row['drawn'] ?? $row['draw'] ?? 0);
                    $lost = (int)($row['lost'] ?? 0);
                    $points = (int)($row['points'] ?? 0);
                    $gf = (int)($row['gf'] ?? $row['goals_for'] ?? 0);
                    $ga = (int)($row['ga'] ?? $row['goals_against'] ?? 0);
                    $gd = (int)($row['gd'] ?? $row['goal_difference'] ?? ($gf - $ga));
                    $groupName = $row['group_name'] ?? 'Main';

                    $stmtCheck = $this->pdo->prepare("SELECT id FROM sports_standings WHERE competition_id = ? AND team_id = ? AND season = ?");
                    $stmtCheck->execute([$compId, $teamId, $season]);
                    $existingId = $stmtCheck->fetchColumn();

                    if ($existingId) {
                        $stmtUpdate = $this->pdo->prepare("
                            UPDATE sports_standings
                            SET position = ?, played = ?, won = ?, drawn = ?, lost = ?, points = ?,
                                goals_for = ?, goals_against = ?, goal_difference = ?, group_name = ?,
                                provider = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmtUpdate->execute([
                            $pos, $played, $won, $drawn, $lost, $points,
                            $gf, $ga, $gd, $groupName,
                            $comp['provider'] ?? $providerName,
                            $existingId
                        ]);
                        $updated++;
                    } else {
                        $stmtInsert = $this->pdo->prepare("
                            INSERT INTO sports_standings
                            (id, competition_id, team_id, position, played, won, drawn, lost, points, goals_for, goals_against, goal_difference, group_name, season, provider)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmtInsert->execute([
                            Ulid::generate(),
                            $compId,
                            $teamId,
                            $pos,
                            $played,
                            $won,
                            $drawn,
                            $lost,
                            $points,
                            $gf,
                            $ga,
                            $gd,
                            $groupName,
                            $season,
                            $comp['provider'] ?? $providerName
                        ]);
                        $updated++;
                    }
                    $processed++;
                }

                // Invalidate standings cache for this competition
                (new \Benchero\Services\CacheService())->forget("sports_standings_{$slug}");
            }

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-standings', 'success', $duration, $processed, $updated);
            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-standings', 'error', $duration, $processed, $updated, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncNews(): array
    {
        $startTime = microtime(true);
        $providerName = 'rss';
        if ($this->newsProvider instanceof MockNewsProvider) {
            $providerName = 'mock';
        }
        $processed = 0;
        $updated = 0;

        try {
            $articles = $this->newsProvider->getLatestNews(20);
            $processed = count($articles);

            foreach ($articles as $art) {
                $sportId = $this->resolveSportId($art['sport'] ?? 'football');

                $stmtCheck = $this->pdo->prepare("SELECT id FROM sports_news WHERE provider = ? AND external_id = ?");
                $stmtCheck->execute([$art['provider'] ?? $providerName, $art['external_id'] ?? $art['id']]);
                if (!$stmtCheck->fetchColumn()) {
                    $stmtInsert = $this->pdo->prepare("
                        INSERT INTO sports_news
                        (id, sport_id, title, slug, summary, content, source, source_url, image_url, category, external_id, provider, published_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsert->execute([
                        Ulid::generate(),
                        $sportId,
                        $art['title'],
                        $art['slug'],
                        $art['summary'],
                        $art['content'] ?? null,
                        $art['source'] ?? 'Benchero Sports Wire',
                        $art['source_url'] ?? null,
                        $art['image_url'] ?? null,
                        $art['category'] ?? 'Football',
                        $art['external_id'] ?? $art['id'],
                        $art['provider'] ?? $providerName,
                        $art['published_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $updated++;
                }
            }

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-news', 'success', $duration, $processed, $updated);

            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($providerName, 'sync-news', 'error', $duration, $processed, $updated, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function resolveSportId(string $sportSlug): string
    {
        $stmt = $this->pdo->prepare("SELECT id FROM sports WHERE slug = ?");
        $stmt->execute([strtolower($sportSlug)]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return $id;
        }

        $fallback = $this->pdo->query("SELECT id FROM sports LIMIT 1")->fetchColumn();
        return $fallback ?: Ulid::generate();
    }

    private function ensureCompetition(string $name, string $slug, string $sportId, string $provider, ?string $externalId = null, ?string $logo = null): string
    {
        $stmt = $this->pdo->prepare("SELECT id FROM sports_competitions WHERE slug = ?");
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return $id;
        }

        $newId = Ulid::generate();
        $stmtInsert = $this->pdo->prepare("
            INSERT INTO sports_competitions (id, sport_id, name, slug, logo, external_id, provider)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$newId, $sportId, $name, $slug, $logo, $externalId, $provider]);
        return $newId;
    }

    private function ensureTeam(string $name, string $slug, string $sportId, string $provider, ?string $externalId = null, ?string $logo = null): string
    {
        $stmt = $this->pdo->prepare("SELECT id FROM sports_teams WHERE slug = ? AND sport_id = ?");
        $stmt->execute([$slug, $sportId]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return $id;
        }

        $newId = Ulid::generate();
        $stmtInsert = $this->pdo->prepare("
            INSERT INTO sports_teams (id, sport_id, name, slug, logo, external_id, provider)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$newId, $sportId, $name, $slug, $logo, $externalId, $provider]);
        return $newId;
    }

    private function logSync(string $provider, string $operation, string $status, int $durationMs, int $processed, int $updated, ?string $error = null): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO sports_sync_logs (provider, operation, status, duration_ms, records_processed, records_updated, error_message)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$provider, $operation, $status, $durationMs, $processed, $updated, $error]);
        } catch (\Throwable $e) {
            error_log("Failed to insert sports_sync_logs: " . $e->getMessage());
        }
    }
}
