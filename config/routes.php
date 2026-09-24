<?php

use Benchero\Core\Routing\Router;

$router = new Router();

// Public Marketing Routes
$router->addRoute('GET', '/', ['Benchero\Controllers\HomeController', 'index']);
$router->addRoute('GET', '/features', ['Benchero\Controllers\HomeController', 'features']);
$router->addRoute('GET', '/about', ['Benchero\Controllers\HomeController', 'about']);
$router->addRoute('GET', '/pricing', ['Benchero\Controllers\HomeController', 'pricing']);
$router->addRoute('POST', '/currency', ['Benchero\Controllers\HomeController', 'setCurrency']);
$router->addRoute('GET', '/currency', ['Benchero\Controllers\HomeController', 'setCurrency']);
$router->addRoute('GET', '/contact', ['Benchero\Controllers\HomeController', 'contactForm']);
$router->addRoute('POST', '/contact', ['Benchero\Controllers\HomeController', 'contactSubmit']);
$router->addRoute('GET', '/terms', ['Benchero\Controllers\HomeController', 'terms']);
$router->addRoute('GET', '/privacy', ['Benchero\Controllers\HomeController', 'privacy']);
$router->addRoute('GET', '/cookies', ['Benchero\Controllers\HomeController', 'cookies']);
$router->addRoute('GET', '/sitemap.xml', ['Benchero\Controllers\Public\SitemapController', 'sitemap']);
$router->addRoute('GET', '/robots.txt', ['Benchero\Controllers\Public\SitemapController', 'robots']);
$router->addRoute('GET', '/health', ['Benchero\Controllers\HealthController', 'check']);

// Public Sports Platform Routes
$router->addRoute('GET', '/sports', ['Benchero\Controllers\Public\SportsController', 'index']);
$router->addRoute('GET', '/sports/live', ['Benchero\Controllers\Public\SportsController', 'live']);
$router->addRoute('GET', '/sports/results', ['Benchero\Controllers\Public\SportsController', 'results']);
$router->addRoute('GET', '/sports/fixtures', ['Benchero\Controllers\Public\SportsController', 'fixtures']);
$router->addRoute('GET', '/sports/news', ['Benchero\Controllers\Public\SportsController', 'news']);
$router->addRoute('GET', '/sports/news/{slug}', ['Benchero\Controllers\Public\SportsController', 'newsDetail']);
$router->addRoute('GET', '/sports/competitions', ['Benchero\Controllers\Public\SportsController', 'competitions']);
$router->addRoute('GET', '/sports/c/{slug}', ['Benchero\Controllers\Public\SportsController', 'competitionDetail']);
$router->addRoute('GET', '/sports/clubs', ['Benchero\Controllers\Public\SportsController', 'clubs']);

// Sports Platform JSON APIs
$router->addRoute('GET', '/api/sports/live', ['Benchero\Controllers\Public\SportsController', 'apiLive']);
$router->addRoute('GET', '/api/sports/results', ['Benchero\Controllers\Public\SportsController', 'apiResults']);
$router->addRoute('GET', '/api/sports/fixtures', ['Benchero\Controllers\Public\SportsController', 'apiFixtures']);
$router->addRoute('GET', '/api/sports/news', ['Benchero\Controllers\Public\SportsController', 'apiNews']);


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
$router->addRoute('GET', '/club/{slug}/card', ['Benchero\Controllers\Public\PublicClubController', 'card']);

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

// Tenant Billing, M-Pesa STK Push & PayPal Checkout
$router->addRoute('GET', '/o/{slug}/billing', ['Benchero\Controllers\Tenant\BillingController', 'index']);
$router->addRoute('POST', '/o/{slug}/billing/payment-intent', ['Benchero\Controllers\Tenant\BillingController', 'createPaymentIntent']);
$router->addRoute('POST', '/o/{slug}/billing/payment-intent/{id}/initiate', ['Benchero\Controllers\Tenant\BillingController', 'initiatePayment']);
$router->addRoute('GET', '/o/{slug}/billing/payment-intent/{id}/status', ['Benchero\Controllers\Tenant\BillingController', 'getPaymentStatus']);
$router->addRoute('POST', '/o/{slug}/billing/stkpush', ['Benchero\Controllers\Tenant\BillingController', 'stkPush']);
$router->addRoute('POST', '/o/{slug}/billing/test-activate', ['Benchero\Controllers\Tenant\BillingController', 'testActivatePlan']);
$router->addRoute('POST', '/o/{slug}/billing/paypal/create-order', ['Benchero\Controllers\Tenant\BillingController', 'createPayPalOrder']);
$router->addRoute('POST', '/o/{slug}/billing/paypal/capture-order', ['Benchero\Controllers\Tenant\BillingController', 'capturePayPalOrder']);
$router->addRoute('POST', '/billing/mpesa/callback', ['Benchero\Controllers\Public\MpesaCallbackController', 'handle']);
$router->addRoute('POST', '/billing/imbank/callback', ['Benchero\Controllers\Public\MpesaCallbackController', 'handle']);
$router->addRoute('POST', '/billing/paypal/webhook', ['Benchero\Controllers\Public\PayPalWebhookController', 'handle']);
$router->addRoute(['GET', 'POST'], '/billing/paypal/return', ['Benchero\Controllers\Public\PayPalReturnController', 'return']);
$router->addRoute(['GET', 'POST'], '/billing/paypal/cancel', ['Benchero\Controllers\Public\PayPalReturnController', 'cancel']);


// Tenant Custom Domains Management
$router->addRoute('GET', '/o/{slug}/domain', ['Benchero\Controllers\Tenant\DomainController', 'index']);
$router->addRoute('POST', '/o/{slug}/domain', ['Benchero\Controllers\Tenant\DomainController', 'save']);
$router->addRoute('POST', '/o/{slug}/domain/verify', ['Benchero\Controllers\Tenant\DomainController', 'verify']);
$router->addRoute('POST', '/o/{slug}/domain/activate', ['Benchero\Controllers\Tenant\DomainController', 'activate']);
$router->addRoute('POST', '/o/{slug}/domain/ssl', ['Benchero\Controllers\Tenant\DomainController', 'checkSsl']);
$router->addRoute('POST', '/o/{slug}/domain/regenerate', ['Benchero\Controllers\Tenant\DomainController', 'regenerate']);
$router->addRoute('POST', '/o/{slug}/domain/delete', ['Benchero\Controllers\Tenant\DomainController', 'delete']);

// Tenant Digital Club Card & QR Codes
$router->addRoute('GET', '/o/{slug}/club-card', ['Benchero\Controllers\Tenant\ClubCardController', 'show']);

// Tenant Contact Messages
$router->addRoute('GET', '/o/{slug}/contact-messages', ['Benchero\Controllers\Tenant\ContactMessageController', 'index']);
$router->addRoute('GET', '/o/{slug}/contact-messages/unread-count', ['Benchero\Controllers\Tenant\ContactMessageController', 'unreadCount']);
$router->addRoute('POST', '/o/{slug}/contact-messages/mark-all-read', ['Benchero\Controllers\Tenant\ContactMessageController', 'markAllAsRead']);
$router->addRoute('POST', '/o/{slug}/contact-messages/{id}/status', ['Benchero\Controllers\Tenant\ContactMessageController', 'updateStatus']);
$router->addRoute('POST', '/o/{slug}/contact-messages/{id}/delete', ['Benchero\Controllers\Tenant\ContactMessageController', 'delete']);

// Tenant Data Export (Pro Feature)
$router->addRoute('GET', '/o/{slug}/export/{type}', ['Benchero\Controllers\Tenant\ExportController', 'export']);

// Platform Admin Routes
$router->addRoute('GET', '/admin', ['Benchero\Controllers\Admin\AdminController', 'index']);
$router->addRoute('POST', '/admin/users/role', ['Benchero\Controllers\Admin\AdminController', 'updateUserRole']);
$router->addRoute('GET', '/admin/payments', ['Benchero\Controllers\Admin\AdminController', 'payments']);
$router->addRoute('POST', '/admin/payments/reconcile', ['Benchero\Controllers\Admin\AdminController', 'reconcile']);
$router->addRoute('POST', '/admin/plans/update', ['Benchero\Controllers\Admin\AdminController', 'updatePlan']);

return $router;
