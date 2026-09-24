<?php

/**
 * Migration 037 — Enhance custom_domains Table for Production Multi-Tenant Lifecycle
 * Adds normalized_domain, granular lifecycle statuses, SSL tracking, audit timestamps, and indexes.
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Helper to check if column exists
        $hasColumn = function (\PDO $pdo, string $table, string $column): bool {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
            ");
            $stmt->execute([$table, $column]);
            return (bool)$stmt->fetchColumn();
        };

        // 2. Add normalized_domain column if missing
        if (!$hasColumn($pdo, 'custom_domains', 'normalized_domain')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN normalized_domain VARCHAR(255) NULL AFTER domain");
            // Backfill existing rows
            $pdo->exec("UPDATE custom_domains SET normalized_domain = LOWER(TRIM(TRAILING '.' FROM TRIM(domain))) WHERE normalized_domain IS NULL");
            $pdo->exec("ALTER TABLE custom_domains MODIFY COLUMN normalized_domain VARCHAR(255) NOT NULL");
        }

        // 3. Add verification_method column
        if (!$hasColumn($pdo, 'custom_domains', 'verification_method')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN verification_method VARCHAR(50) NOT NULL DEFAULT 'dns_txt' AFTER verification_token");
        }

        // 4. Add verification_status column
        if (!$hasColumn($pdo, 'custom_domains', 'verification_status')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN verification_status ENUM('pending', 'verified', 'failed') NOT NULL DEFAULT 'pending' AFTER verification_method");
            $pdo->exec("UPDATE custom_domains SET verification_status = IF(status = 'verified' OR status = 'active', 'verified', IF(status = 'failed', 'failed', 'pending'))");
        }

        // 5. Add activation_status column
        if (!$hasColumn($pdo, 'custom_domains', 'activation_status')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN activation_status ENUM('pending', 'active', 'disabled') NOT NULL DEFAULT 'pending' AFTER verification_status");
            $pdo->exec("UPDATE custom_domains SET activation_status = IF(status = 'active', 'active', IF(status = 'disabled', 'disabled', 'pending'))");
        }

        // 6. Add activated_at column
        if (!$hasColumn($pdo, 'custom_domains', 'activated_at')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN activated_at TIMESTAMP NULL DEFAULT NULL AFTER activation_status");
            $pdo->exec("UPDATE custom_domains SET activated_at = verified_at WHERE status = 'active'");
        }

        // 7. Add ssl_status column
        if (!$hasColumn($pdo, 'custom_domains', 'ssl_status')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN ssl_status ENUM('not_configured', 'pending', 'active', 'failed') NOT NULL DEFAULT 'not_configured' AFTER activated_at");
        }

        // 8. Add ssl_ready_at column
        if (!$hasColumn($pdo, 'custom_domains', 'ssl_ready_at')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN ssl_ready_at TIMESTAMP NULL DEFAULT NULL AFTER ssl_status");
        }

        // 9. Add last_verification_attempt column
        if (!$hasColumn($pdo, 'custom_domains', 'last_verification_attempt')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN last_verification_attempt TIMESTAMP NULL DEFAULT NULL AFTER ssl_ready_at");
        }

        // 10. Add last_verification_error column
        if (!$hasColumn($pdo, 'custom_domains', 'last_verification_error')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN last_verification_error TEXT NULL DEFAULT NULL AFTER last_verification_attempt");
        }

        // 11. Add is_primary column
        if (!$hasColumn($pdo, 'custom_domains', 'is_primary')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN is_primary TINYINT(1) NOT NULL DEFAULT 1 AFTER last_verification_error");
        }

        // 12. Add deleted_at column for soft removal / audit retention
        if (!$hasColumn($pdo, 'custom_domains', 'deleted_at')) {
            $pdo->exec("ALTER TABLE custom_domains ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at");
        }

        // 13. Ensure UNIQUE constraint on normalized_domain
        $hasIndex = function (\PDO $pdo, string $table, string $indexName): bool {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
            ");
            $stmt->execute([$table, $indexName]);
            return (bool)$stmt->fetchColumn();
        };

        if (!$hasIndex($pdo, 'custom_domains', 'uk_normalized_domain')) {
            $pdo->exec("ALTER TABLE custom_domains ADD UNIQUE KEY uk_normalized_domain (normalized_domain)");
        }

        if (!$hasIndex($pdo, 'custom_domains', 'idx_custom_domains_org_active')) {
            $pdo->exec("ALTER TABLE custom_domains ADD KEY idx_custom_domains_org_active (organization_id, activation_status, deleted_at)");
        }
    }
};
