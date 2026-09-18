<?php

/**
 * Migration 030 — Create Sports Platform Tables
 *
 * Creates normalized tables for public Benchero Sports feature:
 * - sports_competitions
 * - sports_teams
 * - sports_matches
 * - sports_standings
 * - sports_news
 * - sports_sync_logs
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. sports_competitions
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sports_competitions (
                id CHAR(26) PRIMARY KEY,
                sport_id CHAR(26) NOT NULL,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(150) NOT NULL UNIQUE,
                country VARCHAR(100) DEFAULT 'International',
                logo VARCHAR(255) NULL,
                is_featured TINYINT(1) DEFAULT 0,
                external_id VARCHAR(100) NULL,
                provider VARCHAR(50) DEFAULT 'mock',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_comp_sport (sport_id),
                INDEX idx_comp_slug (slug),
                INDEX idx_comp_provider_ext (provider, external_id),
                CONSTRAINT fk_sports_comp_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. sports_teams
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sports_teams (
                id CHAR(26) PRIMARY KEY,
                sport_id CHAR(26) NOT NULL,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(150) NOT NULL,
                short_code VARCHAR(10) NULL,
                logo VARCHAR(255) NULL,
                country VARCHAR(100) DEFAULT 'International',
                external_id VARCHAR(100) NULL,
                provider VARCHAR(50) DEFAULT 'mock',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_steam_sport (sport_id),
                INDEX idx_steam_slug (slug),
                INDEX idx_steam_provider_ext (provider, external_id),
                CONSTRAINT fk_sports_team_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. sports_matches
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sports_matches (
                id CHAR(26) PRIMARY KEY,
                sport_id CHAR(26) NOT NULL,
                competition_id CHAR(26) NOT NULL,
                home_team_id CHAR(26) NOT NULL,
                away_team_id CHAR(26) NOT NULL,
                home_score INT NULL,
                away_score INT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'NS', -- NS, LIVE, HT, FT, POSTPONED, CANCELLED, SUSPENDED
                minute VARCHAR(20) NULL, -- e.g. 78', HT, 90+3'
                start_time DATETIME NOT NULL,
                venue VARCHAR(150) NULL,
                round VARCHAR(100) NULL,
                external_id VARCHAR(100) NULL,
                provider VARCHAR(50) DEFAULT 'mock',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_smatch_status (status),
                INDEX idx_smatch_start (start_time),
                INDEX idx_smatch_comp (competition_id),
                INDEX idx_smatch_sport (sport_id),
                INDEX idx_smatch_provider_ext (provider, external_id),
                CONSTRAINT fk_smatch_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE,
                CONSTRAINT fk_smatch_comp FOREIGN KEY (competition_id) REFERENCES sports_competitions(id) ON DELETE CASCADE,
                CONSTRAINT fk_smatch_home FOREIGN KEY (home_team_id) REFERENCES sports_teams(id) ON DELETE CASCADE,
                CONSTRAINT fk_smatch_away FOREIGN KEY (away_team_id) REFERENCES sports_teams(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. sports_standings
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sports_standings (
                id CHAR(26) PRIMARY KEY,
                competition_id CHAR(26) NOT NULL,
                team_id CHAR(26) NOT NULL,
                position INT NOT NULL,
                played INT DEFAULT 0,
                won INT DEFAULT 0,
                drawn INT DEFAULT 0,
                lost INT DEFAULT 0,
                points INT DEFAULT 0,
                goals_for INT DEFAULT 0,
                goals_against INT DEFAULT 0,
                goal_difference INT DEFAULT 0,
                group_name VARCHAR(50) DEFAULT 'Main',
                season VARCHAR(20) DEFAULT '2025/2026',
                provider VARCHAR(50) DEFAULT 'mock',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_sstand_comp (competition_id),
                INDEX idx_sstand_pos (competition_id, position),
                CONSTRAINT fk_sstand_comp FOREIGN KEY (competition_id) REFERENCES sports_competitions(id) ON DELETE CASCADE,
                CONSTRAINT fk_sstand_team FOREIGN KEY (team_id) REFERENCES sports_teams(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. sports_news
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sports_news (
                id CHAR(26) PRIMARY KEY,
                sport_id CHAR(26) NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                summary TEXT NOT NULL,
                content TEXT NULL,
                source VARCHAR(100) NOT NULL DEFAULT 'Benchero Sports',
                source_url VARCHAR(500) NULL,
                image_url VARCHAR(500) NULL,
                category VARCHAR(50) DEFAULT 'Football',
                external_id VARCHAR(100) NULL,
                provider VARCHAR(50) DEFAULT 'mock',
                published_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_snews_published (published_at),
                INDEX idx_snews_category (category),
                INDEX idx_snews_slug (slug),
                CONSTRAINT fk_snews_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. sports_sync_logs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sports_sync_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                provider VARCHAR(50) NOT NULL,
                operation VARCHAR(50) NOT NULL,
                status VARCHAR(20) NOT NULL, -- success, error, warning
                duration_ms INT DEFAULT 0,
                records_processed INT DEFAULT 0,
                records_updated INT DEFAULT 0,
                error_message TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ssync_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
