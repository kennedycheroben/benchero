<?php

/**
 * Migration 024 — Create custom_domains Table
 */

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS custom_domains (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                domain VARCHAR(255) NOT NULL,
                verification_token VARCHAR(100) NOT NULL,
                status ENUM('pending', 'verifying', 'verified', 'active', 'failed', 'disabled') NOT NULL DEFAULT 'pending',
                dns_records LONGTEXT NULL,
                verified_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_domain (domain),
                KEY fk_custom_domains_org (organization_id),
                CONSTRAINT fk_custom_domains_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
