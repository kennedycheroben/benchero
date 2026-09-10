<?php

/**
 * Migration 004 — Core Sports Entity Tables
 *
 * Creates: sports, teams, players, staff
 *
 * Refactored from original: each CREATE TABLE statement is now executed in its
 * own $pdo->exec() call. This prevents a single statement failure from silently
 * leaving the others un-executed, and avoids multi-statement exec() behaviour
 * that is unreliable when PDO::ATTR_EMULATE_PREPARES is false.
 *
 * The resulting schema is identical to the original migration — no column types,
 * constraints, or defaults were changed.
 */
return new class {
    public function up(\PDO $pdo): void
    {
        // sports: platform-level lookup table (not tenant-scoped)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                slug VARCHAR(100) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // teams: tenant-scoped to an organization, associated with a sport
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS teams (
                id CHAR(26) PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                sport_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                CONSTRAINT fk_team_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
                CONSTRAINT fk_team_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // players: tenant-scoped individual player records
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS players (
                id CHAR(26) PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                date_of_birth DATE NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                CONSTRAINT fk_player_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // staff: tenant-scoped staff/coaching records
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS staff (
                id CHAR(26) PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                role VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                CONSTRAINT fk_staff_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
};
