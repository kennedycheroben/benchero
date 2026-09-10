<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

use League\Plates\Engine;

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $title) {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] {$title}\n";
        $passed++;
    } else {
        echo " [FAIL] {$title}\n";
        $failed++;
    }
}

echo "==================================================\n";
echo "  BENCHERO MOTION & TRANSITION SYSTEM TEST SUITE  \n";
echo "==================================================\n\n";

// 1. Static Asset Files Verification
$cssPath = __DIR__ . '/../public/assets/css/benchero-motion.css';
$jsPath = __DIR__ . '/../public/assets/js/benchero-motion.js';

assertTest(file_exists($cssPath) && filesize($cssPath) > 500, "benchero-motion.css exists and is non-empty");
assertTest(file_exists($jsPath) && filesize($jsPath) > 500, "benchero-motion.js exists and is non-empty");

$css = file_get_contents($cssPath);
$js = file_get_contents($jsPath);

// 2. CSS Motion Tokens & Variables
assertTest(str_contains($css, '--motion-fast: 150ms;'), "CSS defines --motion-fast timing token");
assertTest(str_contains($css, '--motion-normal: 220ms;'), "CSS defines --motion-normal timing token");
assertTest(str_contains($css, '--ease-standard:') && str_contains($css, '--ease-out:'), "CSS defines standardized professional easing curves");
assertTest(str_contains($css, '@view-transition'), "CSS includes Cross-Document View Transitions API (@view-transition)");
assertTest(str_contains($css, '::view-transition-old(root)') && str_contains($css, '::view-transition-new(root)'), "CSS defines View Transition pseudo-element animations");
assertTest(str_contains($css, '#benchero-progress-bar'), "CSS defines top progress bar styles");
assertTest(str_contains($css, 'benchero-page-entered') && str_contains($css, 'benchero-page-exiting'), "CSS defines page enter and exit transition classes");
assertTest(str_contains($css, '.benchero-reveal') && str_contains($css, '.benchero-revealed'), "CSS defines section reveal classes");
assertTest(str_contains($css, '.benchero-hero-item') && str_contains($css, '.stagger-1'), "CSS defines hero stagger sequence");
assertTest(str_contains($css, '.btn.is-loading'), "CSS defines button submit loading state");
assertTest(str_contains($css, '@media (prefers-reduced-motion: reduce)'), "CSS includes comprehensive prefers-reduced-motion accessibility overrides");

// 3. JS Transition Coordinator & Exclusions
assertTest(str_contains($js, 'BencheroProgress'), "JS defines BencheroProgress manager");
assertTest(str_contains($js, 'prefersReducedMotion'), "JS detects prefers-reduced-motion");
assertTest(str_contains($js, 'supportsViewTransitions'), "JS detects View Transitions API support");
assertTest(str_contains($js, 'isEligibleNavigation'), "JS implements strict link eligibility filtration");
assertTest(str_contains($js, 'metaKey') && str_contains($js, 'ctrlKey'), "JS respects modifier keys (opens in new tab/window)");
assertTest(str_contains($js, 'download') && str_contains($js, 'mailto') && str_contains($js, 'tel'), "JS excludes downloads, mailto, and tel links");
assertTest(str_contains($js, 'data-no-transition'), "JS supports explicit opt-out via data-no-transition");
assertTest(str_contains($js, '/logout'), "JS excludes sensitive logout requests from animation delays");
assertTest(str_contains($js, 'pageshow') && str_contains($js, 'persisted'), "JS handles BFCache restoration via pageshow event");
assertTest(str_contains($js, 'IntersectionObserver') && str_contains($js, 'obs.unobserve'), "JS implements efficient IntersectionObserver with unobserve");
assertTest(str_contains($js, 'safetyTimeout'), "JS includes navigation safety fallback timeout");

// 4. Template Rendering Verification via Plates Engine
$templates = new Engine(__DIR__ . '/../views');
$templates->registerFunction('url', 'url');

// Public Layout
$publicLayoutHtml = $templates->render('layout', ['title' => 'Test Page', 'content' => '<p>Test</p>']);
assertTest(str_contains($publicLayoutHtml, 'benchero-motion.css'), "Public layout includes benchero-motion.css");
assertTest(str_contains($publicLayoutHtml, 'benchero-motion.js'), "Public layout includes benchero-motion.js");
assertTest(str_contains($publicLayoutHtml, 'benchero-page-content'), "Public layout main element has benchero-page-content class");

// Public Pages
$homeHtml = $templates->render('home', ['title' => 'Home']);
assertTest(str_contains($homeHtml, 'benchero-hero-item'), "Home view contains benchero-hero-item elements");
assertTest(str_contains($homeHtml, 'benchero-reveal'), "Home view contains benchero-reveal elements");
assertTest(str_contains($homeHtml, 'benchero-interactive-card'), "Home view contains interactive card hover classes");

$aboutHtml = $templates->render('public/about');
assertTest(str_contains($aboutHtml, 'benchero-reveal'), "About view contains benchero-reveal elements");

// Club Website Header & Footer
ob_start();
$settings = [
    'theme_id' => 'modern_sport',
    'primary_color' => '#0d6efd',
    'secondary_color' => '#1e293b',
    'accent_color' => '#ffc107',
    'text_color' => '#0f172a',
    'page_visibility' => [],
    'navigation_labels' => [],
    'navigation_order' => ['about', 'teams', 'fixtures'],
];
$org = [
    'name' => 'Nairobi Cheetahs FC',
    'slug' => 'nairobi-cheetahs',
    'description' => 'Official Sports Club',
    'logo_url' => '',
    'club_colors' => 'Blue & Gold',
    'hide_benchero_branding' => 0
];
$activeRoute = 'home';
$pageTitle = 'Nairobi Cheetahs FC — Official Website';
$sponsors = [];

require __DIR__ . '/../views/public/club_website/layouts/theme_header.php';
echo '<div class="test-club-content">Club Body Content</div>';
require __DIR__ . '/../views/public/club_website/layouts/theme_footer.php';
$clubLayoutHtml = ob_get_clean();

assertTest(str_contains($clubLayoutHtml, 'benchero-motion.css'), "Club website layout includes benchero-motion.css");
assertTest(str_contains($clubLayoutHtml, 'benchero-motion.js'), "Club website layout includes benchero-motion.js");
assertTest(str_contains($clubLayoutHtml, 'benchero-page-content'), "Club website layout wraps content in benchero-page-content");

// Standalone Digital Card
ob_start();
$qr_code_svg = '<svg></svg>';
require __DIR__ . '/../views/public/club_website/card.php';
$cardHtml = ob_get_clean();

assertTest(str_contains($cardHtml, 'benchero-motion.css'), "Digital card includes benchero-motion.css");
assertTest(str_contains($cardHtml, 'benchero-motion.js'), "Digital card includes benchero-motion.js");
assertTest(str_contains($cardHtml, 'benchero-page-content'), "Digital card container has benchero-page-content");

// Tenant Main Dashboard Layout
$mainPhpContent = file_get_contents(__DIR__ . '/../views/layouts/main.php');
assertTest(str_contains($mainPhpContent, 'benchero-motion.css'), "Tenant dashboard layout links benchero-motion.css in head");
assertTest(str_contains($mainPhpContent, 'benchero-motion.js'), "Tenant dashboard layout links benchero-motion.js in shutdown function");
assertTest(str_contains($mainPhpContent, 'tenant-main-content'), "Tenant dashboard layout wraps main content with tenant-main-content class");

echo "\n==================================================\n";
echo "RESULTS: {$passed} Passed, {$failed} Failed\n";
echo "==================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
