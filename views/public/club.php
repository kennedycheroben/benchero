<?php $this->layout('layout', ['title' => $title]) ?>

<?php
    $logo = !empty($org['logo_url']) ? $org['logo_url'] : null;
    $cover = !empty($org['cover_url']) ? $org['cover_url'] : null;
    $desc = !empty($org['description']) ? $org['description'] : 'Official Benchero Sports Club page.';
    $founded = !empty($org['founded_year']) ? $org['founded_year'] : null;
    $colors = !empty($org['club_colors']) ? $org['club_colors'] : null;
    $email = !empty($org['contact_email']) ? $org['contact_email'] : null;
    $phone = !empty($org['contact_phone']) ? $org['contact_phone'] : null;
    $address = !empty($org['address']) ? $org['address'] : null;
    $positions = $sportTerminology['positions'] ?? ['Goalkeeper', 'Defender', 'Midfielder', 'Forward'];
?>

<!-- Custom CSS for Public Club Website -->
<style>
    .club-hero-section {
        position: relative;
        background: <?= $cover ? "linear-gradient(rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.95)), url('" . htmlspecialchars($cover) . "') center/cover no-repeat" : "linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2563eb 100%)" ?>;
        color: #ffffff;
        padding: 4.5rem 0 3.5rem;
    }
    .club-logo-img {
        width: 110px;
        height: 110px;
        object-fit: contain;
        background-color: #ffffff;
        border: 4px solid #ffffff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    }
    .club-logo-fallback {
        width: 110px;
        height: 110px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        font-size: 2.5rem;
        font-weight: 800;
        border: 4px solid #ffffff;
        box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    }
    .club-nav-sticky {
        position: sticky;
        top: 0;
        z-index: 1020;
        background-color: #ffffff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    .player-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .player-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.12) !important;
    }
    .jersey-number-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: rgba(15, 23, 42, 0.85);
        color: #ffffff;
        font-weight: 800;
        font-size: 1.1rem;
        padding: 4px 12px;
        border-radius: 20px;
        backdrop-filter: blur(4px);
    }
    .match-card {
        border-left: 4px solid #2563eb;
        transition: all 0.2s ease;
    }
    .match-card:hover {
        background-color: #f8fafc;
    }
    .sponsor-logo-box {
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        filter: grayscale(30%);
        transition: filter 0.2s ease;
    }
    .sponsor-logo-box:hover {
        filter: grayscale(0%);
    }
</style>

<!-- Sticky Sub-Navigation Bar for Club Website -->
<nav class="navbar navbar-expand-lg navbar-light club-nav-sticky py-2 border-bottom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-extrabold text-dark" href="#hero">
            <?php if ($logo): ?>
                <img src="<?= htmlspecialchars($logo) ?>" width="32" height="32" class="rounded-circle object-fit-contain" alt="Logo">
            <?php else: ?>
                <span class="badge bg-primary rounded-circle px-2 py-1 fs-7"><?= strtoupper(substr($org['name'], 0, 2)) ?></span>
            <?php endif; ?>
            <span><?= htmlspecialchars($org['name']) ?></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#clubSubNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="clubSubNav">
            <ul class="navbar-nav me-auto ms-lg-4 gap-lg-1 small fw-semibold">
                <li class="nav-item"><a class="nav-link text-dark" href="#overview">About</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="#teams">Teams</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="#squad">Squad</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="#staff">Staff</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="#fixtures">Fixtures</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="#results">Results</a></li>
                <?php if (!empty($standings)): ?>
                    <li class="nav-item"><a class="nav-link text-dark" href="#standings">Standings</a></li>
                <?php endif; ?>
                <?php if (!empty($news)): ?>
                    <li class="nav-item"><a class="nav-link text-dark" href="#news">News</a></li>
                <?php endif; ?>
                <?php if (!empty($gallery)): ?>
                    <li class="nav-item"><a class="nav-link text-dark" href="#gallery">Gallery</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link text-dark" href="#contact">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <a href="#fixtures" class="btn btn-primary btn-sm fw-bold rounded-pill px-3">Upcoming Matches</a>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section id="hero" class="club-hero-section">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-4 mb-3">
                    <?php if ($logo): ?>
                        <img src="<?= htmlspecialchars($logo) ?>" class="rounded-circle club-logo-img" alt="<?= htmlspecialchars($org['name']) ?>">
                    <?php else: ?>
                        <div class="rounded-circle club-logo-fallback d-flex align-items-center justify-content-center">
                            <?= strtoupper(substr($org['name'], 0, 2)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge bg-primary px-3 py-1 rounded-pill text-uppercase fs-8 fw-bold">Verified Sports Organization</span>
                            <?php if ($primarySport): ?>
                                <span class="badge bg-light text-dark px-3 py-1 rounded-pill fs-8 fw-bold"><?= htmlspecialchars($primarySport['name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h1 class="display-3 fw-extrabold mb-1 tracking-tight"><?= htmlspecialchars($org['name']) ?></h1>
                        <p class="lead text-white-50 mb-0">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($address ?: ($org['country'] ?? 'Kenya')) ?>
                            <?php if ($founded): ?> &bull; Founded <?= (int)$founded ?><?php endif; ?>
                        </p>
                    </div>
                </div>
                <p class="text-white-50 max-w-2xl fs-5 mb-4"><?= htmlspecialchars(substr($desc, 0, 220)) ?><?= strlen($desc) > 220 ? '...' : '' ?></p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#fixtures" class="btn btn-primary btn-lg fw-bold rounded-3 px-4 shadow">
                        <i class="bi bi-calendar3 me-2"></i>View Match Fixtures
                    </a>
                    <a href="#squad" class="btn btn-outline-light btn-lg fw-bold rounded-3 px-4">
                        <i class="bi bi-person-badge me-2"></i>Meet the Squad
                    </a>
                </div>
            </div>

            <!-- Header Quick Info Card -->
            <div class="col-lg-4">
                <div class="card border-0 bg-white text-dark rounded-4 p-4 shadow-lg">
                    <h5 class="fw-bold mb-3 border-bottom pb-2"><i class="bi bi-info-circle-fill text-primary me-2"></i>Club Snapshot</h5>
                    <div class="d-grid gap-2 text-muted small">
                        <div class="d-flex justify-content-between">
                            <span>Active Sports:</span>
                            <strong class="text-dark"><?= count($sports) ?> Sports</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Teams Configured:</span>
                            <strong class="text-dark"><?= count($teams) ?> Teams</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Squad Players:</span>
                            <strong class="text-dark"><?= count($squad) ?> Players</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Matches Scheduled:</span>
                            <strong class="text-dark"><?= count($upcomingFixtures) + count($completedResults) ?> Matches</strong>
                        </div>
                        <?php if ($colors): ?>
                            <div class="d-flex justify-content-between">
                                <span>Club Colors:</span>
                                <strong class="text-dark"><?= htmlspecialchars($colors) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Overview & Details Section -->
<section id="overview" class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <h4 class="fw-bold mb-3"><i class="bi bi-shield-check text-primary me-2"></i>About <?= htmlspecialchars($org['name']) ?></h4>
                    <p class="text-muted leading-relaxed mb-4"><?= nl2br(htmlspecialchars($desc)) ?></p>
                    
                    <?php if (!empty($sports)): ?>
                        <h6 class="fw-bold text-uppercase text-muted fs-7 mb-2">Sports & Disciplines</h6>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($sports as $sp): ?>
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold fs-7">
                                    <i class="bi bi-trophy me-1"></i><?= htmlspecialchars($sp['name']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                    <h5 class="fw-bold mb-3"><i class="bi bi-card-checklist text-info me-2"></i>Club Details</h5>
                    <ul class="list-unstyled text-muted small d-grid gap-3 mb-0">
                        <li class="d-flex justify-content-between border-bottom pb-2">
                            <span>Status:</span> <span class="badge bg-success-subtle text-success fw-bold">Active Member</span>
                        </li>
                        <li class="d-flex justify-content-between border-bottom pb-2">
                            <span>Timezone:</span> <span class="fw-semibold text-dark"><?= htmlspecialchars($org['timezone'] ?? 'Africa/Nairobi') ?></span>
                        </li>
                        <li class="d-flex justify-content-between border-bottom pb-2">
                            <span>Member Since:</span> <span class="fw-semibold text-dark"><?= date('F Y', strtotime($org['created_at'])) ?></span>
                        </li>
                        <?php if ($email): ?>
                            <li class="d-flex justify-content-between border-bottom pb-2">
                                <span>Official Email:</span> <a href="mailto:<?= htmlspecialchars($email) ?>" class="fw-semibold text-primary"><?= htmlspecialchars($email) ?></a>
                            </li>
                        <?php endif; ?>
                        <?php if ($phone): ?>
                            <li class="d-flex justify-content-between">
                                <span>Phone / Contact:</span> <span class="fw-semibold text-dark"><?= htmlspecialchars($phone) ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Teams Section -->
<section id="teams" class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Club Roster Structure</span>
                <h3 class="fw-extrabold mb-0">Our Teams & Divisions</h3>
            </div>
        </div>

        <?php if (empty($teams)): ?>
            <div class="p-4 text-center text-muted bg-light rounded-4">
                <i class="bi bi-people display-5 mb-2 d-block"></i>
                <p class="mb-0">No specific team divisions configured yet.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($teams as $t): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light h-100 border-start border-primary border-4">
                            <span class="badge bg-primary text-white fw-bold px-3 py-1 rounded-pill w-fit mb-2"><?= htmlspecialchars(ucfirst($t['team_type'] ?: 'Senior')) ?></span>
                            <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($t['name']) ?></h5>
                            <p class="text-muted small mb-0"><?= htmlspecialchars($t['description'] ?: 'Official team squad') ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Squad / Players Section -->
<section id="squad" class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
            <div>
                <span class="badge bg-primary px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Players & Athletes</span>
                <h3 class="fw-extrabold mb-0">Current Squad</h3>
            </div>
        </div>

        <?php if (empty($squad)): ?>
            <div class="p-5 text-center text-muted bg-white rounded-4 shadow-sm">
                <i class="bi bi-person-badge display-4 mb-2 d-block"></i>
                <p class="mb-0">No active players assigned to the squad yet.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($squad as $p): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white player-card h-100 position-relative">
                            <?php if (!empty($p['jersey_number'])): ?>
                                <span class="jersey-number-badge">#<?= htmlspecialchars($p['jersey_number']) ?></span>
                            <?php endif; ?>

                            <div class="bg-dark text-center p-4">
                                <?php if (!empty($p['photo_url'])): ?>
                                    <img src="<?= htmlspecialchars($p['photo_url']) ?>" class="rounded-circle object-fit-cover shadow" width="100" height="100" alt="Player">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto fw-extrabold shadow" style="width:100px; height:100px; font-size: 2rem;">
                                        <?= strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body p-4 text-center">
                                <h5 class="fw-bold mb-1">
                                    <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                    <?php if (!empty($p['is_captain'])): ?>
                                        <span class="badge bg-warning text-dark ms-1">C</span>
                                    <?php elseif (!empty($p['is_vice_captain'])): ?>
                                        <span class="badge bg-secondary ms-1">VC</span>
                                    <?php endif; ?>
                                </h5>
                                <div class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill fw-semibold fs-7 mb-2">
                                    <?= htmlspecialchars($p['position'] ?: 'Player') ?>
                                </div>
                                <p class="text-muted small mb-0"><?= htmlspecialchars($p['team_name'] ?: 'Club Squad') ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Staff & Management Section -->
<section id="staff" class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="mb-4">
            <span class="badge bg-info text-dark px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Leadership</span>
            <h3 class="fw-extrabold mb-0">Coaching Staff & Management</h3>
        </div>

        <?php if (empty($staff)): ?>
            <div class="p-4 text-center text-muted bg-light rounded-4">
                <i class="bi bi-person-vcard display-5 mb-2 d-block"></i>
                <p class="mb-0">No staff members published yet.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($staff as $m): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light text-center h-100">
                            <?php if (!empty($m['photo_url'])): ?>
                                <img src="<?= htmlspecialchars($m['photo_url']) ?>" class="rounded-circle object-fit-cover mx-auto mb-3 shadow-sm" width="80" height="80" alt="Staff">
                            <?php else: ?>
                                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center mx-auto mb-3 fw-bold shadow-sm" style="width:80px; height:80px; font-size: 1.5rem;">
                                    <?= strtoupper(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>

                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></h5>
                            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill fw-bold fs-7 mb-2">
                                <?= htmlspecialchars($m['role']) ?>
                            </span>
                            <?php if (!empty($m['team_name'])): ?>
                                <p class="text-muted small mb-0"><i class="bi bi-people me-1"></i><?= htmlspecialchars($m['team_name']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Upcoming Fixtures Section -->
<section id="fixtures" class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="badge bg-primary px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Match Schedule</span>
                <h3 class="fw-extrabold mb-0">Upcoming Fixtures</h3>
            </div>
        </div>

        <?php if (empty($upcomingFixtures)): ?>
            <div class="p-5 text-center text-muted bg-white rounded-4 shadow-sm">
                <i class="bi bi-calendar-x display-4 mb-2 d-block"></i>
                <p class="mb-0">No upcoming fixtures scheduled at the moment.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($upcomingFixtures as $f): ?>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white match-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 rounded-pill fs-8">
                                    <?= htmlspecialchars(ucfirst($f['competition_type'])) ?> &bull; <?= htmlspecialchars($f['competition_name'] ?: 'Match') ?>
                                </span>
                                <span class="badge bg-light text-muted border fs-8"><i class="bi bi-clock me-1"></i><?= date('D, M j, Y @ H:i', strtotime($f['scheduled_at'])) ?></span>
                            </div>

                            <div class="d-flex align-items-center justify-content-between text-center my-3">
                                <div class="flex-1">
                                    <h5 class="fw-extrabold mb-0 text-dark"><?= htmlspecialchars($f['home_team_name']) ?></h5>
                                    <small class="text-muted">HOME</small>
                                </div>
                                <div class="px-3">
                                    <span class="badge bg-dark text-white fs-6 px-3 py-2 fw-bold">VS</span>
                                </div>
                                <div class="flex-1">
                                    <h5 class="fw-extrabold mb-0 text-dark"><?= htmlspecialchars($f['away_team_name']) ?></h5>
                                    <small class="text-muted">AWAY</small>
                                </div>
                            </div>

                            <div class="text-muted small border-top pt-3 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($f['venue_name'] ?: 'TBD') ?></span>
                                <span class="badge bg-info-subtle text-info fw-semibold">Scheduled</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Latest Results Section -->
<section id="results" class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="badge bg-success px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Match Results</span>
                <h3 class="fw-extrabold mb-0">Latest Scores & Completed Games</h3>
            </div>
        </div>

        <?php if (empty($completedResults)): ?>
            <div class="p-4 text-center text-muted bg-light rounded-4">
                <i class="bi bi-trophy display-5 mb-2 d-block"></i>
                <p class="mb-0">No completed match results published yet.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($completedResults as $f): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-dark text-white px-2 py-1 fs-8">FINAL</span>
                                <small class="text-muted"><?= date('M j, Y', strtotime($f['scheduled_at'])) ?></small>
                            </div>

                            <div class="d-flex justify-content-between align-items-center my-3 bg-white p-3 rounded-3 shadow-sm">
                                <div class="fw-bold text-dark"><?= htmlspecialchars($f['home_team_name']) ?></div>
                                <div class="display-6 fw-extrabold text-primary"><?= (int)$f['home_score'] ?> &mdash; <?= (int)$f['away_score'] ?></div>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($f['away_team_name']) ?></div>
                            </div>

                            <div class="small text-muted text-center">
                                <?= htmlspecialchars($f['competition_name'] ?: 'Match') ?> &bull; <?= htmlspecialchars($f['venue_name'] ?: 'Stadium') ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- League Standings Section -->
<?php if (!empty($standings)): ?>
    <section id="standings" class="py-5 bg-light border-bottom">
        <div class="container">
            <div class="mb-4">
                <span class="badge bg-primary px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Competition Table</span>
                <h3 class="fw-extrabold mb-0">League Standings</h3>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-start ps-4">Pos</th>
                                <th class="text-start">Team</th>
                                <th>P</th>
                                <th>W</th>
                                <th>D</th>
                                <th>L</th>
                                <th>GF</th>
                                <th>GA</th>
                                <th>GD</th>
                                <th class="pe-4">Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standings as $row): ?>
                                <tr class="<?= $row['position'] === 1 ? 'table-warning fw-bold' : '' ?>">
                                    <td class="text-start ps-4 fw-bold"><?= (int)$row['position'] ?></td>
                                    <td class="text-start fw-bold text-dark"><?= htmlspecialchars($row['team_name']) ?></td>
                                    <td><?= (int)$row['played'] ?></td>
                                    <td><?= (int)$row['won'] ?></td>
                                    <td><?= (int)$row['drawn'] ?></td>
                                    <td><?= (int)$row['lost'] ?></td>
                                    <td><?= (int)$row['goals_for'] ?></td>
                                    <td><?= (int)$row['goals_against'] ?></td>
                                    <td><?= (int)$row['goal_difference'] ?></td>
                                    <td class="fw-extrabold text-primary fs-5 pe-4"><?= (int)$row['points'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- News Section -->
<?php if (!empty($news)): ?>
    <section id="news" class="py-5 bg-white border-bottom">
        <div class="container">
            <div class="mb-4">
                <span class="badge bg-info text-dark px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Club Updates</span>
                <h3 class="fw-extrabold mb-0">News & Announcements</h3>
            </div>

            <div class="row g-4">
                <?php foreach ($news as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-light">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="<?= htmlspecialchars($item['image_url']) ?>" class="card-img-top" style="height: 180px; object-fit: cover;" alt="News">
                            <?php endif; ?>
                            <div class="card-body p-4">
                                <span class="badge bg-primary-subtle text-primary mb-2 fw-semibold"><?= htmlspecialchars($item['category']) ?></span>
                                <h5 class="fw-bold mb-2"><?= htmlspecialchars($item['title']) ?></h5>
                                <p class="text-muted small mb-3"><?= htmlspecialchars($item['excerpt'] ?: substr(strip_tags($item['content']), 0, 120)) ?></p>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M j, Y', strtotime($item['published_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Photo Gallery Section -->
<?php if (!empty($gallery)): ?>
    <section id="gallery" class="py-5 bg-light border-bottom">
        <div class="container">
            <div class="mb-4">
                <span class="badge bg-secondary px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Media</span>
                <h3 class="fw-extrabold mb-0">Photo Gallery</h3>
            </div>

            <div class="row g-3">
                <?php foreach ($gallery as $img): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <img src="<?= htmlspecialchars($img['image_url']) ?>" class="w-100" style="height: 220px; object-fit: cover;" alt="<?= htmlspecialchars($img['title'] ?? 'Gallery') ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Sponsors Section -->
<?php if (!empty($sponsors)): ?>
    <section class="py-4 bg-white border-bottom">
        <div class="container text-center">
            <h6 class="text-uppercase text-muted fw-bold fs-8 tracking-wider mb-4">Official Club Sponsors & Partners</h6>
            <div class="row align-items-center justify-content-center g-4">
                <?php foreach ($sponsors as $sp): ?>
                    <div class="col-6 col-md-3 col-lg-2">
                        <?php if (!empty($sp['website_url'])): ?>
                            <a href="<?= htmlspecialchars($sp['website_url']) ?>" target="_blank" class="sponsor-logo-box">
                                <img src="<?= htmlspecialchars($sp['logo_url']) ?>" style="max-height: 50px; max-width: 130px; object-fit: contain;" alt="<?= htmlspecialchars($sp['name']) ?>">
                            </a>
                        <?php else: ?>
                            <div class="sponsor-logo-box">
                                <img src="<?= htmlspecialchars($sp['logo_url']) ?>" style="max-height: 50px; max-width: 130px; object-fit: contain;" alt="<?= htmlspecialchars($sp['name']) ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Contact & Footer Section -->
<section id="contact" class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row g-4 justify-content-between">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <?php if ($logo): ?>
                        <img src="<?= htmlspecialchars($logo) ?>" width="40" height="40" class="rounded-circle" alt="Logo">
                    <?php endif; ?>
                    <h3 class="fw-extrabold mb-0"><?= htmlspecialchars($org['name']) ?></h3>
                </div>
                <p class="text-white-50 mb-4"><?= htmlspecialchars(substr($desc, 0, 180)) ?></p>

                <?php if (!empty($socialLinks)): ?>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php if (!empty($socialLinks['facebook'])): ?>
                            <a href="<?= htmlspecialchars($socialLinks['facebook']) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-facebook"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($socialLinks['instagram'])): ?>
                            <a href="<?= htmlspecialchars($socialLinks['instagram']) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($socialLinks['x'])): ?>
                            <a href="<?= htmlspecialchars($socialLinks['x']) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-twitter-x"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($socialLinks['tiktok'])): ?>
                            <a href="<?= htmlspecialchars($socialLinks['tiktok']) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-tiktok"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($socialLinks['youtube'])): ?>
                            <a href="<?= htmlspecialchars($socialLinks['youtube']) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-youtube"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($socialLinks['whatsapp'])): ?>
                            <a href="<?= htmlspecialchars($socialLinks['whatsapp']) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-whatsapp"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-5">
                <h5 class="fw-bold mb-3"><i class="bi bi-envelope-at me-2"></i>Contact Information</h5>
                <ul class="list-unstyled text-white-50 d-grid gap-2 mb-4">
                    <?php if ($email): ?>
                        <li><i class="bi bi-envelope me-2 text-primary"></i><?= htmlspecialchars($email) ?></li>
                    <?php endif; ?>
                    <?php if ($phone): ?>
                        <li><i class="bi bi-telephone me-2 text-success"></i><?= htmlspecialchars($phone) ?></li>
                    <?php endif; ?>
                    <?php if ($address): ?>
                        <li><i class="bi bi-geo-alt me-2 text-danger"></i><?= htmlspecialchars($address) ?></li>
                    <?php endif; ?>
                </ul>

                <a href="<?= url('/register') ?>" class="btn btn-outline-light btn-sm rounded-3">
                    Powered by Benchero Sports Management Platform
                </a>
            </div>
        </div>
    </div>
</section>
