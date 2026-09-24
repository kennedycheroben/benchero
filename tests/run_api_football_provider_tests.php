<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Services\Sports\Providers\ApiFootballSportsProvider;
use Benchero\Services\Sports\Providers\MockSportsProvider;
use Benchero\Contracts\SportsProviderInterface;

echo "==================================================\n";
echo " BENCHERO API-FOOTBALL PROVIDER TEST SUITE\n";
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

// Test 1: Instantiation & Interface implementation
$provider = new ApiFootballSportsProvider('test_key');
assertTest($provider instanceof SportsProviderInterface, "ApiFootballSportsProvider implements SportsProviderInterface", $passed, $failed);

// Test 2: Status normalization logic
$sampleRawMatch = [
    'fixture' => [
        'id' => 123456,
        'date' => '2026-09-20T16:00:00+00:00',
        'status' => ['short' => 'FT', 'elapsed' => 90],
        'venue' => ['name' => 'Nairobi City Stadium']
    ],
    'league' => [
        'name' => 'FKF Premier League',
        'round' => 'Regular Season - 5'
    ],
    'teams' => [
        'home' => ['name' => 'Gor Mahia', 'logo' => 'https://example.com/gor.png'],
        'away' => ['name' => 'AFC Leopards', 'logo' => 'https://example.com/afc.png']
    ],
    'goals' => [
        'home' => 2,
        'away' => 1
    ]
];

$normalized = $provider->normalizeMatch($sampleRawMatch);
assertTest($normalized['id'] === 'af_m_123456', "Match ID prefix is correct (af_m_123456)", $passed, $failed);
assertTest($normalized['external_id'] === '123456', "External ID is preserved as 123456", $passed, $failed);
assertTest($normalized['provider'] === 'api-football', "Provider attribute is set to api-football", $passed, $failed);
assertTest($normalized['competition'] === 'FKF Premier League', "Competition name normalized correctly", $passed, $failed);
assertTest($normalized['home_team'] === 'Gor Mahia' && $normalized['away_team'] === 'AFC Leopards', "Teams normalized correctly", $passed, $failed);
assertTest($normalized['home_score'] === 2 && $normalized['away_score'] === 1, "Score normalized correctly", $passed, $failed);
assertTest($normalized['status'] === 'FT', "Match status mapped to FT", $passed, $failed);

// Test 3: Status code mapping checks
$sampleLiveMatch = $sampleRawMatch;
$sampleLiveMatch['fixture']['status']['short'] = '2H';
$sampleLiveMatch['fixture']['status']['elapsed'] = 72;
$normLive = $provider->normalizeMatch($sampleLiveMatch);
assertTest($normLive['status'] === 'LIVE', "Short status 2H mapped to LIVE", $passed, $failed);
assertTest($normLive['minute'] === "72'", "Live minute mapped to 72'", $passed, $failed);

$sampleNSMatch = $sampleRawMatch;
$sampleNSMatch['fixture']['status']['short'] = 'NS';
$normNS = $provider->normalizeMatch($sampleNSMatch);
assertTest($normNS['status'] === 'NS', "Short status NS mapped to NS", $passed, $failed);

$sampleHTMatch = $sampleRawMatch;
$sampleHTMatch['fixture']['status']['short'] = 'HT';
$normHT = $provider->normalizeMatch($sampleHTMatch);
assertTest($normHT['status'] === 'HT', "Short status HT mapped to HT", $passed, $failed);

// Test 4: Missing API Key bubbles RuntimeException (no false success)
$emptyProvider = new ApiFootballSportsProvider('');
$threwError = false;
try {
    $emptyProvider->getLiveScores();
} catch (\RuntimeException $e) {
    $threwError = ($e->getCode() === 401);
}
assertTest($threwError, "Missing API key bubbles RuntimeException (HTTP 401) without masking as empty success", $passed, $failed);

// Test 5: Competitions list returns featured African/Kenyan & International competitions
$comps = $provider->getCompetitions();
assertTest(count($comps) >= 5, "Competitions returns list of featured leagues", $passed, $failed);
$hasFKF = false;
foreach ($comps as $c) {
    if ($c['slug'] === 'fkf-premier-league') {
        $hasFKF = true;
    }
}
assertTest($hasFKF, "Featured competitions includes FKF Premier League", $passed, $failed);

// Test 6: Competition collision prevention: unmapped "Premier League" from Bhutan / Jamaica does NOT collide with English Premier League
$bhutanMatch = [
    'fixture' => ['id' => 9991, 'date' => '2026-09-24T10:00:00+00:00', 'status' => ['short' => 'FT']],
    'league' => ['id' => 8888, 'name' => 'Premier League', 'country' => 'Bhutan'],
    'teams' => ['home' => ['name' => 'RTC'], 'away' => ['name' => 'Tsirang']],
    'goals' => ['home' => 1, 'away' => 1]
];
$normBhutan = $provider->normalizeMatch($bhutanMatch);
assertTest($normBhutan['competition_slug'] === 'bhutan-premier-league', "Bhutan Premier League slug is prefixed (bhutan-premier-league)", $passed, $failed);
assertTest($normBhutan['competition_slug'] !== 'premier-league', "Bhutan Premier League does NOT collide with English premier-league", $passed, $failed);

$jamaicaMatch = [
    'fixture' => ['id' => 9992, 'date' => '2026-09-24T10:00:00+00:00', 'status' => ['short' => 'FT']],
    'league' => ['id' => 8889, 'name' => 'Premier League', 'country' => 'Jamaica'],
    'teams' => ['home' => ['name' => 'Arnett Gardens'], 'away' => ['name' => 'Dunbeholden']],
    'goals' => ['home' => 2, 'away' => 0]
];
$normJamaica = $provider->normalizeMatch($jamaicaMatch);
assertTest($normJamaica['competition_slug'] === 'jamaica-premier-league', "Jamaica Premier League slug is prefixed (jamaica-premier-league)", $passed, $failed);
assertTest($normJamaica['competition_slug'] !== 'premier-league', "Jamaica Premier League does NOT collide with English premier-league", $passed, $failed);

echo "==================================================\n";
echo " SUMMARY: Passed {$passed} / Failed {$failed}\n";
echo "==================================================\n";

exit($failed > 0 ? 1 : 0);
