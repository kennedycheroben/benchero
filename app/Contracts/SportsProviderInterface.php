<?php

namespace Benchero\Contracts;

interface SportsProviderInterface
{
    /**
     * Get live matches currently in progress.
     */
    public function getLiveScores(): array;

    /**
     * Get finished match results.
     */
    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array;

    /**
     * Get upcoming match fixtures.
     */
    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array;

    /**
     * Get list of competitions.
     */
    public function getCompetitions(?string $sport = null): array;

    /**
     * Get standings table for a specific competition.
     */
    public function getStandings(string $competitionSlug): array;

    /**
     * Get detailed metadata and stats for a specific match.
     */
    public function getMatchDetail(string $matchId): ?array;
}
