<?php
require_once __DIR__ . '/../app/bootstrap.php';
use Benchero\Core\Database\Database;

$pdo = Database::getConnection();

echo "=== BENCHERO DATA INTEGRITY AUDIT ===\n";

// 1. Mock Records Check
$mockMatches = $pdo->query("SELECT COUNT(*) FROM sports_matches WHERE provider = 'mock'")->fetchColumn();
$mockTeams = $pdo->query("SELECT COUNT(*) FROM sports_teams WHERE provider = 'mock'")->fetchColumn();
$mockComps = $pdo->query("SELECT COUNT(*) FROM sports_competitions WHERE provider = 'mock'")->fetchColumn();
$mockNews = $pdo->query("SELECT COUNT(*) FROM sports_news WHERE provider = 'mock'")->fetchColumn();

echo "1. Mock Records:\n";
echo "   - sports_matches: {$mockMatches}\n";
echo "   - sports_teams: {$mockTeams}\n";
echo "   - sports_competitions: {$mockComps}\n";
echo "   - sports_news: {$mockNews}\n";

// 2. Duplicate Records Check
$dupMatches = $pdo->query("SELECT provider, external_id, COUNT(*) as c FROM sports_matches GROUP BY provider, external_id HAVING c > 1")->fetchAll();
$dupTeams = $pdo->query("SELECT provider, sport_id, slug, COUNT(*) as c FROM sports_teams GROUP BY provider, sport_id, slug HAVING c > 1")->fetchAll();

echo "2. Duplicate Records:\n";
echo "   - Duplicate matches: " . count($dupMatches) . "\n";
echo "   - Duplicate teams: " . count($dupTeams) . "\n";

// 3. Impossible Fixture Mappings (Same Home & Away Team)
$sameTeamFixtures = $pdo->query("SELECT COUNT(*) FROM sports_matches WHERE home_team_id = away_team_id")->fetchColumn();
echo "3. Impossible Fixtures (Same Home & Away Team): {$sameTeamFixtures}\n";

// 4. Missing Provider IDs / Provenance
$missingExtIdMatches = $pdo->query("SELECT COUNT(*) FROM sports_matches WHERE external_id IS NULL OR provider IS NULL")->fetchColumn();
echo "4. Missing Provider Metadata (Matches): {$missingExtIdMatches}\n";

// 5. Active Organizations Check
$orgs = $pdo->query("SELECT id, name, slug FROM organizations WHERE deleted_at IS NULL")->fetchAll();
echo "5. Active Production Organizations (" . count($orgs) . "):\n";
foreach ($orgs as $o) {
    echo "   - {$o['name']} ({$o['slug']})\n";
}

echo "\nAudit finished.\n";
