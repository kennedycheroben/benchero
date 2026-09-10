<?php

/**
 * Migration 016 — Add Results Fields to Fixtures & Platform Admin to Users
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // Add results columns to fixtures table if not existing
        $columns = $pdo->query("SHOW COLUMNS FROM fixtures LIKE 'home_score'")->fetchAll();
        if (empty($columns)) {
            $pdo->exec("
                ALTER TABLE fixtures
                ADD COLUMN home_score INT NULL AFTER status,
                ADD COLUMN away_score INT NULL AFTER home_score,
                ADD COLUMN result_notes TEXT NULL AFTER away_score,
                ADD COLUMN completed_at DATETIME NULL AFTER result_notes
            ");
        }

        // Add is_platform_admin column to users table if not existing
        $userCols = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_platform_admin'")->fetchAll();
        if (empty($userCols)) {
            $pdo->exec("
                ALTER TABLE users
                ADD COLUMN is_platform_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER name
            ");
        }
    }
};
