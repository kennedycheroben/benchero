<?php

namespace Benchero\Contracts;

interface NewsProviderInterface
{
    /**
     * Get latest news articles feed.
     */
    public function getLatestNews(int $limit = 10, ?string $sport = null): array;

    /**
     * Get single news article by slug.
     */
    public function getNewsBySlug(string $slug): ?array;
}
