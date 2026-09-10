<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">Club Squad</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Players & Athletes</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Meet the talented players representing <?= htmlspecialchars($org['name']) ?>.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (empty($squad)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-person-badge display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Public Players Found</h4>
                <p class="text-muted mb-0">Player profiles will be displayed here as players are added to team rosters.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($squad as $p): ?>
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white h-100 position-relative overflow-hidden">
                            <?php if (!empty($p['is_captain'])): ?>
                                <span class="position-absolute top-0 end-0 bg-warning text-dark fw-bold px-2 py-1 small rounded-start-2">CAPTAIN</span>
                            <?php endif; ?>

                            <div class="mb-3 mx-auto" style="width:110px; height:110px;">
                                <?php if (!empty($p['photo_url'])): ?>
                                    <img src="<?= htmlspecialchars($p['photo_url']) ?>" alt="Player" class="w-100 h-100 rounded-circle object-fit-cover shadow-sm">
                                <?php else: ?>
                                    <div class="w-100 h-100 bg-light rounded-circle d-flex align-items-center justify-content-center border text-club-primary">
                                        <i class="bi bi-person fs-1"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <span class="badge bg-dark text-white rounded-pill px-3 py-1 mb-2 align-self-center fs-7 fw-bold">
                                #<?= htmlspecialchars($p['jersey_number'] ?: '—') ?>
                            </span>

                            <h5 class="fw-bold text-uppercase mb-1">
                                <?= htmlspecialchars($p['display_name'] ?: ($p['first_name'] . ' ' . $p['last_name'])) ?>
                            </h5>
                            <span class="small text-muted d-block mb-1"><?= htmlspecialchars($p['position'] ?: 'Player') ?></span>
                            <?php if (!empty($p['team_name'])): ?>
                                <small class="text-club-primary font-heading text-uppercase d-block mb-3 fw-bold"><?= htmlspecialchars($p['team_name']) ?></small>
                            <?php endif; ?>

                            <a href="/club/<?= $orgSlug ?>/players/<?= htmlspecialchars($p['id']) ?>" class="btn btn-outline-primary btn-sm rounded-pill mt-auto fw-bold">
                                View Profile
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
