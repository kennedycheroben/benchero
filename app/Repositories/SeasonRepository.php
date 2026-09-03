<?php

namespace Benchero\Repositories;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;

class SeasonRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findActiveByOrgAndSport(string $orgId, string $sportId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM seasons 
            WHERE organization_id = :org_id 
            AND sport_id = :sport_id 
            AND deleted_at IS NULL 
            ORDER BY starts_on DESC
        ");
        $stmt->execute(['org_id' => $orgId, 'sport_id' => $sportId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(string $id, string $orgId, string $sportId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM seasons 
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
            SELECT * FROM seasons 
            WHERE slug = :slug 
            AND organization_id = :org_id 
            AND sport_id = :sport_id 
            AND deleted_at IS NULL
        ");
        $stmt->execute(['slug' => $slug, 'org_id' => $orgId, 'sport_id' => $sportId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO seasons (id, organization_id, sport_id, name, slug, starts_on, ends_on, is_current) 
            VALUES (:id, :org_id, :sport_id, :name, :slug, :starts_on, :ends_on, :is_current)
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $data['organization_id'],
            'sport_id' => $data['sport_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'is_current' => $data['is_current'] ?? 0
        ]);
        return $id;
    }

    public function update(string $id, array $data, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE seasons 
            SET name = :name, slug = :slug, starts_on = :starts_on, ends_on = :ends_on 
            WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
        ");
        return $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'id' => $id,
            'org_id' => $orgId,
            'sport_id' => $sportId
        ]);
    }

    public function archive(string $id, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE seasons 
            SET deleted_at = NOW(), is_current = 0 
            WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
        ");
        return $stmt->execute(['id' => $id, 'org_id' => $orgId, 'sport_id' => $sportId]);
    }

    public function setCurrentTransaction(string $id, string $orgId, string $sportId): void
    {
        try {
            $this->db->beginTransaction();
            
            $stmt1 = $this->db->prepare("
                UPDATE seasons SET is_current = 0 
                WHERE organization_id = :org_id AND sport_id = :sport_id
            ");
            $stmt1->execute(['org_id' => $orgId, 'sport_id' => $sportId]);

            $stmt2 = $this->db->prepare("
                UPDATE seasons SET is_current = 1 
                WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
            ");
            $stmt2->execute(['id' => $id, 'org_id' => $orgId, 'sport_id' => $sportId]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
