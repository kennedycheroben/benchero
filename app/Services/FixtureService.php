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

    /**
     * Validate fixture participants and context.
     *
     * Invariants enforced:
     * - Exactly one or both sides must be a Benchero-owned internal team.
     * - Neither side can be external vs external (at least one internal Benchero team must participate).
     * - Each side must specify EITHER an internal team OR an external opponent name (never both, never neither).
     * - If both sides are internal teams, they cannot be the same team.
     * - Internal teams must exist, be active, and belong to the organization and sport.
     * - Season must exist and belong to the organization and sport.
     * - External opponent names must be trimmed, non-empty, and <= 255 characters.
     */
    private function validateContext(
        string $orgId, 
        string $sportId, 
        string $seasonId, 
        ?string $homeTeamId, 
        ?string $awayTeamId,
        ?string $homeOpponentName = null,
        ?string $awayOpponentName = null
    ): array {
        $homeTeamId = !empty(trim((string)$homeTeamId)) ? trim((string)$homeTeamId) : null;
        $awayTeamId = !empty(trim((string)$awayTeamId)) ? trim((string)$awayTeamId) : null;
        $homeOpponentName = !empty(trim((string)$homeOpponentName)) ? trim((string)$homeOpponentName) : null;
        $awayOpponentName = !empty(trim((string)$awayOpponentName)) ? trim((string)$awayOpponentName) : null;

        // 1. Season validation
        $season = $this->seasonRepo->findById($seasonId, $orgId, $sportId);
        if (!$season) {
            throw new Exception("Invalid season context.");
        }

        // 2. Side definition check: each side must have either team ID OR opponent name, never both, never neither
        $homeHasTeam = ($homeTeamId !== null);
        $homeHasOpponent = ($homeOpponentName !== null);
        if ($homeHasTeam && $homeHasOpponent) {
            throw new Exception("Home side cannot have both an internal team and an external opponent name.");
        }
        if (!$homeHasTeam && !$homeHasOpponent) {
            throw new Exception("Home side must specify either an internal team or an external opponent name.");
        }

        $awayHasTeam = ($awayTeamId !== null);
        $awayHasOpponent = ($awayOpponentName !== null);
        if ($awayHasTeam && $awayHasOpponent) {
            throw new Exception("Away side cannot have both an internal team and an external opponent name.");
        }
        if (!$awayHasTeam && !$awayHasOpponent) {
            throw new Exception("Away side must specify either an internal team or an external opponent name.");
        }

        // 3. Reject External vs External (must have at least one Benchero internal team)
        if (!$homeHasTeam && !$awayHasTeam) {
            throw new Exception("A fixture must have at least one internal Benchero team participating. External vs External matches are not allowed.");
        }

        // 4. Same internal team validation
        if ($homeHasTeam && $awayHasTeam && $homeTeamId === $awayTeamId) {
            throw new Exception("Home team and away team cannot be the same.");
        }

        // 5. Length & syntax validation for external opponents
        if ($homeHasOpponent) {
            if (mb_strlen($homeOpponentName) > 255) {
                throw new Exception("Home opponent name cannot exceed 255 characters.");
            }
        }
        if ($awayHasOpponent) {
            if (mb_strlen($awayOpponentName) > 255) {
                throw new Exception("Away opponent name cannot exceed 255 characters.");
            }
        }

        // 6. Tenant & sport validation for internal teams
        if ($homeHasTeam) {
            $homeTeam = $this->teamRepo->findById($homeTeamId, $orgId, $sportId);
            if (!$homeTeam) {
                throw new Exception("Invalid home team context.");
            }
        }

        if ($awayHasTeam) {
            $awayTeam = $this->teamRepo->findById($awayTeamId, $orgId, $sportId);
            if (!$awayTeam) {
                throw new Exception("Invalid away team context.");
            }
        }

        return [
            'home_team_id' => $homeTeamId,
            'home_opponent_name' => $homeOpponentName,
            'away_team_id' => $awayTeamId,
            'away_opponent_name' => $awayOpponentName,
        ];
    }

    public function createFixture(
        string $orgId, 
        string $sportId, 
        string $seasonId, 
        ?string $homeTeamId, 
        ?string $awayTeamId, 
        string $scheduledAtUTC, 
        ?string $venueName = null, 
        string $competitionType = 'league', 
        ?string $competitionName = null, 
        string $status = 'scheduled',
        ?string $notes = null,
        ?string $homeOpponentName = null,
        ?string $awayOpponentName = null
    ): string {
        $validated = $this->validateContext(
            $orgId, 
            $sportId, 
            $seasonId, 
            $homeTeamId, 
            $awayTeamId, 
            $homeOpponentName, 
            $awayOpponentName
        );

        return $this->repo->create([
            'organization_id' => $orgId,
            'sport_id' => $sportId,
            'season_id' => $seasonId,
            'home_team_id' => $validated['home_team_id'],
            'home_opponent_name' => $validated['home_opponent_name'],
            'away_team_id' => $validated['away_team_id'],
            'away_opponent_name' => $validated['away_opponent_name'],
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
        ?string $homeTeamId, 
        ?string $awayTeamId, 
        string $scheduledAtUTC, 
        ?string $venueName = null, 
        string $competitionType = 'league', 
        ?string $competitionName = null,
        ?string $notes = null,
        ?string $homeOpponentName = null,
        ?string $awayOpponentName = null
    ): void {
        $fixture = $this->repo->findById($id, $orgId, $sportId);
        if (!$fixture) {
            throw new Exception("Fixture not found.");
        }
        
        // Cannot freely edit completed or cancelled fixtures core identity
        if (in_array($fixture['status'], ['completed', 'cancelled'])) {
            throw new Exception("Cannot edit details of a {$fixture['status']} fixture.");
        }

        $validated = $this->validateContext(
            $orgId, 
            $sportId, 
            $seasonId, 
            $homeTeamId, 
            $awayTeamId, 
            $homeOpponentName, 
            $awayOpponentName
        );

        $this->repo->update($id, [
            'home_team_id' => $validated['home_team_id'],
            'home_opponent_name' => $validated['home_opponent_name'],
            'away_team_id' => $validated['away_team_id'],
            'away_opponent_name' => $validated['away_opponent_name'],
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
