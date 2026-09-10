<?php

/**
 * Migration 020 — Add Website Settings, Themes, Homepage Sections, History, Gallery Albums, Media, and Club Pages
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Create website_settings Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS website_settings (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL UNIQUE,
                theme_id VARCHAR(50) NOT NULL DEFAULT 'modern_sport',
                primary_color VARCHAR(20) NOT NULL DEFAULT '#0d6efd',
                secondary_color VARCHAR(20) NOT NULL DEFAULT '#1e293b',
                accent_color VARCHAR(20) NOT NULL DEFAULT '#ffc107',
                text_color VARCHAR(20) NOT NULL DEFAULT '#0f172a',
                header_style VARCHAR(50) NOT NULL DEFAULT 'dark',
                button_style VARCHAR(50) NOT NULL DEFAULT 'pill',
                hero_title VARCHAR(255) NULL,
                hero_subtitle TEXT NULL,
                hero_cta_text VARCHAR(100) NULL,
                hero_cta_url VARCHAR(255) NULL,
                hero_image_url VARCHAR(500) NULL,
                page_visibility LONGTEXT NULL,
                navigation_order LONGTEXT NULL,
                navigation_labels LONGTEXT NULL,
                footer_text TEXT NULL,
                copyright_text VARCHAR(255) NULL,
                show_sponsors_in_footer TINYINT(1) NOT NULL DEFAULT 1,
                map_link VARCHAR(500) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY fk_website_settings_org (organization_id),
                CONSTRAINT fk_website_settings_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Create homepage_sections Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS homepage_sections (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                section_type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NULL,
                subtitle VARCHAR(255) NULL,
                display_order INT NOT NULL DEFAULT 0,
                is_visible TINYINT(1) NOT NULL DEFAULT 1,
                configuration LONGTEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY fk_homepage_sections_org (organization_id),
                CONSTRAINT fk_homepage_sections_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Create club_history Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS club_history (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                year_date VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                image_url VARCHAR(500) NULL,
                category VARCHAR(100) NOT NULL DEFAULT 'Milestone',
                display_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY fk_history_org (organization_id),
                CONSTRAINT fk_history_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. Create gallery_albums Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS gallery_albums (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                description TEXT NULL,
                category VARCHAR(100) NOT NULL DEFAULT 'Team',
                cover_image_url VARCHAR(500) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY fk_gallery_albums_org (organization_id),
                CONSTRAINT fk_gallery_albums_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. Add album_id to gallery_images if missing
        $galleryCols = $pdo->query("SHOW COLUMNS FROM gallery_images LIKE 'album_id'")->fetchAll();
        if (empty($galleryCols)) {
            $pdo->exec("
                ALTER TABLE gallery_images
                ADD COLUMN album_id CHAR(26) NULL AFTER organization_id,
                ADD CONSTRAINT fk_gallery_images_album FOREIGN KEY (album_id) REFERENCES gallery_albums (id) ON DELETE SET NULL
            ");
        }

        // 6. Create media Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS media (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_url VARCHAR(500) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                file_size INT NOT NULL,
                alt_text VARCHAR(255) NULL,
                caption VARCHAR(255) NULL,
                category VARCHAR(100) NOT NULL DEFAULT 'general',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY fk_media_org (organization_id),
                CONSTRAINT fk_media_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 7. Create club_pages Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS club_pages (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                seo_title VARCHAR(255) NULL,
                seo_description TEXT NULL,
                featured_image VARCHAR(500) NULL,
                content LONGTEXT NULL,
                is_published TINYINT(1) NOT NULL DEFAULT 1,
                in_navigation TINYINT(1) NOT NULL DEFAULT 1,
                display_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY fk_pages_org (organization_id),
                CONSTRAINT fk_pages_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
