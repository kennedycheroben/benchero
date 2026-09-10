<?php

/**
 * Migration 015 — Create Fixtures Table
 */

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS fixtures (
                id CHAR(26) PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                sport_id CHAR(26) NOT NULL,
                season_id CHAR(26) NOT NULL,
                home_team_id CHAR(26) NOT NULL,
                away_team_id CHAR(26) NOT NULL,
                scheduled_at DATETIME NOT NULL,
                venue_name VARCHAR(255) NULL,
                competition_type VARCHAR(50) NOT NULL DEFAULT 'league',
                competition_name VARCHAR(255) NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                CONSTRAINT fk_fixture_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
                CONSTRAINT fk_fixture_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE,
                CONSTRAINT fk_fixture_season FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
                CONSTRAINT fk_fixture_home_team FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE CASCADE,
                CONSTRAINT fk_fixture_away_team FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE CASCADE,
                CONSTRAINT chk_fixture_teams CHECK (home_team_id != away_team_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
};
