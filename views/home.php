<?php $this->layout('layout', ['title' => $title]) ?>

<!-- Hero Section -->
<section class="py-5 bg-white border-bottom position-relative overflow-hidden">
    <div class="container py-lg-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-7 text-center text-lg-start">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-3 benchero-hero-item stagger-1">
                    <i class="bi bi-trophy-fill me-1"></i> Multi-Sport Club Management Platform
                </span>
                <div class="d-flex align-items-center gap-3 justify-content-center justify-content-lg-start mb-3 benchero-hero-item stagger-1">
                    <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="64" class="rounded-3 shadow">
                    <h1 class="display-4 fw-extrabold text-slate-900 tracking-tight mb-0">
                        BENCHERO
                    </h1>
                </div>
                <p class="fs-4 fw-medium text-primary mb-4 benchero-hero-item stagger-2">
                    Your Club. Your Teams. Your Players. Your Game. Your Platform.
                </p>
                <p class="lead text-muted mb-4 pe-lg-5 benchero-hero-item stagger-2">
                    Benchero gives sports organizations one simple place to manage their clubs, teams, players, staff, seasons, fixtures, results and public club presence.
                </p>
                <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start gap-3 benchero-hero-item stagger-3">
                    <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg px-4 py-3 fw-bold rounded-3 shadow-sm">
                        Start Free <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                    <a href="<?= url('/about') ?>" class="btn btn-outline-secondary btn-lg px-4 py-3 fw-semibold rounded-3">
                        See How It Works
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-dark text-white p-4 benchero-hero-item stagger-3">
                    <div class="d-flex align-items-center justify-content-between mb-4 border-bottom border-secondary pb-3">
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="28" class="rounded-2">
                            <span class="fw-extrabold fs-4 tracking-tight">BENCHERO</span>
                            <span class="badge bg-success">Live Dashboard</span>
                        </div>
                        <i class="bi bi-shield-check text-success fs-4"></i>
                    </div>
                    <div class="d-grid gap-3">
                        <div class="p-3 bg-secondary bg-opacity-25 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-white-50">Active Sports</div>
                                <div class="fw-bold">Football, Basketball, Volleyball, Rugby</div>
                            </div>
                            <i class="bi bi-dribbble text-warning fs-3"></i>
                        </div>
                        <div class="p-3 bg-secondary bg-opacity-25 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-white-50">Fixture Management</div>
                                <div class="fw-bold">League, Cup & Friendly Schedules</div>
                            </div>
                            <i class="bi bi-calendar-check text-info fs-3"></i>
                        </div>
                        <div class="p-3 bg-secondary bg-opacity-25 rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-white-50">Public Club Presence</div>
                                <div class="fw-bold">Shareable Public Club Pages</div>
                            </div>
                            <i class="bi bi-globe text-primary fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Supported Sports Grid -->
<section class="py-5 bg-light border-bottom">
    <div class="container text-center py-4 benchero-reveal">
        <h6 class="text-uppercase text-muted fw-bold tracking-wider mb-4">Built for All Grassroots & Community Sports</h6>
        <div class="row g-4 justify-content-center">
            <div class="col-6 col-md-3">
                <div class="p-4 bg-white rounded-3 shadow-sm border text-center benchero-interactive-card h-100">
                    <i class="bi bi-activity text-primary fs-1 mb-2 d-block"></i>
                    <h5 class="fw-bold mb-1">Football</h5>
                    <span class="small text-muted">11v11, Futsal & Academies</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-4 bg-white rounded-3 shadow-sm border text-center benchero-interactive-card h-100">
                    <i class="bi bi-dribbble text-warning fs-1 mb-2 d-block"></i>
                    <h5 class="fw-bold mb-1">Basketball</h5>
                    <span class="small text-muted">5v5 & 3x3 Competitions</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-4 bg-white rounded-3 shadow-sm border text-center benchero-interactive-card h-100">
                    <i class="bi bi-circle-square text-info fs-1 mb-2 d-block"></i>
                    <h5 class="fw-bold mb-1">Volleyball</h5>
                    <span class="small text-muted">Indoor & Beach Leagues</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-4 bg-white rounded-3 shadow-sm border text-center benchero-interactive-card h-100">
                    <i class="bi bi-shield-shaded text-danger fs-1 mb-2 d-block"></i>
                    <h5 class="fw-bold mb-1">Rugby</h5>
                    <span class="small text-muted">Union & Sevens Teams</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Everything your club needs -->
<section id="features" class="py-5 bg-white">
    <div class="container py-lg-5">
        <div class="text-center max-w-2xl mx-auto mb-5 benchero-reveal">
            <h2 class="display-6 fw-bold mb-3">Everything your club needs. In one place.</h2>
            <p class="lead text-muted">Designed specifically for grassroots sports clubs, community leagues, schools, and multi-sport academies.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4 benchero-reveal">
                <div class="p-4 rounded-4 border bg-white h-100 shadow-sm benchero-interactive-card">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-buildings fs-3"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Club Management</h4>
                    <p class="text-muted mb-0">Centralize your sports club governance, organization details, and multi-sport tenant administration in one secure environment.</p>
                </div>
            </div>
            <div class="col-md-4 benchero-reveal">
                <div class="p-4 rounded-4 border bg-white h-100 shadow-sm benchero-interactive-card">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Team & Player Rosters</h4>
                    <p class="text-muted mb-0">Organize teams across age groups and categories. Maintain full player databases and seasonal roster assignments.</p>
                </div>
            </div>
            <div class="col-md-4 benchero-reveal">
                <div class="p-4 rounded-4 border bg-white h-100 shadow-sm benchero-interactive-card">
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-calendar-event fs-3"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Fixtures & Results</h4>
                    <p class="text-muted mb-0">Schedule home and away matches, log match scores, track venue details, and keep historical result archives.</p>
                </div>
            </div>
            <div class="col-md-4 benchero-reveal">
                <div class="p-4 rounded-4 border bg-white h-100 shadow-sm benchero-interactive-card">
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-person-badge fs-3"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Staff & Officials</h4>
                    <p class="text-muted mb-0">Assign coaches, team managers, assistant coaches, and administrative personnel to your club structure.</p>
                </div>
            </div>
            <div class="col-md-4 benchero-reveal">
                <div class="p-4 rounded-4 border bg-white h-100 shadow-sm benchero-interactive-card">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-globe fs-3"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Public Club Page</h4>
                    <p class="text-muted mb-0">Give your supporters and parents a clean public web presence showcasing upcoming fixtures, teams, and match results.</p>
                </div>
            </div>
            <div class="col-md-4 benchero-reveal">
                <div class="p-4 rounded-4 border bg-white h-100 shadow-sm benchero-interactive-card">
                    <div class="bg-dark bg-opacity-10 text-dark p-3 rounded-3 d-inline-block mb-3">
                        <i class="bi bi-phone fs-3"></i>
                    </div>
                    <h4 class="fw-bold mb-2">M-Pesa Friendly Billing</h4>
                    <p class="text-muted mb-0">Manage affordable subscriptions effortlessly with local M-Pesa payment integration and automated trial tracking.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-dark text-white text-center">
    <div class="container py-4 benchero-reveal">
        <h2 class="display-6 fw-extrabold mb-3">Ready to transform your club operations?</h2>
        <p class="lead text-white-50 mb-4">Start your free trial today and experience modern sports management built for your organization.</p>
        <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg px-5 py-3 fw-bold rounded-3">
            Start Free Trial Now
        </a>
    </div>
</section>
