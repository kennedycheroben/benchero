<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Repositories\StaffRepository;
use Benchero\Repositories\TeamRepository;
use Benchero\Services\StaffService;

class StaffController extends Controller
{
    private StaffService $service;
    private TeamRepository $teamRepo;

    public function __construct()
    {
        parent::__construct();
        $db = Database::getConnection();
        $repo = new StaffRepository($db);
        $this->service = new StaffService($repo);
        $this->teamRepo = new TeamRepository();
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
        $sport = $request->getAttribute('sport');
        $teams = [];
        if ($sport) {
            $teams = $this->teamRepo->findByOrgAndSport($tenant['id'], $sport['id']);
        }

        return $this->render('tenant/staff/create', [
            'tenant' => $tenant,
            'teams' => $teams
        ]);
    }

    public function store(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $firstName = trim((string)$request->input('first_name'));
        $lastName = trim((string)$request->input('last_name'));
        $role = trim((string)$request->input('role'));

        if (empty($firstName) || empty($lastName) || empty($role)) {
            $sport = $request->getAttribute('sport');
            $teams = $sport ? $this->teamRepo->findByOrgAndSport($tenant['id'], $sport['id']) : [];
            return $this->render('tenant/staff/create', [
                'tenant' => $tenant,
                'teams' => $teams,
                'error' => 'First name, last name, and role are required.'
            ], 400);
        }

        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role,
            'photo_url' => trim((string)$request->input('photo_url')),
            'team_id' => trim((string)$request->input('team_id')),
            'email' => trim((string)$request->input('email')),
            'phone' => trim((string)$request->input('phone')),
            'bio' => trim((string)$request->input('bio'))
        ];

        $this->service->createStaff($tenant['id'], $data);
        $_SESSION['success'] = 'Staff member created successfully.';

        return Response::redirect("/o/{$tenant['slug']}/staff");
    }

    public function edit(Request $request, string $slug, string $id): Response
    {
        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $member = $this->service->getStaffById($id, $tenant['id']);

        if (!$member) {
            return new Response('404 Not Found', 404);
        }

        $teams = $sport ? $this->teamRepo->findByOrgAndSport($tenant['id'], $sport['id']) : [];

        return $this->render('tenant/staff/edit', [
            'tenant' => $tenant,
            'member' => $member,
            'teams' => $teams
        ]);
    }

    public function update(Request $request, string $slug, string $id): Response
    {
        $tenant = $request->getAttribute('tenant');
        $firstName = trim((string)$request->input('first_name'));
        $lastName = trim((string)$request->input('last_name'));
        $role = trim((string)$request->input('role'));

        if (empty($firstName) || empty($lastName) || empty($role)) {
            $_SESSION['error'] = 'First name, last name, and role are required.';
            return Response::redirect("/o/{$tenant['slug']}/staff/{$id}/edit");
        }

        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role,
            'photo_url' => trim((string)$request->input('photo_url')),
            'team_id' => trim((string)$request->input('team_id')),
            'email' => trim((string)$request->input('email')),
            'phone' => trim((string)$request->input('phone')),
            'bio' => trim((string)$request->input('bio'))
        ];

        $this->service->updateStaff($id, $tenant['id'], $data);
        $_SESSION['success'] = 'Staff member updated.';

        return Response::redirect("/o/{$tenant['slug']}/staff");
    }

    public function delete(Request $request, string $slug, string $id): Response
    {
        $tenant = $request->getAttribute('tenant');
        $this->service->deleteStaff($id, $tenant['id']);
        $_SESSION['success'] = 'Staff member removed.';

        return Response::redirect("/o/{$tenant['slug']}/staff");
    }
}
