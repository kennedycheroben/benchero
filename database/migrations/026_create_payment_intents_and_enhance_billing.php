<?php

/**
 * Migration 026 — Create Payment Intents Table and Enhance Billing Schemas
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Create payment_intents table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `payment_intents` (
                `id` CHAR(26) NOT NULL,
                `organization_id` CHAR(26) NOT NULL,
                `user_id` CHAR(26) DEFAULT NULL,
                `plan_id` INT NOT NULL,
                `payment_intent_id` VARCHAR(64) NOT NULL,
                `reference` VARCHAR(64) DEFAULT NULL,
                `amount` DECIMAL(10,2) NOT NULL,
                `currency` VARCHAR(3) NOT NULL DEFAULT 'KES',
                `phone_number` VARCHAR(20) NOT NULL,
                `provider` VARCHAR(50) NOT NULL DEFAULT 'imbank',
                `provider_reference` VARCHAR(100) DEFAULT NULL,
                `status` ENUM('pending', 'initiated', 'completed', 'failed', 'cancelled', 'expired') NOT NULL DEFAULT 'pending',
                `result_code` INT DEFAULT NULL,
                `result_desc` VARCHAR(255) DEFAULT NULL,
                `mpesa_receipt_number` VARCHAR(50) DEFAULT NULL,
                `failure_reason` VARCHAR(255) DEFAULT NULL,
                `expires_at` DATETIME DEFAULT NULL,
                `metadata` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_pi_intent_id` (`payment_intent_id`),
                KEY `idx_pi_org` (`organization_id`),
                KEY `idx_pi_user` (`user_id`),
                KEY `idx_pi_plan` (`plan_id`),
                KEY `idx_pi_provider_ref` (`provider_reference`),
                KEY `idx_pi_status` (`status`),
                CONSTRAINT `fk_pi_org` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_pi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_pi_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        try {
            $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `payment_intent_id` VARCHAR(64) NOT NULL AFTER `plan_id`");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `result_code` INT DEFAULT NULL AFTER `status`");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `result_desc` VARCHAR(255) DEFAULT NULL AFTER `result_code`");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `mpesa_receipt_number` VARCHAR(50) DEFAULT NULL AFTER `result_desc`");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE `payment_intents` MODIFY COLUMN `expires_at` DATETIME DEFAULT NULL");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE `payment_intents` MODIFY COLUMN `user_id` CHAR(26) DEFAULT NULL");
        } catch (\Throwable $e) {}

        // 2. Enhance payments table columns if needed
        try {
            $pdo->exec("ALTER TABLE `payments` ADD COLUMN `payment_intent_id` CHAR(26) DEFAULT NULL AFTER `subscription_id`");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE `payments` ADD COLUMN `provider` VARCHAR(50) NOT NULL DEFAULT 'imbank' AFTER `status`");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE `payments` ADD COLUMN `provider_reference` VARCHAR(100) DEFAULT NULL AFTER `mpesa_receipt_number`");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE `payments` ADD CONSTRAINT `uq_provider_ref` UNIQUE (`provider_reference`)");
        } catch (\Throwable $e) {}

        try {
            $pdo->exec("ALTER TABLE `payments` ADD CONSTRAINT `fk_payment_intent` FOREIGN KEY (`payment_intent_id`) REFERENCES `payment_intents` (`id`) ON DELETE SET NULL");
        } catch (\Throwable $e) {}
    }
};

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../../app/Core/helpers.php';
    $pdo = \Benchero\Core\Database\Database::getConnection();
    $class = require __FILE__;
    if (is_object($class) && method_exists($class, 'up')) {
        $class->up($pdo);
    }
    echo "Migration 026 executed successfully!\n";
}

