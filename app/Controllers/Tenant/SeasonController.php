<?php

namespace Teamora\Controllers\Tenant;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Services\SeasonService;
use Teamora\Repositories\SeasonRepository;

class SeasonController
{
    private SeasonService $service;
    private SeasonRepository $repository;

    public function __construct()
    {
        $this->service = new SeasonService();
        $this->repository = new SeasonRepository();
    }

    private function requireOwnerOrAdmin(Request $request): void
    {
        $role = $request->getAttribute('tenant_role');
        if ($role !== 'owner' && $role !== 'admin') {
            throw new \Exception('403 Forbidden - Insufficient permissions');
        }
    }

    public function index(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $role = $request->getAttribute('tenant_role');

        $seasons = $this->repository->findActiveByOrgAndSport($tenant['id'], $sport['id']);

        ob_start();
        require __DIR__ . '/../../../views/tenant/seasons/index.php';
        return new Response(ob_get_clean());
    }

    public function create(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');

        ob_start();
        require __DIR__ . '/../../../views/tenant/seasons/create.php';
        return new Response(ob_get_clean());
    }

    public function store(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');

        $name = trim($_POST['name'] ?? '');
        $startsOn = trim($_POST['starts_on'] ?? '');
        $endsOn = trim($_POST['ends_on'] ?? '');
        $isCurrent = isset($_POST['is_current']);

        if (!$name || !$startsOn || !$endsOn) {
            $_SESSION['error'] = 'All fields are required.';
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/seasons/create");
        }

        try {
            $this->service->createSeason($tenant['id'], $sport['id'], $name, $startsOn, $endsOn, $isCurrent);
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/seasons");
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/seasons/create");
        }
    }

    public function edit(Request $request, array $params): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $seasonId = $params['id'];

        $season = $this->repository->findById($seasonId, $tenant['id'], $sport['id']);
        if (!$season) {
            return new Response('404 Not Found', 404);
        }

        ob_start();
        require __DIR__ . '/../../../views/tenant/seasons/edit.php';
        return new Response(ob_get_clean());
    }

    public function update(Request $request, array $params): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $seasonId = $params['id'];

        $name = trim($_POST['name'] ?? '');
        $startsOn = trim($_POST['starts_on'] ?? '');
        $endsOn = trim($_POST['ends_on'] ?? '');

        if (!$name || !$startsOn || !$endsOn) {
            $_SESSION['error'] = 'All fields are required.';
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/seasons/{$seasonId}/edit");
        }

        try {
            $this->service->updateSeason($seasonId, $tenant['id'], $sport['id'], $name, $startsOn, $endsOn);
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/seasons");
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/seasons/{$seasonId}/edit");
        }
    }

    public function setCurrent(Request $request, array $params): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $seasonId = $params['id'];

        try {
            $this->service->setCurrentSeason($seasonId, $tenant['id'], $sport['id']);
        } catch (\Exception $e) {
            // ignore
        }
        
        return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/seasons");
    }

    public function delete(Request $request, array $params): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $seasonId = $params['id'];

        try {
            $this->service->archiveSeason($seasonId, $tenant['id'], $sport['id']);
        } catch (\Exception $e) {
            // ignore
        }

        return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/seasons");
    }
}
