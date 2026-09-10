<?php

namespace Benchero\Services;

use Benchero\Repositories\SeasonRepository;

class SeasonService
{
    private SeasonRepository $repository;

    public function __construct()
    {
        $this->repository = new SeasonRepository();
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        return trim($slug, '-');
    }

    public function createSeason(string $orgId, string $sportId, string $name, string $startsOn, string $endsOn, bool $isCurrent = false): string
    {
        if (strtotime($startsOn) > strtotime($endsOn)) {
            throw new \Exception("Start date must be before or equal to end date.");
        }

        $slug = $this->generateSlug($name);
        
        $existing = $this->repository->findBySlug($slug, $orgId, $sportId);
        if ($existing) {
            throw new \Exception("A season with this name already exists for this sport.");
        }

        $id = $this->repository->create([
            'organization_id' => $orgId,
            'sport_id' => $sportId,
            'name' => $name,
            'slug' => $slug,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'is_current' => 0 // handled separately if true
        ]);

        if ($isCurrent) {
            $this->repository->setCurrentTransaction($id, $orgId, $sportId);
        }

        return $id;
    }

    public function updateSeason(string $id, string $orgId, string $sportId, string $name, string $startsOn, string $endsOn): void
    {
        $season = $this->repository->findById($id, $orgId, $sportId);
        if (!$season) {
            throw new \Exception("Season not found.");
        }

        if (strtotime($startsOn) > strtotime($endsOn)) {
            throw new \Exception("Start date must be before or equal to end date.");
        }

        $slug = $this->generateSlug($name);
        $existing = $this->repository->findBySlug($slug, $orgId, $sportId);
        if ($existing && $existing['id'] !== $id) {
            throw new \Exception("A season with this name already exists for this sport.");
        }

        $this->repository->update($id, [
            'name' => $name,
            'slug' => $slug,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn
        ], $orgId, $sportId);
    }

    public function setCurrentSeason(string $id, string $orgId, string $sportId): void
    {
        $season = $this->repository->findById($id, $orgId, $sportId);
        if (!$season) {
            throw new \Exception("Season not found.");
        }

        $this->repository->setCurrentTransaction($id, $orgId, $sportId);
    }

    public function archiveSeason(string $id, string $orgId, string $sportId): void
    {
        $season = $this->repository->findById($id, $orgId, $sportId);
        if (!$season) {
            throw new \Exception("Season not found.");
        }
        
        $this->repository->archive($id, $orgId, $sportId);
    }
}
