<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-warning text-dark text-uppercase px-3 py-2 rounded-pill mb-2">Schedule</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Upcoming Fixtures</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Scheduled competitive matches, kickoff dates, and venues.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (empty($fixtures)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Upcoming Fixtures Scheduled</h4>
                <p class="text-muted mb-0">Fixtures will appear here once scheduled by club officials.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($fixtures as $fix): ?>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill small fw-bold">
                                    <?= htmlspecialchars($fix['competition_name'] ?: ($fix['competition_type'] ?: 'Matchday')) ?>
                                </span>
                                <small class="text-muted fw-bold">
                                    <i class="bi bi-clock me-1"></i><?= date('M j, Y — g:i A', strtotime($fix['scheduled_at'])) ?>
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

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
