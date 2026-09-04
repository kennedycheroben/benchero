<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">Club Identity & History</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">About <?= htmlspecialchars($org['name']) ?></h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Our mission, values, achievements, and story of athletic excellence.</p>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <h2 class="display-6 fw-bold text-uppercase mb-4">Our Vision & Philosophy</h2>
                <div class="lead text-secondary mb-4">
                    <?= !empty($org['description']) 
                        ? nl2br(htmlspecialchars($org['description'])) 
                        : htmlspecialchars($org['name']) . ' is an official sports organization dedicated to athletic excellence, community youth development, and competitive success.' 
                    ?>
                </div>

                <?php if (!empty($org['founded_year'])): ?>
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-4">
                        <div class="bg-club-primary text-white p-3 rounded-circle"><i class="bi bi-flag-fill fs-4"></i></div>
                        <div>
                            <strong class="d-block text-dark text-uppercase">Founded Year</strong>
                            <span class="text-muted fs-5 fw-bold"><?= htmlspecialchars($org['founded_year']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 text-center">
                <?php if (!empty($org['cover_url'])): ?>
                    <img src="<?= htmlspecialchars($org['cover_url']) ?>" alt="Cover" class="img-fluid rounded-4 shadow-lg">
                <?php else: ?>
                    <div class="p-5 bg-light rounded-4 text-center border">
                        <i class="bi bi-shield-shaded display-1 text-club-primary mb-3"></i>
                        <h3 class="fw-bold text-uppercase"><?= htmlspecialchars($org['name']) ?></h3>
                        <p class="text-muted mb-0">Official Sports Club Profile</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($history)): ?>
            <div class="mt-5 pt-5 border-top">
                <div class="text-center mb-5">
                    <span class="badge bg-warning text-dark text-uppercase px-3 py-2 rounded-pill mb-2">Milestones</span>
                    <h2 class="display-6 fw-bold text-uppercase mb-0">Club History & Honors Timeline</h2>
                </div>

                <div class="row g-4">
                    <?php foreach ($history as $h): ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-light h-100 border-start border-4 border-primary">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary text-white fw-bold px-3 py-1 rounded-pill"><?= htmlspecialchars($h['year_date']) ?></span>
                                    <small class="text-muted text-uppercase fw-bold"><?= htmlspecialchars($h['category']) ?></small>
                                </div>
                                <h4 class="fw-bold text-dark text-uppercase mb-2"><?= htmlspecialchars($h['title']) ?></h4>
                                <p class="text-secondary small mb-0"><?= nl2br(htmlspecialchars($h['description'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
