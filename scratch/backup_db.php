<?php
require_once __DIR__ . '/../app/bootstrap.php';
use Benchero\Core\Database\Database;

if (!is_dir(__DIR__ . '/../storage/backups')) {
    mkdir(__DIR__ . '/../storage/backups', 0755, true);
}

$pdo = Database::getConnection();
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$dump = "-- Benchero Production Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $tbl) {
    $create = $pdo->query("SHOW CREATE TABLE `{$tbl}`")->fetch(PDO::FETCH_NUM)[1];
    $dump .= "DROP TABLE IF EXISTS `{$tbl}`;\n{$create};\n\n";
    $rows = $pdo->query("SELECT * FROM `{$tbl}`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $cols = array_map(fn($c) => "`{$c}`", array_keys($r));
        $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), array_values($r));
        $dump .= "INSERT INTO `{$tbl}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
    }
    $dump .= "\n";
}

$backupPath = __DIR__ . '/../storage/backups/backup_before_cleanup.sql';
file_put_contents($backupPath, $dump);
echo "Backup created successfully at: {$backupPath} (" . filesize($backupPath) . " bytes)\n";
