<?php
require_once __DIR__ . '/../app/bootstrap.php';
use Benchero\Core\Database\Database;

$cols = Database::query("DESCRIBE plans")->fetchAll();
print_r($cols);

$plans = Database::query("SELECT * FROM plans")->fetchAll();
print_r($plans);
