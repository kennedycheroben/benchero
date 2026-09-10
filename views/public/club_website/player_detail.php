<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container py-4">
        <div class="row align-items-center g-4">
            <div class="col-md-3 text-center text-md-start">
                <div class="mx-auto" style="width:160px; height:160px;">
                    <?php if (!empty($player['photo_url'])): ?>
                        <img src="<?= htmlspecialchars($player['photo_url']) ?>" alt="Player" class="w-100 h-100 rounded-circle object-fit-cover shadow-lg border border-3 border-white">
                    <?php else: ?>
                        <div class="w-100 h-100 bg-white text-club-primary rounded-circle d-flex align-items-center justify-content-center border border-3 border-white shadow-lg">
                            <i class="bi bi-person display-3"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-9 text-center text-md-start">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                    <span class="badge bg-warning text-dark fs-5 fw-bold px-3 py-1 rounded-pill">
                        #<?= htmlspecialchars($player['jersey_number'] ?: '—') ?>
                    </span>
                    <?php if (!empty($player['is_captain'])): ?>
                        <span class="badge bg-danger text-white px-3 py-1 rounded-pill text-uppercase">Team Captain</span>
                    <?php endif; ?>
                </div>

                <h1 class="display-4 fw-black text-uppercase text-white mb-2">
                    <?= htmlspecialchars($player['display_name'] ?: ($player['first_name'] . ' ' . $player['last_name'])) ?>
                </h1>

                <div class="fs-5 text-white-50 font-heading text-uppercase">
                    <?= htmlspecialchars($player['position'] ?: 'Player') ?>
                    <?php if (!empty($player['team_name'])): ?>
                        • <span class="text-club-accent fw-bold"><?= htmlspecialchars($player['team_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row g-5">
            <!-- Player Stats & Bio -->
            <div class="col-lg-8">
                <h3 class="fw-bold text-uppercase mb-3">Player Biography</h3>
                <div class="lead text-secondary mb-4">
                    <?= !empty($player['bio']) ? nl2br(htmlspecialchars($player['bio'])) : 'No biography published for this player yet.' ?>
                </div>

                <h3 class="fw-bold text-uppercase mb-3 mt-5">Player Profile Metrics</h3>
                <div class="row g-3">
                    <?php if (!empty($player['nationality'])): ?>
                        <div class="col-6 col-md-4">
                            <div class="p-3 bg-light rounded-3 text-center border">
                                <small class="text-muted text-uppercase d-block mb-1">Nationality</small>
                                <strong class="fs-5 text-dark"><?= htmlspecialchars($player['nationality']) ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($player['preferred_foot'])): ?>
                        <div class="col-6 col-md-4">
                            <div class="p-3 bg-light rounded-3 text-center border">
                                <small class="text-muted text-uppercase d-block mb-1">Preferred Foot</small>
                                <strong class="fs-5 text-dark"><?= htmlspecialchars(ucfirst($player['preferred_foot'])) ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($player['date_of_birth'])): ?>
                        <div class="col-6 col-md-4">
                            <div class="p-3 bg-light rounded-3 text-center border">
                                <small class="text-muted text-uppercase d-block mb-1">Date of Birth</small>
                                <strong class="fs-5 text-dark"><?= date('M j, Y', strtotime($player['date_of_birth'])) ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Side Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
                    <h5 class="fw-bold text-uppercase mb-3">Club Info</h5>
                    <ul class="list-unstyled d-grid gap-2 small mb-0">
                        <li><strong>Club:</strong> <?= htmlspecialchars($org['name']) ?></li>
                        <li><strong>Sport:</strong> <?= htmlspecialchars($player['sport_name'] ?: 'General Sports') ?></li>
                        <li><strong>Team:</strong> <?= htmlspecialchars($player['team_name'] ?: 'Unassigned') ?></li>
                        <li><strong>Jersey Number:</strong> #<?= htmlspecialchars($player['jersey_number'] ?: '—') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
