<?php
require __DIR__ . '/../app/bootstrap.php';
$db = Benchero\Core\Database\Database::getConnection();
$slug = 'org-a';
// Fetch public organization details
$stmt = $db->prepare("
    SELECT id, name, slug, country, timezone, created_at
    FROM organizations
    WHERE slug = ? AND deleted_at IS NULL
");
$stmt->execute([$slug]);
$org = $stmt->fetch(PDO::FETCH_ASSOC);

var_dump($org);

$subService = new \Benchero\Services\SubscriptionService();
var_dump($subService->isPublicProfileVisible($org['id']));

$sportsStmt = $db->prepare("
    SELECT s.id, s.name, s.slug
    FROM sports s
    JOIN organization_sports os ON os.sport_id = s.id
    WHERE os.organization_id = ? AND os.is_active = 1 AND s.is_active = 1
");
$sportsStmt->execute([$org['id']]);
$sports = $sportsStmt->fetchAll(PDO::FETCH_ASSOC);
var_dump($sports);

$fixturesStmt = $db->prepare("
    SELECT f.id, f.scheduled_at, f.venue_name, f.competition_type, f.competition_name, f.status, f.home_score, f.away_score,
           ht.name as home_team_name, at.name as away_team_name, s.name as sport_name
    FROM fixtures f
    JOIN teams ht ON f.home_team_id = ht.id
    JOIN teams at ON f.away_team_id = at.id
    JOIN sports s ON f.sport_id = s.id
    WHERE f.organization_id = ? AND f.deleted_at IS NULL
    ORDER BY f.scheduled_at DESC
    LIMIT 15
");
$fixturesStmt->execute([$org['id']]);
$fixtures = $fixturesStmt->fetchAll(PDO::FETCH_ASSOC);
var_dump($fixtures);
