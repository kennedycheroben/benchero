<?php

namespace Benchero\Services\Sports;

use Benchero\Core\Database\Database;
use PDO;

class AdaptiveLiveSyncEngine
{
    private PDO $pdo;
    private SportsQuotaTracker $quotaTracker;
    private bool $adaptiveEnabled;
    private int $fastInterval;
    private int $slowInterval;
    private int $prematchWindow;
    private int $postmatchGrace;
    private int $maxStaleSeconds;

    public const STATE_LIVE_ACTIVE = 'LIVE_ACTIVE';
    public const STATE_PREMATCH_WINDOW = 'PREMATCH_WINDOW';
    public const STATE_POSTMATCH_GRACE = 'POSTMATCH_GRACE';
    public const STATE_IDLE_DISCOVERY = 'IDLE_DISCOVERY';
    public const STATE_DEGRADED_BACKOFF = 'DEGRADED_BACKOFF';

    public function __construct(?PDO $pdo = null, ?SportsQuotaTracker $quotaTracker = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->quotaTracker = $quotaTracker ?? new SportsQuotaTracker($this->pdo);

        $adaptiveRaw = $_ENV['SPORTS_LIVE_ADAPTIVE_ENABLED'] ?? env('SPORTS_LIVE_ADAPTIVE_ENABLED', 'true');
        $this->adaptiveEnabled = filter_var($adaptiveRaw, FILTER_VALIDATE_BOOLEAN);

        $this->fastInterval = (int)($_ENV['SPORTS_LIVE_FAST_INTERVAL_SECONDS'] ?? env('SPORTS_LIVE_FAST_INTERVAL_SECONDS', 30));
        $this->slowInterval = (int)($_ENV['SPORTS_LIVE_SLOW_INTERVAL_SECONDS'] ?? env('SPORTS_LIVE_SLOW_INTERVAL_SECONDS', 900));
        $this->prematchWindow = (int)($_ENV['SPORTS_LIVE_PREMATCH_WINDOW_SECONDS'] ?? env('SPORTS_LIVE_PREMATCH_WINDOW_SECONDS', 900));
        $this->postmatchGrace = (int)($_ENV['SPORTS_LIVE_POSTMATCH_GRACE_SECONDS'] ?? env('SPORTS_LIVE_POSTMATCH_GRACE_SECONDS', 900));
        $this->maxStaleSeconds = (int)($_ENV['SPORTS_LIVE_MAX_STALE_SECONDS'] ?? env('SPORTS_LIVE_MAX_STALE_SECONDS', 120));
    }

    /**
     * Inspect database fixtures, live matches, quotas, and recent activity
     * to determine the optimal synchronization interval and operational state.
     */
    public function determineLiveSyncState(): array
    {
        if (!$this->adaptiveEnabled) {
            $lastSync = $this->getLastSyncTimestamp('sync-live');
            $secondsSince = $lastSync ? (time() - strtotime($lastSync)) : 999999;
            return [
                'state' => 'FIXED_NON_ADAPTIVE',
                'interval_seconds' => $this->slowInterval,
                'live_matches_count' => 0,
                'prematch_matches_count' => 0,
                'grace_matches_count' => 0,
                'is_due' => ($secondsSince >= $this->slowInterval),
                'seconds_since_last_sync' => $secondsSince,
                'seconds_until_next_sync' => max(0, $this->slowInterval - $secondsSince),
                'last_sync_at' => $lastSync,
                'reason' => 'Adaptive mode is disabled in configuration'
            ];
        }

        // 1. Check for active provider quota backoff / exhaustion
        $isAnyInBackoff = false;
        $maxBackoffSecs = 0;
        foreach (['api-football', 'football-data'] as $p) {
            if ($this->quotaTracker->isProviderInBackoff($p)) {
                $isAnyInBackoff = true;
                $rem = $this->quotaTracker->getRemainingBackoffSeconds($p);
                if ($rem > $maxBackoffSecs) {
                    $maxBackoffSecs = $rem;
                }
            }
        }

        // 2. Query count of currently live matches within valid elapsed match window
        $stmtLive = $this->pdo->query("
            SELECT COUNT(*) FROM sports_matches
            WHERE status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED')
              AND provider != 'mock'
              AND start_time >= DATE_SUB(NOW(), INTERVAL 150 MINUTE)
              AND start_time <= DATE_ADD(NOW(), INTERVAL 15 MINUTE)
        ");
        $liveCount = (int)($stmtLive ? $stmtLive->fetchColumn() : 0);

        // 3. Query count of upcoming fixtures within pre-match window (e.g. 15 minutes before kickoff)
        $stmtPrematch = $this->pdo->prepare("
            SELECT COUNT(*) FROM sports_matches
            WHERE status IN ('NS', 'SCHEDULED', 'TIMED')
              AND provider != 'mock'
              AND start_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? SECOND)
        ");
        $stmtPrematch->execute([$this->prematchWindow]);
        $prematchCount = (int)$stmtPrematch->fetchColumn();

        // 4. Query count of recently finished matches within post-match grace window
        $stmtGrace = $this->pdo->prepare("
            SELECT COUNT(*) FROM sports_matches
            WHERE status IN ('FINISHED', 'FT', 'AET', 'PEN')
              AND provider != 'mock'
              AND updated_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
              AND start_time >= DATE_SUB(NOW(), INTERVAL 240 MINUTE)
        ");
        $stmtGrace->execute([$this->postmatchGrace]);
        $graceCount = (int)$stmtGrace->fetchColumn();

        // Determine state & target interval
        $lastSync = $this->getLastSyncTimestamp('sync-live');
        $secondsSince = $lastSync ? (time() - strtotime($lastSync)) : 999999;

        if ($isAnyInBackoff && $liveCount === 0) {
            $state = self::STATE_DEGRADED_BACKOFF;
            $interval = max($this->slowInterval, $maxBackoffSecs);
            $reason = "Provider rate limit or quota backoff active ({$maxBackoffSecs}s remaining)";
        } elseif ($liveCount > 0) {
            $state = self::STATE_LIVE_ACTIVE;
            $interval = $this->fastInterval;
            $reason = "Active live matches detected ({$liveCount} match" . ($liveCount === 1 ? '' : 'es') . " in progress)";
        } elseif ($prematchCount > 0) {
            $state = self::STATE_PREMATCH_WINDOW;
            $interval = min(60, $this->slowInterval);
            $reason = "Upcoming fixtures approaching kickoff within {$this->prematchWindow}s window ({$prematchCount} fixture" . ($prematchCount === 1 ? '' : 's') . ")";
        } elseif ($graceCount > 0 && $secondsSince < $this->postmatchGrace) {
            $state = self::STATE_POSTMATCH_GRACE;
            $interval = min(60, $this->slowInterval);
            $reason = "Recently completed matches in post-match confirmation grace period ({$graceCount} match" . ($graceCount === 1 ? '' : 'es') . ")";
        } else {
            $state = self::STATE_IDLE_DISCOVERY;
            $interval = $this->slowInterval;
            $reason = "No active live matches or approaching kickoffs; running in low-frequency discovery mode";
        }

        $isDue = ($secondsSince >= $interval);

        return [
            'state' => $state,
            'interval_seconds' => $interval,
            'live_matches_count' => $liveCount,
            'prematch_matches_count' => $prematchCount,
            'grace_matches_count' => $graceCount,
            'is_due' => $isDue,
            'seconds_since_last_sync' => $secondsSince,
            'seconds_until_next_sync' => max(0, $interval - $secondsSince),
            'last_sync_at' => $lastSync,
            'reason' => $reason
        ];
    }

    /**
     * Check whether live synchronization is due.
     */
    public function isSyncDue(string $action = 'live'): bool
    {
        if ($action !== 'live' && $action !== 'sync-live') {
            return true;
        }

        $state = $this->determineLiveSyncState();
        return $state['is_due'];
    }

    public function getRecommendedInterval(): int
    {
        $state = $this->determineLiveSyncState();
        return $state['interval_seconds'];
    }

    public function getLastSyncTimestamp(string $operation = 'sync-live'): ?string
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT created_at FROM sports_sync_logs 
                WHERE status = 'success' AND operation = ? 
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$operation]);
            $ts = $stmt->fetchColumn();
            return $ts ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
