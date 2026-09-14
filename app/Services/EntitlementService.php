<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use PDO;

class EntitlementService
{
    private PDO $db;
    private SubscriptionService $subscriptionService;

    // Capabilities Constants
    public const CAP_CUSTOM_DOMAIN = 'custom_domain';
    public const CAP_VIDEO_UPLOADS = 'video_uploads';
    public const CAP_ADVANCED_MEDIA = 'advanced_media';
    public const CAP_ADVANCED_WEBSITE = 'advanced_website_customization';
    public const CAP_ADVANCED_STATS = 'advanced_statistics';
    public const CAP_MULTIPLE_COMPETITIONS = 'multiple_competitions';
    public const CAP_ADVANCED_NEWS = 'advanced_news';
    public const CAP_ADVANCED_SEO = 'advanced_seo';
    public const CAP_CUSTOM_SOCIAL_LINKS = 'custom_social_links';
    public const CAP_CUSTOM_OG_IMAGE = 'custom_og_image';
    public const CAP_QR_CODES = 'qr_codes';
    public const CAP_DIGITAL_CLUB_CARD = 'digital_club_card';
    public const CAP_DATA_EXPORT = 'data_export';
    public const CAP_ADDITIONAL_ADMINS = 'additional_admins';
    public const CAP_ADVANCED_NOTIFICATIONS = 'advanced_notifications';
    public const CAP_CUSTOM_BRANDING = 'custom_branding';
    public const CAP_REMOVE_BRANDING = 'remove_benchero_branding';
    public const CAP_PRIORITY_SUPPORT = 'priority_support';

    public function __construct(?PDO $db = null, ?SubscriptionService $subscriptionService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->subscriptionService = $subscriptionService ?? new SubscriptionService();
    }

    /**
     * Check if an organization has a specific capability enabled in their active subscription.
     */
    public function hasCapability(string $orgId, string $capability): bool
    {
        $status = $this->subscriptionService->getSubscriptionStatus($orgId);

        // Expired or non-active/trialing subscriptions have no pro capabilities enabled
        if (!$status['is_visible']) {
            return false;
        }

        $features = $this->getPlanFeaturesForOrg($orgId);
        return !empty($features[$capability]);
    }

    /**
     * Get numeric quota for an organization (e.g. total_storage_mb, video_storage_mb, player_limit).
     */
    public function getQuota(string $orgId, string $quotaKey, int $default = 0): int
    {
        $status = $this->subscriptionService->getSubscriptionStatus($orgId);
        if (!$status['is_visible']) {
            return 0;
        }

        $features = $this->getPlanFeaturesForOrg($orgId);
        return isset($features[$quotaKey]) ? (int)$features[$quotaKey] : $default;
    }

    /**
     * Retrieve complete feature set for an organization's active plan.
     */
    public function getPlanFeaturesForOrg(string $orgId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.email, u.id FROM users u
                JOIN organization_user ou ON u.id = ou.user_id
                WHERE ou.organization_id = ?
                LIMIT 1
            ");
            $stmt->execute([$orgId]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($owner && (is_test_account($owner['email']) || is_test_account($owner['id']))) {
                return [
                    self::CAP_CUSTOM_DOMAIN => true,
                    self::CAP_VIDEO_UPLOADS => true,
                    self::CAP_ADVANCED_MEDIA => true,
                    self::CAP_ADVANCED_WEBSITE => true,
                    self::CAP_ADVANCED_STATS => true,
                    self::CAP_MULTIPLE_COMPETITIONS => true,
                    self::CAP_ADVANCED_NEWS => true,
                    self::CAP_ADVANCED_SEO => true,
                    self::CAP_CUSTOM_SOCIAL_LINKS => true,
                    self::CAP_CUSTOM_OG_IMAGE => true,
                    self::CAP_QR_CODES => true,
                    self::CAP_DIGITAL_CLUB_CARD => true,
                    self::CAP_DATA_EXPORT => true,
                    self::CAP_ADDITIONAL_ADMINS => true,
                    self::CAP_ADVANCED_NOTIFICATIONS => true,
                    self::CAP_CUSTOM_BRANDING => true,
                    self::CAP_REMOVE_BRANDING => true,
                    self::CAP_PRIORITY_SUPPORT => true,
                    'total_storage_mb' => 10240,
                    'video_storage_mb' => 10240,
                    'player_limit' => 10000,
                    'team_limit' => 1000,
                    'fixture_limit' => 10000
                ];
            }
        } catch (\Throwable $e) {}

        $sub = $this->subscriptionService->getSubscription($orgId);
        if (!$sub || empty($sub['plan_id'])) {
            // Default trial plan fallback
            $trialPlan = $this->subscriptionService->getPlan('free-trial');
            return !empty($trialPlan['features']) ? json_decode($trialPlan['features'], true) : [];
        }

        $plan = $this->subscriptionService->getPlan($sub['plan_id']);
        if (!$plan || empty($plan['features'])) {
            return [];
        }

        return is_array($plan['features']) ? $plan['features'] : (json_decode($plan['features'], true) ?: []);
    }

    /**
     * Get full entitlements summary for organization dashboard & feature gates.
     */
    public function getEntitlementsSummary(string $orgId): array
    {
        $status = $this->subscriptionService->getSubscriptionStatus($orgId);
        $features = $this->getPlanFeaturesForOrg($orgId);

        return [
            'is_active' => $status['is_visible'],
            'status' => $status['status'],
            'plan_name' => $status['plan_name'],
            'features' => $features,
            'capabilities' => [
                self::CAP_CUSTOM_DOMAIN => $this->hasCapability($orgId, self::CAP_CUSTOM_DOMAIN),
                self::CAP_VIDEO_UPLOADS => $this->hasCapability($orgId, self::CAP_VIDEO_UPLOADS),
                self::CAP_ADVANCED_MEDIA => $this->hasCapability($orgId, self::CAP_ADVANCED_MEDIA),
                self::CAP_ADVANCED_WEBSITE => $this->hasCapability($orgId, self::CAP_ADVANCED_WEBSITE),
                self::CAP_ADVANCED_STATS => $this->hasCapability($orgId, self::CAP_ADVANCED_STATS),
                self::CAP_MULTIPLE_COMPETITIONS => $this->hasCapability($orgId, self::CAP_MULTIPLE_COMPETITIONS),
                self::CAP_ADVANCED_NEWS => $this->hasCapability($orgId, self::CAP_ADVANCED_NEWS),
                self::CAP_ADVANCED_SEO => $this->hasCapability($orgId, self::CAP_ADVANCED_SEO),
                self::CAP_CUSTOM_SOCIAL_LINKS => $this->hasCapability($orgId, self::CAP_CUSTOM_SOCIAL_LINKS),
                self::CAP_CUSTOM_OG_IMAGE => $this->hasCapability($orgId, self::CAP_CUSTOM_OG_IMAGE),
                self::CAP_QR_CODES => $this->hasCapability($orgId, self::CAP_QR_CODES),
                self::CAP_DIGITAL_CLUB_CARD => $this->hasCapability($orgId, self::CAP_DIGITAL_CLUB_CARD),
                self::CAP_DATA_EXPORT => $this->hasCapability($orgId, self::CAP_DATA_EXPORT),
                self::CAP_ADDITIONAL_ADMINS => $this->hasCapability($orgId, self::CAP_ADDITIONAL_ADMINS),
                self::CAP_ADVANCED_NOTIFICATIONS => $this->hasCapability($orgId, self::CAP_ADVANCED_NOTIFICATIONS),
                self::CAP_CUSTOM_BRANDING => $this->hasCapability($orgId, self::CAP_CUSTOM_BRANDING),
                self::CAP_REMOVE_BRANDING => $this->hasCapability($orgId, self::CAP_REMOVE_BRANDING),
                self::CAP_PRIORITY_SUPPORT => $this->hasCapability($orgId, self::CAP_PRIORITY_SUPPORT),
            ],
            'quotas' => [
                'total_storage_mb' => $this->getQuota($orgId, 'total_storage_mb', 500),
                'video_storage_mb' => $this->getQuota($orgId, 'video_storage_mb', 0),
                'player_limit' => $this->getQuota($orgId, 'player_limit', 100),
                'team_limit' => $this->getQuota($orgId, 'team_limit', 10),
                'fixture_limit' => $this->getQuota($orgId, 'fixture_limit', 500),
            ]
        ];
    }
}
