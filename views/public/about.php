<?php $this->layout('layout', ['title' => 'About Benchero — Complete Sports Club Management Platform']) ?>

<!-- SECTION 1: HERO -->
<section class="py-5 text-white position-relative bg-slate-900" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container py-lg-5 text-center">
        <span class="badge bg-primary bg-opacity-25 text-info fw-bold px-3 py-2 rounded-pill mb-3">About Benchero</span>
        <h1 class="display-4 fw-extrabold mb-3 max-w-3xl mx-auto text-white">
            Everything for Your Club, In One Place.
        </h1>
        <p class="lead max-w-3xl mx-auto text-slate-300 mb-4">
            Benchero is the complete sports club operating system — designed for clubs, academies, coaches, managers, athletes, and supporters. We bring sporting operations, member records, fixtures, and digital presentation together into one unified platform.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg fw-bold rounded-pill px-4 shadow-sm">
                <i class="bi bi-rocket-takeoff me-2"></i>Start Free Trial
            </a>
            <a href="<?= url('/features') ?>" class="btn btn-outline-light btn-lg fw-semibold rounded-pill px-4">
                Explore Features
            </a>
        </div>
    </div>
</section>

<!-- SECTION 2: WHAT IS BENCHERO? -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-primary fw-bold text-uppercase small">Our Mission</span>
                <h2 class="display-6 fw-bold text-slate-900 mt-1 mb-3">What is Benchero?</h2>
                <p class="text-slate-700 leading-relaxed fs-5">
                    Benchero was built to solve a simple problem: sports clubs, academies, and grassroots organizations too often run on fragmented spreadsheets, informal chat groups, and lost paperwork.
                </p>
                <p class="text-slate-600 leading-relaxed">
                    Benchero gives clubs a modern administrative Control Center to manage teams, squads, player registrations, technical staff, season schedules, and match results. Simultaneously, it automatically deploys a beautiful, public club website for fans, parents, scouts, and sponsors.
                </p>
            </div>
            <div class="col-lg-6">
                <div class="p-4 bg-light rounded-4 border shadow-sm">
                    <h5 class="fw-bold mb-3 text-slate-900"><i class="bi bi-check-circle-fill text-success me-2"></i>Built For Sports Organizations Of All Sizes</h5>
                    <ul class="list-unstyled mb-0 d-grid gap-2">
                        <li class="d-flex align-items-start">
                            <i class="bi bi-arrow-right-short text-primary fs-5 me-2"></i>
                            <span><strong>Football Clubs & Academies:</strong> Manage first teams, reserve squads, and youth tiers.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-arrow-right-short text-primary fs-5 me-2"></i>
                            <span><strong>Basketball Teams:</strong> Roster tracking, positions, fixtures, and division standings.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-arrow-right-short text-primary fs-5 me-2"></i>
                            <span><strong>Rugby & Volleyball Organizations:</strong> Schedules, tournament results, and staff governance.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-arrow-right-short text-primary fs-5 me-2"></i>
                            <span><strong>Community & Regional Leagues:</strong> Multi-club organization, verified rosters, and instant tables.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 3: CORE CAPABILITIES -->
<section class="py-5 bg-light border-bottom">
    <div class="container py-lg-4">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="text-primary fw-bold text-uppercase small">Platform Capabilities</span>
            <h2 class="display-6 fw-bold text-slate-900 mb-2">What Can Benchero Do?</h2>
            <p class="text-slate-600">Sports-specific management tools built directly into the Benchero platform.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 mb-3 d-inline-block" style="width: fit-content;">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2">Club Management</h5>
                    <p class="text-slate-600 small mb-0">Manage club branding, colors, contact details, governance, and organizational settings from one central portal.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 mb-3 d-inline-block" style="width: fit-content;">
                        <i class="bi bi-diagram-3 fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2">Multi-Team Squads</h5>
                    <p class="text-slate-600 small mb-0">Field senior squads, developmental reserves, and age-group academies with independent rosters and managers.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3 mb-3 d-inline-block" style="width: fit-content;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2">Player Roster Profiles</h5>
                    <p class="text-slate-600 small mb-0">Record kit numbers, positions, stats, headshots, date of birth, and emergency contacts securely.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="rounded-3 bg-info bg-opacity-10 text-info p-3 mb-3 d-inline-block" style="width: fit-content;">
                        <i class="bi bi-calendar-event fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2">Fixtures & Scheduling</h5>
                    <p class="text-slate-600 small mb-0">Publish official upcoming matches, home & away venues, opponents, and kickoff times.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3 mb-3 d-inline-block" style="width: fit-content;">
                        <i class="bi bi-trophy fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2">Results & Standings</h5>
                    <p class="text-slate-600 small mb-0">Log completed scores and match reports to automatically calculate league tables and historical records.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 mb-3 d-inline-block" style="width: fit-content;">
                        <i class="bi bi-globe fs-4"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2">Public Club Website</h5>
                    <p class="text-slate-600 small mb-0">Instantly generate a branded public website showcasing news, team rosters, sponsors, and match day centers.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 4: HOW IT WORKS (10 EASY STEPS) -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4">
        <div class="text-center max-w-2xl mx-auto mb-5">
            <span class="text-primary fw-bold text-uppercase small">Simple Setup</span>
            <h2 class="display-6 fw-bold text-slate-900 mb-2">How Benchero Works</h2>
            <p class="text-slate-600">Setting up your sports organization takes just a few minutes with our structured flow.</p>
        </div>
        <div class="row g-3">
            <?php
            $steps = [
                ['num' => '1', 'title' => 'Create Your Club', 'desc' => 'Register your club name, crest, colors, and country details.'],
                ['num' => '2', 'title' => 'Select Your Sport', 'desc' => 'Pick Football, Basketball, Rugby, Volleyball, or multi-sport.'],
                ['num' => '3', 'title' => 'Create Squads', 'desc' => 'Add your senior, academy, or youth divisions (e.g. U17, First Team).'],
                ['num' => '4', 'title' => 'Add Players', 'desc' => 'Enter player profiles, jersey numbers, positions, and rosters.'],
                ['num' => '5', 'title' => 'Add Staff & Coaches', 'desc' => 'Record coaches, team managers, doctors, and club administrators.'],
                ['num' => '6', 'title' => 'Schedule Fixtures', 'desc' => 'Set upcoming matches with dates, venues, and kickoff times.'],
                ['num' => '7', 'title' => 'Record Match Results', 'desc' => 'Update scores after matches to update league records and stats.'],
                ['num' => '8', 'title' => 'Publish Content & Media', 'desc' => 'Upload match photos, news announcements, and sponsor logos.'],
                ['num' => '9', 'title' => 'Deploy Public Website', 'desc' => 'Your official public club portal updates automatically.'],
                ['num' => '10', 'title' => 'Share With Community', 'desc' => 'Share your club link with athletes, parents, scouts, and supporters.'],
            ];
            foreach ($steps as $step):
            ?>
                <div class="col-md-6 col-lg-4 col-xl-2-4">
                    <div class="card h-100 border shadow-sm rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-primary rounded-circle me-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.85rem;">
                                <?= $step['num'] ?>
                            </span>
                            <h6 class="fw-bold mb-0 text-slate-900"><?= $step['title'] ?></h6>
                        </div>
                        <p class="text-slate-600 small mb-0"><?= $step['desc'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SECTION 5: PUBLIC CLUB WEBSITE SHOWCASE -->
<section class="py-5 bg-light border-bottom">
    <div class="container py-lg-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-primary fw-bold text-uppercase small">Professional Online Presence</span>
                <h2 class="display-6 fw-bold text-slate-900 mb-3">Your Official Club Website</h2>
                <p class="text-slate-700 leading-relaxed">
                    Every active organization on Benchero gets a branded, mobile-responsive website. It showcases your club's identity to fans, scouts, sponsors, and visitors without requiring technical web development.
                </p>
                <div class="row g-2 text-slate-700 small">
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Homepage Hero Banner</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>About & Club History</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Squad & Roster Lists</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Individual Player Profiles</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Coaching & Medical Staff</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Upcoming Match Fixtures</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Completed Match Results</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Photo Galleries & Videos</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Official Club Sponsors</div>
                    <div class="col-6"><i class="bi bi-check2 text-success me-2"></i>Direct Contact Inquiries</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-slate-900 text-white p-4">
                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                        <span class="bg-danger rounded-circle me-2" style="width:10px;height:10px;"></span>
                        <span class="bg-warning rounded-circle me-2" style="width:10px;height:10px;"></span>
                        <span class="bg-success rounded-circle me-2" style="width:10px;height:10px;"></span>
                        <span class="small text-slate-400 ms-2">https://benchero.co.ke/club/your-club-name</span>
                    </div>
                    <div class="text-center py-4">
                        <i class="bi bi-globe display-2 text-primary mb-3"></i>
                        <h4 class="fw-bold">Branded For Your Club</h4>
                        <p class="text-slate-400 small max-w-md mx-auto mb-3">Your crest, your club colors, your teams, and your story — with dedicated public links for social media and member outreach.</p>
                        <a href="<?= url('/sports/clubs') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">
                            <i class="bi bi-search me-1"></i> Browse Verified Clubs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECTION 6: CALL TO ACTION -->
<section class="py-5 bg-slate-900 text-white text-center">
    <div class="container py-lg-4">
        <h2 class="display-6 fw-extrabold mb-3">Empower Your Club with Benchero</h2>
        <p class="lead text-slate-400 max-w-2xl mx-auto mb-4">
            Join sports clubs and academies across Kenya and beyond that trust Benchero to streamline operations and present a professional public profile.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-3">
            <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg fw-bold rounded-pill px-5 shadow">
                Start 14-Day Free Trial
            </a>
            <a href="<?= url('/contact') ?>" class="btn btn-outline-light btn-lg fw-semibold rounded-pill px-4">
                Contact Our Sports Team
            </a>
        </div>
    </div>
</section>
