<?php

namespace Teamora\Services;

use Teamora\Core\Database\Database;
use Teamora\Core\Ulid;

class OrganizationService
{
    public function createOrganization(string $name, string $slug, string $country, string $timezone, string $userId): string
    {
        $db = Database::getConnection();
        
        try {
            $db->beginTransaction();

            $orgId = Ulid::generate();

            // 1. Create Organization
            $stmt = $db->prepare("
                INSERT INTO `organizations` (`id`, `name`, `slug`, `country`, `timezone`, `created_at`, `updated_at`) 
                VALUES (:id, :name, :slug, :country, :timezone, NOW(), NOW())
            ");
            $stmt->execute([
                'id' => $orgId,
                'name' => $name,
                'slug' => $slug,
                'country' => $country,
                'timezone' => $timezone
            ]);

            // 2. Create Owner Role
            $stmt = $db->prepare("
                INSERT INTO `organization_user` (`organization_id`, `user_id`, `role`, `created_at`) 
                VALUES (:org_id, :user_id, 'owner', NOW())
            ");
            $stmt->execute([
                'org_id' => $orgId,
                'user_id' => $userId
            ]);

            // 3. Find default plan
            $stmt = $db->prepare("SELECT id FROM `plans` WHERE `slug` = 'standard'");
            $stmt->execute();
            $plan = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($plan) {
                // 4. Initialize Trial Subscription
                $subId = Ulid::generate();
                $stmt = $db->prepare("
                    INSERT INTO `subscriptions` 
                    (`id`, `organization_id`, `plan_id`, `billing_interval`, `status`, `trial_ends_at`, `created_at`, `updated_at`) 
                    VALUES 
                    (:id, :org_id, :plan_id, 'monthly', 'trialing', DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), NOW())
                ");
                $stmt->execute([
                    'id' => $subId,
                    'org_id' => $orgId,
                    'plan_id' => $plan['id']
                ]);
            }

            $db->commit();
            
            return $slug;
            
        } catch (\PDOException $e) {
            $db->rollBack();
            // In a real app we might want to check for unique constraint violation on slug
            if ($e->getCode() == 23000) {
                throw new \Exception('The slug is already in use.');
            }
            throw $e;
        }
    }
}
