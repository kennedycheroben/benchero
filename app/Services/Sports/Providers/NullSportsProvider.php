<?php

namespace Benchero\Services\Sports\Providers;

use Benchero\Contracts\SportsProviderInterface;

class NullSportsProvider implements SportsProviderInterface
{
    public function getLiveScores(): array
    {
        return [];
    }

    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        return [];
    }

    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        return [];
    }

    public function getCompetitions(?string $sport = null): array
    {
        return [];
    }

    public function getStandings(string $competitionSlug): array
    {
        return [
            'competition' => ['name' => ucfirst(str_replace('-', ' ', $competitionSlug)), 'slug' => $competitionSlug],
            'season' => date('Y') . '/' . (date('Y') + 1),
            'table' => []
        ];
    }

    public function getMatchDetail(string $matchId): ?array
    {
        return null;
    }
}
