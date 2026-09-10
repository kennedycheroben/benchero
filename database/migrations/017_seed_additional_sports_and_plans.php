<?php

/**
 * Migration 017 — Seed Additional Sports & Subscription Plans
 */

use Benchero\Core\Ulid;

return new class {
    public function up(\PDO $pdo): void
    {
        $initialSports = [
            ['name' => 'Football', 'slug' => 'football', 'description' => 'Association Football / Soccer'],
            ['name' => 'Basketball', 'slug' => 'basketball', 'description' => 'Standard 5v5 and 3x3 Basketball'],
            ['name' => 'Volleyball', 'slug' => 'volleyball', 'description' => 'Indoor & Beach Volleyball'],
            ['name' => 'Rugby', 'slug' => 'rugby', 'description' => 'Rugby Union & Rugby Sevens'],
        ];

        foreach ($initialSports as $sport) {
            $stmt = $pdo->prepare("SELECT id FROM sports WHERE slug = ?");
            $stmt->execute([$sport['slug']]);
            if (!$stmt->fetch()) {
                $id = Ulid::generate();
                $insert = $pdo->prepare("INSERT INTO sports (id, name, slug, description, is_active) VALUES (?, ?, ?, ?, 1)");
                $insert->execute([$id, $sport['name'], $sport['slug'], $sport['description']]);
            }
        }

        // Seed Plans
        $plans = [
            [
                'id' => 1,
                'name' => 'Free Trial',
                'slug' => 'free-trial',
                'price_kes' => 0.00,
                'features' => json_encode(['sports_management' => true, 'player_limits' => 25, 'teams_limit' => 2, 'fixtures_limit' => 50, 'public_page' => true])
            ],
            [
                'id' => 2,
                'name' => 'Starter Plan',
                'slug' => 'starter',
                'price_kes' => 2500.00,
                'features' => json_encode(['sports_management' => true, 'player_limits' => 100, 'teams_limit' => 5, 'fixtures_limit' => 200, 'public_page' => true, 'support' => 'email'])
            ],
            [
                'id' => 3,
                'name' => 'Pro Plan',
                'slug' => 'pro',
                'price_kes' => 5000.00,
                'features' => json_encode(['sports_management' => true, 'player_limits' => 500, 'teams_limit' => 20, 'fixtures_limit' => 1000, 'public_page' => true, 'support' => 'priority'])
            ]
        ];

        foreach ($plans as $plan) {
            $stmt = $pdo->prepare("SELECT id FROM plans WHERE id = ? OR slug = ?");
            $stmt->execute([$plan['id'], $plan['slug']]);
            if (!$stmt->fetch()) {
                $insert = $pdo->prepare("INSERT INTO plans (id, name, slug, price_kes, features) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$plan['id'], $plan['name'], $plan['slug'], $plan['price_kes'], $plan['features']]);
            }
        }
    }
};
