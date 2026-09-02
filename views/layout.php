<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($title ?? env('BRAND_NAME', 'Teamora')) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= url('/favicon.ico') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('/favicon.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('/images/logo.png') ?>">
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
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $this->url('/') ?>">
                <img src="<?= url('/images/logo.png') ?>" alt="Teamora Logo" height="32" class="d-inline-block rounded-1">
                <span><?= $this->e(env('BRAND_NAME', 'Teamora')) ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->url('/login') ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="<?= $this->url('/register') ?>">Get Started</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <?= $this->section('content') ?>
    </main>

    <footer class="bg-white py-4 mt-5 border-top">
        <div class="container text-center text-muted">
            <small>&copy; <?= date('Y') ?> <?= $this->e(env('BRAND_COMPANY_NAME', 'Teamora Inc.')) ?>. All rights reserved.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
