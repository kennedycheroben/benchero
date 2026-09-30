<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Services\Sports\SportsSyncService;
use Benchero\Core\Database\Database;

$options = getopt('h', ['type:', 'help', 'force', 'max-seconds:']);

$isHelp = isset($options['h']) || isset($options['help']) || (isset($argv[1]) && in_array($argv[1], ['--help', '-h', 'help'], true));

if ($isHelp) {
    echo "Benchero Sports Synchronization & Adaptive Quota CLI\n\n";
    echo "Usage:\n";
    echo "  php bin/sports_sync.php [action] [options]\n";
    echo "  php bin/sports_sync.php --type=<action> [options]\n\n";
    echo "Supported actions:\n";
    echo "  live         Sync active live scores (respects adaptive interval unless --force)\n";
    echo "  adaptive     Adaptive live burst loop for 1-minute cPanel cron (runs up to --max-seconds)\n";
    echo "  worker       Persistent adaptive live sync daemon (for Supervisor / systemd)\n";
    echo "  quota        Display observed API requests, daily quota usage, and provider health\n";
    echo "  status       Alias for quota\n";
    echo "  fixtures     Sync upcoming scheduled fixtures\n";
    echo "  results      Sync finished match results\n";
    echo "  standings    Sync league tables per authoritative provider\n";
    echo "  news         Sync sports news wire feeds\n";
    echo "  all          Run all standard sync operations in sequence (default)\n\n";
    echo "Options:\n";
    echo "  --force              Bypass adaptive sync interval checks and force immediate provider call\n";
    echo "  --max-seconds=<sec>  Maximum runtime in seconds for adaptive mode (default: 55)\n";
    echo "  -h, --help           Display this help message\n";
    exit(0);
}

$action = $options['type'] ?? null;
if (!$action && isset($argv[1]) && !str_starts_with($argv[1], '-')) {
    $action = $argv[1];
}
$action = $action ?? 'all';

$isForced = isset($options['force']) || in_array('--force', $argv, true);
$maxSeconds = isset($options['max-seconds']) ? (int)$options['max-seconds'] : 55;
if ($maxSeconds <= 0 || $maxSeconds > 3600) {
    $maxSeconds = 55;
}

$allowedActions = ['live', 'adaptive', 'worker', 'quota', 'status', 'fixtures', 'results', 'standings', 'news', 'all'];
if (!in_array($action, $allowedActions, true)) {
    fwrite(STDERR, "Error: Unknown action '{$action}'.\nSupported actions: " . implode(', ', $allowedActions) . ".\n");
    exit(1);
}

$syncService = new SportsSyncService();

// Action: QUOTA / STATUS report
if ($action === 'quota' || $action === 'status') {
    echo "====================================================================\n";
    echo "BENCHERO SPORTS PROVIDER QUOTA & SYNCHRONIZATION DIAGNOSTICS\n";
    echo "Generated: " . date('Y-m-d H:i:s T') . "\n";
    echo "====================================================================\n\n";

    $tracker = $syncService->getQuotaTracker();
    $states = $tracker->getAllProviderStates();
    $today = date('Y-m-d');

    echo "--- 1. PROVIDER STATES & DAILY QUOTAS ---\n";
    if (empty($states)) {
        echo "No provider state records found in database.\n";
    } else {
        printf("%-16s | %-12s | %-12s | %-12s | %-14s | %-20s\n", "Provider", "Used Today", "Limit", "Remaining", "Backoff", "Last Live Sync");
        echo str_repeat("-", 96) . "\n";
        foreach ($states as $st) {
            $pName = $st['provider'];
            $used = $st['requests_today'] ?? 0;
            $limit = $st['daily_limit'] !== null ? $st['daily_limit'] : 'unlimited';
            $rem = $st['requests_remaining'] !== null ? $st['requests_remaining'] : 'N/A';
            $backoff = 'None';
            if (!empty($st['backoff_until']) && strtotime($st['backoff_until']) > time()) {
                $backoffSecs = strtotime($st['backoff_until']) - time();
                $backoff = "Active ({$backoffSecs}s)";
            }
            $lastLive = $st['last_successful_live_sync'] ?? 'Never';
            printf("%-16s | %-12s | %-12s | %-12s | %-14s | %-20s\n", $pName, $used, $limit, $rem, $backoff, $lastLive);
        }
    }
    echo "\n";

    echo "--- 2. OBSERVED REQUESTS TODAY ({$today}) BY ACTION ---\n";
    try {
        $pdo = Database::getConnection();
        $stmtReq = $pdo->prepare("
            SELECT provider, action, COUNT(*) as count, 
                   SUM(CASE WHEN http_status >= 200 AND http_status < 300 THEN 1 ELSE 0 END) as http_2xx,
                   SUM(CASE WHEN http_status = 429 THEN 1 ELSE 0 END) as http_429,
                   SUM(CASE WHEN http_status >= 400 AND http_status != 429 THEN 1 ELSE 0 END) as http_4xx_5xx,
                   ROUND(AVG(duration_ms), 1) as avg_duration_ms
            FROM sports_api_requests
            WHERE DATE(created_at) = ?
            GROUP BY provider, action
            ORDER BY provider, action
        ");
        $stmtReq->execute([$today]);
        $rows = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo "No external provider requests recorded today.\n";
        } else {
            printf("%-16s | %-12s | %-8s | %-8s | %-8s | %-10s | %-12s\n", "Provider", "Action", "Total", "2xx OK", "429 Rate", "Errors", "Avg Latency");
            echo str_repeat("-", 84) . "\n";
            $totalReqs = 0;
            foreach ($rows as $r) {
                $totalReqs += (int)$r['count'];
                printf("%-16s | %-12s | %-8d | %-8d | %-8d | %-10d | %-10.1fms\n",
                    $r['provider'],
                    $r['action'] ?: 'unknown',
                    (int)$r['count'],
                    (int)$r['http_2xx'],
                    (int)$r['http_429'],
                    (int)$r['http_4xx_5xx'],
                    (float)$r['avg_duration_ms']
                );
            }
            echo str_repeat("-", 84) . "\n";
            echo "Total external API requests today: {$totalReqs}\n";
        }
    } catch (\Throwable $e) {
        echo "Error fetching request counts: " . $e->getMessage() . "\n";
    }
    echo "\n";

    echo "--- 3. ADAPTIVE LIVE ENGINE CURRENT STATE ---\n";
    $engine = $syncService->getAdaptiveEngine();
    $liveState = $engine->determineLiveSyncState();
    echo "Current State:     " . $liveState['state'] . "\n";
    echo "Reason:            " . $liveState['reason'] . "\n";
    echo "Target Interval:   " . $liveState['interval_seconds'] . " seconds\n";
    echo "Live Matches:      " . $liveState['live_matches_count'] . "\n";
    echo "Upcoming in 15m:   " . $liveState['prematch_matches_count'] . "\n";
    echo "Grace Period:      " . $liveState['grace_matches_count'] . "\n";
    echo "Is Live Sync Due:  " . ($liveState['is_due'] ? 'YES' : 'NO') . "\n";
    echo "====================================================================\n";
    exit(0);
}

// Action: ADAPTIVE (cPanel 1-minute cron burst loop)
if ($action === 'adaptive') {
    $lockHandle = $syncService->acquireLock('live');
    if (!$lockHandle) {
        echo "[Benchero Sports Sync] Another live synchronization loop is currently running. Exiting cleanly.\n";
        exit(0);
    }

    $startTime = microtime(true);
    echo "[Benchero Sports Sync] Adaptive live runner started at " . date('Y-m-d H:i:s') . " (max {$maxSeconds}s)\n";

    try {
        $loopCount = 0;
        while ((microtime(true) - $startTime) < $maxSeconds) {
            $loopCount++;
            $engine = $syncService->getAdaptiveEngine();
            $state = $engine->determineLiveSyncState();

            $elapsed = round(microtime(true) - $startTime, 1);
            echo "[{$elapsed}s] [Loop {$loopCount}] State: {$state['state']} ({$state['reason']}), interval: {$state['interval_seconds']}s\n";

            if ($isForced || $engine->isSyncDue('live')) {
                echo "[{$elapsed}s] Triggering live sync...\n";
                $res = $syncService->syncLive(true);
                echo "[{$elapsed}s] Live sync result: " . json_encode($res) . "\n";
                $isForced = false; // reset after first forced run
            } else {
                echo "[{$elapsed}s] Live sync skipped (interval not yet elapsed).\n";
            }

            // Sleep calculation: if idle, don't sleep excessively in 1-minute cron, exit early to release resources
            if ($state['state'] === 'IDLE_DISCOVERY' && (microtime(true) - $startTime) > 5) {
                echo "No active or upcoming matches. Exiting early to conserve server resources.\n";
                break;
            }

            // Determine sleep duration
            $sleepSecs = min(15, max(5, $state['interval_seconds']));
            // If sleeping would exceed maxSeconds, break early
            if ((microtime(true) - $startTime) + $sleepSecs >= $maxSeconds) {
                break;
            }

            sleep($sleepSecs);
        }

        $totalElapsed = round(microtime(true) - $startTime, 2);
        echo "[Benchero Sports Sync] Adaptive live runner completed in {$totalElapsed}s\n";
    } finally {
        $syncService->releaseLock($lockHandle);
    }
    exit(0);
}

// Action: WORKER (persistent daemon mode)
if ($action === 'worker') {
    $lockHandle = $syncService->acquireLock('live');
    if (!$lockHandle) {
        echo "[Benchero Sports Sync Worker] Another live sync worker or process is already active. Exiting.\n";
        exit(0);
    }

    echo "[Benchero Sports Sync Worker] Starting continuous adaptive worker at " . date('Y-m-d H:i:s') . "\n";
    echo "Press Ctrl+C to terminate.\n";

    // Register signal handlers for clean shutdown if PCNTL is available
    if (function_exists('pcntl_signal')) {
        pcntl_async_signals(true);
        $running = true;
        pcntl_signal(SIGINT, function() use (&$running) { $running = false; echo "\nTerminating gracefully...\n"; });
        pcntl_signal(SIGTERM, function() use (&$running) { $running = false; echo "\nTerminating gracefully...\n"; });
    } else {
        $running = true;
    }

    try {
        while ($running) {
            $engine = $syncService->getAdaptiveEngine();
            $state = $engine->determineLiveSyncState();

            if ($engine->isSyncDue('live')) {
                echo "[" . date('H:i:s') . "] Sync due ({$state['state']}): running live sync...\n";
                $res = $syncService->syncLive(true);
                echo "[" . date('H:i:s') . "] Live sync finished: " . json_encode($res) . "\n";
            }

            $sleepSecs = max(5, min(30, $state['interval_seconds']));
            sleep($sleepSecs);
        }
    } finally {
        $syncService->releaseLock($lockHandle);
        echo "[Benchero Sports Sync Worker] Stopped cleanly.\n";
    }
    exit(0);
}

// Standard actions: live, fixtures, results, standings, news, all
$actionLock = ($action === 'live') ? 'live' : null;
$lockHandle = $syncService->acquireLock($actionLock);
if (!$lockHandle) {
    echo "[Benchero Sports Sync] Another sync process is currently running for '{$action}'. Exiting safely.\n";
    exit(0);
}

try {
    echo "[Benchero Sports Sync] Starting operation '{$action}' at " . date('Y-m-d H:i:s') . "\n";

    if ($action === 'live' || $action === 'all') {
        $res = $syncService->syncLive($isForced);
        echo "Live sync finished: " . json_encode($res) . "\n";
    }

    if ($action === 'fixtures' || $action === 'all') {
        $res = $syncService->syncFixtures();
        echo "Fixtures sync finished: " . json_encode($res) . "\n";
    }

    if ($action === 'results' || $action === 'all') {
        $res = $syncService->syncResults();
        echo "Results sync finished: " . json_encode($res) . "\n";
    }

    if ($action === 'standings' || $action === 'all') {
        $res = $syncService->syncStandings();
        echo "Standings sync finished: " . json_encode($res) . "\n";
    }

    if ($action === 'news' || $action === 'all') {
        $res = $syncService->syncNews();
        echo "News sync finished: " . json_encode($res) . "\n";
    }

    echo "[Benchero Sports Sync] Finished successfully at " . date('Y-m-d H:i:s') . "\n";
} finally {
    $syncService->releaseLock($lockHandle);
}
