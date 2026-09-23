<?php $this->layout('layout', ['title' => $title]) ?>

<!-- Hero Section -->
<section class="py-5 bg-white border-bottom position-relative overflow-hidden">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-10 text-primary small fw-semibold mb-3">
                    <i class="bi bi-cpu-fill"></i> Complete Sports Operating System
                </div>
                <h1 class="display-5 fw-extrabold text-slate-900 tracking-tight mb-3">
                    Built to Run Clubs, Academies & Leagues at Scale
                </h1>
                <p class="lead text-slate-600 mb-4 max-w-2xl">
                    From grassroots academies to multi-division sporting organizations, Benchero provides the digital infrastructure to manage teams, players, fixtures, live scores, websites, and billing — all in one unified platform.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= url('/register') ?>" class="btn btn-benchero-primary btn-lg px-4 d-inline-flex align-items-center gap-2">
                        <span>Start 14-Day Free Trial</span>
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <a href="<?= url('/pricing') ?>" class="btn btn-benchero-outline btn-lg px-4">
                        View Transparent Pricing
                    </a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-slate-900 text-white p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-primary rounded-3 text-white"><i class="bi bi-speedometer2"></i></span>
                            <div>
                                <h6 class="mb-0 fw-bold">Live Club Hub</h6>
                                <small class="text-slate-400">Real-time status</small>
                            </div>
                        </div>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1">Operational</span>
                    </div>
                    <div class="d-grid gap-3">
                        <div class="p-3 bg-slate-800 rounded-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-people-fill text-info fs-4"></i>
                                <div>
                                    <div class="fw-semibold">Roster & Squads</div>
                                    <small class="text-slate-400">All divisions & ages</small>
                                </div>
                            </div>
                            <span class="badge bg-primary rounded-pill">Active</span>
                        </div>
                        <div class="p-3 bg-slate-800 rounded-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-calendar-event text-warning fs-4"></i>
                                <div>
                                    <div class="fw-semibold">Fixtures & Scores</div>
                                    <small class="text-slate-400">Live score updates</small>
                                </div>
                            </div>
                            <span class="badge bg-warning text-dark rounded-pill">Syncing</span>
                        </div>
                        <div class="p-3 bg-slate-800 rounded-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-globe2 text-success fs-4"></i>
                                <div>
                                    <div class="fw-semibold">Public Club Site</div>
                                    <small class="text-slate-400">SEO & Mobile ready</small>
                                </div>
                            </div>
                            <span class="badge bg-success rounded-pill">Published</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Features Grid -->
<section class="py-5 bg-light">
    <div class="container py-lg-4">
        <div class="text-center max-w-3xl mx-auto mb-5">
            <h2 class="h1 fw-bold text-slate-900 tracking-tight mb-2">Designed for Every Facet of Modern Sports</h2>
            <p class="text-slate-600">Explore the comprehensive toolkit built specifically for club directors, team coaches, match organizers, and supporters.</p>
        </div>

        <div class="row g-4">
            <!-- Feature 1: Multi-Sport Flexibility -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 d-inline-block mb-3" style="width: fit-content;">
                        <i class="bi bi-trophy fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold text-slate-900 mb-2">Multi-Sport Architecture</h3>
                    <p class="text-slate-600 mb-0 small">
                        Whether your club fields Football, Basketball, Volleyball, or Rugby squads, Benchero supports specialized rule sets, match tracking, and positions without forcing a one-size-fits-all structure.
                    </p>
                </div>
            </div>

            <!-- Feature 2: Teams & Rosters -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 d-inline-block mb-3" style="width: fit-content;">
                        <i class="bi bi-person-lines-fill fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold text-slate-900 mb-2">Squads & Player Rosters</h3>
                    <p class="text-slate-600 mb-0 small">
                        Organize your first team, reserve squads, and youth academies. Maintain full player profiles with photos, kit numbers, positions, stats, and emergency medical contacts.
                    </p>
                </div>
            </div>

            <!-- Feature 3: Fixture Scheduling -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 d-inline-block mb-3" style="width: fit-content;">
                        <i class="bi bi-calendar3-range fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold text-slate-900 mb-2">Fixtures & Live Match Center</h3>
                    <p class="text-slate-600 mb-0 small">
                        Schedule season schedules with venues and kickoff times. Update match events, cards, and scores in real time, broadcasting instant updates to fans across the web.
                    </p>
                </div>
            </div>

            <!-- Feature 4: Club Website Builder -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift">
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 d-inline-block mb-3" style="width: fit-content;">
                        <i class="bi bi-browser-chrome fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold text-slate-900 mb-2">Official Public Website</h3>
                    <p class="text-slate-600 mb-0 small">
                        Instantly deploy a professional, search-engine optimized public club website. Publish news articles, match previews, sponsor logos, and squad directories automatically.
                    </p>
                </div>
            </div>

            <!-- Feature 5: Digital Club Cards & QR Codes -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 d-inline-block mb-3" style="width: fit-content;">
                        <i class="bi bi-qr-code fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold text-slate-900 mb-2">Digital Cards & SVG QR Codes</h3>
                    <p class="text-slate-600 mb-0 small">
                        Empower members, athletes, and fans with downloadable mobile digital club cards and instant SVG QR codes for verified event check-in, stadium access, and membership validation.
                    </p>
                </div>
            </div>

            <!-- Feature 6: M-Pesa & PayPal Billing -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 d-inline-block mb-3" style="width: fit-content;">
                        <i class="bi bi-credit-card-2-front fs-3"></i>
                    </div>
                    <h3 class="h5 fw-bold text-slate-900 mb-2">Seamless M-Pesa & Global Payments</h3>
                    <p class="text-slate-600 mb-0 small">
                        Collect membership subscriptions and upgrade plans effortlessly using instant M-Pesa STK push for East African organizations and PayPal USD checkout for international clubs.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pro Capabilities Spotlight -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-warning bg-opacity-10 text-dark small fw-bold mb-3">
                    <i class="bi bi-star-fill text-warning"></i> Benchero Pro Tier
                </div>
                <h2 class="h1 fw-extrabold text-slate-900 tracking-tight mb-3">
                    Unlock Custom Domains, Video Uploads & Media Storage
                </h2>
                <p class="text-slate-600 mb-4">
                    Upgrade to Benchero Pro to completely customize your brand presence and engage supporters worldwide with rich multimedia content.
                </p>
                <div class="d-grid gap-3 mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-check-circle-fill text-primary fs-5 mt-1"></i>
                        <div>
                            <span class="fw-bold text-slate-900">Custom Domain Integration:</span>
                            <span class="text-slate-600 small"> Connect your own domain (e.g., <code>www.myclub.co.ke</code>) with automated SSL security certificates.</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-check-circle-fill text-primary fs-5 mt-1"></i>
                        <div>
                            <span class="fw-bold text-slate-900">2 GB Video Highlights Quota:</span>
                            <span class="text-slate-600 small"> Upload match recaps, training sessions, and interviews directly into your club website.</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-check-circle-fill text-primary fs-5 mt-1"></i>
                        <div>
                            <span class="fw-bold text-slate-900">5 GB High-Res Media Center:</span>
                            <span class="text-slate-600 small"> Store high-resolution match day photo galleries, player headshots, and press kits.</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-check-circle-fill text-primary fs-5 mt-1"></i>
                        <div>
                            <span class="fw-bold text-slate-900">White-Label Experience:</span>
                            <span class="text-slate-600 small"> Eliminate Benchero branding on your club portal for a 100% bespoke corporate aesthetic.</span>
                        </div>
                    </div>
                </div>
                <a href="<?= url('/pricing') ?>" class="btn btn-benchero-primary">
                    Compare Pro Plans & Pricing
                </a>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="card border-0 bg-slate-900 text-white rounded-4 shadow-xl p-4 p-md-5">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark px-3 py-1 fw-bold">PRO ARCHITECTURE</span>
                        </div>
                        <span class="text-slate-400 small">benchero.co.ke</span>
                    </div>
                    <div class="bg-slate-800 rounded-3 p-3 mb-3 border border-secondary border-opacity-25">
                        <div class="small text-slate-400 mb-1">Custom Domain Routing</div>
                        <div class="fw-mono text-info fw-bold">https://www.nairobiharriers.co.ke</div>
                    </div>
                    <div class="bg-slate-800 rounded-3 p-3 mb-3 border border-secondary border-opacity-25">
                        <div class="d-flex justify-content-between text-slate-400 small mb-1">
                            <span>Media Storage (5 GB Quota)</span>
                            <span class="text-white fw-bold">1.2 GB / 5.0 GB</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 24%" aria-valuenow="24" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="bg-slate-800 rounded-3 p-3 border border-secondary border-opacity-25">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-camera-reels-fill text-danger fs-5"></i>
                                <span class="fw-semibold">Video Streaming Engine</span>
                            </div>
                            <span class="badge bg-success bg-opacity-25 text-success">Optimized</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section class="py-5 bg-slate-900 text-white text-center">
    <div class="container py-lg-4">
        <h2 class="display-6 fw-extrabold mb-3">Ready to Elevate Your Sports Organization?</h2>
        <p class="lead text-slate-400 mb-4 max-w-2xl mx-auto">
            Get started today with zero risk. Set up your club, add your squads, and publish your official website in under 10 minutes.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-3">
            <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg px-4 fw-semibold">
                Start 14-Day Free Trial
            </a>
            <a href="<?= url('/contact') ?>" class="btn btn-outline-light btn-lg px-4 fw-semibold">
                Talk to Our Sports Team
            </a>
        </div>
    </div>
</section>
