<?php
require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Services\Sports\SportsSyncService;

$options = getopt('', ['type:']);
$targetType = $options['type'] ?? 'all';

$syncService = new SportsSyncService();

$lock = $syncService->acquireLock();
if (!$lock) {
    echo "Sync process is already running. Exiting.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Benchero Sports Synchronization (Type: {$targetType})...\n";

if ($targetType === 'all' || $targetType === 'live') {
    $liveRes = $syncService->syncLive();
    echo "Live Sync: " . json_encode($liveRes) . "\n";
}

if ($targetType === 'all' || $targetType === 'fixtures') {
    $fixRes = $syncService->syncFixtures();
    echo "Fixtures Sync: " . json_encode($fixRes) . "\n";
}

if ($targetType === 'all' || $targetType === 'results') {
    $resRes = $syncService->syncResults();
    echo "Results Sync: " . json_encode($resRes) . "\n";
}

if ($targetType === 'all' || $targetType === 'standings') {
    $standRes = $syncService->syncStandings();
    echo "Standings Sync: " . json_encode($standRes) . "\n";
}

if ($targetType === 'all' || $targetType === 'news') {
    $newsRes = $syncService->syncNews();
    echo "News Sync: " . json_encode($newsRes) . "\n";
}

$syncService->releaseLock($lock);
echo "[" . date('Y-m-d H:i:s') . "] Synchronization Finished Successfully.\n";
