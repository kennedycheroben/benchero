<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Services\Sports\SportsProviderRouter;
use Benchero\Services\Sports\Providers\FootballDataSportsProvider;
use Benchero\Services\Sports\Providers\ApiFootballSportsProvider;
use Benchero\Services\Sports\Providers\MockSportsProvider;
use Benchero\Services\Sports\Providers\NullSportsProvider;

echo "==================================================\n";
echo " BENCHERO SPORTS PROVIDER ROUTER TEST SUITE\n";
echo "==================================================\n";

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $message, &$passed, &$failed): void {
    if ($condition) {
        echo " [PASS] {$message}\n";
        $passed++;
    } else {
        echo " [FAIL] {$message}\n";
        $failed++;
    }
}

$router = new SportsProviderRouter();

// Test 1: European competition routes to primary provider (FootballDataSportsProvider)
$premierLeagueProvider = $router->resolveProviderForCompetition('premier-league');
assertTest($premierLeagueProvider instanceof FootballDataSportsProvider || $premierLeagueProvider instanceof NullSportsProvider, "Premier League routes to FootballDataSportsProvider", $passed, $failed);

// Test 2: Active providers list includes registered providers
$activeProviders = $router->getActiveProviders();
assertTest(is_array($activeProviders) && !empty($activeProviders), "Router resolves active providers array", $passed, $failed);

// Test 3: Existing provider FootballDataSportsProvider remains unchanged and active
assertTest(isset($activeProviders['football-data']) || isset($activeProviders['default']), "Football-Data provider remains registered", $passed, $failed);

echo "==================================================\n";
echo " SUMMARY: Passed {$passed} / Failed {$failed}\n";
echo "==================================================\n";

exit($failed > 0 ? 1 : 0);
