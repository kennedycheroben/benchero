<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2"><?= htmlspecialchars($team['team_type'] ?: 'Official Squad') ?></span>
        <h1 class="display-4 fw-black text-uppercase mb-2"><?= htmlspecialchars($team['name']) ?></h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0"><?= htmlspecialchars($team['description'] ?: 'Division squad roster and coaching staff.') ?></p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <!-- Team Roster -->
        <h3 class="fw-bold text-uppercase mb-4"><i class="bi bi-person-badge text-club-primary me-2"></i>Active Player Roster</h3>
        
        <?php if (empty($players)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white mb-5">
                <i class="bi bi-person-x display-4 text-muted mb-3"></i>
                <h5 class="fw-bold">No Players Assigned to <?= htmlspecialchars($team['name']) ?></h5>
                <p class="text-muted mb-0">Player assignments will appear here once registered on the team roster.</p>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-5">
                <?php foreach ($players as $p): ?>
                    <div class="col-md-3 col-6">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-3 bg-white h-100 position-relative overflow-hidden">
                            <?php if (!empty($p['is_captain'])): ?>
                                <span class="position-absolute top-0 end-0 bg-warning text-dark fw-bold px-2 py-1 small rounded-start-2">CAPTAIN</span>
                            <?php endif; ?>
                            
                            <div class="mb-3 mx-auto" style="width:100px; height:100px;">
                                <?php if (!empty($p['photo_url'])): ?>
                                    <img src="<?= htmlspecialchars($p['photo_url']) ?>" alt="Photo" class="w-100 h-100 rounded-circle object-fit-cover shadow-sm">
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
                            <span class="small text-muted d-block mb-3"><?= htmlspecialchars($p['position'] ?: 'Player') ?></span>

                            <a href="/club/<?= $orgSlug ?>/players/<?= htmlspecialchars($p['id']) ?>" class="btn btn-outline-dark btn-sm rounded-pill mt-auto fw-semibold">
                                View Profile
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Team Staff -->
        <?php if (!empty($staff)): ?>
            <h3 class="fw-bold text-uppercase mb-4"><i class="bi bi-person-vcard text-club-primary me-2"></i>Coaches & Support Staff</h3>
            <div class="row g-4">
                <?php foreach ($staff as $st): ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-row align-items-center gap-3">
                            <?php if (!empty($st['photo_url'])): ?>
                                <img src="<?= htmlspecialchars($st['photo_url']) ?>" alt="Staff" class="rounded-circle object-fit-cover" style="width:64px; height:64px;">
                            <?php else: ?>
                                <div class="bg-club-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:64px; height:64px;">
                                    <i class="bi bi-person-vcard fs-3"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h5 class="fw-bold mb-1 text-uppercase"><?= htmlspecialchars($st['name']) ?></h5>
                                <span class="badge bg-primary-subtle text-primary px-2 py-1 rounded-pill small fw-bold"><?= htmlspecialchars($st['role']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
