<?php

namespace Benchero\Repositories;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use PDO;

class PageRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getByOrganization(string $orgId, bool $publishedOnly = false): array
    {
        $sql = "SELECT * FROM club_pages WHERE organization_id = ?";
        if ($publishedOnly) {
            $sql .= " AND is_published = 1";
        }
        $sql .= " ORDER BY display_order ASC, title ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySlug(string $orgId, string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM club_pages WHERE organization_id = ? AND slug = ?");
        $stmt->execute([$orgId, $slug]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        return $page ?: null;
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO club_pages (id, organization_id, title, slug, seo_title, seo_description, featured_image, content, is_published, in_navigation, display_order, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $id,
            $data['organization_id'],
            $data['title'],
            $data['slug'],
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            $data['featured_image'] ?? null,
            $data['content'] ?? null,
            !empty($data['is_published']) ? 1 : 0,
            !empty($data['in_navigation']) ? 1 : 0,
            (int)($data['display_order'] ?? 0)
        ]);

        return $id;
    }

    public function update(string $id, string $orgId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE club_pages
            SET title = ?, slug = ?, seo_title = ?, seo_description = ?, featured_image = ?, content = ?, is_published = ?, in_navigation = ?, display_order = ?
            WHERE id = ? AND organization_id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            $data['featured_image'] ?? null,
            $data['content'] ?? null,
            !empty($data['is_published']) ? 1 : 0,
            !empty($data['in_navigation']) ? 1 : 0,
            (int)($data['display_order'] ?? 0),
            $id,
            $orgId
        ]);
    }

    public function delete(string $id, string $orgId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM club_pages WHERE id = ? AND organization_id = ?");
        return $stmt->execute([$id, $orgId]);
    }
}
