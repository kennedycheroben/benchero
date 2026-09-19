<?php
require_once __DIR__ . '/../app/bootstrap.php';
use Benchero\Core\Database\Database;

$pdo = Database::getConnection();

echo "=== MOCK SPORTS DATA TARGETED FOR DELETION ===\n";
$mockTeams = $pdo->query("SELECT id, name, slug, provider FROM sports_teams WHERE provider = 'mock'")->fetchAll();
echo "Mock Sports Teams (" . count($mockTeams) . "):\n";
foreach ($mockTeams as $t) {
    echo "  - {$t['name']} ({$t['slug']})\n";
}

$mockNews = $pdo->query("SELECT id, title, slug, provider FROM sports_news WHERE provider = 'mock'")->fetchAll();
echo "Mock Sports News (" . count($mockNews) . "):\n";
foreach ($mockNews as $n) {
    echo "  - {$n['title']}\n";
}

$mockMatches = $pdo->query("SELECT id, provider, status FROM sports_matches WHERE provider = 'mock'")->fetchAll();
echo "Mock Sports Matches (" . count($mockMatches) . "):\n";

echo "\n=== TEST ORGANIZATIONS TARGETED FOR CLEANUP ===\n";
$testOrgs = $pdo->query("SELECT id, name, slug FROM organizations WHERE slug LIKE 'test-%' OR slug IN ('org-a', 'org-b')")->fetchAll();
echo "Test Organizations (" . count($testOrgs) . "):\n";
foreach ($testOrgs as $o) {
    echo "  - ID: {$o['id']} | Name: {$o['name']} | Slug: {$o['slug']}\n";
}

echo "\n=== TEST USERS TARGETED FOR CLEANUP ===\n";
$testUsers = $pdo->query("SELECT id, name, email FROM users WHERE email IN ('a@a.com', 'b@b.com', 'valid@example.com')")->fetchAll();
foreach ($testUsers as $u) {
    echo "  - ID: {$u['id']} | Name: {$u['name']} | Email: {$u['email']}\n";
}
