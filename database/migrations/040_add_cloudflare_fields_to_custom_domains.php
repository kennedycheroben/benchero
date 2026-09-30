<?php

/**
 * Migration 040 — Add Cloudflare for SaaS Fields to custom_domains Table
 * Adds Cloudflare Custom Hostname ID, lifecycle status, SSL status, tracking timestamps, and index.
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

        $hasIndex = function (\PDO $pdo, string $table, string $indexName): bool {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
            ");
            $stmt->execute([$table, $indexName]);
            return (bool)$stmt->fetchColumn();
        };

        // 1. cloudflare_custom_hostname_id
        if (!$hasColumn($pdo, 'custom_domains', 'cloudflare_custom_hostname_id')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN cloudflare_custom_hostname_id VARCHAR(50) NULL DEFAULT NULL AFTER is_primary");
        }

        // 2. cloudflare_status
        if (!$hasColumn($pdo, 'custom_domains', 'cloudflare_status')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN cloudflare_status VARCHAR(50) NULL DEFAULT 'pending' AFTER cloudflare_custom_hostname_id");
        }

        // 3. cloudflare_ssl_status
        if (!$hasColumn($pdo, 'custom_domains', 'cloudflare_ssl_status')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN cloudflare_ssl_status VARCHAR(50) NULL DEFAULT 'pending' AFTER cloudflare_status");
        }

        // 4. cloudflare_last_checked_at
        if (!$hasColumn($pdo, 'custom_domains', 'cloudflare_last_checked_at')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN cloudflare_last_checked_at TIMESTAMP NULL DEFAULT NULL AFTER cloudflare_ssl_status");
        }

        // 5. cloudflare_last_error
        if (!$hasColumn($pdo, 'custom_domains', 'cloudflare_last_error')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN cloudflare_last_error TEXT NULL DEFAULT NULL AFTER cloudflare_last_checked_at");
        }

        // 6. cloudflare_created_at
        if (!$hasColumn($pdo, 'custom_domains', 'cloudflare_created_at')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN cloudflare_created_at TIMESTAMP NULL DEFAULT NULL AFTER cloudflare_last_error");
        }

        // 7. Index on cloudflare_custom_hostname_id
        if (!$hasIndex($pdo, 'custom_domains', 'idx_custom_domains_cf_id')) {
            $pdo->exec("ALTER TABLE custom_domains ADD KEY idx_custom_domains_cf_id (cloudflare_custom_hostname_id)");
        }
    }
};
