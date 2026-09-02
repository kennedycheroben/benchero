<?php

use Teamora\Core\Database\Database;

return new class {
    public function up(): void
    {
        $db = Database::getConnection();

        // 1. Add country and timezone to organizations
        $db->exec("
            ALTER TABLE `organizations`
            ADD COLUMN `country` CHAR(2) NULL AFTER `name`,
            ADD COLUMN `timezone` VARCHAR(64) NULL AFTER `country`
        ");

        // 2. Ensure a default 'standard' plan exists to support onboarding trials
        $stmt = $db->prepare("SELECT id FROM `plans` WHERE `slug` = 'standard'");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            $insertStmt = $db->prepare("
                INSERT INTO `plans` (`name`, `slug`, `price_kes`, `features`)
                VALUES (:name, :slug, :price, :features)
            ");
            $insertStmt->execute([
                'name' => 'MVP Standard Plan',
                'slug' => 'standard',
                'price' => 5000.00,
                'features' => json_encode(['sports_management' => true, 'player_limits' => 50])
            ]);
        }
    }

    public function down(): void
    {
        $db = Database::getConnection();
        
        $db->exec("
            ALTER TABLE `organizations`
            DROP COLUMN `timezone`,
            DROP COLUMN `country`
        ");
        
        $db->exec("DELETE FROM `plans` WHERE `slug` = 'standard'");
    }
};
