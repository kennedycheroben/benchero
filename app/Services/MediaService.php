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
    private CacheService $cacheService;
    private EntitlementService $entitlementService;

    private array $allowedLogoMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    private array $allowedGeneralMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    private array $allowedVideoMimeTypes = [
        'video/mp4'       => 'mp4',
        'video/webm'      => 'webm',
        'video/quicktime' => 'mov',
        'video/x-msvideo' => 'avi'
    ];

    public const MAX_LOGO_SIZE = 2097152; // Exactly 2 MB (2,097,152 bytes)
    public const MAX_GENERAL_SIZE = 5242880; // 5 MB

    public function __construct(?PDO $db = null, ?string $uploadBaseDir = null, ?EntitlementService $entitlementService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->uploadBaseDir = $uploadBaseDir ?? (__DIR__ . '/../../public/uploads');
        $this->cacheService = new CacheService();
        $this->entitlementService = $entitlementService ?? new EntitlementService($this->db);
        $this->ensureUploadDirectorySecurity();
    }

    /**
     * Ensure security htaccess is present in uploads directory to block script execution.
     */
    private function ensureUploadDirectorySecurity(): void
    {
        if (!is_dir($this->uploadBaseDir)) {
            @mkdir($this->uploadBaseDir, 0755, true);
        }

        $htaccessPath = $this->uploadBaseDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = <<<HTACCESS
# Block PHP execution in uploads directory
<FilesMatch "\.(php|php3|php4|php5|phtml|phps|pl|py|jsp|asp|htm|html|sh|cgi)$">
    SetHandler none
    Require all denied
</FilesMatch>
Options -ExecCGI
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
HTACCESS;
            @file_put_contents($htaccessPath, $htaccessContent);
        }
    }

    /**
     * Dedicated secure club logo upload handler.
     * Enforces: max 2MB (2097152 bytes), finfo + getimagesize validation, random filename,
     * delayed deletion of old logo after successful new save.
     */
    public function uploadLogo(string $orgId, array $file, ?string $oldLogoUrl = null): array
    {
        if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Upload failed or no file provided.');
        }

        if ($file['size'] > self::MAX_LOGO_SIZE) {
            throw new InvalidArgumentException('Logo file size exceeds the maximum allowed limit of 2 MB (2,097,152 bytes).');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Invalid file upload source.');
        }

        // 1. Validate actual MIME type server-side with finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($realMimeType, $this->allowedLogoMimeTypes)) {
            throw new InvalidArgumentException('Invalid image format. Allowed formats: PNG, JPG, JPEG, WEBP.');
        }

        // 2. Validate actual image dimensions with getimagesize
        $imageSize = @getimagesize($file['tmp_name']);
        if ($imageSize === false || $imageSize[0] <= 0 || $imageSize[1] <= 0) {
            throw new InvalidArgumentException('Corrupted or invalid image file content.');
        }

        $extension = $this->allowedLogoMimeTypes[$realMimeType];
        $safeOrgId = preg_replace('/[^a-zA-Z0-9_-]/', '', $orgId);
        $randomFilename = 'logo_' . $safeOrgId . '_' . Ulid::generate() . '.' . $extension;

        $targetDir = $this->uploadBaseDir . '/logos/' . $safeOrgId;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $randomFilename;

        // Move newly uploaded file to target location
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new InvalidArgumentException('Failed to store uploaded logo on server.');
        }

        $fileUrl = '/uploads/logos/' . $safeOrgId . '/' . $randomFilename;
        $mediaId = Ulid::generate();

        // Database record
        $stmt = $this->db->prepare("
            INSERT INTO media (id, organization_id, filename, file_path, file_url, mime_type, file_size, alt_text, category, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Club Logo', 'logo', NOW(), NOW())
        ");
        $stmt->execute([
            $mediaId, $orgId, $randomFilename, $targetPath, $fileUrl, $realMimeType, $file['size']
        ]);

        // 3. Delete old logo ONLY after new upload is completely validated and saved successfully
        if (!empty($oldLogoUrl) && str_starts_with($oldLogoUrl, '/uploads/')) {
            $oldPath = __DIR__ . '/../../public' . $oldLogoUrl;
            if (file_exists($oldPath) && is_file($oldPath) && $oldPath !== $targetPath) {
                @unlink($oldPath);
            }
        }

        // 4. Invalidate public club website cache
        $this->cacheService->flushOrgCache($orgId);

        return [
            'id' => $mediaId,
            'url' => $fileUrl,
            'filename' => $randomFilename,
            'mime_type' => $realMimeType,
            'file_size' => $file['size']
        ];
    }

    /**
     * General image upload (for team photos, gallery, staff, etc.).
     */
    public function uploadImage(string $orgId, array $file, string $category = 'general', ?string $altText = null, ?string $caption = null): array
    {
        if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Upload failed or no file provided.');
        }

        if ($file['size'] > self::MAX_GENERAL_SIZE) {
            throw new InvalidArgumentException('File size exceeds maximum allowed limit (5 MB).');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Invalid file upload source.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($realMimeType, $this->allowedGeneralMimeTypes)) {
            throw new InvalidArgumentException('Invalid file type. Only JPEG, PNG, WEBP, and GIF images are allowed.');
        }

        $imageSize = @getimagesize($file['tmp_name']);
        if ($imageSize === false || $imageSize[0] <= 0 || $imageSize[1] <= 0) {
            throw new InvalidArgumentException('Corrupted or invalid image file content.');
        }

        // Check storage quota
        $usage = $this->getStorageUsage($orgId);
        $newTotalMb = ($usage['total_bytes'] + $file['size']) / (1024 * 1024);
        if ($newTotalMb > $usage['total_quota_mb']) {
            throw new InvalidArgumentException("Media storage limit exceeded. Your plan allows up to {$usage['total_quota_mb']} MB.");
        }

        $extension = $this->allowedGeneralMimeTypes[$realMimeType];
        $safeOrgId = preg_replace('/[^a-zA-Z0-9_-]/', '', $orgId);
        $filename = $category . '_' . $safeOrgId . '_' . Ulid::generate() . '.' . $extension;

        $orgDir = $this->uploadBaseDir . '/orgs/' . $safeOrgId;
        if (!is_dir($orgDir)) {
            @mkdir($orgDir, 0755, true);
        }

        $targetPath = $orgDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new InvalidArgumentException('Failed to save uploaded file.');
        }

        $fileUrl = '/uploads/orgs/' . $safeOrgId . '/' . $filename;
        $mediaId = Ulid::generate();

        $stmt = $this->db->prepare("
            INSERT INTO media (id, organization_id, filename, file_path, file_url, mime_type, file_size, alt_text, caption, category, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $mediaId, $orgId, $filename, $targetPath, $fileUrl, $realMimeType, $file['size'], $altText, $caption, $category
        ]);

        $this->cacheService->flushOrgCache($orgId);

        return [
            'id' => $mediaId,
            'url' => $fileUrl,
            'filename' => $filename,
            'mime_type' => $realMimeType,
            'file_size' => $file['size']
        ];
    }

    /**
     * Controlled Video Upload — Pro Feature only.
     */
    public function uploadVideo(string $orgId, array $file, string $category = 'matches', ?string $altText = null, ?string $caption = null): array
    {
        if (!$this->entitlementService->hasCapability($orgId, EntitlementService::CAP_VIDEO_UPLOADS)) {
            throw new InvalidArgumentException('Video uploads require a Benchero Pro subscription plan.');
        }

        if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Video upload failed or no file provided.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Invalid video upload source.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!array_key_exists($realMimeType, $this->allowedVideoMimeTypes)) {
            throw new InvalidArgumentException('Invalid video format. Allowed formats: MP4, WEBM, MOV, AVI.');
        }

        $usage = $this->getStorageUsage($orgId);
        $videoQuotaBytes = $usage['video_quota_mb'] * 1024 * 1024;
        
        if ($videoQuotaBytes > 0 && ($usage['video_bytes'] + $file['size']) > $videoQuotaBytes) {
            throw new InvalidArgumentException("Video storage quota exceeded. Your plan allows up to {$usage['video_quota_mb']} MB of video content.");
        }

        $extension = $this->allowedVideoMimeTypes[$realMimeType];
        $safeOrgId = preg_replace('/[^a-zA-Z0-9_-]/', '', $orgId);
        $filename = 'video_' . $category . '_' . $safeOrgId . '_' . Ulid::generate() . '.' . $extension;

        $targetDir = $this->uploadBaseDir . '/videos/' . $safeOrgId;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new InvalidArgumentException('Failed to save uploaded video file.');
        }

        $fileUrl = '/uploads/videos/' . $safeOrgId . '/' . $filename;
        $mediaId = Ulid::generate();

        $stmt = $this->db->prepare("
            INSERT INTO media (id, organization_id, filename, file_path, file_url, mime_type, file_size, alt_text, caption, category, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $mediaId, $orgId, $filename, $targetPath, $fileUrl, $realMimeType, $file['size'], $altText, $caption, 'video_' . $category
        ]);

        $this->cacheService->flushOrgCache($orgId);

        return [
            'id' => $mediaId,
            'url' => $fileUrl,
            'filename' => $filename,
            'mime_type' => $realMimeType,
            'file_size' => $file['size']
        ];
    }

    public function getStorageUsage(string $orgId): array
    {
        $stmt = $this->db->prepare("
            SELECT mime_type, SUM(file_size) as total_bytes
            FROM media
            WHERE organization_id = ?
            GROUP BY mime_type
        ");
        $stmt->execute([$orgId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $imageBytes = 0;
        $videoBytes = 0;

        foreach ($rows as $row) {
            $mime = strtolower($row['mime_type']);
            $bytes = (int)$row['total_bytes'];
            if (str_starts_with($mime, 'video/')) {
                $videoBytes += $bytes;
            } else {
                $imageBytes += $bytes;
            }
        }

        $totalBytes = $imageBytes + $videoBytes;
        $totalQuotaMb = $this->entitlementService->getQuota($orgId, 'total_storage_mb', 500);
        $videoQuotaMb = $this->entitlementService->getQuota($orgId, 'video_storage_mb', 0);

        return [
            'image_bytes' => $imageBytes,
            'video_bytes' => $videoBytes,
            'total_bytes' => $totalBytes,
            'image_mb' => round($imageBytes / (1024 * 1024), 2),
            'video_mb' => round($videoBytes / (1024 * 1024), 2),
            'total_mb' => round($totalBytes / (1024 * 1024), 2),
            'total_quota_mb' => $totalQuotaMb,
            'video_quota_mb' => $videoQuotaMb,
        ];
    }

    public function getMediaByOrg(string $orgId, ?string $category = null, int $limit = 100): array
    {
        $limitInt = max(1, (int)$limit);
        if ($category) {
            $stmt = $this->db->prepare("
                SELECT * FROM media WHERE organization_id = ? AND (category = ? OR category LIKE ?)
                ORDER BY created_at DESC LIMIT {$limitInt}
            ");
            $stmt->execute([$orgId, $category, $category . '%']);
        } else {
            $stmt = $this->db->prepare("
                SELECT * FROM media WHERE organization_id = ?
                ORDER BY created_at DESC LIMIT {$limitInt}
            ");
            $stmt->execute([$orgId]);
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
        $res = $delStmt->execute([$mediaId, $orgId]);
        
        $this->cacheService->flushOrgCache($orgId);

        return $res;
    }
}
