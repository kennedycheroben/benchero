<?php

/**
 * Migration 012 — Create Seasons Table
 *
 * Creates the `seasons` table scoped by organization and sport.
 */

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS seasons (
                id CHAR(26) PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                sport_id CHAR(26) NOT NULL,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                starts_on DATE NOT NULL,
                ends_on DATE NOT NULL,
                is_current TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                CONSTRAINT fk_season_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
                CONSTRAINT fk_season_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE,
                UNIQUE KEY uq_season_slug_per_org_sport (organization_id, sport_id, slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
};
