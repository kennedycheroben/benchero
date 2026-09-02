<?php

namespace Teamora\Services\Auth;

use PDO;
use Teamora\Core\Ulid;

class AuthTokenService
{
    private PDO $pdo;

    public const TYPE_EMAIL_VERIFICATION = 'email_verification';
    public const TYPE_PASSWORD_RESET = 'password_reset';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Generates a new secure token, hashes it, stores it in the database,
     * and returns the plaintext token (to be sent via email).
     */
    public function generateToken(string $userId, string $type, int $expiresInSeconds = 3600): string
    {
        // Invalidate any previously active tokens of this type for this user
        $this->invalidateTokens($userId, $type);

        $plaintextToken = bin2hex(random_bytes(32)); // 64 chars plaintext
        $tokenHash = hash('sha256', $plaintextToken);

        $id = Ulid::generate();
        
        $stmt = $this->pdo->prepare("
            INSERT INTO auth_tokens (id, user_id, type, token_hash, expires_at, created_at)
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), NOW())
        ");
        $stmt->execute([$id, $userId, $type, $tokenHash, $expiresInSeconds]);

        return $plaintextToken;
    }

    /**
     * Validates a plaintext token and marks it as used if valid.
     * 
     * @return string|null Returns the user_id if valid, null otherwise.
     */
    public function validateAndUseToken(string $plaintextToken, string $type): ?string
    {
        $tokenHash = hash('sha256', $plaintextToken);

        $stmt = $this->pdo->prepare("
            SELECT id, user_id, expires_at, used_at
            FROM auth_tokens 
            WHERE token_hash = ? AND type = ?
        ");
        $stmt->execute([$tokenHash, $type]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$token) {
            return null; // Not found
        }

        if ($token['used_at'] !== null) {
            return null; // Already used
        }

        if (strtotime($token['expires_at']) < time()) {
            return null; // Expired
        }

        // Mark as used
        $update = $this->pdo->prepare("UPDATE auth_tokens SET used_at = NOW() WHERE id = ?");
        $update->execute([$token['id']]);

        return $token['user_id'];
    }

    /**
     * Invalidates all active tokens of a specific type for a user.
     */
    public function invalidateTokens(string $userId, string $type): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE auth_tokens 
            SET used_at = NOW() 
            WHERE user_id = ? AND type = ? AND used_at IS NULL AND expires_at > NOW()
        ");
        $stmt->execute([$userId, $type]);
    }
}
