<?php

namespace Benchero\Services\Sports;

use Benchero\Contracts\NewsProviderInterface;
use Benchero\Services\Sports\Providers\MockNewsProvider;
use Benchero\Services\Sports\Providers\RSSNewsProvider;
use Benchero\Services\CacheService;

class NewsService
{
    private NewsProviderInterface $provider;
    private CacheService $cache;
    private string $providerType;

    public function __construct(?NewsProviderInterface $provider = null, ?CacheService $cache = null)
    {
        $this->cache = $cache ?? new CacheService();
        $this->providerType = strtolower((string)env('NEWS_PROVIDER', 'mock'));
        $this->provider = $provider ?? $this->resolveProvider();
    }

    private function resolveProvider(): NewsProviderInterface
    {
        return match ($this->providerType) {
            'real', 'rss' => new RSSNewsProvider(),
            'mock' => new MockNewsProvider(),
            default => new MockNewsProvider()
        };
    }

    public function getLatestNews(int $limit = 10, ?string $sport = null): array
    {
        $cacheKey = "sports_news_feed_{$sport}_{$limit}";
        $cached = $this->cache->get($cacheKey);

        try {
            $data = $this->provider->getLatestNews($limit, $sport);
            if (!empty($data)) {
                $this->cache->set($cacheKey, $data, 900); // 15 mins cache
                return $data;
            }
            return $cached ?? [];
        } catch (\Throwable $e) {
            error_log("NewsService getLatestNews error: " . $e->getMessage());
            return $cached ?? [];
        }
    }

    public function getNewsBySlug(string $slug): ?array
    {
        $cacheKey = "sports_news_article_{$slug}";
        $cached = $this->cache->get($cacheKey);

        try {
            $article = $this->provider->getNewsBySlug($slug);
            if ($article) {
                $this->cache->set($cacheKey, $article, 3600);
                return $article;
            }
            return $cached;
        } catch (\Throwable $e) {
            error_log("NewsService getNewsBySlug error: " . $e->getMessage());
            return $cached;
        }
    }
}
