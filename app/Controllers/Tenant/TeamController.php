<?php

namespace Teamora\Controllers\Tenant;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Services\TeamService;
use Teamora\Repositories\TeamRepository;

class TeamController
{
    private TeamService $service;
    private TeamRepository $repository;

    public function __construct()
    {
        $this->service = new TeamService();
        $this->repository = new TeamRepository();
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

        $teams = $this->repository->findActiveByOrgAndSport($tenant['id'], $sport['id']);

        ob_start();
        require __DIR__ . '/../../../views/tenant/teams/index.php';
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
        require __DIR__ . '/../../../views/tenant/teams/create.php';
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

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $teamType = trim($_POST['team_type'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']);

        if (empty($name)) {
            $_SESSION['error'] = 'Team name is required.';
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams/create");
        }

        try {
            $this->service->createTeam($tenant['id'], $sport['id'], $name, $description, $teamType, $isActive, $displayOrder);
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams");
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams/create");
        }
    }

    public function show(Request $request, array $params): Response
    {
        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $teamId = $params['id'];

        $team = $this->repository->findById($teamId, $tenant['id'], $sport['id']);
        if (!$team) {
            return new Response('404 Not Found', 404);
        }

        ob_start();
        require __DIR__ . '/../../../views/tenant/teams/show.php';
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
        $teamId = $params['id'];

        $team = $this->repository->findById($teamId, $tenant['id'], $sport['id']);
        if (!$team) {
            return new Response('404 Not Found', 404);
        }

        ob_start();
        require __DIR__ . '/../../../views/tenant/teams/edit.php';
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
        $teamId = $params['id'];

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $teamType = trim($_POST['team_type'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']);

        if (empty($name)) {
            $_SESSION['error'] = 'Team name is required.';
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams/{$teamId}/edit");
        }

        try {
            $this->service->updateTeam($teamId, $tenant['id'], $sport['id'], $name, $description, $teamType, $isActive, $displayOrder);
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams/{$teamId}");
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams/{$teamId}/edit");
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
        $teamId = $params['id'];

        try {
            $this->service->archiveTeam($teamId, $tenant['id'], $sport['id']);
        } catch (\Exception $e) {
            // ignore
        }

        return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams");
    }
}
