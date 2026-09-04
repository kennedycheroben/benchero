<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-success text-white text-uppercase px-3 py-2 rounded-pill mb-2">Scores</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Completed Match Results</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Official final match scores and competitive results archive.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (empty($results)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-trophy display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Match Results Recorded</h4>
                <p class="text-muted mb-0">Completed match scores will be archived here.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($results as $res): ?>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-success">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted fw-bold"><?= date('F j, Y', strtotime($res['scheduled_at'])) ?></small>
                                <span class="badge bg-success px-3 py-1 small text-uppercase">FINAL</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between my-3 text-center">
                                <div class="fw-bold fs-5 text-uppercase text-dark flex-1"><?= htmlspecialchars($res['home_team_name']) ?></div>
                                <div class="display-6 fw-black text-dark px-4 bg-light rounded-3 py-1">
                                    <?= (int)$res['home_score'] ?> - <?= (int)$res['away_score'] ?>
                                </div>
                                <div class="fw-bold fs-5 text-uppercase text-dark flex-1"><?= htmlspecialchars($res['away_team_name']) ?></div>
                            </div>
                            <?php if (!empty($res['result_notes'])): ?>
                                <div class="mt-2 pt-2 border-top small text-muted">
                                    <i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($res['result_notes']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
