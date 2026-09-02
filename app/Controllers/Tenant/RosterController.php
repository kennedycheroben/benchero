<?php

namespace Teamora\Controllers\Tenant;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Services\RosterService;
use Teamora\Repositories\RosterRepository;
use Teamora\Repositories\TeamRepository;
use Teamora\Repositories\SeasonRepository;
use Teamora\Repositories\PlayerRepository;

class RosterController
{
    private RosterService $service;
    private RosterRepository $repository;
    private TeamRepository $teamRepo;
    private SeasonRepository $seasonRepo;
    private PlayerRepository $playerRepo;

    public function __construct()
    {
        $this->service = new RosterService();
        $this->repository = new RosterRepository();
        $this->teamRepo = new TeamRepository();
        $this->seasonRepo = new SeasonRepository();
        $this->playerRepo = new PlayerRepository();
    }

    private function requireManagerRole(Request $request): void
    {
        $role = $request->getAttribute('tenant_role');
        if (!in_array($role, ['owner', 'admin', 'manager'])) {
            throw new \Exception('403 Forbidden - Insufficient permissions');
        }
    }

    public function index(Request $request, array $params): Response
    {
        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport');
        $role = $request->getAttribute('tenant_role');
        $teamId = $params['team_id'];
        $seasonId = $params['season_id'];

        $team = $this->teamRepo->findById($teamId, $tenant['id'], $sport['id']);
        $season = $this->seasonRepo->findById($seasonId, $tenant['id'], $sport['id']);

        if (!$team || !$season) {
            return new Response('404 Not Found', 404);
        }

        $roster = $this->repository->findByTeamAndSeason($teamId, $seasonId, $tenant['id'], $sport['id']);

        // Fetch available players for assignment
        $availablePlayers = [];
        if (in_array($role, ['owner', 'admin', 'manager'])) {
            $allPlayers = $this->playerRepo->findActiveByOrgAndSport($tenant['id'], $sport['id']);
            $assignedPlayerIds = array_column($roster, 'player_id');
            foreach ($allPlayers as $p) {
                if (!in_array($p['id'], $assignedPlayerIds)) {
                    $availablePlayers[] = $p;
                }
            }
        }

        ob_start();
        require __DIR__ . '/../../../views/tenant/rosters/index.php';
        return new Response(ob_get_clean());
    }

    public function store(Request $request, array $params): Response
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
        $teamId = $params['team_id'];
        $seasonId = $params['season_id'];
        $playerId = $_POST['player_id'] ?? '';
        $jerseyNumber = $_POST['jersey_number'] ?? '';
        $position = $_POST['position'] ?? '';
        $status = $_POST['status'] ?? 'active';

        try {
            $this->service->assignPlayerToRoster($tenant['id'], $sport['id'], $playerId, $teamId, $seasonId, $jerseyNumber, $position, $status);
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams/{$teamId}/rosters/{$seasonId}");
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
        $teamId = $params['team_id'];
        $seasonId = $params['season_id'];
        $assignmentId = $params['assignment_id'];
        $jerseyNumber = $_POST['jersey_number'] ?? '';
        $position = $_POST['position'] ?? '';
        $status = $_POST['status'] ?? 'active';

        try {
            $this->service->updateAssignment($assignmentId, $tenant['id'], $sport['id'], $jerseyNumber, $position, $status);
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams/{$teamId}/rosters/{$seasonId}");
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
        $teamId = $params['team_id'];
        $seasonId = $params['season_id'];
        $assignmentId = $params['assignment_id'];

        try {
            $this->service->archiveAssignment($assignmentId, $tenant['id'], $sport['id']);
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        return Response::redirect("/o/{$tenant['slug']}/s/{$sport['slug']}/teams/{$teamId}/rosters/{$seasonId}");
    }
}
