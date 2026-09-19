<?php
$data = json_decode(file_get_contents(__DIR__ . '/audit_results.json'), true);

echo "=== ORGANIZATIONS (" . count($data['organizations']) . ") ===\n";
foreach ($data['organizations'] as $o) {
    echo "ID: {$o['id']} | Name: {$o['name']} | Slug: {$o['slug']} | Users: {$o['users_count']} | Teams: {$o['teams_count']} | Players: {$o['players_count']}\n";
    if (!empty($o['latest_subscription'])) {
        $sub = $o['latest_subscription'];
        echo "  Sub Plan: {$sub['plan_name']} ({$sub['plan_slug']}) | Status: {$sub['status']}\n";
    }
}

echo "\n=== SPORTS TABLES COUNTS ===\n";
foreach ($data['sports_tables'] as $tbl => $info) {
    $countStr = isset($info['count']) ? $info['count'] : ($info['error'] ?? '0');
    echo "{$tbl}: {$countStr}\n";
}

echo "\n=== PLANS & PRICING ===\n";
if (is_array($data['plans'])) {
    foreach ($data['plans'] as $p) {
        echo "{$p['name']} ({$p['slug']}) - Monthly: KSh {$p['price_monthly']} | Yearly: KSh {$p['price_yearly']} | Player limit: {$p['max_players']}\n";
    }
}

echo "\n=== KEYWORD MATCHES IN DB ===\n";
if (!empty($data['keyword_matches'])) {
    foreach ($data['keyword_matches'] as $tbl => $matches) {
        echo "Table: {$tbl}\n";
        foreach ($matches as $kw => $m) {
            echo "  Keyword '{$kw}': {$m['count']} matches\n";
            foreach ($m['samples'] as $sample) {
                echo "    Sample: " . json_encode($sample) . "\n";
            }
        }
    }
}
