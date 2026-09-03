<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(($tenant['name'] ?? 'Benchero') . ' — Benchero') ?></title>
    <link rel="icon" type="image/x-icon" href="<?= url('/favicon.ico') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/images/logo.png') ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
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
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/') ?>">
                <span class="navbar-brand-text text-white">BENCHERO</span>
                <span class="badge bg-primary fs-7"><?= htmlspecialchars($tenant['name'] ?? 'Club') ?></span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#tenantTopNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="tenantTopNav">
                <ul class="navbar-nav me-auto ms-lg-3">
                    <?php if (isset($tenant['slug'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/club/' . urlencode($tenant['slug'])) ?>" target="_blank">
                                <i class="bi bi-box-arrow-up-right me-1"></i> View Public Club Page
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item">
                        <a class="nav-link text-white-50" href="<?= url('/organizations') ?>">Switch Club</a>
                    </li>
                    <li class="nav-item">
                        <form action="<?= url('/logout') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php if (isset($tenant['slug'])): ?>
                <div class="col-lg-2 col-md-3 p-3 tenant-sidebar d-none d-md-block">
                    <div class="text-uppercase text-muted fw-bold fs-7 mb-2 px-2">Navigation</div>
                    <nav class="nav flex-column gap-1">
                        <a class="tenant-nav-link" href="<?= url('/o/' . urlencode($tenant['slug']) . '/dashboard') ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a class="tenant-nav-link" href="<?= url('/o/' . urlencode($tenant['slug']) . '/sports') ?>">
                            <i class="bi bi-trophy"></i> Sports
                        </a>
                        
                        <?php if (isset($sport['slug'])): ?>
                            <div class="text-uppercase text-muted fw-bold fs-7 mt-3 mb-2 px-2"><?= htmlspecialchars($sport['name']) ?></div>
                            <a class="tenant-nav-link" href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/seasons') ?>">
                                <i class="bi bi-calendar-event"></i> Seasons
                            </a>
                            <a class="tenant-nav-link" href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/teams') ?>">
                                <i class="bi bi-people"></i> Teams
                            </a>
                            <a class="tenant-nav-link" href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/players') ?>">
                                <i class="bi bi-person-badge"></i> Players
                            </a>
                            <a class="tenant-nav-link" href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff') ?>">
                                <i class="bi bi-person-vcard"></i> Staff
                            </a>
                            <a class="tenant-nav-link" href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/fixtures') ?>">
                                <i class="bi bi-calendar3"></i> Fixtures & Results
                            </a>
                        <?php endif; ?>

                        <div class="text-uppercase text-muted fw-bold fs-7 mt-3 mb-2 px-2">Club Settings</div>
                        <a class="tenant-nav-link" href="<?= url('/o/' . urlencode($tenant['slug']) . '/billing') ?>">
                            <i class="bi bi-credit-card"></i> Billing & M-Pesa
                        </a>
                    </nav>
                </div>
                <div class="col-lg-10 col-md-9 p-4">
            <?php else: ?>
                <div class="col-12 p-4">
            <?php endif; ?>

<?php
register_shutdown_function(function() {
    static $rendered = false;
    if (!$rendered) {
        $rendered = true;
        echo '</div></div></div>';
        echo '<footer class="bg-white py-3 mt-auto border-top"><div class="container text-center text-muted"><small>&copy; ' . date('Y') . ' Benchero. All rights reserved.</small></div></footer>';
        echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
    }
});
?>
