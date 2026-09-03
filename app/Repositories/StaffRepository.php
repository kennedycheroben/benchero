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
            SELECT * FROM staff
            WHERE organization_id = ? AND deleted_at IS NULL
            ORDER BY created_at DESC
        ");
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->pdo->prepare("
            INSERT INTO staff (id, organization_id, first_name, last_name, role, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $id,
            $data['organization_id'],
            $data['first_name'],
            $data['last_name'],
            $data['role']
        ]);

        return $id;
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
