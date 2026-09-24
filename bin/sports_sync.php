<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Services\Sports\SportsSyncService;

$options = getopt('', ['type:']);
$action = $options['type'] ?? 'all';
if (!isset($options['type']) && isset($argv[1]) && !str_starts_with($argv[1], '-')) {
    $action = $argv[1];
}

$syncService = new SportsSyncService();

// Acquire mutex file lock to prevent overlapping cron runs
$lockHandle = $syncService->acquireLock();
if (!$lockHandle) {
    echo "[Benchero Sports Sync] Another sync process is currently running. Exiting safely.\n";
    exit(0);
}

try {
    echo "[Benchero Sports Sync] Starting operation '{$action}' at " . date('Y-m-d H:i:s') . "\n";

    if ($action === 'live' || $action === 'all') {
        $res = $syncService->syncLive();
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
