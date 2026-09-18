<?php

namespace Benchero\Services\Sports;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\CacheService;
use PDO;

class SportsSyncService
{
    private PDO $pdo;
    private SportsService $sportsService;
    private NewsService $newsService;
    private string $lockFile;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->sportsService = new SportsService();
        $this->newsService = new NewsService();
        
        $locksDir = __DIR__ . '/../../storage/locks';
        if (!is_dir($locksDir)) {
            @mkdir($locksDir, 0755, true);
        }
        $this->lockFile = $locksDir . '/sports_sync.lock';
    }

    public function acquireLock(): mixed
    {
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
        $provider = env('SPORTS_PROVIDER', 'mock');
        $processed = 0;
        $updated = 0;

        try {
            $liveData = $this->sportsService->getLiveScores();
            $matches = $liveData['matches'] ?? [];
            $processed = count($matches);

            foreach ($matches as $match) {
                $sportId = $this->resolveSportId($match['sport'] ?? 'football');
                $compId = $this->ensureCompetition($match['competition'] ?? 'League', $match['competition_slug'] ?? 'league', $sportId, $match['provider'] ?? $provider);
                $homeId = $this->ensureTeam($match['home_team'] ?? 'Home Team', $match['home_slug'] ?? 'home-team', $sportId, $match['provider'] ?? $provider);
                $awayId = $this->ensureTeam($match['away_team'] ?? 'Away Team', $match['away_slug'] ?? 'away-team', $sportId, $match['provider'] ?? $provider);

                $stmtCheck = $this->pdo->prepare("SELECT id FROM sports_matches WHERE provider = ? AND external_id = ?");
                $stmtCheck->execute([$match['provider'] ?? $provider, $match['external_id'] ?? $match['id']]);
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
                        $match['provider'] ?? $provider
                    ]);
                    $updated++;
                }
            }

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-live', 'success', $duration, $processed, $updated);

            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-live', 'error', $duration, $processed, $updated, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncFixtures(): array
    {
        $startTime = microtime(true);
        $provider = env('SPORTS_PROVIDER', 'mock');
        $processed = 0;
        $updated = 0;

        try {
            $data = $this->sportsService->getFixtures();
            $matches = $data['fixtures'] ?? [];
            $processed = count($matches);

            foreach ($matches as $match) {
                $sportId = $this->resolveSportId($match['sport'] ?? 'football');
                $compId = $this->ensureCompetition($match['competition'] ?? 'League', $match['competition_slug'] ?? 'league', $sportId, $match['provider'] ?? $provider);
                $homeId = $this->ensureTeam($match['home_team'] ?? 'Home Team', $match['home_slug'] ?? 'home-team', $sportId, $match['provider'] ?? $provider);
                $awayId = $this->ensureTeam($match['away_team'] ?? 'Away Team', $match['away_slug'] ?? 'away-team', $sportId, $match['provider'] ?? $provider);

                $stmtCheck = $this->pdo->prepare("SELECT id FROM sports_matches WHERE provider = ? AND external_id = ?");
                $stmtCheck->execute([$match['provider'] ?? $provider, $match['external_id'] ?? $match['id']]);
                $existingId = $stmtCheck->fetchColumn();

                if ($existingId) {
                    $stmtUpdate = $this->pdo->prepare("
                        UPDATE sports_matches
                        SET status = ?, start_time = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$match['status'] ?? 'SCHEDULED', $match['start_time'] ?? date('Y-m-d H:i:s'), $existingId]);
                    $updated++;
                } else {
                    $stmtInsert = $this->pdo->prepare("
                        INSERT INTO sports_matches
                        (id, sport_id, competition_id, home_team_id, away_team_id, status, start_time, external_id, provider)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsert->execute([
                        Ulid::generate(), $sportId, $compId, $homeId, $awayId,
                        $match['status'] ?? 'SCHEDULED', $match['start_time'] ?? date('Y-m-d H:i:s'),
                        $match['external_id'] ?? $match['id'], $match['provider'] ?? $provider
                    ]);
                    $updated++;
                }
            }

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-fixtures', 'success', $duration, $processed, $updated);
            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-fixtures', 'error', $duration, $processed, $updated, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncResults(): array
    {
        $startTime = microtime(true);
        $provider = env('SPORTS_PROVIDER', 'mock');
        $processed = 0;
        $updated = 0;

        try {
            $data = $this->sportsService->getResults();
            $matches = $data['results'] ?? [];
            $processed = count($matches);

            foreach ($matches as $match) {
                $sportId = $this->resolveSportId($match['sport'] ?? 'football');
                $compId = $this->ensureCompetition($match['competition'] ?? 'League', $match['competition_slug'] ?? 'league', $sportId, $match['provider'] ?? $provider);
                $homeId = $this->ensureTeam($match['home_team'] ?? 'Home Team', $match['home_slug'] ?? 'home-team', $sportId, $match['provider'] ?? $provider);
                $awayId = $this->ensureTeam($match['away_team'] ?? 'Away Team', $match['away_slug'] ?? 'away-team', $sportId, $match['provider'] ?? $provider);

                $stmtCheck = $this->pdo->prepare("SELECT id FROM sports_matches WHERE provider = ? AND external_id = ?");
                $stmtCheck->execute([$match['provider'] ?? $provider, $match['external_id'] ?? $match['id']]);
                $existingId = $stmtCheck->fetchColumn();

                if ($existingId) {
                    $stmtUpdate = $this->pdo->prepare("
                        UPDATE sports_matches
                        SET home_score = ?, away_score = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmtUpdate->execute([$match['home_score'], $match['away_score'], $match['status'] ?? 'FINISHED', $existingId]);
                    $updated++;
                } else {
                    $stmtInsert = $this->pdo->prepare("
                        INSERT INTO sports_matches
                        (id, sport_id, competition_id, home_team_id, away_team_id, home_score, away_score, status, start_time, external_id, provider)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsert->execute([
                        Ulid::generate(), $sportId, $compId, $homeId, $awayId,
                        $match['home_score'], $match['away_score'], $match['status'] ?? 'FINISHED',
                        $match['start_time'] ?? date('Y-m-d H:i:s'), $match['external_id'] ?? $match['id'], $match['provider'] ?? $provider
                    ]);
                    $updated++;
                }
            }

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-results', 'success', $duration, $processed, $updated);
            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-results', 'error', $duration, $processed, $updated, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncStandings(): array
    {
        $startTime = microtime(true);
        $provider = env('SPORTS_PROVIDER', 'mock');
        $processed = 0;
        $updated = 0;

        try {
            $comps = $this->sportsService->getCompetitions('football');
            foreach ($comps as $comp) {
                $slug = $comp['slug'] ?? 'premier-league';
                $standings = $this->sportsService->getStandings($slug);
                $table = $standings['table'] ?? [];
                $processed += count($table);
                $updated += count($table);
            }

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-standings', 'success', $duration, $processed, $updated);
            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-standings', 'error', $duration, $processed, $updated, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function syncNews(): array
    {
        $startTime = microtime(true);
        $provider = env('NEWS_PROVIDER', 'mock');
        $processed = 0;
        $updated = 0;

        try {
            $articles = $this->newsService->getLatestNews(20);
            $processed = count($articles);

            foreach ($articles as $art) {
                $sportId = $this->resolveSportId($art['sport'] ?? 'football');

                $stmtCheck = $this->pdo->prepare("SELECT id FROM sports_news WHERE provider = ? AND external_id = ?");
                $stmtCheck->execute([$art['provider'] ?? $provider, $art['external_id'] ?? $art['id']]);
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
                        $art['provider'] ?? $provider,
                        $art['published_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $updated++;
                }
            }

            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-news', 'success', $duration, $processed, $updated);

            return ['success' => true, 'processed' => $processed, 'updated' => $updated];
        } catch (\Throwable $e) {
            $duration = (int)round((microtime(true) - $startTime) * 1000);
            $this->logSync($provider, 'sync-news', 'error', $duration, $processed, $updated, $e->getMessage());
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

    private function ensureCompetition(string $name, string $slug, string $sportId, string $provider): string
    {
        $stmt = $this->pdo->prepare("SELECT id FROM sports_competitions WHERE slug = ?");
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return $id;
        }

        $newId = Ulid::generate();
        $stmtInsert = $this->pdo->prepare("
            INSERT INTO sports_competitions (id, sport_id, name, slug, provider)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$newId, $sportId, $name, $slug, $provider]);
        return $newId;
    }

    private function ensureTeam(string $name, string $slug, string $sportId, string $provider): string
    {
        $stmt = $this->pdo->prepare("SELECT id FROM sports_teams WHERE slug = ? AND sport_id = ?");
        $stmt->execute([$slug, $sportId]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return $id;
        }

        $newId = Ulid::generate();
        $stmtInsert = $this->pdo->prepare("
            INSERT INTO sports_teams (id, sport_id, name, slug, provider)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtInsert->execute([$newId, $sportId, $name, $slug, $provider]);
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
