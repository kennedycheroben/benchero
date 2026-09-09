<?php

/**
 * Migration 025 — Add Social Media Profiles, Custom OG Image, and Branding Control Fields to Organizations Table
 */

return new class {
    public function up(\PDO $pdo): void
    {
        $cols = $pdo->query("SHOW COLUMNS FROM organizations")->fetchAll(\PDO::FETCH_COLUMN);

        if (!in_array('tiktok_url', $cols)) {
            $pdo->exec("ALTER TABLE organizations ADD COLUMN tiktok_url VARCHAR(500) NULL AFTER social_links");
        }
        if (!in_array('whatsapp_number', $cols)) {
            $pdo->exec("ALTER TABLE organizations ADD COLUMN whatsapp_number VARCHAR(50) NULL AFTER tiktok_url");
        }
        if (!in_array('telegram_url', $cols)) {
            $pdo->exec("ALTER TABLE organizations ADD COLUMN telegram_url VARCHAR(500) NULL AFTER whatsapp_number");
        }
        if (!in_array('youtube_url', $cols)) {
            $pdo->exec("ALTER TABLE organizations ADD COLUMN youtube_url VARCHAR(500) NULL AFTER telegram_url");
        }
        if (!in_array('og_image_url', $cols)) {
            $pdo->exec("ALTER TABLE organizations ADD COLUMN og_image_url VARCHAR(500) NULL AFTER youtube_url");
        }
        if (!in_array('hide_benchero_branding', $cols)) {
            $pdo->exec("ALTER TABLE organizations ADD COLUMN hide_benchero_branding TINYINT(1) NOT NULL DEFAULT 0 AFTER og_image_url");
        }
    }
};
