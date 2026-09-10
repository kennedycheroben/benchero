<?php

/**
 * Migration 023 — Add Benchero Pro Plan and Centralized Capability Definitions
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Define complete capabilities and plan structures
        $plans = [
            [
                'id' => 1,
                'name' => 'Free Trial',
                'slug' => 'free-trial',
                'price_kes' => 0.00,
                'billing_interval' => 'trial',
                'features' => json_encode([
                    'player_limit' => 25,
                    'team_limit' => 2,
                    'fixture_limit' => 50,
                    'video_storage_mb' => 0,
                    'total_storage_mb' => 100,
                    'custom_domain' => false,
                    'video_uploads' => false,
                    'advanced_media' => false,
                    'advanced_website_customization' => false,
                    'advanced_statistics' => false,
                    'multiple_competitions' => false,
                    'advanced_news' => false,
                    'advanced_seo' => false,
                    'custom_social_links' => true,
                    'custom_og_image' => false,
                    'qr_codes' => false,
                    'digital_club_card' => false,
                    'data_export' => false,
                    'additional_admins' => false,
                    'advanced_notifications' => false,
                    'custom_branding' => false,
                    'remove_benchero_branding' => false,
                    'priority_support' => false,
                    'description' => 'Full-feature 14-day trial for new clubs'
                ])
            ],
            [
                'id' => 2,
                'name' => 'Standard Monthly',
                'slug' => 'standard-monthly',
                'price_kes' => 1000.00,
                'billing_interval' => 'monthly',
                'features' => json_encode([
                    'player_limit' => 100,
                    'team_limit' => 10,
                    'fixture_limit' => 500,
                    'video_storage_mb' => 0,
                    'total_storage_mb' => 500,
                    'custom_domain' => false,
                    'video_uploads' => false,
                    'advanced_media' => false,
                    'advanced_website_customization' => false,
                    'advanced_statistics' => false,
                    'multiple_competitions' => false,
                    'advanced_news' => false,
                    'advanced_seo' => false,
                    'custom_social_links' => true,
                    'custom_og_image' => false,
                    'qr_codes' => false,
                    'digital_club_card' => false,
                    'data_export' => false,
                    'additional_admins' => false,
                    'advanced_notifications' => false,
                    'custom_branding' => false,
                    'remove_benchero_branding' => false,
                    'priority_support' => false,
                    'description' => 'Essential club management and public website (KSh 1,000/mo)'
                ])
            ],
            [
                'id' => 3,
                'name' => 'Standard Yearly',
                'slug' => 'standard-yearly',
                'price_kes' => 10000.00,
                'billing_interval' => 'yearly',
                'features' => json_encode([
                    'player_limit' => 500,
                    'team_limit' => 25,
                    'fixture_limit' => 2000,
                    'video_storage_mb' => 0,
                    'total_storage_mb' => 1000,
                    'custom_domain' => false,
                    'video_uploads' => false,
                    'advanced_media' => false,
                    'advanced_website_customization' => false,
                    'advanced_statistics' => false,
                    'multiple_competitions' => false,
                    'advanced_news' => false,
                    'advanced_seo' => false,
                    'custom_social_links' => true,
                    'custom_og_image' => false,
                    'qr_codes' => false,
                    'digital_club_card' => false,
                    'data_export' => false,
                    'additional_admins' => false,
                    'advanced_notifications' => false,
                    'custom_branding' => false,
                    'remove_benchero_branding' => false,
                    'priority_support' => false,
                    'description' => 'Annual club management (KSh 10,000/yr — Save KSh 2,000)'
                ])
            ],
            [
                'id' => 4,
                'name' => 'Benchero Pro',
                'slug' => 'benchero-pro',
                'price_kes' => 20000.00,
                'billing_interval' => 'yearly',
                'features' => json_encode([
                    'player_limit' => 5000,
                    'team_limit' => 100,
                    'fixture_limit' => 10000,
                    'video_storage_mb' => 2048, // 2 GB controlled video storage
                    'total_storage_mb' => 5120, // 5 GB overall media capacity
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
                    'description' => 'Build your club\'s complete digital presence (KSh 20,000/yr)'
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
    }
};
