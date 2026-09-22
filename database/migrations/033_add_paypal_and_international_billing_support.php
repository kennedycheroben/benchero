<?php

/**
 * Migration 033 — Add PayPal and International Billing Support
 *
 * 1. Modifies payment_intents to allow null phone_number (for PayPal / Card international checkout)
 * 2. Adds payment_method column to payment_intents
 * 3. Adds base_amount, base_currency, exchange_rate, payer_email, payer_id to payment_intents and payments
 * 4. Seeds/ensures Plan 5: Benchero Pro Monthly (KSh 2,500 / month) with full Pro entitlements
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Modify payment_intents.phone_number to NULLABLE
        try {
            $pdo->exec("ALTER TABLE `payment_intents` MODIFY COLUMN `phone_number` VARCHAR(20) NULL DEFAULT NULL");
        } catch (\Throwable $e) {}

        // 2. Add payment_method to payment_intents if missing
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payment_intents` LIKE 'payment_method'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `payment_method` VARCHAR(50) NOT NULL DEFAULT 'mpesa' AFTER `provider`");
            }
        } catch (\Throwable $e) {}

        // 3. Add base_amount, base_currency, exchange_rate, payer_email to payment_intents
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payment_intents` LIKE 'base_amount'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `base_amount` DECIMAL(10,2) NULL DEFAULT NULL AFTER `amount`");
            }
        } catch (\Throwable $e) {}

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payment_intents` LIKE 'base_currency'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `base_currency` VARCHAR(3) NOT NULL DEFAULT 'KES' AFTER `currency`");
            }
        } catch (\Throwable $e) {}

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payment_intents` LIKE 'exchange_rate'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `exchange_rate` DECIMAL(10,4) NULL DEFAULT NULL AFTER `base_currency`");
            }
        } catch (\Throwable $e) {}

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payment_intents` LIKE 'payer_email'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `payer_email` VARCHAR(255) NULL DEFAULT NULL AFTER `phone_number`");
            }
        } catch (\Throwable $e) {}

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payment_intents` LIKE 'payer_id'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payment_intents` ADD COLUMN `payer_id` VARCHAR(100) NULL DEFAULT NULL AFTER `payer_email`");
            }
        } catch (\Throwable $e) {}

        // 4. Add base_amount, base_currency, exchange_rate, payer_email, payer_id to payments
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payments` LIKE 'base_amount'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payments` ADD COLUMN `base_amount` DECIMAL(10,2) NULL DEFAULT NULL AFTER `amount`");
            }
        } catch (\Throwable $e) {}

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payments` LIKE 'base_currency'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payments` ADD COLUMN `base_currency` VARCHAR(3) NOT NULL DEFAULT 'KES' AFTER `currency`");
            }
        } catch (\Throwable $e) {}

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payments` LIKE 'exchange_rate'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payments` ADD COLUMN `exchange_rate` DECIMAL(10,4) NULL DEFAULT NULL AFTER `base_currency`");
            }
        } catch (\Throwable $e) {}

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payments` LIKE 'payer_email'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payments` ADD COLUMN `payer_email` VARCHAR(255) NULL DEFAULT NULL AFTER `mpesa_receipt_number`");
            }
        } catch (\Throwable $e) {}

        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `payments` LIKE 'payer_id'")->fetch();
            if (!$cols) {
                $pdo->exec("ALTER TABLE `payments` ADD COLUMN `payer_id` VARCHAR(100) NULL DEFAULT NULL AFTER `payer_email`");
            }
        } catch (\Throwable $e) {}

        // 5. Ensure Plan 5 (Benchero Pro Monthly) exists with full Pro features and Plan 4 is Benchero Pro Yearly
        $proFeatures = json_encode([
            'player_limit' => 5000,
            'team_limit' => 100,
            'fixture_limit' => 10000,
            'video_storage_mb' => 2048,
            'total_storage_mb' => 5120,
            'custom_domain' => true,
            'video_uploads' => true,
            'advanced_media' => true,
            'advanced_website_customization' => true,
            'advanced_statistics' => true,
            'multiple_competitions' => true,
            'advanced_news' => true,
            'advanced_seo' => true,
            'custom_social_links' => true,
            'custom_og_image' => true,
            'qr_codes' => true,
            'digital_club_card' => true,
            'data_export' => true,
            'additional_admins' => true,
            'advanced_notifications' => true,
            'custom_branding' => true,
            'remove_benchero_branding' => true,
            'priority_support' => true,
            'description' => 'Build your club\'s complete digital presence'
        ]);

        // Check if plan 5 exists
        $stmt = $pdo->prepare("SELECT id FROM plans WHERE id = 5 OR slug = 'pro-monthly'");
        $stmt->execute();
        $plan5 = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($plan5) {
            $up5 = $pdo->prepare("
                UPDATE plans 
                SET name = 'Benchero Pro Monthly', slug = 'pro-monthly', price_kes = 2500.00, billing_interval = 'monthly', features = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $up5->execute([$proFeatures, $plan5['id']]);
        } else {
            $in5 = $pdo->prepare("
                INSERT INTO plans (id, name, slug, price_kes, billing_interval, features, created_at, updated_at)
                VALUES (5, 'Benchero Pro Monthly', 'pro-monthly', 2500.00, 'monthly', ?, NOW(), NOW())
            ");
            $in5->execute([$proFeatures]);
        }

        // Verify Plan 4 is Benchero Pro Yearly at 20000 KES
        $stmt4 = $pdo->prepare("SELECT id FROM plans WHERE id = 4");
        $stmt4->execute();
        if ($stmt4->fetch()) {
            $up4 = $pdo->prepare("
                UPDATE plans 
                SET name = 'Benchero Pro Yearly', slug = 'benchero-pro', price_kes = 20000.00, billing_interval = 'yearly', features = ?, updated_at = NOW()
                WHERE id = 4
            ");
            $up4->execute([$proFeatures]);
        }
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
    echo "Migration 033 executed successfully!\n";
}
