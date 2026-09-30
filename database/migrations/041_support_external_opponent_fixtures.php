<?php

/**
 * Migration 041 — Support External Opponent Fixtures
 * 
 * Allows either the home or away side of a fixture to represent an external
 * opponent rather than requiring both sides to be internal Benchero teams.
 * 
 * - Makes home_team_id and away_team_id nullable.
 * - Adds home_opponent_name and away_opponent_name columns.
 * - Preserves existing foreign key constraints on teams(id).
 * - Preserves existing internal fixtures without data loss.
 */

return new class {
    public function up(\PDO $pdo): void
    {
        $hasColumn = function (\PDO $pdo, string $table, string $column): bool {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
            ");
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        // 1. Make home_team_id and away_team_id nullable
        $pdo->exec("ALTER TABLE fixtures MODIFY home_team_id CHAR(26) NULL DEFAULT NULL");
        $pdo->exec("ALTER TABLE fixtures MODIFY away_team_id CHAR(26) NULL DEFAULT NULL");

        // 2. Add home_opponent_name
        if (!$hasColumn($pdo, 'fixtures', 'home_opponent_name')) {
            $pdo->exec("ALTER TABLE fixtures ADD COLUMN home_opponent_name VARCHAR(255) NULL DEFAULT NULL AFTER home_team_id");
        }

        // 3. Add away_opponent_name
        if (!$hasColumn($pdo, 'fixtures', 'away_opponent_name')) {
            $pdo->exec("ALTER TABLE fixtures ADD COLUMN away_opponent_name VARCHAR(255) NULL DEFAULT NULL AFTER away_team_id");
        }
    }
};
