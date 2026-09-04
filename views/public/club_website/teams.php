<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">Club Divisions</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Our Sports Teams</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Explore senior squads, reserves, academy youth, and sport division teams.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (empty($teams)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-people display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Active Teams Found</h4>
                <p class="text-muted mb-0">Teams will appear here as soon as the club owner registers divisions.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($teams as $t): ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white h-100">
                            <div class="bg-club-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm" style="width:70px; height:70px;">
                                <i class="bi bi-shield-fill fs-2"></i>
                            </div>
                            <h3 class="fw-bold text-uppercase mb-2"><?= htmlspecialchars($t['name']) ?></h3>
                            <span class="badge bg-light text-dark border mb-3 w-auto mx-auto"><?= htmlspecialchars($t['team_type'] ?: 'Senior Team') ?></span>
                            <p class="text-muted small mb-4"><?= htmlspecialchars($t['description'] ?: 'Official division squad representing ' . $org['name'] . '.') ?></p>
                            <a href="/club/<?= $orgSlug ?>/teams/<?= htmlspecialchars($t['slug']) ?>" class="btn btn-club-primary rounded-pill fw-bold mt-auto">
                                View Team Roster & Details <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
