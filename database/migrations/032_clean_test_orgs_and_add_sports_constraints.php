<?php

return new class {
    public function up(PDO $pdo): void
    {
        // 1. Clean up test/demo organizations and their dependent records
        $testOrgSlugs = ['org-a', 'org-b', 'test-club-alpha', 'tigers', 'cheeter'];
        
        // Find test org IDs by slug patterns
        $stmt = $pdo->query("SELECT id FROM organizations WHERE slug IN ('org-a', 'org-b', 'test-club-alpha', 'tigers', 'cheeter') OR slug LIKE 'test-%'");
        $testOrgIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($testOrgIds)) {
            $inClause = implode(',', array_fill(0, count($testOrgIds), '?'));

            // Delete dependent records
            $pdo->prepare("DELETE FROM organization_user WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM subscriptions WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM payments WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM payment_intents WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM website_settings WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM custom_domains WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM contact_messages WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM audit_logs WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM fixtures WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM players WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM teams WHERE organization_id IN ($inClause)")->execute($testOrgIds);
            $pdo->prepare("DELETE FROM organizations WHERE id IN ($inClause)")->execute($testOrgIds);
        }

        // 2. Clean up test users
        $testEmails = ['a@a.com', 'b@b.com', 'valid@example.com'];
        $inEmails = implode(',', array_fill(0, count($testEmails), '?'));
        $pdo->prepare("DELETE FROM users WHERE email IN ($inEmails)")->execute($testEmails);

        // 3. Purge mock sports records
        $pdo->exec("DELETE FROM sports_matches WHERE provider = 'mock'");
        $pdo->exec("DELETE FROM sports_news WHERE provider = 'mock'");
        $pdo->exec("DELETE FROM sports_teams WHERE provider = 'mock'");
        $pdo->exec("DELETE FROM sports_sync_logs WHERE provider = 'mock'");

        // 4. Centralize authoritative plans
        // Ensure pro-monthly exists and standard/pro pricing is exact
        $proFeatures = json_encode([
            'player_limit' => -1,
            'team_limit' => -1,
            'fixture_limit' => -1,
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
            'description' => "Build your club's complete digital presence"
        ]);

        $pdo->exec("
            INSERT INTO plans (id, name, slug, price_kes, billing_interval, features)
            VALUES 
            (1, 'Free Trial', 'free-trial', 0.00, 'trial', '{\"player_limit\":25,\"team_limit\":2,\"fixture_limit\":50,\"total_storage_mb\":100,\"description\":\"Full-feature 14-day trial for new clubs\"}'),
            (2, 'Standard Monthly', 'standard-monthly', 1000.00, 'monthly', '{\"player_limit\":100,\"team_limit\":10,\"fixture_limit\":500,\"total_storage_mb\":500,\"description\":\"Essential club management (KSh 1,000\/mo)\"}'),
            (3, 'Standard Yearly', 'standard-yearly', 10000.00, 'yearly', '{\"player_limit\":500,\"team_limit\":25,\"fixture_limit\":2000,\"total_storage_mb\":1000,\"description\":\"Annual club management (KSh 10,000\/yr)\"}'),
            (4, 'Benchero Pro Monthly', 'pro-monthly', 2500.00, 'monthly', " . $pdo->quote($proFeatures) . "),
            (5, 'Benchero Pro Yearly', 'benchero-pro', 20000.00, 'yearly', " . $pdo->quote($proFeatures) . ")
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                price_kes = VALUES(price_kes),
                billing_interval = VALUES(billing_interval),
                features = VALUES(features)
        ");

        // 5. Add unique composite constraint to prevent cross-competition match duplication
        try {
            $pdo->exec("ALTER TABLE sports_matches ADD UNIQUE KEY unique_match_provider_ext (provider, external_id)");
        } catch (\PDOException $e) {
            // Index already exists
        }

        try {
            $pdo->exec("ALTER TABLE sports_competitions ADD UNIQUE KEY unique_comp_provider_slug (provider, slug)");
        } catch (\PDOException $e) {
            // Index already exists
        }

        try {
            $pdo->exec("ALTER TABLE sports_teams ADD UNIQUE KEY unique_team_provider_slug (provider, sport_id, slug)");
        } catch (\PDOException $e) {
            // Index already exists
        }

        try {
            $pdo->exec("ALTER TABLE sports_news ADD UNIQUE KEY unique_news_provider_ext (provider, external_id)");
        } catch (\PDOException $e) {
            // Index already exists
        }
    }

    public function down(PDO $pdo): void
    {
        // Non-reversible cleanup migration
    }
};
