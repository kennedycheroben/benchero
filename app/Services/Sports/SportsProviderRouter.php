<?php

namespace Benchero\Services\Sports;

use Benchero\Contracts\SportsProviderInterface;
use Benchero\Services\Sports\Providers\FootballDataSportsProvider;
use Benchero\Services\Sports\Providers\ApiFootballSportsProvider;
use Benchero\Services\Sports\Providers\MockSportsProvider;
use Benchero\Services\Sports\Providers\NullSportsProvider;

class SportsProviderRouter
{
    private bool $isProduction;
    private array $providers = [];

    private const KENYA_AFRICA_SLUGS = [
        'fkf-premier-league',
        'kenya-premier-league',
        'kenya-super-league',
        'caf-champions-league',
        'caf-confederation-cup',
        'afcon',
        'africa-cup-of-nations',
        'chan'
    ];

    public function __construct()
    {
        $this->isProduction = (env('APP_ENV') === 'production');
    }

    public function resolveProviderForCompetition(?string $competitionSlug = null): SportsProviderInterface
    {
        $configuredProvider = strtolower((string)($_ENV['SPORTS_PROVIDER'] ?? env('SPORTS_PROVIDER', 'football-data')));

        if ($this->isProduction && $configuredProvider === 'mock') {
            return new NullSportsProvider();
        }

        // If competition is Kenyan or African regional, route to API-Football if configured
        if ($competitionSlug && in_array(strtolower($competitionSlug), self::KENYA_AFRICA_SLUGS, true)) {
            $apiKey = env('API_FOOTBALL_API_KEY');
            if (!empty($apiKey)) {
                return new ApiFootballSportsProvider();
            }
        }

        // Default routing based on configured primary provider
        return match ($configuredProvider) {
            'api-football', 'apifootball' => new ApiFootballSportsProvider(),
            'real', 'football-data', 'football' => new FootballDataSportsProvider(),
            'mock' => $this->isProduction ? new NullSportsProvider() : new MockSportsProvider(),
            default => $this->isProduction ? new NullSportsProvider() : new FootballDataSportsProvider()
        };
    }

    public function getActiveProviders(): array
    {
        $providers = [];

        // 1. Football-Data.org (Primary European Provider)
        $fdKey = env('FOOTBALL_DATA_API_KEY');
        if (!empty($fdKey) || !$this->isProduction) {
            $providers['football-data'] = new FootballDataSportsProvider();
        }

        // 2. API-Football (Secondary Kenya / Africa / Global Provider)
        $afKey = env('API_FOOTBALL_API_KEY');
        if (!empty($afKey)) {
            $providers['api-football'] = new ApiFootballSportsProvider();
        }

        // Fallback for development/testing if no keys are present
        if (empty($providers)) {
            $providers['default'] = $this->isProduction ? new NullSportsProvider() : new MockSportsProvider();
        }

        return $providers;
    }
}
