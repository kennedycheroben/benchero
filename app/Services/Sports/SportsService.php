<?php

namespace Benchero\Services\Sports;

use Benchero\Contracts\SportsProviderInterface;
use Benchero\Services\Sports\Providers\MockSportsProvider;
use Benchero\Services\Sports\Providers\FootballDataSportsProvider;
use Benchero\Services\CacheService;

class SportsService
{
    private SportsProviderInterface $provider;
    private CacheService $cache;
    private string $providerType;
    private bool $isProduction;

    public function __construct(?SportsProviderInterface $provider = null, ?CacheService $cache = null)
    {
        $this->cache = $cache ?? new CacheService();
        $this->isProduction = (env('APP_ENV') === 'production');
        $this->providerType = strtolower((string)env('SPORTS_PROVIDER', 'mock'));
        $this->provider = $provider ?? $this->resolveProvider();
    }

    private function resolveProvider(): SportsProviderInterface
    {
        if ($this->isProduction && $this->providerType === 'mock') {
            error_log("[WARNING] Production environment specified SPORTS_PROVIDER=mock. Mock data must not be displayed in production unless explicitly overridden.");
        }

        return match ($this->providerType) {
            'real', 'football-data' => new FootballDataSportsProvider(),
            'mock' => new MockSportsProvider(),
            default => $this->isProduction ? new FootballDataSportsProvider() : new MockSportsProvider()
        };
    }

    public function getLiveScores(): array
    {
        $cacheKey = 'sports_live_scores_v1';
        $cached = $this->cache->get($cacheKey);

        try {
            $data = $this->provider->getLiveScores();
            $result = [
                'matches' => $data,
                'updated_at' => date('Y-m-d H:i:s'),
                'is_stale' => false
            ];
            $this->cache->set($cacheKey, $result, 30); // 30s cache
            return $result;
        } catch (\Throwable $e) {
            error_log("SportsService getLiveScores error: " . $e->getMessage());

            if ($cached !== null) {
                $cached['is_stale'] = true;
                $cached['notice'] = 'Live scores temporarily delayed.';
                return $cached;
            }

            return [
                'matches' => [],
                'updated_at' => date('Y-m-d H:i:s'),
                'is_stale' => true,
                'notice' => 'Live scores temporarily unavailable.'
            ];
        }
    }

    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        $cacheKey = "sports_results_{$sport}_{$date}_{$limit}";
        $cached = $this->cache->get($cacheKey);

        try {
            $data = $this->provider->getResults($sport, $date, $limit);
            $result = [
                'results' => $data,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->cache->set($cacheKey, $result, 600); // 10 mins cache
            return $result;
        } catch (\Throwable $e) {
            error_log("SportsService getResults error: " . $e->getMessage());
            return $cached ?? ['results' => [], 'updated_at' => date('Y-m-d H:i:s')];
        }
    }

    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        $cacheKey = "sports_fixtures_{$sport}_{$date}_{$limit}";
        $cached = $this->cache->get($cacheKey);

        try {
            $data = $this->provider->getFixtures($sport, $date, $limit);
            $result = [
                'fixtures' => $data,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $this->cache->set($cacheKey, $result, 600); // 10 mins cache
            return $result;
        } catch (\Throwable $e) {
            error_log("SportsService getFixtures error: " . $e->getMessage());
            return $cached ?? ['fixtures' => [], 'updated_at' => date('Y-m-d H:i:s')];
        }
    }

    public function getCompetitions(?string $sport = null): array
    {
        $cacheKey = "sports_competitions_{$sport}";
        $cached = $this->cache->get($cacheKey);

        try {
            $data = $this->provider->getCompetitions($sport);
            $this->cache->set($cacheKey, $data, 3600); // 1 hr cache
            return $data;
        } catch (\Throwable $e) {
            error_log("SportsService getCompetitions error: " . $e->getMessage());
            return $cached ?? [];
        }
    }

    public function getStandings(string $competitionSlug): array
    {
        $cacheKey = "sports_standings_{$competitionSlug}";
        $cached = $this->cache->get($cacheKey);

        try {
            $data = $this->provider->getStandings($competitionSlug);
            $this->cache->set($cacheKey, $data, 1800); // 30 mins cache
            return $data;
        } catch (\Throwable $e) {
            error_log("SportsService getStandings error: " . $e->getMessage());
            return $cached ?? [];
        }
    }

    public function getMatchDetail(string $matchId): ?array
    {
        try {
            return $this->provider->getMatchDetail($matchId);
        } catch (\Throwable $e) {
            error_log("SportsService getMatchDetail error: " . $e->getMessage());
            return null;
        }
    }
}
