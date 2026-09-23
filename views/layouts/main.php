<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= htmlspecialchars(($tenant['name'] ?? 'Benchero') . ' — Benchero Management') ?></title>
    <link rel="icon" type="image/x-icon" href="<?= url('/favicon.ico') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/images/benchero_logo.png') ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= url('/assets/css/benchero-motion.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/benchero-design-system.css') ?>">
    
    <style>
        :root {
            --benchero-primary: #0f172a;
            --benchero-accent: #2563eb;
            --benchero-bg: #f8fafc;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--benchero-bg);
            color: var(--benchero-primary);
        }
        .navbar-brand-text {
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.025em;
        }
        .tenant-sidebar {
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            min-height: calc(100vh - 65px);
        }
        @media (max-width: 767.98px) {
            .tenant-sidebar.offcanvas-md {
                width: 280px;
                max-width: 85vw;
                border-right: none;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
            .tenant-main-content {
                min-width: 0;
            }
        }
        .tenant-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            color: #475569;
            font-weight: 500;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .tenant-nav-link:hover, .tenant-nav-link.active {
            background-color: #eff6ff;
            color: var(--benchero-accent);
            font-weight: 600;
        }
        .fs-7 { font-size: 0.75rem; }

        /* Benchero Unread Messages Notification Badge */
        .benchero-unread-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25em 0.55em;
            line-height: 1;
            transition: all 0.2s ease;
        }
        @keyframes benchero-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.25); }
            100% { transform: scale(1); }
        }
        .benchero-unread-badge.pulse {
            animation: benchero-pulse 0.4s ease-in-out 2;
        }
        @media (prefers-reduced-motion: reduce) {
            .benchero-unread-badge.pulse {
                animation: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid px-3 px-md-4">
            <div class="d-flex align-items-center gap-2">
                <?php if (isset($tenant['slug'])): ?>
                    <button class="btn btn-outline-light btn-sm d-md-none me-1 px-2 py-1 d-flex align-items-center gap-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#tenantSidebar" aria-controls="tenantSidebar" aria-label="Toggle Sidebar Menu">
                        <i class="bi bi-layout-sidebar-inset fs-5"></i>
                        <span class="fw-semibold small">Menu</span>
                    </button>
                <?php endif; ?>
                <a class="navbar-brand d-flex align-items-center gap-2 m-0" href="<?= url('/') ?>">
                    <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="30" class="rounded-2">
                    <span class="navbar-brand-text text-white">BENCHERO</span>
                    <span class="badge bg-primary fs-7 text-truncate" style="max-width: 130px;"><?= htmlspecialchars($tenant['name'] ?? 'Club') ?></span>
                </a>
            </div>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#tenantTopNav" aria-controls="tenantTopNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="tenantTopNav">
                <ul class="navbar-nav me-auto ms-lg-3">
                    <?php if (isset($tenant['slug'])): ?>
                        <li class="nav-item">
                            <a class="nav-link text-info fw-semibold d-flex align-items-center" href="<?= url('/club/' . urlencode($tenant['slug'])) ?>" target="_blank">
                                <i class="bi bi-box-arrow-up-right me-1"></i> View Public Website
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="<?= url('/organizations') ?>"><i class="bi bi-arrow-left-right me-1"></i>Switch Club</a>
                    </li>
                    <li class="nav-item">
                        <form action="<?= url('/logout') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
                        </form>
                    </li>
                </ul>
                <?php if (isset($tenant['slug'])): ?>
                    <div class="d-md-none border-top border-secondary mt-3 pt-2">
                        <button class="btn btn-primary btn-sm w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#tenantSidebar" data-bs-dismiss="collapse">
                            <i class="bi bi-grid-fill"></i> Browse Control Center Menu
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php if (isset($tenant['slug'])): ?>
                <?php
                    $currentUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
                    $isActive = function($path) use ($currentUri) {
                        if ($path === '/website') {
                            return (str_ends_with($currentUri, '/website') || str_contains($currentUri, '/website?')) ? 'active' : '';
                        }
                        return (str_contains($currentUri, $path)) ? 'active' : '';
                    };
                ?>
                <div class="col-lg-2 col-md-3 p-0 p-md-3 tenant-sidebar offcanvas-md offcanvas-start bg-white" id="tenantSidebar" tabindex="-1" aria-labelledby="tenantSidebarLabel">
                    <div class="offcanvas-header border-bottom p-3 bg-dark text-white d-md-none justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="24" class="rounded">
                            <h5 class="offcanvas-title fw-bold fs-6 mb-0 text-white" id="tenantSidebarLabel">
                                <?= htmlspecialchars($tenant['name'] ?? 'Club') ?> Menu
                            </h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#tenantSidebar" aria-label="Close"></button>
                    </div>

                    <div class="offcanvas-body flex-column p-3 p-md-0">
                        <div class="text-uppercase text-muted fw-bold fs-7 mb-2 px-2">Control Center</div>
                        <nav class="nav flex-column gap-1">
                            <a class="tenant-nav-link <?= $isActive('/dashboard') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/dashboard') ?>">
                                <i class="bi bi-speedometer2"></i> Overview
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/profile') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/profile') ?>">
                                <i class="bi bi-sliders"></i> Club Branding
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/sports') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/sports') ?>">
                                <i class="bi bi-trophy"></i> Sports & Disciplines
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/staff') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff') ?>">
                                <i class="bi bi-person-vcard"></i> Staff & Management
                            </a>

                            <?php if (isset($sport['slug'])): ?>
                                <div class="text-uppercase text-muted fw-bold fs-7 mt-3 mb-2 px-2"><?= htmlspecialchars($sport['name']) ?></div>
                                <a class="tenant-nav-link <?= $isActive('/seasons') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/seasons') ?>">
                                    <i class="bi bi-calendar-event"></i> Seasons
                                </a>
                                <a class="tenant-nav-link <?= $isActive('/teams') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/teams') ?>">
                                    <i class="bi bi-people"></i> Teams
                                </a>
                                <a class="tenant-nav-link <?= $isActive('/players') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/players') ?>">
                                    <i class="bi bi-person-badge"></i> Players
                                </a>
                                <a class="tenant-nav-link <?= $isActive('/fixtures') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/fixtures') ?>">
                                    <i class="bi bi-calendar3"></i> Fixtures & Results
                                </a>
                            <?php endif; ?>

                            <div class="text-uppercase text-muted fw-bold fs-7 mt-3 mb-2 px-2">Club Website</div>
                            <a class="tenant-nav-link <?= ($isActive('/website') && !$isActive('/website/')) ? 'active' : '' ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/website') ?>">
                                <i class="bi bi-globe2"></i> Website Status
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/website/customize') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/website/customize') ?>">
                                <i class="bi bi-paint-bucket"></i> Customize & Crest
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/website/homepage') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/website/homepage') ?>">
                                <i class="bi bi-layout-split"></i> Homepage Builder
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/website/navigation') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/website/navigation') ?>">
                                <i class="bi bi-compass"></i> Nav & Visibility
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/website/themes') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/website/themes') ?>">
                                <i class="bi bi-brush"></i> Themes
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/website/history') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/website/history') ?>">
                                <i class="bi bi-clock-history"></i> Club Timeline
                            </a>

                            <div class="text-uppercase text-muted fw-bold fs-7 mt-3 mb-2 px-2">Media & Platform</div>
                            <?php
                                $unreadContactCount = isset($tenant['id']) ? \Benchero\Services\ContactMessageService::getUnreadCount($tenant['id']) : 0;
                                $badgeText = $unreadContactCount > 9 ? '10+' : (string)$unreadContactCount;
                                $hasUnread = $unreadContactCount > 0;
                            ?>
                            <a class="tenant-nav-link d-flex align-items-center justify-content-between <?= $isActive('/contact-messages') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/contact-messages') ?>">
                                <span class="d-flex align-items-center gap-2 text-truncate">
                                    <i class="bi bi-envelope-open"></i> Contact Messages
                                </span>
                                <span id="unread-contact-badge" 
                                      class="badge rounded-pill bg-success ms-auto align-self-center benchero-unread-badge <?= $hasUnread ? '' : 'd-none' ?>" 
                                      data-count="<?= $unreadContactCount ?>"
                                      title="<?= $unreadContactCount ?> unread message<?= $unreadContactCount === 1 ? '' : 's' ?>"
                                      aria-label="<?= $unreadContactCount ?> unread contact message<?= $unreadContactCount === 1 ? '' : 's' ?>">
                                    <?= htmlspecialchars($badgeText) ?>
                                </span>
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/media') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/media') ?>">
                                <i class="bi bi-folder2-open"></i> Media Library
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/content') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/content') ?>">
                                <i class="bi bi-newspaper"></i> News & Gallery
                            </a>
                            <a class="tenant-nav-link <?= $isActive('/billing') ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/billing') ?>">
                                <i class="bi bi-credit-card"></i> Subscription & M-Pesa
                            </a>
                        </nav>
                    </div>
                </div>
                <div class="col-lg-10 col-md-9 p-3 p-md-4 tenant-main-content">
            <?php else: ?>
                <div class="col-12 p-3 p-md-4 tenant-main-content">
            <?php endif; ?>

<?php
register_shutdown_function(function() use ($tenant) {
    static $rendered = false;
    if (!$rendered) {
        $rendered = true;
        echo '</div></div></div>';
        echo '<footer class="bg-white py-3 mt-auto border-top"><div class="container text-center text-muted"><small>&copy; ' . date('Y') . ' Benchero — Multi-Sport Management.</small></div></footer>';
        echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>';
        if (isset($tenant['slug'])) {
            $unreadEndpoint = json_encode(url('/o/' . urlencode($tenant['slug']) . '/contact-messages/unread-count'));
            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                const badge = document.getElementById('unread-contact-badge');
                if (!badge) return;
                function checkUnreadCount() {
                    fetch({$unreadEndpoint}, {
                        headers: { 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success && typeof data.unread_count === 'number') {
                            const newCount = data.unread_count;
                            const oldCount = parseInt(badge.getAttribute('data-count') || '0', 10);
                            badge.setAttribute('data-count', newCount);
                            badge.setAttribute('title', newCount + ' unread message' + (newCount === 1 ? '' : 's'));
                            badge.setAttribute('aria-label', newCount + ' unread contact message' + (newCount === 1 ? '' : 's'));
                            if (newCount > 0) {
                                badge.textContent = newCount > 9 ? '10+' : newCount;
                                badge.classList.remove('d-none');
                                if (newCount > oldCount) {
                                    badge.classList.remove('pulse');
                                    void badge.offsetWidth;
                                    badge.classList.add('pulse');
                                }
                            } else {
                                badge.classList.add('d-none');
                            }
                        }
                    })
                    .catch(() => {});
                }
                setInterval(checkUnreadCount, 45000);
            });
            </script>";
        }
        echo '<script src="' . url('/assets/js/benchero-motion.js') . '" defer></script></body></html>';
    }
});
?>
