<?php

namespace Benchero\Services;

use Benchero\Repositories\TeamRepository;

class TeamService
{
    private TeamRepository $repository;

    public function __construct()
    {
        $this->repository = new TeamRepository();
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        return trim($slug, '-');
    }

    public function createTeam(string $orgId, string $sportId, string $name, ?string $description, ?string $teamType, bool $isActive = true, int $displayOrder = 0): string
    {
        $slug = $this->generateSlug($name);
        if (empty($slug)) {
            throw new \Exception("Invalid team name.");
        }
        
        $existing = $this->repository->findBySlug($slug, $orgId, $sportId);
        if ($existing) {
            throw new \Exception("A team with a similar name already exists for this sport.");
        }

        return $this->repository->create([
            'organization_id' => $orgId,
            'sport_id' => $sportId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'team_type' => $teamType,
            'is_active' => $isActive ? 1 : 0,
            'display_order' => $displayOrder
        ]);
    }

    public function updateTeam(string $id, string $orgId, string $sportId, string $name, ?string $description, ?string $teamType, bool $isActive = true, int $displayOrder = 0): void
    {
        $team = $this->repository->findById($id, $orgId, $sportId);
        if (!$team) {
            throw new \Exception("Team not found.");
        }

        $slug = $this->generateSlug($name);
        if (empty($slug)) {
            throw new \Exception("Invalid team name.");
        }
        
        $existing = $this->repository->findBySlug($slug, $orgId, $sportId);
        if ($existing && $existing['id'] !== $id) {
            throw new \Exception("A team with a similar name already exists for this sport.");
        }

        $this->repository->update($id, [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'team_type' => $teamType,
            'is_active' => $isActive ? 1 : 0,
            'display_order' => $displayOrder
        ], $orgId, $sportId);
    }

    public function archiveTeam(string $id, string $orgId, string $sportId): void
    {
        $team = $this->repository->findById($id, $orgId, $sportId);
        if (!$team) {
            throw new \Exception("Team not found.");
        }
        
        $this->repository->archive($id, $orgId, $sportId);
    }
}
