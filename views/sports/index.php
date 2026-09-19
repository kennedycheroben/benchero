<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<!-- Sports Hero & Ticker -->
<section class="py-4 bg-dark text-white border-bottom border-secondary">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger text-uppercase fw-bold px-3 py-2 rounded-2">
                    <i class="bi bi-broadcast me-1"></i> Live Scoreboard
                </span>
                <?php if (!empty($isStale)): ?>
                    <span class="badge bg-warning text-dark small">Scores may be temporarily delayed</span>
                <?php endif; ?>
            </div>
            <a href="<?= url('/sports/live') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3 fw-semibold">
                View All Live Scores <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <?php if (!empty($liveMatches)): ?>
            <div class="row g-3">
                <?php foreach (array_slice($liveMatches, 0, 3) as $match): ?>
                    <div class="col-md-4">
                        <div class="p-3 bg-secondary bg-opacity-25 rounded-3 border border-secondary border-opacity-50 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                <span class="fw-semibold text-light"><?= $this->e($match['competition']) ?></span>
                                <span class="badge bg-danger rounded-pill px-2"><?= $this->e(!empty($match['minute']) ? $match['minute'] : 'LIVE') ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center fw-bold fs-5">
                                <div class="text-truncate flex-grow-1 text-white"><?= $this->e($match['home_team']) ?></div>
                                <div class="px-2 text-warning"><?= $this->e($match['home_score']) ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center fw-bold fs-5">
                                <div class="text-truncate flex-grow-1 text-white"><?= $this->e($match['away_team']) ?></div>
                                <div class="px-2 text-warning"><?= $this->e($match['away_score']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-3 bg-secondary bg-opacity-10 rounded-3 text-center text-white-50">
                <i class="bi bi-info-circle me-1"></i> No live matches in progress right now. Check today's upcoming fixtures below.
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="container py-5">
    <div class="row g-4">
        <!-- Main Content Area: Results, Fixtures & News -->
        <div class="col-lg-8">
            
            <!-- Latest News Section -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h3 class="fw-extrabold mb-0 text-slate-900">
                    <i class="bi bi-newspaper text-primary me-2"></i> Latest Sports News
                </h3>
                <a href="<?= url('/sports/news') ?>" class="text-decoration-none fw-semibold">More News &rarr;</a>
            </div>

            <div class="row g-4 mb-5">
                <?php foreach ($latestNews as $article): ?>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden benchero-interactive-card">
                            <?php if (!empty($article['image_url'])): ?>
                                <img src="<?= $this->e($article['image_url']) ?>" class="card-img-top" alt="<?= $this->e($article['title']) ?>" style="height: 180px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=800&auto=format&fit=crop&q=80'">
                            <?php else: ?>
                                <div class="bg-dark text-white p-4 text-center d-flex align-items-center justify-content-center" style="height: 180px;">
                                    <i class="bi bi-trophy fs-1 text-warning"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column p-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1 rounded-2">
                                        <?= $this->e($article['category']) ?>
                                    </span>
                                    <span class="small text-muted"><?= $this->e($article['source']) ?></span>
                                </div>
                                <h5 class="card-title fw-bold text-slate-900 mb-2">
                                    <a href="<?= url('/sports/news/' . $article['slug']) ?>" class="text-dark text-decoration-none">
                                        <?= $this->e($article['title']) ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted small flex-grow-1 mb-3">
                                    <?= $this->e($article['summary']) ?>
                                </p>
                                <a href="<?= url('/sports/news/' . $article['slug']) ?>" class="btn btn-sm btn-outline-primary fw-semibold rounded-3 align-self-start">
                                    Read Story <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Results -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h3 class="fw-extrabold mb-0 text-slate-900">
                    <i class="bi bi-check-circle-fill text-success me-2"></i> Recent Results
                </h3>
                <a href="<?= url('/sports/results') ?>" class="text-decoration-none fw-semibold">View All Results &rarr;</a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden">
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentResults as $res): ?>
                        <li class="list-group-item p-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-secondary bg-opacity-10 text-dark small fw-bold">
                                    <?= $this->e($res['competition']) ?>
                                </span>
                                <span class="badge bg-secondary">FT</span>
                            </div>
                            <div class="row align-items-center py-2">
                                <div class="col-5 text-end fw-bold text-dark">
                                    <?= $this->e($res['home_team']) ?>
                                </div>
                                <div class="col-2 text-center">
                                    <span class="px-3 py-1 bg-dark text-white rounded-pill fw-extrabold">
                                        <?= $this->e($res['home_score']) ?> - <?= $this->e($res['away_score']) ?>
                                    </span>
                                </div>
                                <div class="col-5 text-start fw-bold text-dark">
                                    <?= $this->e($res['away_team']) ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Upcoming Fixtures -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h3 class="fw-extrabold mb-0 text-slate-900">
                    <i class="bi bi-calendar-event-fill text-info me-2"></i> Upcoming Fixtures
                </h3>
                <a href="<?= url('/sports/fixtures') ?>" class="text-decoration-none fw-semibold">View All Fixtures &rarr;</a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                <ul class="list-group list-group-flush">
                    <?php foreach ($upcomingFixtures as $fix): ?>
                        <li class="list-group-item p-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-info bg-opacity-10 text-info small fw-bold">
                                    <?= $this->e($fix['competition']) ?>
                                </span>
                                <span class="small text-muted fw-semibold">
                                    <i class="bi bi-clock me-1"></i><?= date('D, M j - H:i', strtotime($fix['start_time'])) ?>
                                </span>
                            </div>
                            <div class="row align-items-center py-2">
                                <div class="col-5 text-end fw-bold text-dark">
                                    <?= $this->e($fix['home_team']) ?>
                                </div>
                                <div class="col-2 text-center">
                                    <span class="px-3 py-1 bg-light border text-muted rounded-pill fw-bold small">
                                        VS
                                    </span>
                                </div>
                                <div class="col-5 text-start fw-bold text-dark">
                                    <?= $this->e($fix['away_team']) ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>

        <!-- Sidebar: Popular Competitions & SaaS CTA -->
        <div class="col-lg-4">
            
            <!-- SaaS Conversion Card -->
            <div class="card border-0 shadow-lg rounded-4 text-white bg-dark p-4 mb-4 position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 p-3 opacity-10">
                    <i class="bi bi-trophy-fill display-1 text-warning"></i>
                </div>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="32" class="rounded-2">
                    <span class="fw-extrabold fs-4 tracking-tight">BENCHERO</span>
                </div>
                <h4 class="fw-bold mb-2">Manage Your Sports Club</h4>
                <p class="text-white-50 small mb-4">
                    Create your club's official digital home on Benchero. Manage teams, rosters, fixtures, public web pages, and member billing seamlessly.
                </p>
                <div class="d-grid gap-2">
                    <a href="<?= url('/register') ?>" class="btn btn-primary fw-bold py-2 rounded-3">
                        <i class="bi bi-plus-circle me-1"></i> Create Your Club Now
                    </a>
                    <a href="<?= url('/pricing') ?>" class="btn btn-outline-light btn-sm fw-semibold rounded-3">
                        View Pricing Plans
                    </a>
                </div>
            </div>

            <!-- Popular Competitions Hub -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold mb-3 text-slate-900">
                    <i class="bi bi-award text-warning me-2"></i> Popular Competitions
                </h5>
                <div class="d-grid gap-2">
                    <?php foreach ($competitions as $comp): ?>
                        <a href="<?= url('/sports/c/' . $comp['slug']) ?>" class="d-flex align-items-center justify-content-between p-3 rounded-3 border bg-light text-decoration-none text-dark hover-bg-white">
                            <div>
                                <div class="fw-bold text-slate-900"><?= $this->e($comp['name']) ?></div>
                                <div class="small text-muted"><?= $this->e($comp['country']) ?></div>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Public Clubs Hub -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3 text-slate-900">
                    <i class="bi bi-shield-check text-success me-2"></i> Benchero Clubs
                </h5>
                <p class="small text-muted mb-3">Explore public club presence pages managed by team managers on Benchero.</p>
                <a href="<?= url('/sports/clubs') ?>" class="btn btn-outline-primary btn-sm w-100 fw-bold rounded-3">
                    Explore Club Directory <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

        </div>
    </div>
</div>
