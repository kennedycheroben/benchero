<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-warning text-dark text-uppercase px-3 py-2 rounded-pill mb-2">Heritage</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Club History & Timeline</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Milestones, league titles, championships, and key historical dates.</p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-4 max-w-4xl">
        <?php if (empty($history)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-light">
                <i class="bi bi-clock-history display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Milestones Published</h4>
                <p class="text-muted mb-0">Timeline milestones will appear here as recorded by club officials.</p>
            </div>
        <?php else: ?>
            <div class="timeline position-relative">
                <div class="d-grid gap-4">
                    <?php foreach ($history as $h): ?>
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light border-start border-5 border-primary">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-club-primary text-white fw-bold fs-6 px-3 py-1 rounded-pill"><?= htmlspecialchars($h['year_date']) ?></span>
                                <span class="badge bg-secondary-subtle text-secondary small text-uppercase"><?= htmlspecialchars($h['category']) ?></span>
                            </div>
                            <h3 class="fw-bold text-dark text-uppercase mb-2"><?= htmlspecialchars($h['title']) ?></h3>
                            <p class="text-secondary mb-0 lead fs-6"><?= nl2br(htmlspecialchars($h['description'])) ?></p>
                            <?php if (!empty($h['image_url'])): ?>
                                <img src="<?= htmlspecialchars($h['image_url']) ?>" alt="Milestone Image" class="img-fluid rounded-3 mt-3 shadow-sm" style="max-height:300px; object-fit:cover;">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
