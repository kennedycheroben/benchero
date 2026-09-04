<?php

namespace Benchero\Repositories;

use PDO;
use Benchero\Core\Repository;
use Benchero\Core\Ulid;

class StaffRepository extends Repository
{
    public function getByOrganization(string $orgId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, t.name as team_name
            FROM staff s
            LEFT JOIN teams t ON s.team_id = t.id
            WHERE s.organization_id = ? AND s.deleted_at IS NULL
            ORDER BY s.display_order ASC, s.created_at DESC
        ");
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(string $id, string $orgId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, t.name as team_name
            FROM staff s
            LEFT JOIN teams t ON s.team_id = t.id
            WHERE s.id = ? AND s.organization_id = ? AND s.deleted_at IS NULL
        ");
        $stmt->execute([$id, $orgId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->pdo->prepare("
            INSERT INTO staff (id, organization_id, first_name, last_name, role, photo_url, team_id, email, phone, bio, created_at, updated_at)
            VALUES (:id, :org_id, :first_name, :last_name, :role, :photo_url, :team_id, :email, :phone, :bio, NOW(), NOW())
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $data['organization_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => $data['role'],
            'photo_url' => $data['photo_url'] ?? null,
            'team_id' => !empty($data['team_id']) ? $data['team_id'] : null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null
        ]);

        return $id;
    }

    public function update(string $id, string $orgId, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE staff
            SET first_name = :first_name,
                last_name = :last_name,
                role = :role,
                photo_url = :photo_url,
                team_id = :team_id,
                email = :email,
                phone = :phone,
                bio = :bio,
                updated_at = NOW()
            WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL
        ");
        return $stmt->execute([
            'id' => $id,
            'org_id' => $orgId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => $data['role'],
            'photo_url' => $data['photo_url'] ?? null,
            'team_id' => !empty($data['team_id']) ? $data['team_id'] : null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null
        ]);
    }

    public function delete(string $id, string $orgId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE staff SET deleted_at = NOW()
            WHERE id = ? AND organization_id = ? AND deleted_at IS NULL
        ");
        return $stmt->execute([$id, $orgId]);
    }
}
