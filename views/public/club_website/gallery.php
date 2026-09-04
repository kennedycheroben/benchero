<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">Media</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Photo Gallery</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Matchday action snapshots, training sessions, and club events.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (empty($gallery)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-images display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Photos in Gallery</h4>
                <p class="text-muted mb-0">Matchday and team photos will appear here.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($gallery as $img): ?>
                    <div class="col-md-4 col-6">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white h-100 position-relative group">
                            <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="<?= htmlspecialchars($img['title'] ?: 'Gallery Photo') ?>" class="w-100 h-100 object-fit-cover" style="min-height:240px; max-height:280px;">
                            <?php if (!empty($img['title'])): ?>
                                <div class="position-absolute bottom-0 start-0 w-100 p-3 bg-dark bg-opacity-75 text-white">
                                    <div class="fw-bold small text-uppercase"><?= htmlspecialchars($img['title']) ?></div>
                                    <small class="text-white-50"><?= htmlspecialchars($img['category']) ?></small>
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
