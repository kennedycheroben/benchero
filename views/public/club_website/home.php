<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<!-- Dynamic Homepage Sections -->
<?php foreach ($sections as $sec): ?>
    <?php if (empty($sec['is_visible'])) continue; ?>

    <?php switch ($sec['section_type']): 
        case 'hero': ?>
            <!-- Hero Banner Section -->
            <section class="hero-section text-white py-5 position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, var(--club-secondary) 0%, #000000 100%); min-height: 480px;">
                <?php if (!empty($settings['hero_image_url'])): ?>
                    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-25" 
                         style="background-image: url('<?= htmlspecialchars($settings['hero_image_url']) ?>'); background-size: cover; background-position: center;"></div>
                <?php endif; ?>
                
                <div class="container position-relative py-5">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <?php if (!empty($org['logo_url'])): ?>
                                <img src="<?= htmlspecialchars($org['logo_url']) ?>" alt="Crest" height="80" class="mb-4 bg-white p-2 rounded-3 shadow benchero-hero-item stagger-1">
                            <?php endif; ?>
                            <h1 class="display-3 fw-black text-uppercase text-white mb-3 lh-1 benchero-hero-item stagger-1">
                                <?= htmlspecialchars($settings['hero_title'] ?: $org['name']) ?>
                            </h1>
                            <p class="lead text-white-50 mb-4 fs-4 max-w-2xl benchero-hero-item stagger-2">
                                <?= htmlspecialchars($settings['hero_subtitle'] ?: ($org['description'] ?: 'United by passion. Driven by victory.')) ?>
                            </p>
                            <div class="d-flex flex-wrap gap-3 benchero-hero-item stagger-3">
                                <a href="<?= htmlspecialchars($settings['hero_cta_url'] ?: ('/club/' . $orgSlug . '/fixtures')) ?>" class="btn btn-club-primary btn-lg rounded-pill px-4 fw-bold text-uppercase">
                                    <?= htmlspecialchars($settings['hero_cta_text'] ?: 'View Fixtures') ?> <i class="bi bi-arrow-right ms-2"></i>
                                </a>
                                <a href="/club/<?= $orgSlug ?>/teams" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold text-uppercase">
                                    Meet The Team
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php break; ?>

        <?php case 'about': ?>
            <!-- About Section -->
            <section class="py-5 bg-white border-bottom">
                <div class="container py-4 benchero-reveal">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-6">
                            <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-3">About The Club</span>
                            <h2 class="display-5 fw-bold text-uppercase mb-4"><?= htmlspecialchars($sec['title'] ?: 'Our Passion & Legacy') ?></h2>
                            <p class="lead text-secondary mb-4">
                                <?= htmlspecialchars($org['description'] ?: ($org['name'] . ' is a premier sports club built on athletic excellence, teamwork, and sportsmanship.')) ?>
                            </p>
                            <?php if (!empty($org['founded_year'])): ?>
                                <div class="p-3 bg-light rounded-3 border border-start border-4 border-primary mb-4">
                                    <strong class="text-dark">Established:</strong> <?= htmlspecialchars($org['founded_year']) ?>
                                </div>
                            <?php endif; ?>
                            <a href="/club/<?= $orgSlug ?>/about" class="btn btn-outline-dark rounded-pill fw-bold px-4">Read Full History & Mission</a>
                        </div>
                        <div class="col-lg-6 text-center">
                            <?php if (!empty($org['cover_url'])): ?>
                                <img src="<?= htmlspecialchars($org['cover_url']) ?>" alt="Club Cover" class="img-fluid rounded-4 shadow-lg">
                            <?php else: ?>
                                <div class="bg-light rounded-4 p-5 text-center border">
                                    <i class="bi bi-trophy display-1 text-club-primary mb-3"></i>
                                    <h4 class="fw-bold text-uppercase mb-0"><?= htmlspecialchars($org['name']) ?></h4>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php break; ?>

        <?php case 'fixtures': ?>
            <!-- Upcoming Fixtures Section -->
            <section class="py-5 bg-light border-bottom">
                <div class="container py-4 benchero-reveal">
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <div>
                            <span class="badge bg-warning text-dark text-uppercase px-3 py-2 rounded-pill mb-2">Match Schedule</span>
                            <h2 class="display-6 fw-bold text-uppercase mb-0"><?= htmlspecialchars($sec['title'] ?: 'Upcoming Fixtures') ?></h2>
                        </div>
                        <a href="/club/<?= $orgSlug ?>/fixtures" class="btn btn-link text-club-primary text-decoration-none fw-bold">All Fixtures <i class="bi bi-arrow-right"></i></a>
                    </div>

                    <?php if (empty($upcomingFixtures)): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                            <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                            <h5 class="fw-bold">No Upcoming Fixtures Scheduled</h5>
                            <p class="text-muted mb-0">Check back soon for upcoming match dates and kickoff times.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($upcomingFixtures as $fix): ?>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill small fw-bold">
                                                <?= htmlspecialchars($fix['competition_name'] ?: ($fix['competition_type'] ?: 'Matchday')) ?>
                                            </span>
                                            <small class="text-muted fw-bold">
                                                <i class="bi bi-calendar-event me-1"></i><?= date('M j, Y — g:i A', strtotime($fix['scheduled_at'])) ?>
                                            </small>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between text-center my-3">
                                            <div class="flex-1 fw-bold fs-5 text-uppercase text-dark"><?= htmlspecialchars($fix['home_team_name']) ?></div>
                                            <div class="px-3">
                                                <span class="badge bg-danger text-white fs-6 px-3 py-2 rounded-pill">VS</span>
                                            </div>
                                            <div class="flex-1 fw-bold fs-5 text-uppercase text-dark"><?= htmlspecialchars($fix['away_team_name']) ?></div>
                                        </div>
                                        <div class="text-center mt-3 pt-3 border-top small text-muted">
                                            <i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($fix['venue_name'] ?: 'Club Grounds') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php break; ?>

        <?php case 'results': ?>
            <!-- Match Results Section -->
            <section class="py-5 bg-white border-bottom">
                <div class="container py-4 benchero-reveal">
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <div>
                            <span class="badge bg-success text-white text-uppercase px-3 py-2 rounded-pill mb-2">Scores</span>
                            <h2 class="display-6 fw-bold text-uppercase mb-0"><?= htmlspecialchars($sec['title'] ?: 'Latest Match Results') ?></h2>
                        </div>
                        <a href="/club/<?= $orgSlug ?>/results" class="btn btn-link text-club-primary text-decoration-none fw-bold">Full Results Archive <i class="bi bi-arrow-right"></i></a>
                    </div>

                    <?php if (empty($completedResults)): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-light">
                            <i class="bi bi-trophy display-4 text-muted mb-3"></i>
                            <h5 class="fw-bold">No Completed Results Recorded Yet</h5>
                            <p class="text-muted mb-0">Match scores will be published here as soon as games finish.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($completedResults as $res): ?>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-light border-start border-4 border-success">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted fw-bold"><?= date('M j, Y', strtotime($res['scheduled_at'])) ?></small>
                                            <span class="badge bg-success px-2 py-1 small">FINAL</span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between my-2">
                                            <div class="fw-bold fs-5"><?= htmlspecialchars($res['home_team_name']) ?></div>
                                            <div class="display-6 fw-black text-dark px-3">
                                                <?= (int)$res['home_score'] ?> - <?= (int)$res['away_score'] ?>
                                            </div>
                                            <div class="fw-bold fs-5"><?= htmlspecialchars($res['away_team_name']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php break; ?>

        <?php case 'teams': ?>
            <!-- Teams Section -->
            <section class="py-5 bg-light border-bottom">
                <div class="container py-4 benchero-reveal">
                    <div class="text-center mb-5">
                        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">Club Roster</span>
                        <h2 class="display-6 fw-bold text-uppercase mb-0"><?= htmlspecialchars($sec['title'] ?: 'Our Sports Squads') ?></h2>
                    </div>

                    <?php if (empty($teams)): ?>
                        <div class="text-center p-5 bg-white rounded-4 shadow-sm">
                            <i class="bi bi-people display-4 text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">No active teams created for this club profile.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4 justify-content-center">
                            <?php foreach ($teams as $t): ?>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white h-100">
                                        <div class="bg-club-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width:64px; height:64px;">
                                            <i class="bi bi-shield-fill fs-2"></i>
                                        </div>
                                        <h4 class="fw-bold text-uppercase mb-2"><?= htmlspecialchars($t['name']) ?></h4>
                                        <span class="badge bg-light text-dark border mb-3 w-auto mx-auto"><?= htmlspecialchars($t['team_type'] ?: 'Senior Division') ?></span>
                                        <p class="small text-muted mb-4"><?= htmlspecialchars($t['description'] ?: 'Official club division team competing in regional competitions.') ?></p>
                                        <a href="/club/<?= $orgSlug ?>/teams/<?= htmlspecialchars($t['slug']) ?>" class="btn btn-outline-primary rounded-pill btn-sm mt-auto fw-bold">View Squad & Roster</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php break; ?>

        <?php case 'news': ?>
            <!-- News Section -->
            <section class="py-5 bg-white border-bottom">
                <div class="container py-4 benchero-reveal">
                    <div class="d-flex justify-content-between align-items-end mb-4">
                        <div>
                            <span class="badge bg-danger text-white text-uppercase px-3 py-2 rounded-pill mb-2">Matchday & News</span>
                            <h2 class="display-6 fw-bold text-uppercase mb-0"><?= htmlspecialchars($sec['title'] ?: 'Latest Club Updates') ?></h2>
                        </div>
                        <a href="/club/<?= $orgSlug ?>/news" class="btn btn-link text-club-primary text-decoration-none fw-bold">Read All News <i class="bi bi-arrow-right"></i></a>
                    </div>

                    <?php if (empty($news)): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-light">
                            <i class="bi bi-newspaper display-4 text-muted mb-3"></i>
                            <h5 class="fw-bold">No Published News Articles</h5>
                            <p class="text-muted mb-0">Stay tuned for match updates, signing announcements, and club developments.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($news as $article): ?>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
                                        <?php if (!empty($article['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="Article" class="card-img-top" style="height:200px; object-fit:cover;">
                                        <?php endif; ?>
                                        <div class="card-body p-4 d-flex flex-column">
                                            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill small fw-bold w-auto mb-2"><?= htmlspecialchars($article['category']) ?></span>
                                            <h5 class="fw-bold text-dark text-uppercase mb-2"><?= htmlspecialchars($article['title']) ?></h5>
                                            <p class="small text-muted mb-4"><?= htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 120) . '...') ?></p>
                                            <a href="/club/<?= $orgSlug ?>/news/<?= htmlspecialchars($article['slug']) ?>" class="btn btn-link text-club-primary text-decoration-none p-0 fw-bold mt-auto">
                                                Read Article <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php break; ?>

        <?php case 'contact': ?>
            <!-- Contact Section -->
            <section class="py-5 bg-light">
                <div class="container py-4 benchero-reveal">
                    <div class="row align-items-center g-5">
                        <div class="col-lg-6">
                            <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">Get In Touch</span>
                            <h2 class="display-6 fw-bold text-uppercase mb-4"><?= htmlspecialchars($sec['title'] ?: 'Contact The Club') ?></h2>
                            <p class="text-secondary lead mb-4">Have questions about player trials, sponsorship opportunities, or tickets? Reach out to our club management directly.</p>
                            
                            <ul class="list-unstyled d-grid gap-3 mb-4">
                                <?php if (!empty($org['contact_email'])): ?>
                                    <li class="d-flex align-items-center gap-3">
                                        <div class="bg-white p-3 rounded-circle shadow-sm text-club-primary"><i class="bi bi-envelope fs-4"></i></div>
                                        <div><strong class="d-block text-dark">Email Us</strong><span class="text-muted"><?= htmlspecialchars($org['contact_email']) ?></span></div>
                                    </li>
                                <?php endif; ?>
                                <?php if (!empty($org['contact_phone'])): ?>
                                    <li class="d-flex align-items-center gap-3">
                                        <div class="bg-white p-3 rounded-circle shadow-sm text-club-primary"><i class="bi bi-telephone fs-4"></i></div>
                                        <div><strong class="d-block text-dark">Call Office</strong><span class="text-muted"><?= htmlspecialchars($org['contact_phone']) ?></span></div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-lg rounded-4 p-4 bg-white">
                                <h4 class="fw-bold mb-3">Send Message</h4>
                                <form action="/club/<?= $orgSlug ?>/contact" method="POST">
                                    <div class="mb-3">
                                        <input type="text" name="name" class="form-control rounded-3 p-3" placeholder="Your Name" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="email" name="email" class="form-control rounded-3 p-3" placeholder="Your Email Address" required>
                                    </div>
                                    <div class="mb-3">
                                        <textarea name="message" rows="4" class="form-control rounded-3 p-3" placeholder="Write your message here..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-club-primary btn-lg rounded-pill w-100 fw-bold">Send Message</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        <?php break; ?>
    <?php endswitch; ?>
<?php endforeach; ?>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
