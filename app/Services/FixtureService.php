<?php

namespace Benchero\Services;

use Benchero\Repositories\FixtureRepository;
use Benchero\Repositories\TeamRepository;
use Benchero\Repositories\SeasonRepository;
use Exception;

class FixtureService
{
    private FixtureRepository $repo;
    private TeamRepository $teamRepo;
    private SeasonRepository $seasonRepo;

    public function __construct()
    {
        $this->repo = new FixtureRepository();
        $this->teamRepo = new TeamRepository();
        $this->seasonRepo = new SeasonRepository();
    }

    private function validateContext(string $orgId, string $sportId, string $seasonId, string $homeTeamId, string $awayTeamId): void
    {
        if ($homeTeamId === $awayTeamId) {
            throw new Exception("Home team and away team cannot be the same.");
        }

        $season = $this->seasonRepo->findById($seasonId, $orgId, $sportId);
        if (!$season) {
            throw new Exception("Invalid season context.");
        }

        $homeTeam = $this->teamRepo->findById($homeTeamId, $orgId, $sportId);
        if (!$homeTeam) {
            throw new Exception("Invalid home team context.");
        }

        $awayTeam = $this->teamRepo->findById($awayTeamId, $orgId, $sportId);
        if (!$awayTeam) {
            throw new Exception("Invalid away team context.");
        }
    }

    public function createFixture(
        string $orgId, 
        string $sportId, 
        string $seasonId, 
        string $homeTeamId, 
        string $awayTeamId, 
        string $scheduledAtUTC, 
        ?string $venueName = null, 
        string $competitionType = 'league', 
        ?string $competitionName = null, 
        string $status = 'scheduled',
        ?string $notes = null
    ): string {
        $this->validateContext($orgId, $sportId, $seasonId, $homeTeamId, $awayTeamId);

        return $this->repo->create([
            'organization_id' => $orgId,
            'sport_id' => $sportId,
            'season_id' => $seasonId,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $scheduledAtUTC,
            'venue_name' => $venueName,
            'competition_type' => $competitionType,
            'competition_name' => $competitionName,
            'status' => $status,
            'notes' => $notes
        ]);
    }

    public function updateFixture(
        string $id,
        string $orgId, 
        string $sportId, 
        string $seasonId, 
        string $homeTeamId, 
        string $awayTeamId, 
        string $scheduledAtUTC, 
        ?string $venueName = null, 
        string $competitionType = 'league', 
        ?string $competitionName = null,
        ?string $notes = null
    ): void {
        $fixture = $this->repo->findById($id, $orgId, $sportId);
        if (!$fixture) {
            throw new Exception("Fixture not found.");
        }
        
        // Cannot freely edit completed or cancelled fixtures core identity
        if (in_array($fixture['status'], ['completed', 'cancelled'])) {
            throw new Exception("Cannot edit details of a {$fixture['status']} fixture.");
        }

        $this->validateContext($orgId, $sportId, $seasonId, $homeTeamId, $awayTeamId);

        $this->repo->update($id, [
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'scheduled_at' => $scheduledAtUTC,
            'venue_name' => $venueName,
            'competition_type' => $competitionType,
            'competition_name' => $competitionName,
            'notes' => $notes
        ], $orgId, $sportId);
    }

    public function updateStatus(string $id, string $orgId, string $sportId, string $newStatus): void
    {
        $fixture = $this->repo->findById($id, $orgId, $sportId);
        if (!$fixture) {
            throw new Exception("Fixture not found.");
        }

        $validStatuses = ['scheduled', 'postponed', 'completed', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            throw new Exception("Invalid status.");
        }

        // Add rules: a completed or cancelled fixture shouldn't easily revert to scheduled in MVP
        // (We might allow postponed -> scheduled, but generally completed is terminal)
        if (in_array($fixture['status'], ['completed', 'cancelled']) && !in_array($newStatus, ['completed', 'cancelled'])) {
            throw new Exception("Cannot change status from {$fixture['status']} to {$newStatus}.");
        }

        $this->repo->updateStatus($id, $newStatus, $orgId, $sportId);
    }

    public function recordResult(string $id, string $orgId, string $sportId, int $homeScore, int $awayScore, ?string $notes = null): void
    {
        $fixture = $this->repo->findById($id, $orgId, $sportId);
        if (!$fixture) {
            throw new Exception("Fixture not found.");
        }

        if ($homeScore < 0 || $awayScore < 0) {
            throw new Exception("Scores cannot be negative.");
        }

        $this->repo->updateResult($id, $homeScore, $awayScore, $notes, $orgId, $sportId);
    }

    public function deleteFixture(string $id, string $orgId, string $sportId): void
    {
        $fixture = $this->repo->findById($id, $orgId, $sportId);
        if (!$fixture) {
            throw new Exception("Fixture not found.");
        }
        
        $this->repo->delete($id, $orgId, $sportId);
    }
}
