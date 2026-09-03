<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use PDO;

class SubscriptionService
{
    private PDO $db;

    public const STATUS_TRIAL = 'TRIAL';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_PAST_DUE = 'PAST_DUE';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Get trial duration in days from central configuration.
     */
    public static function getTrialDurationDays(): int
    {
        return (int) (env('TRIAL_DURATION_DAYS', 14));
    }

    /**
     * Get central subscription plan definitions.
     */
    public function getPlans(): array
    {
        $stmt = $this->db->query("SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get plan by ID or Slug.
     */
    public function getPlan($idOrSlug): ?array
    {
        if (is_numeric($idOrSlug)) {
            $stmt = $this->db->prepare("SELECT * FROM plans WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([(int)$idOrSlug]);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM plans WHERE slug = ? AND deleted_at IS NULL");
            $stmt->execute([(string)$idOrSlug]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get subscription record for an organization.
     */
    public function getSubscription(string $orgId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, p.name as plan_name, p.slug as plan_slug, p.price_kes, p.features, p.billing_interval as plan_interval
            FROM subscriptions s
            LEFT JOIN plans p ON s.plan_id = p.id
            WHERE s.organization_id = ?
        ");
        $stmt->execute([$orgId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);
        return $sub ?: null;
    }

    /**
     * Get complete normalized subscription status for an organization.
     */
    public function getSubscriptionStatus($orgOrId): array
    {
        $orgId = is_array($orgOrId) ? ($orgOrId['id'] ?? '') : (string)$orgOrId;
        $sub = $this->getSubscription($orgId);

        if (!$sub) {
            return [
                'status' => self::STATUS_EXPIRED,
                'raw_status' => 'expired',
                'is_visible' => false,
                'starts_at' => null,
                'expires_at' => null,
                'days_remaining' => 0,
                'plan_id' => null,
                'plan_name' => 'None',
                'price_kes' => 0.00,
                'billing_interval' => 'none',
                'warning_message' => 'No active subscription found. Your public profile is hidden.'
            ];
        }

        $nowTs = time();
        
        // Resolve expiration date (expires_at preferred, fallback to current_period_end or trial_ends_at)
        $expiryStr = $sub['expires_at'] ?? $sub['current_period_end'] ?? $sub['trial_ends_at'] ?? null;
        $expiryTs = $expiryStr ? strtotime($expiryStr) : 0;
        
        $startsStr = $sub['starts_at'] ?? $sub['created_at'] ?? null;

        $rawStatus = strtolower($sub['status'] ?? 'trialing');
        $isExpiredByDate = ($expiryTs > 0 && $nowTs >= $expiryTs);

        // Standardize status
        if ($rawStatus === 'canceled' || $rawStatus === 'cancelled') {
            $status = self::STATUS_CANCELLED;
            $isVisible = false;
        } elseif ($rawStatus === 'past_due') {
            $status = self::STATUS_PAST_DUE;
            $isVisible = false;
        } elseif ($isExpiredByDate) {
            $status = self::STATUS_EXPIRED;
            $isVisible = false;
        } elseif ($rawStatus === 'trialing') {
            $status = self::STATUS_TRIAL;
            $isVisible = true;
        } elseif ($rawStatus === 'active') {
            $status = self::STATUS_ACTIVE;
            $isVisible = true;
        } else {
            $status = self::STATUS_EXPIRED;
            $isVisible = false;
        }

        // Calculate days remaining
        $secondsDiff = $expiryTs - $nowTs;
        $daysRemaining = max(0, (int)ceil($secondsDiff / 86400));

        // Generate warning banner text
        $warningMessage = null;
        if ($status === self::STATUS_EXPIRED) {
            $warningMessage = 'Your subscription has expired. Your public club profile is currently hidden. Renew your subscription to make it visible again.';
        } elseif ($daysRemaining === 1) {
            $warningMessage = 'Your Benchero subscription expires tomorrow. Renew now to keep your public club profile visible.';
        } elseif ($daysRemaining === 3) {
            $warningMessage = 'Your Benchero subscription expires in 3 days. Renew now to keep your public club profile visible.';
        } elseif ($daysRemaining > 0 && $daysRemaining <= 7) {
            $expiryFormatted = date('F j, Y', $expiryTs);
            $warningMessage = "Your Benchero subscription expires on {$expiryFormatted}.";
        }

        return [
            'status' => $status,
            'raw_status' => $rawStatus,
            'is_visible' => $isVisible,
            'starts_at' => $startsStr,
            'expires_at' => $expiryStr,
            'expiry_timestamp' => $expiryTs,
            'days_remaining' => $daysRemaining,
            'plan_id' => $sub['plan_id'],
            'plan_name' => $sub['plan_name'] ?? 'Free Trial',
            'price_kes' => (float)($sub['price_kes'] ?? 0),
            'billing_interval' => $sub['billing_interval'] ?? $sub['plan_interval'] ?? 'monthly',
            'warning_message' => $warningMessage,
            'subscription_id' => $sub['id']
        ];
    }

    /**
     * Backend check: Is subscription active?
     */
    public function isSubscriptionActive($orgOrId): bool
    {
        $status = $this->getSubscriptionStatus($orgOrId);
        return $status['status'] === self::STATUS_ACTIVE;
    }

    /**
     * Backend check: Is public profile visible?
     * Enforces: VALID TRIAL OR ACTIVE SUBSCRIPTION WITH NON-EXPIRED expires_at
     */
    public function isPublicProfileVisible($orgOrId): bool
    {
        $status = $this->getSubscriptionStatus($orgOrId);
        return $status['is_visible'];
    }

    /**
     * Get days remaining for subscription.
     */
    public function getDaysRemaining($orgOrId): int
    {
        $status = $this->getSubscriptionStatus($orgOrId);
        return $status['days_remaining'];
    }

    /**
     * Activate or renew a subscription upon verified payment.
     */
    public function activateSubscription(string $orgId, int $planId, ?string $paymentRef = null, ?string $paymentId = null): bool
    {
        $plan = $this->getPlan($planId);
        if (!$plan) {
            return false;
        }

        $sub = $this->getSubscription($orgId);
        $nowTs = time();
        $interval = strtolower($plan['billing_interval'] ?? 'monthly');

        // Check if existing sub is active and not expired for stacking/extension
        $existingExpiryStr = $sub['expires_at'] ?? $sub['current_period_end'] ?? null;
        $existingExpiryTs = $existingExpiryStr ? strtotime($existingExpiryStr) : 0;
        
        $baseTs = ($existingExpiryTs > $nowTs) ? $existingExpiryTs : $nowTs;

        if ($interval === 'yearly') {
            $newExpiryTs = strtotime('+1 year', $baseTs);
        } else {
            $newExpiryTs = strtotime('+30 days', $baseTs);
        }

        $startsAt = date('Y-m-d H:i:s', $nowTs);
        $expiresAt = date('Y-m-d H:i:s', $newExpiryTs);

        if ($sub) {
            $stmt = $this->db->prepare("
                UPDATE subscriptions
                SET plan_id = ?,
                    billing_interval = ?,
                    status = 'active',
                    starts_at = COALESCE(starts_at, ?),
                    expires_at = ?,
                    current_period_end = ?,
                    payment_reference = ?,
                    provider = 'mpesa',
                    updated_at = NOW()
                WHERE organization_id = ?
            ");
            $stmt->execute([
                $plan['id'],
                $interval,
                $startsAt,
                $expiresAt,
                $expiresAt,
                $paymentRef,
                $orgId
            ]);
        } else {
            $subId = Ulid::generate();
            $stmt = $this->db->prepare("
                INSERT INTO subscriptions 
                (id, organization_id, plan_id, billing_interval, status, starts_at, expires_at, current_period_end, payment_reference, provider, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'active', ?, ?, ?, ?, 'mpesa', NOW(), NOW())
            ");
            $stmt->execute([
                $subId,
                $orgId,
                $plan['id'],
                $interval,
                $startsAt,
                $expiresAt,
                $expiresAt,
                $paymentRef
            ]);
        }

        return true;
    }

    /**
     * Initialize trial subscription for a new organization.
     */
    public function initializeTrialSubscription(string $orgId): bool
    {
        $trialPlan = $this->getPlan('free-trial');
        $planId = $trialPlan ? $trialPlan['id'] : 1;
        $days = self::getTrialDurationDays();

        $startsAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $subId = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO subscriptions 
            (id, organization_id, plan_id, billing_interval, status, trial_ends_at, starts_at, expires_at, current_period_end, created_at, updated_at)
            VALUES (?, ?, ?, 'trial', 'trialing', ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            plan_id = VALUES(plan_id),
            billing_interval = VALUES(billing_interval),
            status = VALUES(status),
            trial_ends_at = VALUES(trial_ends_at),
            expires_at = VALUES(expires_at),
            updated_at = NOW()
        ");
        return $stmt->execute([
            $subId,
            $orgId,
            $planId,
            $expiresAt,
            $startsAt,
            $expiresAt,
            $expiresAt
        ]);
    }

    /**
     * Synchronize database status for expired subscriptions.
     */
    public function syncSubscriptionStatus(?string $orgId = null): void
    {
        $sql = "
            UPDATE subscriptions
            SET status = 'expired', updated_at = NOW()
            WHERE status IN ('trialing', 'active')
            AND COALESCE(expires_at, current_period_end, trial_ends_at) <= NOW()
        ";
        if ($orgId) {
            $sql .= " AND organization_id = " . $this->db->quote($orgId);
        }
        $this->db->exec($sql);
    }
}
