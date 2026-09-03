<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;

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

            // 3. Initialize Trial Subscription using central SubscriptionService
            $subService = new SubscriptionService();
            $subService->initializeTrialSubscription($orgId);

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
