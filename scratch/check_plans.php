<?php
require_once __DIR__ . '/../app/bootstrap.php';
use Benchero\Core\Database\Database;

$plans = Database::query("SELECT * FROM plans")->fetchAll();
foreach ($plans as $p) {
    echo "ID: {$p['id']} | Name: {$p['name']} | Slug: {$p['slug']} | Monthly: {$p['price_monthly']} | Yearly: {$p['price_yearly']} | Max Teams: {$p['max_teams']} | Max Players: {$p['max_players']}\n";
}
