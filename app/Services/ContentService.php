<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use PDO;

class ContentService
{
    // ==========================================
    // NEWS & ANNOUNCEMENTS
    // ==========================================

    public function getNews(string $organizationId, int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, organization_id, title, slug, category, excerpt, content, image_url, published_at, created_at
            FROM news_articles
            WHERE organization_id = :org_id
            ORDER BY published_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':org_id', $organizationId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createNews(
        string $organizationId,
        string $title,
        string $category,
        ?string $excerpt,
        string $content,
        ?string $imageUrl
    ): string {
        $db = Database::getConnection();
        $id = Ulid::generate();
        $slug = $this->slugify($title) . '-' . substr(strtolower($id), 0, 6);

        if (empty($excerpt) && !empty($content)) {
            $excerpt = substr(strip_tags($content), 0, 160) . '...';
        }

        $stmt = $db->prepare("
            INSERT INTO news_articles (id, organization_id, title, slug, category, excerpt, content, image_url, published_at)
            VALUES (:id, :org_id, :title, :slug, :category, :excerpt, :content, :image_url, NOW())
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $organizationId,
            'title' => $title,
            'slug' => $slug,
            'category' => $category ?: 'General',
            'excerpt' => $excerpt,
            'content' => $content,
            'image_url' => $imageUrl
        ]);

        return $id;
    }

    public function deleteNews(string $organizationId, string $newsId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM news_articles WHERE id = :id AND organization_id = :org_id");
        return $stmt->execute(['id' => $newsId, 'org_id' => $organizationId]);
    }

    // ==========================================
    // GALLERY IMAGES
    // ==========================================

    public function getGallery(string $organizationId, ?string $category = null): array
    {
        $db = Database::getConnection();
        $sql = "SELECT id, organization_id, title, category, image_url, created_at FROM gallery_images WHERE organization_id = :org_id";
        $params = ['org_id' => $organizationId];

        if ($category && $category !== 'All') {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addGalleryImage(string $organizationId, ?string $title, string $category, string $imageUrl): string
    {
        $db = Database::getConnection();
        $id = Ulid::generate();

        $stmt = $db->prepare("
            INSERT INTO gallery_images (id, organization_id, title, category, image_url)
            VALUES (:id, :org_id, :title, :category, :image_url)
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $organizationId,
            'title' => $title,
            'category' => $category ?: 'Matchday',
            'image_url' => $imageUrl
        ]);

        return $id;
    }

    public function deleteGalleryImage(string $organizationId, string $imageId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM gallery_images WHERE id = :id AND organization_id = :org_id");
        return $stmt->execute(['id' => $imageId, 'org_id' => $organizationId]);
    }

    // ==========================================
    // SPONSORS
    // ==========================================

    public function getSponsors(string $organizationId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, organization_id, name, logo_url, website_url, sponsor_level, display_order
            FROM sponsors
            WHERE organization_id = :org_id
            ORDER BY display_order ASC, name ASC
        ");
        $stmt->execute(['org_id' => $organizationId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addSponsor(
        string $organizationId,
        string $name,
        string $logoUrl,
        ?string $websiteUrl = null,
        string $sponsorLevel = 'Official Partner'
    ): string {
        $db = Database::getConnection();
        $id = Ulid::generate();

        $stmt = $db->prepare("
            INSERT INTO sponsors (id, organization_id, name, logo_url, website_url, sponsor_level)
            VALUES (:id, :org_id, :name, :logo_url, :website_url, :sponsor_level)
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $organizationId,
            'name' => $name,
            'logo_url' => $logoUrl,
            'website_url' => $websiteUrl,
            'sponsor_level' => $sponsorLevel ?: 'Official Partner'
        ]);

        return $id;
    }

    public function deleteSponsor(string $organizationId, string $sponsorId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM sponsors WHERE id = :id AND organization_id = :org_id");
        return $stmt->execute(['id' => $sponsorId, 'org_id' => $organizationId]);
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
}
