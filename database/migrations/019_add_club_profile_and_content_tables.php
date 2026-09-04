<?php

/**
 * Migration 019 — Add Club Profile Fields, Player & Staff Enhancements, and Content Management Tables
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Add Profile Fields to Organizations table if missing
        $orgCols = $pdo->query("SHOW COLUMNS FROM organizations LIKE 'logo_url'")->fetchAll();
        if (empty($orgCols)) {
            $pdo->exec("
                ALTER TABLE organizations
                ADD COLUMN logo_url VARCHAR(500) NULL AFTER name,
                ADD COLUMN cover_url VARCHAR(500) NULL AFTER logo_url,
                ADD COLUMN description TEXT NULL AFTER timezone,
                ADD COLUMN founded_year INT NULL AFTER description,
                ADD COLUMN club_colors VARCHAR(255) NULL AFTER founded_year,
                ADD COLUMN contact_email VARCHAR(255) NULL AFTER club_colors,
                ADD COLUMN contact_phone VARCHAR(100) NULL AFTER contact_email,
                ADD COLUMN address VARCHAR(255) NULL AFTER contact_phone,
                ADD COLUMN social_links LONGTEXT NULL AFTER address,
                ADD COLUMN featured_video_url VARCHAR(500) NULL AFTER social_links
            ");
        }

        // 2. Add Profile Fields to Players table if missing
        $playerCols = $pdo->query("SHOW COLUMNS FROM players LIKE 'photo_url'")->fetchAll();
        if (empty($playerCols)) {
            $pdo->exec("
                ALTER TABLE players
                ADD COLUMN photo_url VARCHAR(500) NULL AFTER display_name,
                ADD COLUMN nationality VARCHAR(100) NULL AFTER date_of_birth,
                ADD COLUMN preferred_foot VARCHAR(50) NULL AFTER nationality,
                ADD COLUMN emergency_contact TEXT NULL AFTER preferred_foot
            ");
        }

        // 3. Add Profile & Team Fields to Staff table if missing
        $staffCols = $pdo->query("SHOW COLUMNS FROM staff LIKE 'photo_url'")->fetchAll();
        if (empty($staffCols)) {
            $pdo->exec("
                ALTER TABLE staff
                ADD COLUMN photo_url VARCHAR(500) NULL AFTER role,
                ADD COLUMN team_id CHAR(26) NULL AFTER photo_url,
                ADD COLUMN email VARCHAR(255) NULL AFTER team_id,
                ADD COLUMN phone VARCHAR(100) NULL AFTER email,
                ADD COLUMN bio TEXT NULL AFTER phone,
                ADD COLUMN display_order INT NOT NULL DEFAULT 0 AFTER bio
            ");
        }

        // 4. Add Captain Flags to Roster Assignments table if missing
        $rosterCols = $pdo->query("SHOW COLUMNS FROM roster_assignments LIKE 'is_captain'")->fetchAll();
        if (empty($rosterCols)) {
            $pdo->exec("
                ALTER TABLE roster_assignments
                ADD COLUMN is_captain TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
                ADD COLUMN is_vice_captain TINYINT(1) NOT NULL DEFAULT 0 AFTER is_captain
            ");
        }

        // 5. Create news_articles Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS news_articles (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                category VARCHAR(100) NOT NULL DEFAULT 'General',
                excerpt TEXT NULL,
                content LONGTEXT NOT NULL,
                image_url VARCHAR(500) NULL,
                published_at DATETIME NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY fk_news_org (organization_id),
                CONSTRAINT fk_news_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. Create gallery_images Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS gallery_images (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                title VARCHAR(255) NULL,
                category VARCHAR(100) NOT NULL DEFAULT 'Club',
                image_url VARCHAR(500) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY fk_gallery_org (organization_id),
                CONSTRAINT fk_gallery_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 7. Create sponsors Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sponsors (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                name VARCHAR(255) NOT NULL,
                logo_url VARCHAR(500) NOT NULL,
                website_url VARCHAR(500) NULL,
                sponsor_level VARCHAR(100) NOT NULL DEFAULT 'Official Partner',
                display_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY fk_sponsors_org (organization_id),
                CONSTRAINT fk_sponsors_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
