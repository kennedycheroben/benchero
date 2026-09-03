<?php

namespace Benchero\Repositories;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;

class PlayerRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findActiveByOrgAndSport(string $orgId, string $sportId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM players 
            WHERE organization_id = :org_id 
            AND sport_id = :sport_id
            AND deleted_at IS NULL 
            ORDER BY first_name ASC, last_name ASC
        ");
        $stmt->execute(['org_id' => $orgId, 'sport_id' => $sportId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(string $id, string $orgId, string $sportId = ''): ?array
    {
        $sql = "SELECT * FROM players WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL";
        $params = ['id' => $id, 'org_id' => $orgId];
        if (!empty($sportId)) {
            $sql .= " AND sport_id = :sport_id";
            $params['sport_id'] = $sportId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO players (id, organization_id, sport_id, first_name, last_name, display_name, bio, is_active, date_of_birth) 
            VALUES (:id, :org_id, :sport_id, :first_name, :last_name, :display_name, :bio, :is_active, :date_of_birth)
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $data['organization_id'],
            'sport_id' => $data['sport_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['display_name'] ?? null,
            'bio' => $data['bio'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'date_of_birth' => $data['date_of_birth'] ?? null
        ]);
        return $id;
    }

    public function update(string $id, array $data, string $orgId, string $sportId = ''): bool
    {
        $stmt = $this->db->prepare("
            UPDATE players 
            SET first_name = :first_name, last_name = :last_name, display_name = :display_name, bio = :bio, is_active = :is_active, date_of_birth = :date_of_birth 
            WHERE id = :id AND organization_id = :org_id
        ");
        return $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['display_name'] ?? null,
            'bio' => $data['bio'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'id' => $id,
            'org_id' => $orgId
        ]);
    }

    public function archive(string $id, string $orgId, string $sportId = ''): bool
    {
        $stmt = $this->db->prepare("
            UPDATE players 
            SET deleted_at = NOW() 
            WHERE id = :id AND organization_id = :org_id
        ");
        return $stmt->execute(['id' => $id, 'org_id' => $orgId]);
    }
}
