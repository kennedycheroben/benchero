<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-danger text-white text-uppercase px-3 py-2 rounded-pill mb-2">Media & Press</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Club News & Updates</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Latest club announcements, match previews, and official reports.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (empty($news)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-newspaper display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Articles Published Yet</h4>
                <p class="text-muted mb-0">Check back regularly for match previews, squad updates, and club announcements.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($news as $article): ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white">
                            <?php if (!empty($article['image_url'])): ?>
                                <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="News" class="card-img-top" style="height:220px; object-fit:cover;">
                            <?php endif; ?>
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill small fw-bold"><?= htmlspecialchars($article['category']) ?></span>
                                    <small class="text-muted"><?= date('M j, Y', strtotime($article['published_at'])) ?></small>
                                </div>
                                <h4 class="fw-bold text-dark text-uppercase mb-2"><?= htmlspecialchars($article['title']) ?></h4>
                                <p class="small text-muted mb-4"><?= htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 140) . '...') ?></p>
                                <a href="/club/<?= $orgSlug ?>/news/<?= htmlspecialchars($article['slug']) ?>" class="btn btn-club-primary btn-sm rounded-pill mt-auto fw-bold">
                                    Read Article <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
