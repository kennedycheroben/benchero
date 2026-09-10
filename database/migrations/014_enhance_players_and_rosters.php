<?php

/**
 * Migration 014 — Enhance Players and Add Rosters
 */
return new class {
    public function up(\PDO $pdo): void
    {
        $dbName = env('DB_DATABASE', 'benchero_dev');

        if (!$this->columnExists($pdo, $dbName, 'players', 'sport_id')) {
            $pdo->exec("
                ALTER TABLE players 
                ADD COLUMN sport_id CHAR(26) NOT NULL AFTER organization_id,
                ADD CONSTRAINT fk_player_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE RESTRICT
            ");
        }

        if (!$this->columnExists($pdo, $dbName, 'players', 'display_name')) {
            $pdo->exec("
                ALTER TABLE players 
                ADD COLUMN display_name VARCHAR(100) NULL AFTER last_name
            ");
        }

        if (!$this->columnExists($pdo, $dbName, 'players', 'bio')) {
            $pdo->exec("
                ALTER TABLE players 
                ADD COLUMN bio TEXT NULL AFTER display_name
            ");
        }

        if (!$this->columnExists($pdo, $dbName, 'players', 'is_active')) {
            $pdo->exec("
                ALTER TABLE players 
                ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER bio
            ");
        }
        
        if (!$this->indexExists($pdo, $dbName, 'players', 'idx_player_org_sport')) {
            $pdo->exec("
                ALTER TABLE players ADD INDEX idx_player_org_sport (organization_id, sport_id)
            ");
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS roster_assignments (
                id CHAR(26) PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                sport_id CHAR(26) NOT NULL,
                player_id CHAR(26) NOT NULL,
                team_id CHAR(26) NOT NULL,
                season_id CHAR(26) NOT NULL,
                jersey_number VARCHAR(10) NULL,
                position VARCHAR(100) NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'active',
                joined_at DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                CONSTRAINT fk_roster_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
                CONSTRAINT fk_roster_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE RESTRICT,
                CONSTRAINT fk_roster_player FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
                CONSTRAINT fk_roster_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
                CONSTRAINT fk_roster_season FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
                CONSTRAINT uq_roster_assignment UNIQUE (player_id, team_id, season_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function columnExists(\PDO $pdo, string $schema, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ');
        $stmt->execute([$schema, $table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function indexExists(\PDO $pdo, string $schema, string $table, string $indexName): bool
    {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
        ');
        $stmt->execute([$schema, $table, $indexName]);
        return (int) $stmt->fetchColumn() > 0;
    }
};
