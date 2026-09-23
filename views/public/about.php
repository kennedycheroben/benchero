<?php $this->layout('layout', ['title' => 'About Benchero — Complete Sports Club Management Platform']) ?>

<!-- ======================================================== -->
<!-- NEW SECTION: EXPANDED BENCHERO OVERVIEW & CAPABILITIES -->
<!-- ======================================================== -->
<div class="benchero-overview-extension bg-white border-bottom">
    
    <!-- SUBSECTION A: WHAT IS BENCHERO? -->
    <section class="py-5 bg-light border-bottom">
        <div class="container py-lg-3 benchero-reveal">
            <div class="row align-items-center g-4">
                <div class="col-lg-8 mx-auto text-center">
                    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-3">Platform Overview</span>
                    <h2 class="display-6 fw-extrabold text-dark mb-3">What is Benchero?</h2>
                    <p class="lead text-secondary mb-3 fs-5">
                        Benchero is a digital platform built for sports clubs, teams, academies, and organizations that want to manage their sporting operations and build a stronger digital presence from one place.
                    </p>
                    <p class="text-muted mb-4">
                        From teams and players to fixtures, results, competitions, media, and public club information, Benchero brings the essential parts of your sports organization together in one organized platform.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="<?= url('/register') ?>" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm">
                            <i class="bi bi-rocket-takeoff me-2"></i>Start Free Trial
                        </a>
                        <a href="<?= url('/pricing') ?>" class="btn btn-outline-secondary fw-semibold rounded-pill px-4">
                            <i class="bi bi-tag me-2"></i>View Pricing
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SUBSECTION B: WHAT CAN BENCHERO DO? -->
    <section class="py-5 bg-white border-bottom">
        <div class="container py-lg-2 benchero-reveal">
            <div class="text-center max-w-2xl mx-auto mb-4" style="max-width: 720px;">
                <span class="text-primary fw-bold text-uppercase small">Core Capabilities</span>
                <h3 class="h2 fw-bold text-dark mt-1 mb-2">What Can Benchero Do?</h3>
                <p class="text-muted small mb-0">Verified, sports-specific management tools built directly into the Benchero platform.</p>
            </div>
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-shield-check fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Club Management</h6>
                        </div>
                        <p class="text-muted small mb-0">Manage your sports organization, club profile, governance, and operational details from a centralized platform.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-diagram-3 fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Team Management</h6>
                        </div>
                        <p class="text-muted small mb-0">Create and manage multiple teams, squads, and age-group tiers operating within your organization.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-people fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Player Management</h6>
                        </div>
                        <p class="text-muted small mb-0">Organize player profiles, positions, contact details, jersey numbers, and team roster assignments.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-calendar-event fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Fixtures</h6>
                        </div>
                        <p class="text-muted small mb-0">Schedule and publish upcoming match fixtures, home and away venues, opponents, and kick-off times.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-trophy fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Results</h6>
                        </div>
                        <p class="text-muted small mb-0">Record and present completed match scores and final results to keep players and supporters updated.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-table fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Competitions & Standings</h6>
                        </div>
                        <p class="text-muted small mb-0">Organize tournament fixtures and view structured league tables and sporting standings where supported.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-globe fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Public Club Website</h6>
                        </div>
                        <p class="text-muted small mb-0">Give your club a dedicated public digital website where visitors discover your teams, matches, and news.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-images fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Sports Media</h6>
                        </div>
                        <p class="text-muted small mb-0">Manage photo galleries, match reports, and media content (available where supported by your selected plan).</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-3 bg-primary-subtle text-primary p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-graph-up fs-5"></i>
                            </div>
                            <h6 class="fw-bold mb-0">Statistics</h6>
                        </div>
                        <p class="text-muted small mb-0">Present sporting statistics, match performance information, and historical records where implemented.</p>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border shadow-sm rounded-3 p-3 bg-light">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                            <div class="d-flex align-items-center">
                                <div class="rounded-3 bg-primary text-white p-2 me-3 d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-qr-code-scan fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0">Custom Digital Identity</h6>
                                    <p class="text-muted small mb-0">Use digital club cards, SVG QR codes, and custom domain connections where included in your selected plan.</p>
                                </div>
                            </div>
                            <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 align-self-start align-self-md-center">Plan-Specific (Pro)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SUBSECTION C: THE ADVANTAGES -->
    <section class="py-5 bg-light border-bottom">
        <div class="container py-lg-2 benchero-reveal">
            <div class="text-center max-w-2xl mx-auto mb-4" style="max-width: 720px;">
                <span class="text-primary fw-bold text-uppercase small">Real Benefits</span>
                <h3 class="h2 fw-bold text-dark mt-1 mb-2">Why Use Benchero?</h3>
                <p class="text-muted small mb-0">Purpose-built advantages designed around the everyday needs of sports administrators.</p>
            </div>
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="text-primary mb-2"><i class="bi bi-collection fs-4"></i></div>
                        <h6 class="fw-bold mb-1">One Central Platform</h6>
                        <p class="text-muted small mb-0">Keep important club information and sporting operations organized in one place instead of relying on scattered spreadsheets, messages, documents, and social-media posts.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="text-primary mb-2"><i class="bi bi-clock-history fs-4"></i></div>
                        <h6 class="fw-bold mb-1">Less Administrative Work</h6>
                        <p class="text-muted small mb-0">Reduce repetitive manual work involved in managing teams, players, fixtures, results, and club information.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="text-primary mb-2"><i class="bi bi-grid-fill fs-4"></i></div>
                        <h6 class="fw-bold mb-1">Multiple Teams</h6>
                        <p class="text-muted small mb-0">Manage multiple teams under one sports organization with separated rosters and dedicated team profiles.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="text-primary mb-2"><i class="bi bi-award fs-4"></i></div>
                        <h6 class="fw-bold mb-1">Professional Digital Presence</h6>
                        <p class="text-muted small mb-0">Give your club a dedicated digital presence instead of depending entirely on social-media pages.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="text-primary mb-2"><i class="bi bi-phone fs-4"></i></div>
                        <h6 class="fw-bold mb-1">Easy Access</h6>
                        <p class="text-muted small mb-0">Keep important sporting information organized and accessible from any desktop or mobile device.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="text-primary mb-2"><i class="bi bi-dribbble fs-4"></i></div>
                        <h6 class="fw-bold mb-1">Built for Sports</h6>
                        <p class="text-muted small mb-0">Benchero is designed around the needs of sports organizations rather than being a generic business-management tool.</p>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center">
                            <div class="text-success me-3"><i class="bi bi-arrow-up-right-circle fs-3"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1">Grow With Your Organization</h6>
                                <p class="text-muted small mb-0">Choose the plan that fits your organization's current needs and upgrade seamlessly when additional capabilities are required.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SUBSECTION D: FROM CLUB OPERATIONS TO PUBLIC PRESENCE -->
    <section class="py-5 bg-white border-bottom">
        <div class="container py-lg-2 benchero-reveal">
            <div class="text-center max-w-2xl mx-auto mb-4" style="max-width: 780px;">
                <span class="text-primary fw-bold text-uppercase small">Structured Connection</span>
                <h3 class="h2 fw-bold text-dark mt-1 mb-2">More Than Club Administration</h3>
                <p class="text-secondary small leading-relaxed mb-0">
                    Your club needs more than a place to store player names and fixtures. It needs a structured way to manage sporting operations and present the organization to players, supporters, parents, opponents, and the wider community.
                </p>
            </div>
            
            <!-- Connection Flow -->
            <div class="p-3 bg-light rounded-4 border shadow-sm">
                <div class="row row-cols-2 row-cols-sm-4 row-cols-lg-8 g-2 text-center align-items-center">
                    <div class="col">
                        <div class="p-2 bg-white rounded-3 border h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-shield-check text-primary fs-5 mb-1"></i>
                            <span class="fw-bold small">Club</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2 bg-white rounded-3 border h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-diagram-3 text-primary fs-5 mb-1"></i>
                            <span class="fw-bold small">Teams</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2 bg-white rounded-3 border h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-people text-primary fs-5 mb-1"></i>
                            <span class="fw-bold small">Players</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2 bg-white rounded-3 border h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-calendar-event text-primary fs-5 mb-1"></i>
                            <span class="fw-bold small">Fixtures</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2 bg-white rounded-3 border h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-trophy text-primary fs-5 mb-1"></i>
                            <span class="fw-bold small">Results</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2 bg-white rounded-3 border h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-table text-primary fs-5 mb-1"></i>
                            <span class="fw-bold small">Competitions</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2 bg-white rounded-3 border h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-images text-primary fs-5 mb-1"></i>
                            <span class="fw-bold small">Media</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="p-2 bg-primary text-white rounded-3 h-100 d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-globe fs-5 mb-1"></i>
                            <span class="fw-bold small">Public Site</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SUBSECTION E: NO SEPARATE BENCHERO MAINTENANCE FEES -->
    <section class="py-5 bg-light border-bottom">
        <div class="container py-lg-2 benchero-reveal">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <div class="row align-items-center g-4">
                            <div class="col-md-7">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-success-subtle text-success fw-bold px-3 py-1 rounded-pill me-2">Transparent Pricing</span>
                                </div>
                                <h3 class="h3 fw-bold text-dark mb-2">No Separate Benchero Maintenance Fees</h3>
                                <p class="text-secondary small leading-relaxed mb-2">
                                    Your selected Benchero plan includes access to the platform and the features included in that plan without separate Benchero setup or maintenance charges.
                                </p>
                                <p class="text-muted small leading-relaxed mb-3">
                                    Once you purchase your selected plan, you do not pay an additional recurring Benchero maintenance fee on top of that plan.
                                </p>
                                <p class="text-muted" style="font-size: 0.8rem; line-height: 1.4;">
                                    <i class="bi bi-info-circle me-1 text-primary"></i>
                                    <em>Please note: Optional third-party services, such as domain registration with a domain registrar where applicable, are separate external costs if selected.</em>
                                </p>
                            </div>
                            <div class="col-md-5">
                                <div class="p-3 bg-light rounded-3 border text-center">
                                    <div class="p-2 mb-2 bg-white rounded-2 border small fw-bold text-dark">
                                        YOUR SELECTED PLAN
                                    </div>
                                    <div class="text-primary py-1"><i class="bi bi-arrow-down fs-5"></i></div>
                                    <div class="p-2 mb-2 bg-white rounded-2 border small fw-bold text-primary">
                                        BENCHERO PLATFORM ACCESS
                                    </div>
                                    <div class="text-primary py-1"><i class="bi bi-arrow-down fs-5"></i></div>
                                    <div class="p-2 bg-success text-white rounded-2 small fw-bold shadow-sm">
                                        NO SEPARATE BENCHERO MAINTENANCE CHARGE
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SUBSECTION F: WHAT YOU GET WITH BENCHERO -->
    <section class="py-5 bg-white border-bottom">
        <div class="container py-lg-2 benchero-reveal">
            <div class="text-center max-w-2xl mx-auto mb-4" style="max-width: 720px;">
                <span class="text-primary fw-bold text-uppercase small">Platform Deliverables</span>
                <h3 class="h2 fw-bold text-dark mt-1 mb-2">What You Get With Benchero</h3>
                <p class="text-muted small mb-0">Exact capabilities depend on your organization's selected plan, providing verified features with zero guesswork.</p>
            </div>
            <div class="row g-3 justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-light">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
                                <span class="small fw-semibold text-dark">Sports organization management</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
                                <span class="small fw-semibold text-dark">Team management</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
                                <span class="small fw-semibold text-dark">Player and roster management</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
                                <span class="small fw-semibold text-dark">Fixtures and results</span>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
                                <span class="small fw-semibold text-dark">Public club presence</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6 col-lg-5">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-light">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
                                <span class="small fw-semibold text-dark">Sports-focused tools</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
                                <span class="small fw-semibold text-dark">Plan-specific advanced features</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
                                <span class="small fw-semibold text-dark">Centralized club information</span>
                            </li>
                            <li class="d-flex align-items-center">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
                                <span class="small fw-semibold text-dark">Digital tools for presenting your organization</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SUBSECTION G: SIMPLE WORKFLOW -->
    <section class="py-5 bg-light border-bottom">
        <div class="container py-lg-2 benchero-reveal">
            <div class="text-center max-w-2xl mx-auto mb-4" style="max-width: 720px;">
                <span class="text-primary fw-bold text-uppercase small">Simple Workflow</span>
                <h3 class="h2 fw-bold text-dark mt-1 mb-2">How Benchero Fits Into Your Club</h3>
                <p class="text-muted small mb-0">A straightforward, five-step path from setup to an active public presence.</p>
            </div>
            <div class="row g-2 g-md-3">
                <div class="col-6 col-md">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white text-center">
                        <span class="badge bg-primary text-white rounded-circle mx-auto mb-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">1</span>
                        <h6 class="fw-bold mb-1 small text-dark">Create Your Organization</h6>
                        <p class="text-muted mb-0" style="font-size: 0.78rem;">Set up your club or sports organization.</p>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white text-center">
                        <span class="badge bg-primary text-white rounded-circle mx-auto mb-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">2</span>
                        <h6 class="fw-bold mb-1 small text-dark">Add Your Teams</h6>
                        <p class="text-muted mb-0" style="font-size: 0.78rem;">Organize the teams operating under your organization.</p>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white text-center">
                        <span class="badge bg-primary text-white rounded-circle mx-auto mb-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">3</span>
                        <h6 class="fw-bold mb-1 small text-dark">Add Your Players</h6>
                        <p class="text-muted mb-0" style="font-size: 0.78rem;">Maintain player and roster information.</p>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white text-center">
                        <span class="badge bg-primary text-white rounded-circle mx-auto mb-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">4</span>
                        <h6 class="fw-bold mb-1 small text-dark">Manage Sporting Activities</h6>
                        <p class="text-muted mb-0" style="font-size: 0.78rem;">Manage fixtures, results, competitions, and supported sporting info.</p>
                    </div>
                </div>
                <div class="col-12 col-md">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-primary text-white text-center">
                        <span class="badge bg-white text-primary rounded-circle mx-auto mb-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">5</span>
                        <h6 class="fw-bold mb-1 small text-white">Present Your Club</h6>
                        <p class="text-white-50 mb-0" style="font-size: 0.78rem;">Give your organization a structured public digital presence.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- SUBTLE DIVIDER TO EXISTING ABOUT PAGE -->
    <div class="py-3 bg-light text-center border-top border-bottom">
        <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.05em;">
            <i class="bi bi-arrow-down-circle text-primary me-1"></i> Complete Platform Details & Club Architecture Below
        </span>
    </div>

</div>
<!-- ======================================================== -->
<!-- END NEW SECTION: EXPANDED BENCHERO OVERVIEW & CAPABILITIES -->
<!-- ======================================================== -->

<!-- SECTION 1: HERO -->
<section class="py-5 bg-gradient text-white position-relative" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container py-lg-5 text-center">
        <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill mb-3">About Us</span>
        <h1 class="display-4 fw-extrabold mb-3" style="max-width: 800px; color: black;">Everything for Your Club, In One Place.</h1>
        <p class="lead max-w-3xl mx-auto opacity-90 mb-4" style="max-width: 800px; color: black;">
           Benchero is your complete sports club management platform — designed for clubs, academies, coaches, managers, and supporters. We bring everything together in one simple, organized system.
        </p>
        <a href="<?= url("/register") ?>" class="btn btn-primary btn-lg fw-bold rounded-pill px-4 shadow-sm">
            <i class="bi bi-rocket-takeoff me-2"></i>Start Your Free Trial
        </a>
    </div>
</section>

<!-- SECTION 2: WHAT IS BENCHERO? -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4 benchero-reveal">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-primary fw-bold text-uppercase small">Simple & Powerful</span>
                <h2 class="display-6 fw-bold text-dark mt-1 mb-3">What is Benchero?</h2>
                <p class="text-secondary leading-relaxed fs-5">
                    Benchero helps sports clubs organize their teams, players, coaches, managers, fixtures, match results, and club news all in one centralized location.
                </p>
                <p class="text-muted leading-relaxed">
                    Instead of managing your organization across paper records, group chats, and spreadsheets, Benchero gives you a clean management dashboard — and automatically powers a dedicated, professional public website for your club.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="p-4 bg-light rounded-4 border shadow-sm">
                    <h5 class="fw-bold mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Built For Sports Organizations Of All Sizes</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2 d-flex align-items-start"><i class="bi bi-arrow-right-short text-primary fs-5 me-2"></i><span><strong>Football Clubs & Academies:</strong> Youth and senior squad management.</span></li>
                        <li class="mb-2 d-flex align-items-start"><i class="bi bi-arrow-right-short text-primary fs-5 me-2"></i><span><strong>Basketball Teams:</strong> Roster tracking, fixtures, and standings.</span></li>
                        <li class="mb-2 d-flex align-items-start"><i class="bi bi-arrow-right-short text-primary fs-5 me-2"></i><span><strong>Rugby & Volleyball Organizations:</strong> Schedules, results, and staff details.</span></li>
                        <li class="d-flex align-items-start"><i class="bi bi-arrow-right-short text-primary fs-5 me-2"></i><span><strong>Community Sports Leagues:</strong> Multi-team organizations and tournaments.</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 3: HOW BENCHERO WORKS -->
<section class="py-5 bg-light border-bottom">
    <div class="container py-lg-4 benchero-reveal">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="text-primary fw-bold text-uppercase small">Easy Step-By-Step</span>
            <h2 class="display-6 fw-bold mb-2">How Benchero Works</h2>
            <p class="text-muted">Setting up your sports organization takes just a few minutes with 10 simple steps.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">1</span>
                        <h5 class="fw-bold mb-0">Create Your Club</h5>
                    </div>
                    <p class="text-muted small mb-0">Sign up and register your club name, logo, colors, and basic details.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">2</span>
                        <h5 class="fw-bold mb-0">Choose Your Sport</h5>
                    </div>
                    <p class="text-muted small mb-0">Select your sports category (Football, Basketball, Rugby, Volleyball, Netball, etc.).</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">3</span>
                        <h5 class="fw-bold mb-0">Create Your Teams</h5>
                    </div>
                    <p class="text-muted small mb-0">Add senior squads, reserves, academy teams, or age-group squads (U13, U17, Senior Men, Women).</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">4</span>
                        <h5 class="fw-bold mb-0">Add Your Players</h5>
                    </div>
                    <p class="text-muted small mb-0">Enter player profiles, positions, jersey numbers, and assign them to active team rosters.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">5</span>
                        <h5 class="fw-bold mb-0">Add Coaches & Staff</h5>
                    </div>
                    <p class="text-muted small mb-0">Record head coaches, assistant managers, team doctors, and club executives.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">6</span>
                        <h5 class="fw-bold mb-0">Organize Fixtures</h5>
                    </div>
                    <p class="text-muted small mb-0">Schedule upcoming match fixtures with dates, kick-off times, venues, and opposition teams.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">7</span>
                        <h5 class="fw-bold mb-0">Record Match Results</h5>
                    </div>
                    <p class="text-muted small mb-0">Update scores after games to automatically update league standings and team statistics.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">8</span>
                        <h5 class="fw-bold mb-0">Post News & Photos</h5>
                    </div>
                    <p class="text-muted small mb-0">Publish match reports, club announcements, team photos, and sponsor banners.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">9</span>
                        <h5 class="fw-bold mb-0">Publish Your Website</h5>
                    </div>
                    <p class="text-muted small mb-0">Your customized public club website is automatically published and updated live.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4 mx-auto">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-primary text-white">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-white text-primary rounded-circle me-3 fs-5" style="width: 38px; height: 38px; line-height: 26px;">10</span>
                        <h5 class="fw-bold mb-0">Share With Fans</h5>
                    </div>
                    <p class="opacity-90 small mb-0">Share your custom club website link with players, parents, sponsors, and supporters!</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 4: YOUR CLUB WEBSITE -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <span class="text-primary fw-bold text-uppercase small">Professional Online Presence</span>
                <h2 class="display-6 fw-bold mb-3">Your Official Club Website</h2>
                <p class="text-secondary leading-relaxed">
                    Every active organization on Benchero gets a branded, mobile-responsive website. Your website showcases your club's identity to fans, scouts, sponsors, and visitors.
                </p>
                <p class="text-muted">Available website sections include:</p>
                <div class="row g-2">
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Homepage Banner</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>About & History</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Squad & Roster</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Player Profiles</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Coaches & Staff</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Fixtures & Results</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>League Standings</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>News & Gallery</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Sponsor Banners</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Contact Details</div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="p-4 bg-dark text-white rounded-4 shadow-lg border border-secondary">
                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom border-secondary">
                        <span class="bg-danger rounded-circle me-2" style="width:12px;height:12px;"></span>
                        <span class="bg-warning rounded-circle me-2" style="width:12px;height:12px;"></span>
                        <span class="bg-success rounded-circle me-2" style="width:12px;height:12px;"></span>
                        <span class="small text-muted ms-2">https://benchero.co.ke/club/your-club-name</span>
                    </div>
                    <div class="text-center py-4">
                        <i class="bi bi-globe display-1 text-primary mb-3"></i>
                        <h4 class="fw-bold">Branded For Your Club</h4>
                        <p class="text-muted small max-w-md mx-auto">Your logo, your club colors, your teams, and your story — with subtle platform support behind the scenes.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 5: MANAGE YOUR PEOPLE -->
<section class="py-5 bg-light border-bottom">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <span class="text-primary fw-bold text-uppercase small">People & Rosters</span>
                <h2 class="display-6 fw-bold mb-3">Manage Your People</h2>
                <p class="text-secondary leading-relaxed">
                    Keep accurate, centralized records for everyone involved in your sports club. Organizers can add, update, and manage players, team captains, technical bench coaches, managers, and medical staff.
                </p>
                <div class="d-flex mb-3">
                    <div class="fs-3 text-primary me-3"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Detailed Player Profiles</h6>
                        <p class="text-muted small mb-0">Record positions, jersey numbers, date of birth, preferred foot/hand, and player photos.</p>
                    </div>
                </div>
                <div class="d-flex">
                    <div class="fs-3 text-success me-3"><i class="bi bi-person-gear"></i></div>
                    <div>
                        <h6 class="fw-bold mb-1">Coaches & Team Staff</h6>
                        <p class="text-muted small mb-0">Organize technical staff by team so supporters know who leads each squad on game day.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1 text-center">
                <div class="p-5 bg-white rounded-4 shadow-sm border">
                    <i class="bi bi-people-fill text-primary" style="font-size: 80px;"></i>
                    <h4 class="fw-bold mt-3">Organized Squad Roster</h4>
                    <p class="text-muted mb-0">No more lost player details or confusion over jersey assignments.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 6: MANAGE YOUR MATCHES -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-primary fw-bold text-uppercase small">Fixtures & Results</span>
                <h2 class="display-6 fw-bold mb-3">Manage Your Matches</h2>
                <p class="text-secondary leading-relaxed">
                    Never lose track of a kick-off time or match result again. Benchero lets you record upcoming fixtures, friendly games, tournament matches, home & away venues, and completed scores.
                </p>
                <ul class="list-unstyled mb-0 text-muted">
                    <li class="mb-2"><i class="bi bi-calendar-event text-primary me-2"></i>Schedule upcoming home and away matches</li>
                    <li class="mb-2"><i class="bi bi-trophy text-warning me-2"></i>Record final scores and match notes</li>
                    <li class="mb-2"><i class="bi bi-table text-success me-2"></i>Automatic standings calculations</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="p-4 bg-light rounded-4 border shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-success">Completed Match Result</span>
                        <span class="small text-muted">Stadium Field 1</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-around py-3 bg-white rounded-3 border">
                        <div class="text-center">
                            <h5 class="fw-bold mb-0">Home Team</h5>
                            <span class="display-6 fw-bold text-primary">3</span>
                        </div>
                        <span class="fs-4 text-muted fw-bold">VS</span>
                        <div class="text-center">
                            <h5 class="fw-bold mb-0">Away Team</h5>
                            <span class="display-6 fw-bold text-secondary">1</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 7: TELL YOUR CLUB'S STORY -->
<section class="py-5 bg-light border-bottom">
    <div class="container py-lg-4 text-center">
        <span class="text-primary fw-bold text-uppercase small">Content & Media</span>
        <h2 class="display-6 fw-bold mb-3">Tell Your Club's Story</h2>
        <p class="text-muted max-w-2xl mx-auto mb-5" style="max-width: 700px;">
            Share club history, news articles, match highlights, photo galleries, and sponsor partnerships directly on your public website.
        </p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border h-100">
                    <div class="text-primary fs-1 mb-2"><i class="bi bi-newspaper"></i></div>
                    <h5 class="fw-bold mb-2">Club News & Reports</h5>
                    <p class="text-muted small mb-0">Publish match reports, player announcements, and club updates for your audience.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border h-100">
                    <div class="text-success fs-1 mb-2"><i class="bi bi-images"></i></div>
                    <h5 class="fw-bold mb-2">Photo Galleries</h5>
                    <p class="text-muted small mb-0">Upload match photos, team squad pictures, trophy celebrations, and event galleries.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border h-100">
                    <div class="text-warning fs-1 mb-2"><i class="bi bi-award"></i></div>
                    <h5 class="fw-bold mb-2">Sponsor Recognition</h5>
                    <p class="text-muted small mb-0">Give official partners and local sponsors prominent visibility on your website.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 8: SHARE YOUR CLUB -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4 text-center">
        <div class="max-w-2xl mx-auto" style="max-width: 750px;">
            <i class="bi bi-share-fill display-3 text-primary mb-3"></i>
            <h2 class="display-6 fw-bold mb-3">Share Your Club With Everyone</h2>
            <p class="text-secondary leading-relaxed fs-5 mb-4">
                Your Benchero public website URL is ready to share on social media, WhatsApp groups, flyers, and emails. Players, parents, supporters, and league officials can check match schedules and results anytime on mobile or desktop.
            </p>
        </div>
    </div>
</section>

<!-- SECTION 9: SUBSCRIPTIONS -->
<section class="py-5 bg-light border-bottom">
    <div class="container py-lg-4">
        <div class="text-center max-w-2xl mx-auto mb-5" style="max-width: 700px;">
            <span class="text-primary fw-bold text-uppercase small">Simple & Affordable</span>
            <h2 class="display-6 fw-bold mb-2">Subscriptions & Pricing</h2>
            <p class="text-muted">Benchero offers a simple, risk-free trial followed by transparent subscription plans.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-2"><i class="bi bi-gift text-primary me-2"></i>14-Day Free Trial</h5>
                    <p class="text-muted small">Every new club gets 14 days of full access to test out all features and publish their website with zero obligation.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-5">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-2"><i class="bi bi-credit-card text-success me-2"></i>Affordable Plans with M-Pesa</h5>
                    <p class="text-muted small">Choose monthly or discounted yearly plans. Convenient local payment via M-Pesa STK push restores or extends your public website visibility instantly.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 10: WHY BENCHERO? -->
<section class="py-5 bg-white">
    <div class="container py-lg-4 text-center">
        <span class="text-primary fw-bold text-uppercase small">The Benchero Advantage</span>
        <h2 class="display-6 fw-bold mb-4">Why Choose Benchero?</h2>
        <div class="row g-4 text-start">
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-light rounded-4 h-100 border">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-speedometer2"></i></div>
                    <h6 class="fw-bold mb-2">Simpler Management</h6>
                    <p class="text-muted small mb-0">No complicated setup or technical skills required. Designed for non-technical users.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-light rounded-4 h-100 border">
                    <div class="fs-2 text-success mb-2"><i class="bi bi-folder-check"></i></div>
                    <h6 class="fw-bold mb-2">Centralized Info</h6>
                    <p class="text-muted small mb-0">All player records, fixtures, and results stored safely in one secure account.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-light rounded-4 h-100 border">
                    <div class="fs-2 text-warning mb-2"><i class="bi bi-stars"></i></div>
                    <h6 class="fw-bold mb-2">Professional Site</h6>
                    <p class="text-muted small mb-0">Give your club the professional online image it deserves to attract players and sponsors.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="p-4 bg-light rounded-4 h-100 border">
                    <div class="fs-2 text-info mb-2"><i class="bi bi-chat-dots"></i></div>
                    <h6 class="fw-bold mb-2">Easier Communication</h6>
                    <p class="text-muted small mb-0">Keep fans, players, and parents informed with up-to-date match schedules and reports.</p>
                </div>
            </div>
        </div>
        <div class="mt-5">
            <a href="<?= url("/register") ?>" class="btn btn-primary btn-lg fw-bold rounded-pill px-5 shadow">
                Create Your Club Website Today
            </a>
        </div>
    </div>
</section>
