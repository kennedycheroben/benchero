<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use InvalidArgumentException;
use PDO;

class MediaService
{
    private PDO $db;
    private string $uploadBaseDir;

    private array $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    private int $maxFileSize = 5242880; // 5MB limit

    public function __construct(?PDO $db = null, ?string $uploadBaseDir = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->uploadBaseDir = $uploadBaseDir ?? (__DIR__ . '/../../public/uploads');
    }

    public function uploadImage(string $orgId, array $file, string $category = 'general', ?string $altText = null, ?string $caption = null): array
    {
        if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Upload failed or no file provided.');
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new InvalidArgumentException('File size exceeds maximum allowed limit (5MB).');
        }

        // Validate MIME type with finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($realMimeType, $this->allowedMimeTypes)) {
            throw new InvalidArgumentException('Invalid file type. Only JPEG, PNG, WEBP, and GIF images are allowed.');
        }

        $extension = $this->allowedMimeTypes[$realMimeType];
        $filename = Ulid::generate() . '.' . $extension;

        $orgDir = $this->uploadBaseDir . '/orgs/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $orgId);
        if (!is_dir($orgDir)) {
            mkdir($orgDir, 0755, true);
        }

        $targetPath = $orgDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new InvalidArgumentException('Failed to save uploaded file.');
        }

        $fileUrl = '/uploads/orgs/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $orgId) . '/' . $filename;
        $mediaId = Ulid::generate();

        $stmt = $this->db->prepare("
            INSERT INTO media (id, organization_id, filename, file_path, file_url, mime_type, file_size, alt_text, caption, category, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $mediaId, $orgId, $filename, $targetPath, $fileUrl, $realMimeType, $file['size'], $altText, $caption, $category
        ]);

        return [
            'id' => $mediaId,
            'url' => $fileUrl,
            'filename' => $filename,
            'mime_type' => $realMimeType,
            'file_size' => $file['size']
        ];
    }

    public function getMediaByOrg(string $orgId, ?string $category = null, int $limit = 50): array
    {
        if ($category) {
            $stmt = $this->db->prepare("
                SELECT * FROM media WHERE organization_id = ? AND category = ?
                ORDER BY created_at DESC LIMIT ?
            ");
            $stmt->execute([$orgId, $category, $limit]);
        } else {
            $stmt = $this->db->prepare("
                SELECT * FROM media WHERE organization_id = ?
                ORDER BY created_at DESC LIMIT ?
            ");
            $stmt->execute([$orgId, $limit]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteMedia(string $orgId, string $mediaId): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM media WHERE id = ? AND organization_id = ?");
        $stmt->execute([$mediaId, $orgId]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$media) {
            return false;
        }

        if (file_exists($media['file_path'])) {
            @unlink($media['file_path']);
        }

        $delStmt = $this->db->prepare("DELETE FROM media WHERE id = ? AND organization_id = ?");
        return $delStmt->execute([$mediaId, $orgId]);
    }
}
