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

    public function getStaffById(string $id, string $orgId): ?array
    {
        return $this->repository->findById($id, $orgId);
    }

    public function createStaff(string $orgId, array $data): string
    {
        $data['organization_id'] = $orgId;
        return $this->repository->create($data);
    }

    public function updateStaff(string $id, string $orgId, array $data): bool
    {
        return $this->repository->update($id, $orgId, $data);
    }

    public function deleteStaff(string $id, string $orgId): bool
    {
        return $this->repository->delete($id, $orgId);
    }
}
