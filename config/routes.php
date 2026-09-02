<?php

use Teamora\Core\Routing\Router;

$router = new Router();

$router->addRoute('GET', '/', ['Teamora\Controllers\HomeController', 'index']);
$router->addRoute('GET', '/health', ['Teamora\Controllers\HealthController', 'check']);

// Task 5: Auth - Registration & Verification
$router->addRoute('GET', '/register', ['Teamora\Controllers\AuthController', 'registerForm']);
$router->addRoute('POST', '/register', ['Teamora\Controllers\AuthController', 'register']);
$router->addRoute('GET', '/verify-email/{id}/{token}', ['Teamora\Controllers\AuthController', 'verifyEmail']);
$router->addRoute('GET', '/verify-email/resend', ['Teamora\Controllers\AuthController', 'resendVerificationForm']);
$router->addRoute('POST', '/verify-email/resend', ['Teamora\Controllers\AuthController', 'resendVerification']);

// Task 6: Auth - Login & Logout
$router->addRoute('GET', '/login', ['Teamora\Controllers\AuthController', 'loginForm']);
$router->addRoute('POST', '/login', ['Teamora\Controllers\AuthController', 'login']);
$router->addRoute('POST', '/logout', ['Teamora\Controllers\AuthController', 'logout']);

// Task 7: Organization Onboarding & Selection
$router->addRoute('GET', '/organizations', ['Teamora\Controllers\OrganizationSelectionController', 'index']);
$router->addRoute('GET', '/onboarding', ['Teamora\Controllers\OnboardingController', 'index']);
$router->addRoute('POST', '/onboarding', ['Teamora\Controllers\OnboardingController', 'store']);

// Task 7: Tenant Context routes
$router->addRoute('GET', '/o/{slug}/dashboard', ['Teamora\Controllers\Tenant\DashboardController', 'index']);

// Task 8: Sports Foundation routes
$router->addRoute('GET', '/o/{slug}/sports', ['Teamora\Controllers\Tenant\SportController', 'index']);
$router->addRoute('POST', '/o/{slug}/sports/toggle', ['Teamora\Controllers\Tenant\SportController', 'toggle']);
// Task 10: Team routes
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams', ['Teamora\Controllers\Tenant\TeamController', 'index']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams/create', ['Teamora\Controllers\Tenant\TeamController', 'create']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams', ['Teamora\Controllers\Tenant\TeamController', 'store']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams/{id}', ['Teamora\Controllers\Tenant\TeamController', 'show']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams/{id}/edit', ['Teamora\Controllers\Tenant\TeamController', 'edit']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{id}', ['Teamora\Controllers\Tenant\TeamController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{id}/delete', ['Teamora\Controllers\Tenant\TeamController', 'delete']);

// Task 9: Seasons routes
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/seasons', ['Teamora\Controllers\Tenant\SeasonController', 'index']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/seasons/create', ['Teamora\Controllers\Tenant\SeasonController', 'create']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/seasons', ['Teamora\Controllers\Tenant\SeasonController', 'store']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/seasons/{id}/edit', ['Teamora\Controllers\Tenant\SeasonController', 'edit']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/seasons/{id}', ['Teamora\Controllers\Tenant\SeasonController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/seasons/{id}/current', ['Teamora\Controllers\Tenant\SeasonController', 'setCurrent']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/seasons/{id}/delete', ['Teamora\Controllers\Tenant\SeasonController', 'delete']);

// Task 11: Player routes
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/players', ['Teamora\Controllers\Tenant\PlayerController', 'index']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/players/create', ['Teamora\Controllers\Tenant\PlayerController', 'create']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/players', ['Teamora\Controllers\Tenant\PlayerController', 'store']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/players/{id}', ['Teamora\Controllers\Tenant\PlayerController', 'show']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/players/{id}/edit', ['Teamora\Controllers\Tenant\PlayerController', 'edit']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/players/{id}', ['Teamora\Controllers\Tenant\PlayerController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/players/{id}/delete', ['Teamora\Controllers\Tenant\PlayerController', 'delete']);

// Task 11: Roster routes
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams/{team_id}/rosters/{season_id}', ['Teamora\Controllers\Tenant\RosterController', 'index']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{team_id}/rosters/{season_id}', ['Teamora\Controllers\Tenant\RosterController', 'store']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{team_id}/rosters/{season_id}/assignments/{assignment_id}', ['Teamora\Controllers\Tenant\RosterController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{team_id}/rosters/{season_id}/assignments/{assignment_id}/delete', ['Teamora\Controllers\Tenant\RosterController', 'delete']);

// Task 12: Fixture routes
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/fixtures', ['Teamora\Controllers\Tenant\FixtureController', 'index']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/fixtures/create', ['Teamora\Controllers\Tenant\FixtureController', 'create']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/fixtures', ['Teamora\Controllers\Tenant\FixtureController', 'store']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/fixtures/{id}', ['Teamora\Controllers\Tenant\FixtureController', 'show']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/fixtures/{id}/edit', ['Teamora\Controllers\Tenant\FixtureController', 'edit']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/fixtures/{id}', ['Teamora\Controllers\Tenant\FixtureController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/fixtures/{id}/status', ['Teamora\Controllers\Tenant\FixtureController', 'status']);
// Task 12: Public Fixture routes
$router->addRoute('GET', '/{org_slug}/{sport_slug}/fixtures', ['Teamora\Controllers\Public\FixtureController', 'index']);

return $router;
