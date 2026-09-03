<?php

namespace Benchero\Services;

use Benchero\Repositories\PlayerRepository;

class PlayerService
{
    private PlayerRepository $repository;

    public function __construct()
    {
        $this->repository = new PlayerRepository();
    }

    public function createPlayer(string $orgId, string $sportId, string $firstName, string $lastName, ?string $displayName = null, ?string $bio = null, ?string $dob = null, bool $isActive = true): string
    {
        if (empty(trim($firstName)) || empty(trim($lastName))) {
            throw new \Exception("First name and last name are required.");
        }

        return $this->repository->create([
            'organization_id' => $orgId,
            'sport_id' => $sportId,
            'first_name' => trim($firstName),
            'last_name' => trim($lastName),
            'display_name' => $displayName ? trim($displayName) : null,
            'bio' => $bio ? trim($bio) : null,
            'date_of_birth' => $dob ?: null,
            'is_active' => $isActive ? 1 : 0
        ]);
    }

    public function updatePlayer(string $id, string $orgId, string $sportId, string $firstName, string $lastName, ?string $displayName = null, ?string $bio = null, ?string $dob = null, bool $isActive = true): void
    {
        $player = $this->repository->findById($id, $orgId, $sportId);
        if (!$player) {
            throw new \Exception("Player not found.");
        }

        if (empty(trim($firstName)) || empty(trim($lastName))) {
            throw new \Exception("First name and last name are required.");
        }

        $this->repository->update($id, [
            'first_name' => trim($firstName),
            'last_name' => trim($lastName),
            'display_name' => $displayName ? trim($displayName) : null,
            'bio' => $bio ? trim($bio) : null,
            'date_of_birth' => $dob ?: null,
            'is_active' => $isActive ? 1 : 0
        ], $orgId, $sportId);
    }

    public function archivePlayer(string $id, string $orgId, string $sportId): void
    {
        $player = $this->repository->findById($id, $orgId, $sportId);
        if (!$player) {
            throw new \Exception("Player not found.");
        }
        
        $this->repository->archive($id, $orgId, $sportId);
    }
}
