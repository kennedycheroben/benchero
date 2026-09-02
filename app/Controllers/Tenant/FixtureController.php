<?php

namespace Teamora\Controllers\Tenant;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Services\FixtureService;
use Teamora\Repositories\FixtureRepository;
use Teamora\Repositories\SeasonRepository;
use Teamora\Repositories\TeamRepository;
use DateTime;
use DateTimeZone;

class FixtureController
{
    private FixtureService $service;
    private FixtureRepository $repository;
    private SeasonRepository $seasonRepo;
    private TeamRepository $teamRepo;

    public function __construct()
    {
        $this->service = new FixtureService();
        $this->repository = new FixtureRepository();
        $this->seasonRepo = new SeasonRepository();
        $this->teamRepo = new TeamRepository();
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

        $seasonId = $_GET['season_id'] ?? null;
        $seasons = $this->seasonRepo->findActiveByOrgAndSport($tenant['id'], $sport['id']);

        if (!$seasonId && !empty($seasons)) {
            $seasonId = $seasons[0]['id'];
        }

        $fixtures = [];
        if ($seasonId) {
            $fixtures = $this->repository->findBySeason($seasonId, $tenant['id'], $sport['id']);
            
            // Convert UTC to Organization Timezone
            $tz = new DateTimeZone($tenant['timezone'] ?? 'UTC');
            $utcTz = new DateTimeZone('UTC');
            foreach ($fixtures as &$f) {
                $dt = new DateTime($f['scheduled_at'], $utcTz);
                $dt->setTimezone($tz);
                $f['scheduled_at_local'] = $dt->format('Y-m-d H:i');
            }
        }

        ob_start();
        require __DIR__ . '/../../../views/tenant/fixtures/index.php';
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

        $seasons = $this->seasonRepo->findActiveByOrgAndSport($tenant['id'], $sport['id']);
        $teams = $this->teamRepo->findActiveByOrgAndSport($tenant['id'], $sport['id']);

        ob_start();
        require __DIR__ . '/../../../views/tenant/fixtures/create.php';
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

        $seasonId = $_POST['season_id'] ?? '';
        $homeTeamId = $_POST['home_team_id'] ?? '';
        $awayTeamId = $_POST['away_team_id'] ?? '';
        $scheduledAtLocal = $_POST['scheduled_at'] ?? ''; // YYYY-MM-DDTHH:MM
        $venueName = trim($_POST['venue_name'] ?? '');
        $competitionType = $_POST['competition_type'] ?? 'league';
        $competitionName = trim($_POST['competition_name'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        try {
            // Convert local time to UTC for storage
            $localTz = new DateTimeZone($tenant['timezone'] ?? 'UTC');
            $dt = new DateTime($scheduledAtLocal, $localTz);
            $dt->setTimezone(new DateTimeZone('UTC'));
            $scheduledAtUTC = $dt->format('Y-m-d H:i:s');

            $this->service->createFixture(
                $tenant['id'], 
                $sport['id'], 
                $seasonId, 
                $homeTeamId, 
                $awayTeamId, 
                $scheduledAtUTC, 
                $venueName ?: null, 
                $competitionType, 
                $competitionName ?: null, 
                'scheduled',
                $notes ?: null
            );
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/fixtures?season_id={$seasonId}");
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/fixtures/create");
        }
    }

    public function show(Request $request, array $params): Response
    {
        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $role = $request->getAttribute('tenant_role');
        
        $fixture = $this->repository->findById($params['id'], $tenant['id'], $sport['id']);
        if (!$fixture) {
            return new Response('404 Not Found', 404);
        }

        // Localize time
        $tz = new DateTimeZone($tenant['timezone'] ?? 'UTC');
        $utcTz = new DateTimeZone('UTC');
        $dt = new DateTime($fixture['scheduled_at'], $utcTz);
        $dt->setTimezone($tz);
        $fixture['scheduled_at_local'] = $dt->format('Y-m-d H:i');

        ob_start();
        require __DIR__ . '/../../../views/tenant/fixtures/show.php';
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
        
        $fixture = $this->repository->findById($params['id'], $tenant['id'], $sport['id']);
        if (!$fixture) {
            return new Response('404 Not Found', 404);
        }

        if (in_array($fixture['status'], ['completed', 'cancelled'])) {
            $_SESSION['error'] = "Cannot edit a {$fixture['status']} fixture.";
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/fixtures/{$params['id']}");
        }

        // Localize time for the datetime-local input
        $tz = new DateTimeZone($tenant['timezone'] ?? 'UTC');
        $utcTz = new DateTimeZone('UTC');
        $dt = new DateTime($fixture['scheduled_at'], $utcTz);
        $dt->setTimezone($tz);
        $fixture['scheduled_at_input'] = $dt->format('Y-m-d\TH:i');

        $seasons = $this->seasonRepo->findActiveByOrgAndSport($tenant['id'], $sport['id']);
        $teams = $this->teamRepo->findActiveByOrgAndSport($tenant['id'], $sport['id']);

        ob_start();
        require __DIR__ . '/../../../views/tenant/fixtures/edit.php';
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
        $id = $params['id'];

        $seasonId = $_POST['season_id'] ?? '';
        $homeTeamId = $_POST['home_team_id'] ?? '';
        $awayTeamId = $_POST['away_team_id'] ?? '';
        $scheduledAtLocal = $_POST['scheduled_at'] ?? '';
        $venueName = trim($_POST['venue_name'] ?? '');
        $competitionType = $_POST['competition_type'] ?? 'league';
        $competitionName = trim($_POST['competition_name'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        try {
            $localTz = new DateTimeZone($tenant['timezone'] ?? 'UTC');
            $dt = new DateTime($scheduledAtLocal, $localTz);
            $dt->setTimezone(new DateTimeZone('UTC'));
            $scheduledAtUTC = $dt->format('Y-m-d H:i:s');

            $this->service->updateFixture(
                $id,
                $tenant['id'], 
                $sport['id'], 
                $seasonId, 
                $homeTeamId, 
                $awayTeamId, 
                $scheduledAtUTC, 
                $venueName ?: null, 
                $competitionType, 
                $competitionName ?: null,
                $notes ?: null
            );
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/fixtures/{$id}");
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/fixtures/{$id}/edit");
        }
    }

    public function status(Request $request, array $params): Response
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
        $id = $params['id'];
        $newStatus = $_POST['status'] ?? '';

        try {
            $this->service->updateStatus($id, $tenant['id'], $sport['id'], $newStatus);
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/fixtures/{$id}");
    }
}
