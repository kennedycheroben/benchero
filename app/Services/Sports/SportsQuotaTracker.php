<?php

namespace Benchero\Services\Sports;

use Benchero\Core\Database\Database;
use PDO;

class SportsQuotaTracker
{
    private PDO $pdo;
    private int $minRemaining;
    private int $backoffSeconds;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
        $this->minRemaining = (int)($_ENV['SPORTS_API_QUOTA_MIN_REMAINING'] ?? env('SPORTS_API_QUOTA_MIN_REMAINING', 10));
        $this->backoffSeconds = (int)($_ENV['SPORTS_API_QUOTA_BACKOFF_SECONDS'] ?? env('SPORTS_API_QUOTA_BACKOFF_SECONDS', 300));
    }

    /**
     * Record an outgoing API request and update provider state.
     */
    public function recordRequest(
        string $provider,
        string $action,
        string $endpoint,
        int $httpStatus,
        int $durationMs,
        ?int $quotaRemaining = null,
        ?int $quotaLimit = null,
        ?string $error = null
    ): void {
        try {
            $cleanError = $error !== null ? $this->sanitizeError($error) : null;

            // 1. Insert into sports_api_requests
            $stmtReq = $this->pdo->prepare("
                INSERT INTO sports_api_requests
                (provider, action, endpoint, http_status, duration_ms, quota_remaining, quota_limit, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmtReq->execute([
                $provider,
                $action,
                $endpoint,
                $httpStatus,
                $durationMs,
                $quotaRemaining,
                $quotaLimit
            ]);

            // 2. Determine degradation / backoff triggers
            $isDegraded = false;
            $backoffUntil = null;

            if ($httpStatus === 429) {
                $isDegraded = true;
                $backoffUntil = date('Y-m-d H:i:s', time() + $this->backoffSeconds);
            } elseif ($quotaRemaining !== null && $quotaRemaining <= $this->minRemaining) {
                $isDegraded = true;
                $backoffUntil = date('Y-m-d H:i:s', time() + $this->backoffSeconds);
            } elseif ($cleanError && (stripos($cleanError, 'quota') !== false || stripos($cleanError, 'request limit') !== false)) {
                $isDegraded = true;
                $backoffUntil = date('Y-m-d H:i:s', time() + $this->backoffSeconds);
            }

            // 3. Upsert provider state with today-reset logic
            $today = date('Y-m-d');
            $stmtState = $this->pdo->prepare("
                INSERT INTO sports_provider_states 
                (provider, requests_today, requests_date, quota_remaining, quota_limit, last_request_at, last_error, is_degraded, backoff_until, created_at, updated_at)
                VALUES (?, 1, ?, ?, ?, NOW(), ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    requests_today = IF(requests_date = VALUES(requests_date), requests_today + 1, 1),
                    requests_date = VALUES(requests_date),
                    quota_remaining = COALESCE(VALUES(quota_remaining), quota_remaining),
                    quota_limit = COALESCE(VALUES(quota_limit), quota_limit),
                    last_request_at = NOW(),
                    last_error = COALESCE(VALUES(last_error), last_error),
                    is_degraded = IF(VALUES(is_degraded) = 1, 1, is_degraded),
                    backoff_until = COALESCE(VALUES(backoff_until), backoff_until),
                    updated_at = NOW()
            ");

            $stmtState->execute([
                $provider,
                $today,
                $quotaRemaining,
                $quotaLimit,
                $cleanError,
                $isDegraded ? 1 : 0,
                $backoffUntil
            ]);
        } catch (\Throwable $e) {
            error_log("SportsQuotaTracker recordRequest error: " . $e->getMessage());
        }
    }

    /**
     * Explicitly record a rate-limit or quota backoff hit for a provider.
     */
    public function recordRateLimitHit(string $provider, int $httpStatus = 429, ?int $backoffSeconds = null): void
    {
        $duration = $backoffSeconds ?? $this->backoffSeconds;
        $backoffUntil = date('Y-m-d H:i:s', time() + $duration);
        try {
            $stmt = $this->pdo->prepare("
                UPDATE sports_provider_states
                SET is_degraded = 1,
                    backoff_until = ?,
                    last_error = ?,
                    updated_at = NOW()
                WHERE provider = ?
            ");
            $stmt->execute([$backoffUntil, "Rate limit / quota exceeded (HTTP {$httpStatus})", $provider]);
        } catch (\Throwable $e) {
            error_log("SportsQuotaTracker recordRateLimitHit error: " . $e->getMessage());
        }
    }

    /**
     * Record a successful sync completion for provider freshness tracking.
     */
    public function recordSuccessfulSync(string $provider, string $action): void
    {
        try {
            $isLive = ($action === 'live' || $action === 'sync-live');
            $sql = "
                UPDATE sports_provider_states
                SET last_successful_sync_at = NOW(),
                    last_error = NULL,
            ";
            if ($isLive) {
                $sql .= " last_live_sync_at = NOW(), ";
            }
            // Clear degradation only if backoff window has expired
            $sql .= " is_degraded = IF(backoff_until IS NOT NULL AND backoff_until > NOW(), 1, 0),
                      updated_at = NOW()
                WHERE provider = ?
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$provider]);
        } catch (\Throwable $e) {
            error_log("SportsQuotaTracker recordSuccessfulSync error: " . $e->getMessage());
        }
    }

    /**
     * Check if provider is currently in a backoff state.
     */
    public function isProviderInBackoff(string $provider): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT is_degraded, backoff_until, quota_remaining 
                FROM sports_provider_states 
                WHERE provider = ?
            ");
            $stmt->execute([$provider]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return false;
            }

            if (!empty($row['backoff_until']) && strtotime($row['backoff_until']) > time()) {
                return true;
            }

            if ($row['quota_remaining'] !== null && (int)$row['quota_remaining'] <= $this->minRemaining) {
                return true;
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get remaining backoff seconds for a provider.
     */
    public function getRemainingBackoffSeconds(string $provider): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT backoff_until FROM sports_provider_states WHERE provider = ?");
            $stmt->execute([$provider]);
            $until = $stmt->fetchColumn();
            if ($until && strtotime($until) > time()) {
                return (int)(strtotime($until) - time());
            }
            return 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Get provider state row.
     */
    public function getProviderState(string $provider): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sports_provider_states WHERE provider = ?");
            $stmt->execute([$provider]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Get all provider states.
     */
    public function getAllProviderStates(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM sports_provider_states ORDER BY provider ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get request counts grouped by provider and action for today.
     */
    public function getRequestsSummaryToday(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT provider, action, COUNT(*) as count, 
                       AVG(duration_ms) as avg_duration,
                       MIN(quota_remaining) as min_quota_remaining
                FROM sports_api_requests
                WHERE DATE(created_at) = CURRENT_DATE()
                GROUP BY provider, action
                ORDER BY provider, action
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get recent API requests.
     */
    public function getRecentRequests(int $limit = 20): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM sports_api_requests 
                ORDER BY id DESC LIMIT ?
            ");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Sanitize error message to prevent accidental leakage of API keys or credentials.
     */
    public function sanitizeError(string $error): string
    {
        // Redact potential 32/40-char hex keys, tokens, or URL query parameters
        $sanitized = preg_replace('/(key|token|secret|password|auth)=([^\s&]+)/i', '$1=REDACTED', $error);
        $sanitized = preg_replace('/([a-f0-9]{32,64})/i', '[REDACTED]', $sanitized);
        return substr($sanitized, 0, 500);
    }
}
