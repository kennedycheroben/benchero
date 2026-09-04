<?php
    $themeId = $settings['theme_id'] ?? 'modern_sport';
    $primaryColor = $settings['primary_color'] ?? '#0d6efd';
    $secondaryColor = $settings['secondary_color'] ?? '#1e293b';
    $accentColor = $settings['accent_color'] ?? '#ffc107';
    $textColor = $settings['text_color'] ?? '#0f172a';
    $pageVis = $settings['page_visibility'] ?? [];
    $navLabels = $settings['navigation_labels'] ?? [];
    $navOrder = $settings['navigation_order'] ?? ['about', 'teams', 'players', 'staff', 'fixtures', 'results', 'standings', 'news', 'gallery', 'history', 'sponsors', 'contact'];

    $currentRoute = $activeRoute ?? 'home';
    $orgSlug = htmlspecialchars($org['slug']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? ($org['name'] . ' — Official Website')) ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($org['description'] ?: ($org['name'] . ' official sports club website powered by Benchero.')) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($org['name']) ?> Official Website">
    <meta property="og:description" content="<?= htmlspecialchars($org['description'] ?: 'Official club fixtures, results, team roster, news, and history.') ?>">
    <?php if (!empty($org['logo_url'])): ?>
        <meta property="og:image" content="<?= htmlspecialchars($org['logo_url']) ?>">
    <?php endif; ?>

    <!-- Favicon & Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom Theme Variables -->
    <style>
        :root {
            --club-primary: <?= $primaryColor ?>;
            --club-secondary: <?= $secondaryColor ?>;
            --club-accent: <?= $accentColor ?>;
            --club-text: <?= $textColor ?>;
            --font-heading: 'Outfit', sans-serif;
            --font-body: 'Inter', sans-serif;
        }

        body {
            font-family: var(--font-body);
            color: var(--club-text);
            background-color: #f8fafc;
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: var(--font-heading);
            letter-spacing: -0.02em;
        }

        .btn-club-primary {
            background-color: var(--club-primary);
            color: #ffffff;
            border: none;
        }
        .btn-club-primary:hover {
            background-color: var(--club-secondary);
            color: #ffffff;
        }

        .bg-club-header {
            background-color: var(--club-secondary);
        }

        .bg-club-primary {
            background-color: var(--club-primary);
        }

        .text-club-primary {
            color: var(--club-primary);
        }
        .text-club-accent {
            color: var(--club-accent);
        }

        .club-navbar {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .club-nav-link {
            font-family: var(--font-heading);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.85);
            transition: all 0.2s ease;
            padding: 0.6rem 1rem !important;
            border-radius: 0.375rem;
        }

        .club-nav-link:hover, .club-nav-link.active {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.12);
        }

        .club-nav-link.active {
            border-bottom: 3px solid var(--club-accent);
        }

        /* Theme Variants */
        <?php if ($themeId === 'classic_club'): ?>
            .club-navbar { border-bottom: 4px solid var(--club-accent); }
            .hero-card { border-radius: 0 !important; }
        <?php elseif ($themeId === 'dynamic_athletic'): ?>
            .club-navbar { clip-path: polygon(0 0, 100% 0, 100% 90%, 0 100%); padding-bottom: 1rem; }
            .badge-category { transform: skewX(-10deg); }
        <?php endif; ?>
    </style>
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-xl sticky-top club-navbar bg-club-header navbar-dark py-2">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-3" href="/club/<?= $orgSlug ?>">
                <?php if (!empty($org['logo_url'])): ?>
                    <img src="<?= htmlspecialchars($org['logo_url']) ?>" alt="<?= htmlspecialchars($org['name']) ?>" height="46" class="rounded-2 bg-white p-1">
                <?php else: ?>
                    <div class="bg-club-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width:46px; height:46px;">
                        <?= strtoupper(substr($org['name'], 0, 2)) ?>
                    </div>
                <?php endif; ?>
                <div>
                    <span class="fw-black fs-4 text-white d-block lh-1 text-uppercase"><?= htmlspecialchars($org['name']) ?></span>
                    <?php if (!empty($org['club_colors'])): ?>
                        <small class="text-white-50 small font-heading text-uppercase" style="font-size:0.7rem; letter-spacing:0.1em;"><?= htmlspecialchars($org['club_colors']) ?></small>
                    <?php endif; ?>
                </div>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#clubNavbarOffcanvas" aria-controls="clubNavbarOffcanvas">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Desktop & Mobile Navigation Links -->
            <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="clubNavbarOffcanvas">
                <div class="offcanvas-header bg-club-header border-bottom border-secondary">
                    <h5 class="offcanvas-title fw-bold text-uppercase"><?= htmlspecialchars($org['name']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body bg-club-header">
                    <ul class="navbar-nav ms-auto align-items-xl-center gap-1">
                        <li class="nav-item">
                            <a class="nav-link club-nav-link <?= $currentRoute === 'home' ? 'active' : '' ?>" href="/club/<?= $orgSlug ?>">Home</a>
                        </li>

                        <?php foreach ($navOrder as $pageKey): ?>
                            <?php 
                                $isVisible = $pageVis[$pageKey] ?? true;
                                if (!$isVisible) continue;
                                $label = $navLabels[$pageKey] ?? ucfirst($pageKey);
                            ?>
                            <li class="nav-item">
                                <a class="nav-link club-nav-link <?= $currentRoute === $pageKey ? 'active' : '' ?>" href="/club/<?= $orgSlug ?>/<?= $pageKey ?>">
                                    <?= htmlspecialchars($label) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
