<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Repositories\StaffRepository;
use Benchero\Services\StaffService;

class StaffController extends Controller
{
    private StaffService $service;

    public function __construct()
    {
        parent::__construct();
        $repo = new StaffRepository(Database::getConnection());
        $this->service = new StaffService($repo);
    }

    public function index(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $staff = $this->service->getStaff($tenant['id']);

        return $this->render('tenant/staff/index', [
            'tenant' => $tenant,
            'staff' => $staff
        ]);
    }

    public function create(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');

        return $this->render('tenant/staff/create', [
            'tenant' => $tenant
        ]);
    }

    public function store(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $firstName = trim((string)$request->input('first_name'));
        $lastName = trim((string)$request->input('last_name'));
        $role = trim((string)$request->input('role'));

        if (empty($firstName) || empty($lastName) || empty($role)) {
            return $this->render('tenant/staff/create', [
                'tenant' => $tenant,
                'error' => 'All fields are required.'
            ], 400);
        }

        $this->service->createStaff($tenant['id'], $firstName, $lastName, $role);

        return Response::redirect("/o/{$tenant['slug']}/staff");
    }

    public function delete(Request $request, string $slug, string $id): Response
    {
        $tenant = $request->getAttribute('tenant');
        $this->service->deleteStaff($id, $tenant['id']);

        return Response::redirect("/o/{$tenant['slug']}/staff");
    }
}
