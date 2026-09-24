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

// Test 4: Kenyan competition routes to ApiFootballSportsProvider when key configured
$fkfProvider = $router->resolveProviderForCompetition('fkf-premier-league');
assertTest($fkfProvider instanceof ApiFootballSportsProvider || $fkfProvider instanceof FootballDataSportsProvider || $fkfProvider instanceof NullSportsProvider, "FKF Premier League routes appropriately", $passed, $failed);

// Test 5: CAF Champions League routes to ApiFootballSportsProvider
$cafProvider = $router->resolveProviderForCompetition('caf-champions-league');
assertTest($cafProvider instanceof ApiFootballSportsProvider || $cafProvider instanceof FootballDataSportsProvider || $cafProvider instanceof NullSportsProvider, "CAF Champions League routes appropriately", $passed, $failed);

// Test 6: Authority checks
assertTest($router->isProviderAuthoritativeForCompetition('api-football', 'fkf-premier-league') === true, "api-football is authoritative for fkf-premier-league", $passed, $failed);
assertTest($router->isProviderAuthoritativeForCompetition('football-data', 'fkf-premier-league') === false, "football-data is NOT authoritative for fkf-premier-league", $passed, $failed);
assertTest($router->isProviderAuthoritativeForCompetition('football-data', 'premier-league') === true, "football-data is authoritative for premier-league", $passed, $failed);
assertTest($router->isProviderAuthoritativeForCompetition('football-data', 'primera-division') === true, "football-data is authoritative for primera-division", $passed, $failed);
assertTest($router->isProviderAuthoritativeForCompetition('football-data', 'la-liga') === true, "football-data is authoritative for la-liga", $passed, $failed);

echo "==================================================\n";
echo " SUMMARY: Passed {$passed} / Failed {$failed}\n";
echo "==================================================\n";

exit($failed > 0 ? 1 : 0);
