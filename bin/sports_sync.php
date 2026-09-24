<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Services\Sports\SportsSyncService;

$options = getopt('h', ['type:', 'help']);

if (isset($options['h']) || isset($options['help']) || (isset($argv[1]) && in_array($argv[1], ['--help', '-h', 'help'], true))) {
    echo "Benchero Sports Synchronization CLI\n\n";
    echo "Usage:\n";
    echo "  php bin/sports_sync.php [action]\n";
    echo "  php bin/sports_sync.php --type=<action>\n\n";
    echo "Supported actions:\n";
    echo "  live        Sync active live scores and cleanup stale live matches\n";
    echo "  fixtures    Sync upcoming scheduled fixtures\n";
    echo "  results     Sync finished match results\n";
    echo "  standings   Sync league tables per authoritative provider\n";
    echo "  news        Sync sports news wire feeds\n";
    echo "  all         Run all synchronization tasks in sequence (default)\n\n";
    echo "Options:\n";
    echo "  -h, --help  Display this help message\n";
    exit(0);
}

$action = $options['type'] ?? null;
if (!$action && isset($argv[1]) && !str_starts_with($argv[1], '-')) {
    $action = $argv[1];
}
$action = $action ?? 'all';

$allowedActions = ['live', 'fixtures', 'results', 'standings', 'news', 'all'];
if (!in_array($action, $allowedActions, true)) {
    fwrite(STDERR, "Error: Unknown action '{$action}'.\nSupported actions: " . implode(', ', $allowedActions) . ".\n");
    exit(1);
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
