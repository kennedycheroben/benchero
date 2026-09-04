<?php

namespace Benchero\Repositories;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use PDO;

class HistoryRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getByOrganization(string $orgId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM club_history
            WHERE organization_id = ?
            ORDER BY display_order ASC, year_date ASC
        ");
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO club_history (id, organization_id, year_date, title, description, image_url, category, display_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $id,
            $data['organization_id'],
            $data['year_date'],
            $data['title'],
            $data['description'],
            $data['image_url'] ?? null,
            $data['category'] ?? 'Milestone',
            (int)($data['display_order'] ?? 0)
        ]);

        return $id;
    }

    public function update(string $id, string $orgId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE club_history
            SET year_date = ?, title = ?, description = ?, image_url = ?, category = ?, display_order = ?
            WHERE id = ? AND organization_id = ?
        ");
        return $stmt->execute([
            $data['year_date'],
            $data['title'],
            $data['description'],
            $data['image_url'] ?? null,
            $data['category'] ?? 'Milestone',
            (int)($data['display_order'] ?? 0),
            $id,
            $orgId
        ]);
    }

    public function delete(string $id, string $orgId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM club_history WHERE id = ? AND organization_id = ?");
        return $stmt->execute([$id, $orgId]);
    }
}
