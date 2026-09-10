<?php

namespace Benchero\Services;

use Benchero\Repositories\RosterRepository;
use Benchero\Repositories\PlayerRepository;
use Benchero\Repositories\TeamRepository;

class RosterService
{
    private RosterRepository $rosterRepo;
    private PlayerRepository $playerRepo;
    private TeamRepository $teamRepo;

    public function __construct()
    {
        $this->rosterRepo = new RosterRepository();
        $this->playerRepo = new PlayerRepository();
        $this->teamRepo = new TeamRepository();
    }

    public function assignPlayerToRoster(string $orgId, string $sportId, string $playerId, string $teamId, string $seasonId, ?string $jerseyNumber, ?string $position, string $status = 'active', ?string $joinedAt = null, bool $isCaptain = false, bool $isViceCaptain = false): string
    {
        // Validate player belongs to this org & sport
        $player = $this->playerRepo->findById($playerId, $orgId, $sportId);
        if (!$player) {
            throw new \Exception("Invalid player.");
        }

        // Validate team belongs to this org & sport
        $team = $this->teamRepo->findById($teamId, $orgId, $sportId);
        if (!$team) {
            throw new \Exception("Invalid team.");
        }

        // Check if already assigned
        $existing = $this->rosterRepo->findAssignment($playerId, $teamId, $seasonId, $orgId, $sportId);
        if ($existing) {
            throw new \Exception("Player is already assigned to this roster.");
        }

        return $this->rosterRepo->create([
            'organization_id' => $orgId,
            'sport_id' => $sportId,
            'player_id' => $playerId,
            'team_id' => $teamId,
            'season_id' => $seasonId,
            'jersey_number' => $jerseyNumber ? trim($jerseyNumber) : null,
            'position' => $position ? trim($position) : null,
            'status' => $status,
            'joined_at' => $joinedAt ?: date('Y-m-d'),
            'is_captain' => $isCaptain,
            'is_vice_captain' => $isViceCaptain
        ]);
    }

    public function updateAssignment(string $assignmentId, string $orgId, string $sportId, ?string $jerseyNumber, ?string $position, string $status, bool $isCaptain = false, bool $isViceCaptain = false): void
    {
        $this->rosterRepo->update($assignmentId, [
            'jersey_number' => $jerseyNumber ? trim($jerseyNumber) : null,
            'position' => $position ? trim($position) : null,
            'status' => $status,
            'is_captain' => $isCaptain,
            'is_vice_captain' => $isViceCaptain
        ], $orgId, $sportId);
    }

    public function removeFromRoster(string $assignmentId, string $orgId, string $sportId): void
    {
        $this->rosterRepo->archive($assignmentId, $orgId, $sportId);
    }
}
