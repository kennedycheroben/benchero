<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\PlayerService;
use Benchero\Repositories\PlayerRepository;
use Benchero\Repositories\RosterRepository;

class PlayerController
{
    private PlayerService $service;
    private PlayerRepository $repository;
    private RosterRepository $rosterRepo;

    public function __construct()
    {
        $this->service = new PlayerService();
        $this->repository = new PlayerRepository();
        $this->rosterRepo = new RosterRepository();
    }

    private function requireManagerRole(Request $request): void
    {
        $role = $request->getAttribute('tenant_role');
        if (!in_array($role, ['owner', 'admin', 'manager'])) {
            throw new \Exception('403 Forbidden - Insufficient permissions');
        }
    }

    public function index(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $role = $request->getAttribute('tenant_role');

        $players = $this->repository->findActiveByOrgAndSport($tenant['id'], $sport['id']);

        ob_start();
        require __DIR__ . '/../../../views/tenant/players/index.php';
        return new Response(ob_get_clean());
    }

    public function create(Request $request): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');

        ob_start();
        require __DIR__ . '/../../../views/tenant/players/create.php';
        return new Response(ob_get_clean());
    }

    public function store(Request $request): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $dob = trim($_POST['date_of_birth'] ?? '');
        $isActive = isset($_POST['is_active']);

        if (empty($firstName) || empty($lastName)) {
            $_SESSION['error'] = 'First name and last name are required.';
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/players/create");
        }

        try {
            $this->service->createPlayer($tenant['id'], $sport['id'], $firstName, $lastName, $displayName, $bio, $dob, $isActive);
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/players");
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/players/create");
        }
    }

    public function show(Request $request, array $params): Response
    {
        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $playerId = $params['id'];

        $player = $this->repository->findById($playerId, $tenant['id'], $sport['id']);
        if (!$player) {
            return new Response('404 Not Found', 404);
        }

        $assignments = $this->rosterRepo->findAssignmentsByPlayer($playerId, $tenant['id'], $sport['id']);

        ob_start();
        require __DIR__ . '/../../../views/tenant/players/show.php';
        return new Response(ob_get_clean());
    }

    public function edit(Request $request, array $params): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $playerId = $params['id'];

        $player = $this->repository->findById($playerId, $tenant['id'], $sport['id']);
        if (!$player) {
            return new Response('404 Not Found', 404);
        }

        ob_start();
        require __DIR__ . '/../../../views/tenant/players/edit.php';
        return new Response(ob_get_clean());
    }

    public function update(Request $request, array $params): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $playerId = $params['id'];

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $dob = trim($_POST['date_of_birth'] ?? '');
        $isActive = isset($_POST['is_active']);

        if (empty($firstName) || empty($lastName)) {
            $_SESSION['error'] = 'First name and last name are required.';
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/players/{$playerId}/edit");
        }

        try {
            $this->service->updatePlayer($playerId, $tenant['id'], $sport['id'], $firstName, $lastName, $displayName, $bio, $dob, $isActive);
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/players/{$playerId}");
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/players/{$playerId}/edit");
        }
    }

    public function delete(Request $request, array $params): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $playerId = $params['id'];

        try {
            $this->service->archivePlayer($playerId, $tenant['id'], $sport['id']);
        } catch (\Exception $e) {
            // ignore
        }

        return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/players");
    }
}
