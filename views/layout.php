<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($title ?? 'Benchero — Your Club. Your Teams. Your Players. Your Game. Your Platform.') ?></title>
    <meta name="description" content="<?= $this->e($description ?? 'Benchero gives sports organizations one simple place to manage their clubs, teams, players, staff, seasons, fixtures and results.') ?>">
    <meta property="og:title" content="<?= $this->e($title ?? 'Benchero — Sports Club Management Platform') ?>">
    <meta property="og:description" content="Benchero is the modern multi-sport site platform for clubs, teams, players, fixtures and results.">
    <meta property="og:type" content="website">
    <?php if (!empty($robots)): ?>
    <meta name="robots" content="<?= $this->e($robots) ?>">
    <?php endif; ?>
    
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

        .nav-link:hover,
        .nav-link.active {
            color: var(--benchero-accent);
        }

        .dropdown-item {
            font-size: 0.9rem;
            padding: 0.55rem 1rem;
            color: #334155;
            transition: all 0.15s ease;
        }

        .dropdown-item:hover {
            background-color: #eff6ff;
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

        footer .text-slate-300 {
            color: #cbd5e1 !important;
        }

        footer .text-slate-400,
        footer .text-muted {
            color: #94a3b8 !important;
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
        <nav id="bencheroMainNavbar" class="navbar navbar-expand-lg navbar-benchero benchero-header-sticky">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/') ?>">
                    <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="34" class="rounded-2">
                    <span class="navbar-brand-text">BENCHERO</span>
                </a>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#bencheroNavbar" aria-controls="bencheroNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="bencheroNavbar">
                    <ul class="navbar-nav me-auto ms-lg-4 gap-lg-1">
                        <li class="nav-item">
                            <a class="nav-link <?= (($_SERVER['REQUEST_URI'] ?? '') === '/' || ($_SERVER['REQUEST_URI'] ?? '') === '') ? 'active' : '' ?>" href="<?= url('/') ?>">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/features') ? 'active' : '' ?>" href="<?= url('/features') ?>">Features</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/pricing') ? 'active' : '' ?>" href="<?= url('/pricing') ?>">Pricing</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-1 <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/sports') ? 'active' : '' ?>" href="#" id="sportsMenuDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>Sports Hub</span>
                            </a>
                            <ul class="dropdown-menu shadow-sm border-slate-200" aria-labelledby="sportsMenuDropdown">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="<?= url('/sports/live') ?>">
                                        <span><i class="bi bi-broadcast text-danger me-2"></i>Live Match Center</span>
                                        <span class="badge bg-danger rounded-pill px-2">LIVE</span>
                                    </a>
                                </li>
                                <li><a class="dropdown-item" href="<?= url('/sports/fixtures') ?>"><i class="bi bi-calendar3 text-primary me-2"></i>Upcoming Fixtures</a></li>
                                <li><a class="dropdown-item" href="<?= url('/sports/results') ?>"><i class="bi bi-trophy text-success me-2"></i>Latest Results</a></li>
                                <li><a class="dropdown-item" href="<?= url('/sports/competitions') ?>"><i class="bi bi-diagram-3 text-warning me-2"></i>Leagues & Tables</a></li>
                                <li><a class="dropdown-item" href="<?= url('/sports/clubs') ?>"><i class="bi bi-shield-check text-info me-2"></i>Verified Clubs</a></li>
                                <li><a class="dropdown-item" href="<?= url('/sports/news') ?>"><i class="bi bi-newspaper text-secondary me-2"></i>Sports News</a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><a class="dropdown-item fw-semibold" href="<?= url('/sports') ?>"><i class="bi bi-grid-fill me-2 text-primary"></i>All Sports Overview</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/about') ? 'active' : '' ?>" href="<?= url('/about') ?>">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/contact') ? 'active' : '' ?>" href="<?= url('/contact') ?>">Contact</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="<?= url('/organizations') ?>" class="btn btn-benchero-outline d-inline-flex align-items-center gap-1">
                                <i class="bi bi-grid"></i>
                                <span>My Clubs</span>
                            </a>
                            <form action="<?= url('/logout') ?>" method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-link text-decoration-none text-muted" aria-label="Logout">
                                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="<?= url('/login') ?>" class="btn btn-benchero-outline">Login</a>
                            <a href="<?= url('/register') ?>" class="btn btn-benchero-primary">Start Free Trial</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow-1 benchero-page-content">
        <?= $this->section('content') ?>
    </main>

    <footer>
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 fw-bold text-white fs-4 mb-2">
                        <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="32" class="rounded-2">
                        BENCHERO
                    </div>
                    <p class="small text-slate-300 mb-3">Your Club. Your Teams. Your Players. Your Game. Your Platform.</p>
                    <p class="small text-slate-400 mb-0">The modern multi-sport operating system for sports organizations, clubs, academies, leagues, and tournaments.</p>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Platform</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="<?= url('/features') ?>">Features</a></li>
                        <li><a href="<?= url('/pricing') ?>">Pricing Plans</a></li>
                        <li><a href="<?= url('/register') ?>">Start Free Trial</a></li>
                        <li><a href="<?= url('/about') ?>">About Benchero</a></li>
                        <li><a href="<?= url('/contact') ?>">Get in Touch</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Sports Hub</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="<?= url('/sports/live') ?>"><span class="badge bg-danger rounded-pill me-1" style="font-size: 0.65rem;">LIVE</span> Scores</a></li>
                        <li><a href="<?= url('/sports/fixtures') ?>">Upcoming Fixtures</a></li>
                        <li><a href="<?= url('/sports/results') ?>">Match Results</a></li>
                        <li><a href="<?= url('/sports/competitions') ?>">Leagues & Tables</a></li>
                        <li><a href="<?= url('/sports/clubs') ?>">Verified Clubs</a></li>
                        <li><a href="<?= url('/sports/news') ?>">Sports News</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Legal & Trust</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="<?= url('/terms') ?>">Terms of Service</a></li>
                        <li><a href="<?= url('/privacy') ?>">Privacy Policy</a></li>
                        <li><a href="<?= url('/cookies') ?>">Cookie Policy</a></li>
                        <li><a href="<?= url('/contact') ?>">Support Center</a></li>
                    </ul>
                </div>
                <div class="col-6 col-lg-2">
                    <h6 class="text-white fw-semibold mb-3">Access</h6>
                    <ul class="list-unstyled small d-grid gap-2">
                        <li><a href="<?= url('/login') ?>">Club Staff Login</a></li>
                        <li><a href="<?= url('/register') ?>">Register New Club</a></li>
                        <li><a href="<?= url('/organizations') ?>">Control Center</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary opacity-25 my-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-slate-400 gap-2">
                <div>&copy; <?= date('Y') ?> Benchero Ltd. All rights reserved.</div>
                <div>Engineered for Grassroots, Academies & Professional Sports Organizations</div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= url('/assets/js/benchero-motion.js') ?>" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var nav = document.getElementById('bencheroMainNavbar');
            if (nav) {
                var handleScroll = function() {
                    if (window.scrollY > 20) {
                        nav.classList.add('navbar-scrolled');
                    } else {
                        nav.classList.remove('navbar-scrolled');
                    }
                };
                window.addEventListener('scroll', handleScroll, { passive: true });
                handleScroll();
            }
        });
    </script>
</body>
</html>
