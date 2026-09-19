<?php

namespace Benchero\Services\Sports;

use Benchero\Contracts\NewsProviderInterface;
use Benchero\Services\Sports\Providers\MockNewsProvider;
use Benchero\Services\Sports\Providers\RSSNewsProvider;
use Benchero\Services\CacheService;
use Benchero\Core\Database\Database;
use PDO;

class NewsService
{
    private NewsProviderInterface $provider;
    private CacheService $cache;
    private string $providerType;
    private bool $isProduction;
    private PDO $pdo;

    public function __construct(?NewsProviderInterface $provider = null, ?CacheService $cache = null)
    {
        $this->pdo = Database::getConnection();
        $this->cache = $cache ?? new CacheService();
        $this->isProduction = (env('APP_ENV') === 'production');
        $this->providerType = strtolower((string)($_ENV['NEWS_PROVIDER'] ?? env('NEWS_PROVIDER', 'mock')));
        $this->provider = $provider ?? $this->resolveProvider();
    }

    private function resolveProvider(): NewsProviderInterface
    {
        if ($this->isProduction && $this->providerType === 'mock') {
            error_log("NEWS_PROVIDER=mock configured in production. Falling back to RSSNewsProvider.");
            return new RSSNewsProvider();
        }

        return match ($this->providerType) {
            'real', 'rss' => new RSSNewsProvider(),
            'mock' => $this->isProduction ? new RSSNewsProvider() : new MockNewsProvider(),
            default => $this->isProduction ? new RSSNewsProvider() : new MockNewsProvider()
        };
    }

    public function getLatestNews(int $limit = 10, ?string $sport = null): array
    {
        $cacheKey = "sports_news_feed_{$sport}_{$limit}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // First check Benchero sports_news database table
            $sql = "SELECT * FROM sports_news WHERE provider != 'mock'";
            $params = [];
            if ($sport) {
                $sql .= " AND (LOWER(category) = LOWER(?) OR LOWER(sport_id) IN (SELECT id FROM sports WHERE LOWER(slug) = LOWER(?)))";
                $params[] = $sport;
                $params[] = $sport;
            }
            $sql .= " ORDER BY published_at DESC LIMIT " . (int)$limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $dbNews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($dbNews)) {
                $this->cache->set($cacheKey, $dbNews, 300);
                return $dbNews;
            }

            if ($this->providerType === 'mock' && $this->isProduction) {
                return [];
            }

            // Otherwise fetch via provider
            $data = $this->provider->getLatestNews($limit, $sport);
            if (!empty($data)) {
                $this->cache->set($cacheKey, $data, 600);
                return $data;
            }

            return [];
        } catch (\Throwable $e) {
            error_log("NewsService getLatestNews error: " . $e->getMessage());
            return $cached ?? [];
        }
    }

    public function getNewsBySlug(string $slug): ?array
    {
        if (empty($slug)) return null;

        $cacheKey = "sports_news_article_{$slug}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // Query DB first
            $stmt = $this->pdo->prepare("SELECT * FROM sports_news WHERE slug = ? AND provider != 'mock'");
            $stmt->execute([$slug]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($article) {
                $this->cache->set($cacheKey, $article, 3600);
                return $article;
            }

            if ($this->providerType === 'mock' && $this->isProduction) {
                return null;
            }

            // Fallback to provider
            $article = $this->provider->getNewsBySlug($slug);
            if ($article) {
                $this->cache->set($cacheKey, $article, 3600);
                return $article;
            }

            return null;
        } catch (\Throwable $e) {
            error_log("NewsService getNewsBySlug error: " . $e->getMessage());
            return $cached;
        }
    }
}
