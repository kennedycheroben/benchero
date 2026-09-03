<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;

try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT slug, id FROM organizations");
    $orgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total orgs: " . count($orgs) . "\n";
    foreach ($orgs as $org) {
        echo "- " . $org['slug'] . "\n";
    }
} catch (\Throwable $e) {
    echo "EXCEPTION CAUGHT:\n";
    echo $e->getMessage() . "\n";
}
