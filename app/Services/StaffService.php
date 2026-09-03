<?php

namespace Benchero\Services;

use Benchero\Repositories\StaffRepository;

class StaffService
{
    private StaffRepository $repository;

    public function __construct(StaffRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getStaff(string $orgId): array
    {
        return $this->repository->getByOrganization($orgId);
    }

    public function createStaff(string $orgId, string $firstName, string $lastName, string $role): string
    {
        return $this->repository->create([
            'organization_id' => $orgId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role
        ]);
    }

    public function deleteStaff(string $id, string $orgId): bool
    {
        return $this->repository->delete($id, $orgId);
    }
}
