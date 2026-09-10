<?php

/**
 * Migration 018 — Update Plans and Subscriptions Schema & Seeding
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Add billing_interval to plans table if not existing
        $stmt = $pdo->query("SHOW COLUMNS FROM plans LIKE 'billing_interval'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE plans ADD COLUMN billing_interval ENUM('trial', 'monthly', 'yearly') NOT NULL DEFAULT 'monthly' AFTER price_kes");
        }

        // 2. Update or insert Benchero subscription plans
        $plans = [
            [
                'id' => 1,
                'name' => 'Free Trial',
                'slug' => 'free-trial',
                'price_kes' => 0.00,
                'billing_interval' => 'trial',
                'features' => json_encode([
                    'sports_management' => true,
                    'player_limits' => 25,
                    'teams_limit' => 2,
                    'fixtures_limit' => 50,
                    'public_page' => true,
                    'description' => 'Full feature trial for new clubs'
                ])
            ],
            [
                'id' => 2,
                'name' => 'Monthly Plan',
                'slug' => 'monthly',
                'price_kes' => 1000.00,
                'billing_interval' => 'monthly',
                'features' => json_encode([
                    'sports_management' => true,
                    'player_limits' => 100,
                    'teams_limit' => 10,
                    'fixtures_limit' => 500,
                    'public_page' => true,
                    'support' => 'email',
                    'description' => 'KSh 1,000 per month'
                ])
            ],
            [
                'id' => 3,
                'name' => 'Yearly Plan',
                'slug' => 'yearly',
                'price_kes' => 10000.00,
                'billing_interval' => 'yearly',
                'features' => json_encode([
                    'sports_management' => true,
                    'player_limits' => 500,
                    'teams_limit' => 25,
                    'fixtures_limit' => 2000,
                    'public_page' => true,
                    'savings_kes' => 2000,
                    'support' => 'priority',
                    'description' => 'KSh 10,000 per year (Save KSh 2,000)'
                ])
            ]
        ];

        foreach ($plans as $plan) {
            $stmt = $pdo->prepare("SELECT id FROM plans WHERE id = ?");
            $stmt->execute([$plan['id']]);
            if ($stmt->fetch()) {
                $update = $pdo->prepare("
                    UPDATE plans 
                    SET name = ?, slug = ?, price_kes = ?, billing_interval = ?, features = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $update->execute([$plan['name'], $plan['slug'], $plan['price_kes'], $plan['billing_interval'], $plan['features'], $plan['id']]);
            } else {
                $insert = $pdo->prepare("
                    INSERT INTO plans (id, name, slug, price_kes, billing_interval, features, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $insert->execute([$plan['id'], $plan['name'], $plan['slug'], $plan['price_kes'], $plan['billing_interval'], $plan['features']]);
            }
        }

        // 3. Update subscriptions table schema
        $pdo->exec("
            ALTER TABLE subscriptions 
            MODIFY COLUMN status ENUM('trialing', 'active', 'expired', 'canceled', 'past_due') NOT NULL DEFAULT 'trialing',
            MODIFY COLUMN billing_interval VARCHAR(50) NOT NULL DEFAULT 'monthly'
        ");

        $cols = $pdo->query("SHOW COLUMNS FROM subscriptions")->fetchAll(\PDO::FETCH_COLUMN);

        if (!in_array('billing_interval', $cols)) {
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN billing_interval ENUM('trial', 'monthly', 'yearly') NOT NULL DEFAULT 'trial' AFTER plan_id");
        }

        if (!in_array('starts_at', $cols)) {
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN starts_at TIMESTAMP NULL DEFAULT NULL AFTER status");
        }

        if (!in_array('expires_at', $cols)) {
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN expires_at TIMESTAMP NULL DEFAULT NULL AFTER starts_at");
        }

        if (!in_array('payment_reference', $cols)) {
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN payment_reference VARCHAR(100) NULL DEFAULT NULL AFTER expires_at");
        }

        if (!in_array('provider', $cols)) {
            $pdo->exec("ALTER TABLE subscriptions ADD COLUMN provider VARCHAR(50) NULL DEFAULT 'mpesa' AFTER payment_reference");
        }

        // Backfill starts_at and expires_at for existing subscriptions
        $pdo->exec("
            UPDATE subscriptions
            SET starts_at = COALESCE(starts_at, created_at),
                expires_at = COALESCE(expires_at, current_period_end, trial_ends_at)
            WHERE expires_at IS NULL
        ");

        // Add indexes for performance
        try {
            $pdo->exec("CREATE INDEX idx_subscriptions_status ON subscriptions(status)");
        } catch (\Exception $e) {}

        try {
            $pdo->exec("CREATE INDEX idx_subscriptions_expires_at ON subscriptions(expires_at)");
        } catch (\Exception $e) {}

        try {
            $pdo->exec("CREATE INDEX idx_subscriptions_payment_ref ON subscriptions(payment_reference)");
        } catch (\Exception $e) {}
    }
};
