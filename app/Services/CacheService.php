<?php

namespace Benchero\Services;

class CacheService
{
    private string $cacheDir;
    private string $appVersion;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../storage/cache';
        $this->appVersion = (string)env('APP_CACHE_VERSION', '1.0');
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    /**
     * Get item from cache. Returns null if expired or missing.
     */
    public function get(string $key)
    {
        $filePath = $this->getFilePath($key);
        if (!file_exists($filePath)) {
            return null;
        }

        $content = @file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $data = @unserialize($content);
        if (!$data || !is_array($data) || !isset($data['expires_at'])) {
            @unlink($filePath);
            return null;
        }

        // Deployment versioning: invalidate if version mismatch
        if (isset($data['version']) && $data['version'] !== $this->appVersion) {
            @unlink($filePath);
            return null;
        }

        if (time() >= $data['expires_at']) {
            @unlink($filePath);
            return null;
        }

        return $data['value'];
    }

    /**
     * Set item in cache with TTL in seconds.
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $filePath = $this->getFilePath($key);
        $data = [
            'key' => $key,
            'version' => $this->appVersion,
            'expires_at' => time() + $ttl,
            'value' => $value
        ];

        $serialized = serialize($data);
        $tempPath = $filePath . '.' . uniqid('tmp_', true);

        if (@file_put_contents($tempPath, $serialized, LOCK_EX) === false) {
            return false;
        }

        @chmod($tempPath, 0664);

        return @rename($tempPath, $filePath);
    }

    /**
     * Forget specific cache key.
     */
    public function forget(string $key): bool
    {
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }

    /**
     * Flush all cache entries belonging to a specific organization.
     */
    public function flushOrgCache(string $orgId): int
    {
        if (empty($orgId)) {
            return 0;
        }

        $count = 0;
        $hashPrefix = md5("org_{$orgId}_");
        // Simple scan of cache dir for files matching tenant prefix or key pattern
        $files = glob($this->cacheDir . '/*.cache');
        if ($files) {
            foreach ($files as $file) {
                $content = @file_get_contents($file);
                if ($content !== false) {
                    $data = @unserialize($content);
                    if (is_array($data) && isset($data['value']) && is_array($data['value']) && isset($data['value']['_org_id']) && $data['value']['_org_id'] === $orgId) {
                        @unlink($file);
                        $count++;
                    }
                }
            }
        }

        // Also delete files that match the key hash prefix directly
        $prefixFiles = glob($this->cacheDir . '/' . md5("org_{$orgId}_") . '*.cache');
        if ($prefixFiles) {
            foreach ($prefixFiles as $pf) {
                if (file_exists($pf)) {
                    @unlink($pf);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Flush all sports platform caches.
     */
    public function flushSportsCache(): int
    {
        $count = 0;
        $files = glob($this->cacheDir . '/*.cache');
        if ($files) {
            foreach ($files as $file) {
                $content = @file_get_contents($file);
                if ($content !== false) {
                    $data = @unserialize($content);
                    if (is_array($data)) {
                        $key = (string)($data['key'] ?? '');
                        if (str_starts_with($key, 'sports_')) {
                            @unlink($file);
                            $count++;
                            continue;
                        }
                        $val = $data['value'] ?? null;
                        if (is_array($val) && (isset($val['matches']) || isset($val['fixtures']) || isset($val['results']) || isset($val['standings']))) {
                            @unlink($file);
                            $count++;
                        }
                    }
                }
            }
        }
        return $count;
    }

    /**
     * Generate safe filesystem path from key.
     */
    private function getFilePath(string $key): string
    {
        $safeName = md5($this->appVersion . ':' . $key) . '.cache';
        return $this->cacheDir . '/' . $safeName;
    }
}
