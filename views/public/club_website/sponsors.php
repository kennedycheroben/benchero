<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">Partners</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Official Sponsors & Partners</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">We are proudly supported by organizations driving athletic growth.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (empty($sponsors)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-handbag display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Partners Listed</h4>
                <p class="text-muted mb-0">Official club sponsors and commercial partners will be displayed here.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($sponsors as $s): ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white h-100">
                            <div class="p-3 bg-light rounded-3 mb-3 d-flex align-items-center justify-content-center" style="height:120px;">
                                <img src="<?= htmlspecialchars($s['logo_url']) ?>" alt="Logo" class="img-fluid object-fit-contain" style="max-height:90px;">
                            </div>
                            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill small fw-bold w-auto mx-auto mb-2"><?= htmlspecialchars($s['sponsor_level']) ?></span>
                            <h4 class="fw-bold text-uppercase mb-2"><?= htmlspecialchars($s['name']) ?></h4>
                            <?php if (!empty($s['website_url'])): ?>
                                <a href="<?= htmlspecialchars($s['website_url']) ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill mt-auto fw-bold">
                                    Visit Official Website <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
