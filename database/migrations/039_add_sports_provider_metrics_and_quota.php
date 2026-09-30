<?php

use Benchero\Core\Ulid;

/**
 * Migration 039: Add Sports Provider Metrics, Quota Tracking, and Request Logging Tables
 *
 * 1. sports_api_requests: Logs individual outgoing provider API requests, HTTP codes, duration, and quota metadata.
 * 2. sports_provider_states: Tracks persistent quota, daily request counts, backoff states, and provider freshness.
 */
return new class {
    public function up(PDO $pdo): void
    {
        echo "Running Migration 039: Add Sports Provider Metrics and Quota Tracking...\n";

        // 1. Create sports_api_requests table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sports_api_requests (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                provider VARCHAR(50) NOT NULL,
                action VARCHAR(50) NOT NULL,
                endpoint VARCHAR(255) NOT NULL,
                http_status INT NOT NULL,
                duration_ms INT NOT NULL DEFAULT 0,
                quota_remaining INT NULL,
                quota_limit INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sar_prov_date (provider, created_at),
                INDEX idx_sar_action (action, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "  [OK] Created sports_api_requests table\n";

        // 2. Create sports_provider_states table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS sports_provider_states (
                provider VARCHAR(50) PRIMARY KEY,
                requests_today INT NOT NULL DEFAULT 0,
                requests_date DATE NULL,
                quota_remaining INT NULL,
                quota_limit INT NULL,
                last_request_at TIMESTAMP NULL,
                last_successful_sync_at TIMESTAMP NULL,
                last_live_sync_at TIMESTAMP NULL,
                last_error TEXT NULL,
                is_degraded TINYINT(1) NOT NULL DEFAULT 0,
                backoff_until TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "  [OK] Created sports_provider_states table\n";

        // 3. Seed initial provider state rows for known providers if not present
        $providers = ['football-data', 'api-football', 'rss'];
        $stmtSeed = $pdo->prepare("
            INSERT INTO sports_provider_states (provider, requests_today, requests_date, updated_at)
            VALUES (?, 0, CURRENT_DATE(), NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        foreach ($providers as $prov) {
            $stmtSeed->execute([$prov]);
        }
        echo "  [OK] Seeded initial sports_provider_states records\n";
    }
};
