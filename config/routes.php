<?php

use Benchero\Core\Routing\Router;

$router = new Router();

// Public Marketing Routes
$router->addRoute('GET', '/', ['Benchero\Controllers\HomeController', 'index']);
$router->addRoute('GET', '/about', ['Benchero\Controllers\HomeController', 'about']);
$router->addRoute('GET', '/pricing', ['Benchero\Controllers\HomeController', 'pricing']);
$router->addRoute('GET', '/contact', ['Benchero\Controllers\HomeController', 'contactForm']);
$router->addRoute('POST', '/contact', ['Benchero\Controllers\HomeController', 'contactSubmit']);
$router->addRoute('GET', '/terms', ['Benchero\Controllers\HomeController', 'terms']);
$router->addRoute('GET', '/privacy', ['Benchero\Controllers\HomeController', 'privacy']);
$router->addRoute('GET', '/cookies', ['Benchero\Controllers\HomeController', 'cookies']);
$router->addRoute('GET', '/sitemap.xml', ['Benchero\Controllers\Public\SitemapController', 'sitemap']);
$router->addRoute('GET', '/robots.txt', ['Benchero\Controllers\Public\SitemapController', 'robots']);
$router->addRoute('GET', '/health', ['Benchero\Controllers\HealthController', 'check']);

// Auth - Registration & Verification
$router->addRoute('GET', '/register', ['Benchero\Controllers\AuthController', 'registerForm']);
$router->addRoute('POST', '/register', ['Benchero\Controllers\AuthController', 'register']);
$router->addRoute('GET', '/verify-email/{id}/{token}', ['Benchero\Controllers\AuthController', 'verifyEmail']);
$router->addRoute('GET', '/verify-email/resend', ['Benchero\Controllers\AuthController', 'resendVerificationForm']);
$router->addRoute('POST', '/verify-email/resend', ['Benchero\Controllers\AuthController', 'resendVerification']);

// Auth - Login & Logout & Recovery
$router->addRoute('GET', '/login', ['Benchero\Controllers\AuthController', 'loginForm']);
$router->addRoute('POST', '/login', ['Benchero\Controllers\AuthController', 'login']);
$router->addRoute('POST', '/logout', ['Benchero\Controllers\AuthController', 'logout']);
$router->addRoute('GET', '/forgot-password', ['Benchero\Controllers\AuthController', 'forgotPasswordForm']);
$router->addRoute('POST', '/forgot-password', ['Benchero\Controllers\AuthController', 'forgotPasswordSubmit']);
$router->addRoute('GET', '/reset-password/{token}', ['Benchero\Controllers\AuthController', 'resetPasswordForm']);
$router->addRoute('POST', '/reset-password', ['Benchero\Controllers\AuthController', 'resetPasswordSubmit']);

// Organization Onboarding & Selection
$router->addRoute('GET', '/organizations', ['Benchero\Controllers\OrganizationSelectionController', 'index']);
$router->addRoute('GET', '/onboarding', ['Benchero\Controllers\OnboardingController', 'index']);
$router->addRoute('POST', '/onboarding', ['Benchero\Controllers\OnboardingController', 'store']);

// Public Multi-Page Club Website Routes
$router->addRoute('GET', '/club/{slug}', ['Benchero\Controllers\Public\PublicClubController', 'show']);
$router->addRoute('GET', '/club/{slug}/about', ['Benchero\Controllers\Public\PublicClubController', 'about']);
$router->addRoute('GET', '/club/{slug}/teams', ['Benchero\Controllers\Public\PublicClubController', 'teams']);
$router->addRoute('GET', '/club/{slug}/teams/{team_slug}', ['Benchero\Controllers\Public\PublicClubController', 'teamDetail']);
$router->addRoute('GET', '/club/{slug}/players', ['Benchero\Controllers\Public\PublicClubController', 'players']);
$router->addRoute('GET', '/club/{slug}/players/{player_slug}', ['Benchero\Controllers\Public\PublicClubController', 'playerDetail']);
$router->addRoute('GET', '/club/{slug}/staff', ['Benchero\Controllers\Public\PublicClubController', 'staff']);
$router->addRoute('GET', '/club/{slug}/fixtures', ['Benchero\Controllers\Public\PublicClubController', 'fixtures']);
$router->addRoute('GET', '/club/{slug}/results', ['Benchero\Controllers\Public\PublicClubController', 'results']);
$router->addRoute('GET', '/club/{slug}/standings', ['Benchero\Controllers\Public\PublicClubController', 'standings']);
$router->addRoute('GET', '/club/{slug}/news', ['Benchero\Controllers\Public\PublicClubController', 'news']);
$router->addRoute('GET', '/club/{slug}/news/{article_slug}', ['Benchero\Controllers\Public\PublicClubController', 'newsDetail']);
$router->addRoute('GET', '/club/{slug}/gallery', ['Benchero\Controllers\Public\PublicClubController', 'gallery']);
$router->addRoute('GET', '/club/{slug}/history', ['Benchero\Controllers\Public\PublicClubController', 'history']);
$router->addRoute('GET', '/club/{slug}/sponsors', ['Benchero\Controllers\Public\PublicClubController', 'sponsors']);
$router->addRoute('GET', '/club/{slug}/contact', ['Benchero\Controllers\Public\PublicClubController', 'contact']);
$router->addRoute('POST', '/club/{slug}/contact', ['Benchero\Controllers\Public\PublicClubController', 'contactSubmit']);

$router->addRoute('GET', '/{org_slug}/{sport_slug}/fixtures', ['Benchero\Controllers\Public\FixtureController', 'index']);

// Tenant Context Routes
$router->addRoute('GET', '/o/{slug}/dashboard', ['Benchero\Controllers\Tenant\DashboardController', 'index']);

// Tenant Club Profile & Branding
$router->addRoute('GET', '/o/{slug}/profile', ['Benchero\Controllers\Tenant\ClubProfileController', 'edit']);
$router->addRoute('POST', '/o/{slug}/profile', ['Benchero\Controllers\Tenant\ClubProfileController', 'update']);

// Tenant Website Builder & CMS Management
$router->addRoute('GET', '/o/{slug}/website', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'overview']);
$router->addRoute('GET', '/o/{slug}/website/customize', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'customizeForm']);
$router->addRoute('POST', '/o/{slug}/website/customize', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'customizeSave']);
$router->addRoute('GET', '/o/{slug}/website/homepage', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'homepageForm']);
$router->addRoute('POST', '/o/{slug}/website/homepage', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'homepageSave']);
$router->addRoute('GET', '/o/{slug}/website/navigation', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'navigationForm']);
$router->addRoute('POST', '/o/{slug}/website/navigation', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'navigationSave']);
$router->addRoute('GET', '/o/{slug}/website/themes', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'themesForm']);
$router->addRoute('POST', '/o/{slug}/website/themes', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'themesSave']);
$router->addRoute('GET', '/o/{slug}/website/history', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'historyIndex']);
$router->addRoute('POST', '/o/{slug}/website/history', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'historySave']);
$router->addRoute('POST', '/o/{slug}/website/history/{id}/delete', ['Benchero\Controllers\Tenant\WebsiteBuilderController', 'historyDelete']);

// Tenant Media Library Management
$router->addRoute('GET', '/o/{slug}/media', ['Benchero\Controllers\Tenant\MediaController', 'index']);
$router->addRoute('POST', '/o/{slug}/media', ['Benchero\Controllers\Tenant\MediaController', 'store']);
$router->addRoute('POST', '/o/{slug}/media/{id}/delete', ['Benchero\Controllers\Tenant\MediaController', 'delete']);

// Tenant Content & Media Management (News, Gallery, Sponsors)
$router->addRoute('GET', '/o/{slug}/content', ['Benchero\Controllers\Tenant\ContentController', 'index']);
$router->addRoute('POST', '/o/{slug}/content/news', ['Benchero\Controllers\Tenant\ContentController', 'storeNews']);
$router->addRoute('POST', '/o/{slug}/content/news/{id}/delete', ['Benchero\Controllers\Tenant\ContentController', 'deleteNews']);
$router->addRoute('POST', '/o/{slug}/content/gallery', ['Benchero\Controllers\Tenant\ContentController', 'storeGallery']);
$router->addRoute('POST', '/o/{slug}/content/gallery/{id}/delete', ['Benchero\Controllers\Tenant\ContentController', 'deleteGallery']);
$router->addRoute('POST', '/o/{slug}/content/sponsors', ['Benchero\Controllers\Tenant\ContentController', 'storeSponsor']);
$router->addRoute('POST', '/o/{slug}/content/sponsors/{id}/delete', ['Benchero\Controllers\Tenant\ContentController', 'deleteSponsor']);

// Tenant Sports
$router->addRoute('GET', '/o/{slug}/sports', ['Benchero\Controllers\Tenant\SportController', 'index']);
$router->addRoute('POST', '/o/{slug}/sports/toggle', ['Benchero\Controllers\Tenant\SportController', 'toggle']);

// Tenant Teams
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams', ['Benchero\Controllers\Tenant\TeamController', 'index']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams/create', ['Benchero\Controllers\Tenant\TeamController', 'create']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams', ['Benchero\Controllers\Tenant\TeamController', 'store']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams/{id}', ['Benchero\Controllers\Tenant\TeamController', 'show']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams/{id}/edit', ['Benchero\Controllers\Tenant\TeamController', 'edit']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{id}', ['Benchero\Controllers\Tenant\TeamController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{id}/delete', ['Benchero\Controllers\Tenant\TeamController', 'delete']);

// Tenant Seasons
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/seasons', ['Benchero\Controllers\Tenant\SeasonController', 'index']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/seasons/create', ['Benchero\Controllers\Tenant\SeasonController', 'create']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/seasons', ['Benchero\Controllers\Tenant\SeasonController', 'store']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/seasons/{id}/edit', ['Benchero\Controllers\Tenant\SeasonController', 'edit']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/seasons/{id}', ['Benchero\Controllers\Tenant\SeasonController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/seasons/{id}/current', ['Benchero\Controllers\Tenant\SeasonController', 'setCurrent']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/seasons/{id}/delete', ['Benchero\Controllers\Tenant\SeasonController', 'delete']);

// Tenant Players & Rosters
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/players', ['Benchero\Controllers\Tenant\PlayerController', 'index']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/players/create', ['Benchero\Controllers\Tenant\PlayerController', 'create']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/players', ['Benchero\Controllers\Tenant\PlayerController', 'store']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/players/{id}', ['Benchero\Controllers\Tenant\PlayerController', 'show']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/players/{id}/edit', ['Benchero\Controllers\Tenant\PlayerController', 'edit']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/players/{id}', ['Benchero\Controllers\Tenant\PlayerController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/players/{id}/delete', ['Benchero\Controllers\Tenant\PlayerController', 'delete']);

$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/teams/{team_id}/rosters/{season_id}', ['Benchero\Controllers\Tenant\RosterController', 'index']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{team_id}/rosters/{season_id}', ['Benchero\Controllers\Tenant\RosterController', 'store']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{team_id}/rosters/{season_id}/assignments/{assignment_id}', ['Benchero\Controllers\Tenant\RosterController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/teams/{team_id}/rosters/{season_id}/assignments/{assignment_id}/delete', ['Benchero\Controllers\Tenant\RosterController', 'delete']);

// Tenant Staff
$router->addRoute('GET', '/o/{slug}/staff', ['Benchero\Controllers\Tenant\StaffController', 'index']);
$router->addRoute('GET', '/o/{slug}/staff/create', ['Benchero\Controllers\Tenant\StaffController', 'create']);
$router->addRoute('POST', '/o/{slug}/staff', ['Benchero\Controllers\Tenant\StaffController', 'store']);
$router->addRoute('GET', '/o/{slug}/staff/{id}/edit', ['Benchero\Controllers\Tenant\StaffController', 'edit']);
$router->addRoute('POST', '/o/{slug}/staff/{id}', ['Benchero\Controllers\Tenant\StaffController', 'update']);
$router->addRoute('POST', '/o/{slug}/staff/{id}/delete', ['Benchero\Controllers\Tenant\StaffController', 'delete']);

// Tenant Fixtures & Results
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/fixtures', ['Benchero\Controllers\Tenant\FixtureController', 'index']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/fixtures/create', ['Benchero\Controllers\Tenant\FixtureController', 'create']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/fixtures', ['Benchero\Controllers\Tenant\FixtureController', 'store']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/fixtures/{id}', ['Benchero\Controllers\Tenant\FixtureController', 'show']);
$router->addRoute('GET', '/o/{slug}/s/{sport_slug}/fixtures/{id}/edit', ['Benchero\Controllers\Tenant\FixtureController', 'edit']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/fixtures/{id}', ['Benchero\Controllers\Tenant\FixtureController', 'update']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/fixtures/{id}/status', ['Benchero\Controllers\Tenant\FixtureController', 'status']);
$router->addRoute('POST', '/o/{slug}/s/{sport_slug}/fixtures/{id}/result', ['Benchero\Controllers\Tenant\FixtureController', 'saveResult']);

// Tenant Billing & M-Pesa
$router->addRoute('GET', '/o/{slug}/billing', ['Benchero\Controllers\Tenant\BillingController', 'index']);
$router->addRoute('POST', '/o/{slug}/billing/stkpush', ['Benchero\Controllers\Tenant\BillingController', 'stkPush']);
$router->addRoute('POST', '/billing/mpesa/callback', ['Benchero\Controllers\Public\MpesaCallbackController', 'handle']);

// Platform Admin Routes
$router->addRoute('GET', '/admin', ['Benchero\Controllers\Admin\AdminController', 'index']);

return $router;
