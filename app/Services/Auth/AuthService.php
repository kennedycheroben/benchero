<?php

namespace Benchero\Services\Auth;

use PDO;
use Benchero\Core\Ulid;

class AuthService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findUserById(string $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function registerUser(string $name, string $email, string $password): ?string
    {
        $existing = $this->findUserByEmail($email);
        if ($existing) {
            return null; // Email already in use
        }

        $id = Ulid::generate();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (id, name, email, password_hash, created_at, updated_at) 
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$id, $name, $email, $passwordHash]);

        return $id;
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function updateLastLogin(string $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }

    public function markEmailVerified(string $userId): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ? AND email_verified_at IS NULL");
        $stmt->execute([$userId]);
    }

    public function updatePassword(string $userId, string $newPassword): void
    {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$passwordHash, $userId]);
    }

    public function getUserOrganizations(string $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.id, o.slug, o.name 
            FROM organizations o
            JOIN organization_user ou ON o.id = ou.organization_id
            WHERE ou.user_id = ? AND o.deleted_at IS NULL
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
