<?php

/**
 * Migration 021 — Standardize Database and Tables Collation to utf8mb4_unicode_ci
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Set collation for the active database
        $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
        if ($dbName) {
            $pdo->exec("ALTER DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        // 2. Convert all existing tables in the database to utf8mb4_unicode_ci
        $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            try {
                $pdo->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (\Exception $e) {
                // Ignore views or tables that fail alteration
            }
        }
    }
};
