<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($title ?? 'Benchero — Your Club. Your Teams. Your Players. Your Game. Your Platform.') ?></title>
    <meta name="description" content="<?= $this->e($description ?? 'Benchero gives sports organizations one simple place to manage their clubs, teams, players, staff, seasons, fixtures and results.') ?>">
    <meta property="og:title" content="<?= $this->e($title ?? 'Benchero — Sports Club Management Platform') ?>">
    <meta property="og:description" content="Benchero is the modern multi-sport SaaS platform for clubs, teams, players, fixtures and results.">
    <meta property="og:type" content="website">
    
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
            --benchero-accent-hover: #1d4ed8;
            --benchero-bg: #f8fafc;
            --benchero-text: #0f172a;
            --benchero-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--benchero-bg);
            color: var(--benchero-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-benchero {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.85rem 0;
        }

        .navbar-brand-text {
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -0.025em;
            color: var(--benchero-primary);
        }

        .navbar-brand-badge {
            font-size: 0.65rem;
            font-weight: 700;
            background: #eff6ff;
            color: var(--benchero-accent);
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .nav-link {
            font-weight: 500;
            color: #334155;
            transition: color 0.15s ease;
        }

        .nav-link:hover {
            color: var(--benchero-accent);
        }

        .btn-benchero-primary {
            background-color: var(--benchero-accent);
            color: #ffffff;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
        }

        .btn-benchero-primary:hover {
            background-color: var(--benchero-accent-hover);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .btn-benchero-outline {
            border: 1px solid #cbd5e1;
            color: var(--benchero-primary);
            font-weight: 600;
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            transition: all 0.2s ease;
        }

        .btn-benchero-outline:hover {
            background-color: #f1f5f9;
            color: var(--benchero-primary);
        }

        footer {
            margin-top: auto;
            background-color: #0f172a;
            color: #94a3b8;
            padding-top: 3.5rem;
            padding-bottom: 2rem;
        }

        footer a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        footer a:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-benchero sticky-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/') ?>">
                    <span class="navbar-brand-text">BENCHERO</span>
                    <span class="navbar-brand-badge">SaaS</span>
                </a>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#bencheroNavbar" aria-controls="bencheroNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="bencheroNavbar">
                    <ul class="navbar-nav me-auto ms-lg-4 gap-lg-2">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/') ?>">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/about') ?>">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/pricing') ?>">Pricing</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/contact') ?>">Contact</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="<?= url('/organizations') ?>" class="btn btn-benchero-outline">Organizations</a>
                            <form action="<?= url('/logout') ?>" method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-link text-decoration-none text-muted">Logout</button>
                            </form>
                        <?php else: ?>
                            <a href="<?= url('/login') ?>" class="btn btn-benchero-outline">Login</a>
                            <a href="<?= url('/register') ?>" class="btn btn-benchero-primary">Start Free</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow-1">
        <?= $this->section('content') ?>
    </main>

    <footer>
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="fw-bold text-white fs-4 mb-2">BENCHERO</div>
                    <p class="small text-slate-400 mb-3">Your Club. Your Teams. Your Players. Your Game. Your Platform.</p>
                    <p class="small text-muted mb-0">The modern multi-sport platform for sports organizations, clubs, and academies.</p>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Product</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="<?= url('/') ?>#features">Features</a></li>
                        <li><a href="<?= url('/pricing') ?>">Pricing</a></li>
                        <li><a href="<?= url('/register') ?>">Get Started</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Company</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="<?= url('/about') ?>">About Us</a></li>
                        <li><a href="<?= url('/contact') ?>">Contact</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Legal</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="<?= url('/terms') ?>">Terms of Service</a></li>
                        <li><a href="<?= url('/privacy') ?>">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Account</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="<?= url('/login') ?>">Login</a></li>
                        <li><a href="<?= url('/register') ?>">Create Account</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary opacity-25 my-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted gap-2">
                <div>&copy; <?= date('Y') ?> Benchero. All rights reserved.</div>
                <div>Designed for Grassroots & Professional Sports Organizations</div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
