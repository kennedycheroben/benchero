<?php

namespace Teamora\Core;

use PDO;

class RateLimiter
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function hit(string $key, int $maxAttempts = 5, int $decaySeconds = 60): bool
    {
        $this->cleanup();

        // Atomic UPSERT
        $stmt = $this->pdo->prepare("
            INSERT INTO rate_limits (rate_key, attempts, expires_at) 
            VALUES (?, 1, DATE_ADD(NOW(), INTERVAL ? SECOND))
            ON DUPLICATE KEY UPDATE 
                attempts = IF(expires_at > NOW(), attempts + 1, 1),
                expires_at = IF(expires_at > NOW(), expires_at, DATE_ADD(NOW(), INTERVAL ? SECOND))
        ");
        $stmt->execute([$key, $decaySeconds, $decaySeconds]);

        // Fetch the newly updated attempts
        $check = $this->pdo->prepare("SELECT attempts FROM rate_limits WHERE rate_key = ?");
        $check->execute([$key]);
        $attempts = (int)$check->fetchColumn();

        return $attempts <= $maxAttempts;
    }

    /**
     * Clear rate limit for a key (e.g. upon successful login)
     */
    public function clear(string $key): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM rate_limits WHERE rate_key = ?");
        $stmt->execute([$key]);
    }

    /**
     * Periodically clean up expired tokens to keep the table small.
     * In a production system, this might be offloaded to a cron.
     */
    private function cleanup(): void
    {
        // 1% chance to run cleanup on any given hit to avoid locking every request
        if (mt_rand(1, 100) === 1) {
            $this->pdo->exec("DELETE FROM rate_limits WHERE expires_at <= NOW()");
        }
    }
}
