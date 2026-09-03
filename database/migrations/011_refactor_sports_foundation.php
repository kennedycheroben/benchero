<?php

/**
 * Migration 011 — Refactor Sports Foundation
 *
 * - Alters `teams` to use CHAR(26) for `sport_id`.
 * - Recreates `sports` with CHAR(26) ULID primary key and additional metadata.
 * - Creates `organization_sports` pivot table.
 * - Seeds the global sports catalogue.
 */

use Benchero\Core\Ulid;

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Drop fk and modify teams
        $pdo->exec("ALTER TABLE teams DROP FOREIGN KEY fk_team_sport");
        $pdo->exec("ALTER TABLE teams MODIFY sport_id CHAR(26) NOT NULL");
        
        // 2. Recreate sports
        $pdo->exec("DROP TABLE IF EXISTS sports");
        $pdo->exec("
            CREATE TABLE sports (
                id CHAR(26) PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                slug VARCHAR(100) NOT NULL UNIQUE,
                description TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 3. Re-add fk on teams
        $pdo->exec("ALTER TABLE teams ADD CONSTRAINT fk_team_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE RESTRICT");

        // 4. Create organization_sports pivot table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS organization_sports (
                organization_id CHAR(26) NOT NULL,
                sport_id CHAR(26) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (organization_id, sport_id),
                CONSTRAINT fk_org_sport_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
                CONSTRAINT fk_org_sport_sport FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 5. Idempotent seeding of core sports
        $coreSports = [
            'football' => 'Football',
            'basketball' => 'Basketball',
            'volleyball' => 'Volleyball',
            'rugby' => 'Rugby'
        ];

        $stmtCheck = $pdo->prepare("SELECT id FROM sports WHERE slug = :slug");
        $stmtInsert = $pdo->prepare("INSERT INTO sports (id, name, slug) VALUES (:id, :name, :slug)");

        foreach ($coreSports as $slug => $name) {
            $stmtCheck->execute(['slug' => $slug]);
            if (!$stmtCheck->fetch()) {
                $stmtInsert->execute([
                    'id' => Ulid::generate(),
                    'name' => $name,
                    'slug' => $slug
                ]);
            }
        }
    }
};
