<?php

namespace Teamora\Repositories;

use Teamora\Core\Database\Database;
use Teamora\Core\Ulid;

class TeamRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findActiveByOrgAndSport(string $orgId, string $sportId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM teams 
            WHERE organization_id = :org_id 
            AND sport_id = :sport_id 
            AND deleted_at IS NULL 
            ORDER BY display_order ASC, name ASC
        ");
        $stmt->execute(['org_id' => $orgId, 'sport_id' => $sportId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(string $id, string $orgId, string $sportId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM teams 
            WHERE id = :id 
            AND organization_id = :org_id 
            AND sport_id = :sport_id 
            AND deleted_at IS NULL
        ");
        $stmt->execute(['id' => $id, 'org_id' => $orgId, 'sport_id' => $sportId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function findBySlug(string $slug, string $orgId, string $sportId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM teams 
            WHERE (id = :slug OR slug = :slug_val OR name = :name_val)
            AND organization_id = :org_id 
            AND sport_id = :sport_id 
            AND deleted_at IS NULL
        ");
        $stmt->execute(['slug' => $slug, 'slug_val' => $slug, 'name_val' => $slug, 'org_id' => $orgId, 'sport_id' => $sportId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO teams (id, organization_id, sport_id, name, slug, description, team_type, is_active, display_order) 
            VALUES (:id, :org_id, :sport_id, :name, :slug, :description, :team_type, :is_active, :display_order)
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $data['organization_id'],
            'sport_id' => $data['sport_id'],
            'name' => $data['name'],
            'slug' => $data['slug'] ?? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']))),
            'description' => $data['description'] ?? null,
            'team_type' => $data['team_type'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'display_order' => $data['display_order'] ?? 0
        ]);
        return $id;
    }

    public function update(string $id, array $data, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE teams 
            SET name = :name, slug = :slug, description = :description, team_type = :team_type, is_active = :is_active, display_order = :display_order 
            WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
        ");
        return $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']))),
            'description' => $data['description'] ?? null,
            'team_type' => $data['team_type'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'display_order' => $data['display_order'] ?? 0,
            'id' => $id,
            'org_id' => $orgId,
            'sport_id' => $sportId
        ]);
    }

    public function archive(string $id, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE teams 
            SET deleted_at = NOW(), is_active = 0 
            WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
        ");
        return $stmt->execute(['id' => $id, 'org_id' => $orgId, 'sport_id' => $sportId]);
    }
}
