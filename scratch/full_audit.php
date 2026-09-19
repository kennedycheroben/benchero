<?php
require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;

$pdo = Database::getConnection();

function fetchAll($sql, $params = []) {
    $stmt = Database::query($sql, $params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchOne($sql, $params = []) {
    $stmt = Database::query($sql, $params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$audit = [];

// 1. Audit Organizations
$orgs = fetchAll("SELECT * FROM organizations");
$audit['organizations'] = [];
foreach ($orgs as $o) {
    $userCount = fetchOne("SELECT COUNT(*) as c FROM organization_user WHERE organization_id = ?", [$o['id']])['c'];
    $teamCount = fetchOne("SELECT COUNT(*) as c FROM teams WHERE organization_id = ?", [$o['id']])['c'];
    $playerCount = fetchOne("SELECT COUNT(*) as c FROM players WHERE organization_id = ?", [$o['id']])['c'];
    $sub = fetchOne("SELECT s.*, p.name as plan_name, p.slug as plan_slug FROM subscriptions s LEFT JOIN plans p ON s.plan_id = p.id WHERE s.organization_id = ? ORDER BY s.id DESC LIMIT 1", [$o['id']]);
    
    $audit['organizations'][] = [
        'id' => $o['id'],
        'name' => $o['name'],
        'slug' => $o['slug'],
        'status' => $o['status'] ?? 'unknown',
        'is_public' => $o['is_public'] ?? 0,
        'created_at' => $o['created_at'],
        'users_count' => $userCount,
        'teams_count' => $teamCount,
        'players_count' => $playerCount,
        'latest_subscription' => $sub
    ];
}

// 2. Audit Sports Data Tables
$tablesToAudit = ['sports', 'competitions', 'seasons', 'competition_teams', 'fixtures', 'fixture_results', 'standings', 'sports_news', 'sports_providers'];
foreach ($tablesToAudit as $tbl) {
    try {
        $count = fetchOne("SELECT COUNT(*) as c FROM {$tbl}")['c'];
        $sample = fetchAll("SELECT * FROM {$tbl} LIMIT 10");
        $audit['sports_tables'][$tbl] = [
            'count' => $count,
            'sample' => $sample
        ];
    } catch (\Throwable $e) {
        $audit['sports_tables'][$tbl] = ['error' => $e->getMessage()];
    }
}

// 3. Audit Plans & Pricing
try {
    $plans = fetchAll("SELECT * FROM plans");
    $audit['plans'] = $plans;
} catch (\Throwable $e) {
    $audit['plans'] = ['error' => $e->getMessage()];
}

// 4. Audit Subscriptions & Payments
try {
    $subscriptions = fetchAll("SELECT s.*, o.name as org_name, p.name as plan_name FROM subscriptions s LEFT JOIN organizations o ON s.organization_id = o.id LEFT JOIN plans p ON s.plan_id = p.id");
    $audit['subscriptions'] = $subscriptions;
} catch (\Throwable $e) {
    $audit['subscriptions'] = ['error' => $e->getMessage()];
}

// 5. Search suspicious names across database tables
$suspiciousKeywords = [
    'mock', 'demo', 'dummy', 'sample', 'test', 'testing', 'placeholder', 'fake', 'fallback',
    'hardcoded', 'seed', 'fixture', 'example', 'temporary', 'lorem', 'Arsenal', 'Chelsea',
    'Liverpool', 'Manchester City', 'Real Madrid', 'Barcelona', 'Bayern', 'Gor Mahia',
    'AFC Leopards', 'Test Club Alpha', 'Tigers', 'cheeter'
];

$audit['keyword_matches'] = [];
$allTables = fetchAll("SHOW TABLES");
foreach ($allTables as $tRow) {
    $tableName = array_values($tRow)[0];
    $cols = fetchAll("DESCRIBE {$tableName}");
    $textCols = [];
    foreach ($cols as $c) {
        if (strpos($c['Type'], 'varchar') !== false || strpos($c['Type'], 'text') !== false || strpos($c['Type'], 'char') !== false) {
            $textCols[] = "`" . $c['Field'] . "`";
        }
    }
    if (empty($textCols)) continue;
    
    foreach ($suspiciousKeywords as $kw) {
        $whereClause = [];
        foreach ($textCols as $col) {
            $whereClause[] = "{$col} LIKE " . $pdo->quote("%{$kw}%");
        }
        $whereStr = implode(' OR ', $whereClause);
        $cnt = fetchOne("SELECT COUNT(*) as c FROM {$tableName} WHERE {$whereStr}")['c'];
        if ($cnt > 0) {
            $samples = fetchAll("SELECT * FROM {$tableName} WHERE {$whereStr} LIMIT 5");
            $audit['keyword_matches'][$tableName][$kw] = [
                'count' => $cnt,
                'samples' => $samples
            ];
        }
    }
}

file_put_contents(__DIR__ . '/audit_results.json', json_encode($audit, JSON_PRETTY_PRINT));
echo "Audit complete! Saved to scratch/audit_results.json\n";
