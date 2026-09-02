<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tenant['name'] ?? 'Teamora') ?></title>
    <link rel="icon" type="image/x-icon" href="/teamora/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/teamora/favicon.png">
    <link rel="apple-touch-icon" href="/teamora/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="/teamora/">
                <img src="/teamora/images/logo.png" alt="Teamora Logo" height="32" class="d-inline-block rounded-1">
                <span><?= htmlspecialchars($tenant['name'] ?? 'Teamora') ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($tenant['slug'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/teamora/o/<?= htmlspecialchars($tenant['slug']) ?>/dashboard">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/teamora/o/<?= htmlspecialchars($tenant['slug']) ?>/sports">Sports</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/teamora/organizations">Organizations</a>
                    </li>
                    <li class="nav-item">
                        <form action="/teamora/logout" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-link nav-link text-decoration-none">Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<?php
register_shutdown_function(function() {
    static $rendered = false;
    if (!$rendered) {
        $rendered = true;
        echo '<footer class="bg-white py-4 mt-5 border-top"><div class="container text-center text-muted"><small>&copy; ' . date('Y') . ' Teamora Inc. All rights reserved.</small></div></footer>';
        echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>';
    }
});
?>
