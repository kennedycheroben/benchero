<?php

/**
 * Migration 005 — Billing Tables
 *
 * Creates: plans, subscriptions, payments
 *
 * Refactored from original: each CREATE TABLE statement is now executed in its
 * own $pdo->exec() call, consistent with the refactored migration 004 and for
 * the same reliability reason (PDO::ATTR_EMULATE_PREPARES => false).
 *
 * The resulting schema is identical to the original migration — no column types,
 * constraints, or defaults were changed. Financial integrity FK corrections
 * (CASCADE → RESTRICT) are applied in migration 007 rather than here, to
 * preserve the integrity of the migration history.
 */
return new class {
    public function up(\PDO $pdo): void
    {
        // plans: platform-level subscription tiers
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS plans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL UNIQUE,
                price_kes DECIMAL(10, 2) NOT NULL,
                features JSON NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // subscriptions: one active subscription per organization
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS subscriptions (
                id CHAR(26) PRIMARY KEY,
                organization_id CHAR(26) NOT NULL UNIQUE,
                plan_id INT NOT NULL,
                status ENUM('active', 'past_due', 'canceled', 'trialing') NOT NULL DEFAULT 'trialing',
                trial_ends_at TIMESTAMP NULL DEFAULT NULL,
                current_period_end TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_sub_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
                CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // payments: payment records (M-Pesa and future gateways)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payments (
                id CHAR(26) PRIMARY KEY,
                organization_id CHAR(26) NOT NULL,
                subscription_id CHAR(26) NULL DEFAULT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                currency VARCHAR(3) NOT NULL DEFAULT 'KES',
                status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
                mpesa_receipt_number VARCHAR(100) NULL DEFAULT NULL,
                metadata JSON NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_payment_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
                CONSTRAINT fk_payment_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
};
