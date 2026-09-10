<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use PDO;

class OrganizationService
{
    public function getOrganizationById(string $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM `organizations` WHERE `id` = ? AND `deleted_at` IS NULL");
        $stmt->execute([$id]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        return $org ?: null;
    }

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
            if ($e->getCode() == 23000) {
                throw new \Exception('The slug is already in use.');
            }
            throw $e;
        }
    }

    public function getOrganizationBySlug(string $slug): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM `organizations` WHERE `slug` = ? AND `deleted_at` IS NULL");
        $stmt->execute([$slug]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        return $org ?: null;
    }

    public function updateOrganization(string $orgId, array $data): bool
    {
        return $this->updateClubProfile($orgId, $data);
    }

    public function updateClubProfile(string $orgId, array $data): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE `organizations`
            SET `name` = :name,
                `country` = :country,
                `timezone` = :timezone,
                `logo_url` = :logo_url,
                `cover_url` = :cover_url,
                `description` = :description,
                `founded_year` = :founded_year,
                `club_colors` = :club_colors,
                `contact_email` = :contact_email,
                `contact_phone` = :contact_phone,
                `address` = :address,
                `social_links` = :social_links,
                `tiktok_url` = :tiktok_url,
                `whatsapp_number` = :whatsapp_number,
                `telegram_url` = :telegram_url,
                `youtube_url` = :youtube_url,
                `og_image_url` = :og_image_url,
                `hide_benchero_branding` = :hide_benchero_branding,
                `featured_video_url` = :featured_video_url,
                `updated_at` = NOW()
            WHERE `id` = :id AND `deleted_at` IS NULL
        ");

        return $stmt->execute([
            'id' => $orgId,
            'name' => $data['name'] ?? '',
            'country' => $data['country'] ?? 'KE',
            'timezone' => $data['timezone'] ?? 'Africa/Nairobi',
            'logo_url' => $data['logo_url'] ?? null,
            'cover_url' => $data['cover_url'] ?? null,
            'description' => $data['description'] ?? null,
            'founded_year' => !empty($data['founded_year']) ? (int)$data['founded_year'] : null,
            'club_colors' => $data['club_colors'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'address' => $data['address'] ?? null,
            'social_links' => is_array($data['social_links'] ?? null) ? json_encode($data['social_links']) : ($data['social_links'] ?? null),
            'tiktok_url' => $data['tiktok_url'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'telegram_url' => $data['telegram_url'] ?? null,
            'youtube_url' => $data['youtube_url'] ?? null,
            'og_image_url' => $data['og_image_url'] ?? null,
            'hide_benchero_branding' => !empty($data['hide_benchero_branding']) ? 1 : 0,
            'featured_video_url' => $data['featured_video_url'] ?? null,
        ]);
    }
}
