<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container py-4">
        <div class="max-w-3xl mx-auto">
            <span class="badge bg-primary px-3 py-1 rounded-pill text-uppercase fw-bold mb-3"><?= htmlspecialchars($article['category']) ?></span>
            <h1 class="display-4 fw-black text-uppercase text-white mb-3"><?= htmlspecialchars($article['title']) ?></h1>
            <div class="small text-white-50">
                <i class="bi bi-calendar3 me-1"></i> Published on <?= date('F j, Y', strtotime($article['published_at'])) ?>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row g-5">
            <div class="col-lg-8">
                <?php if (!empty($article['image_url'])): ?>
                    <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="Cover" class="img-fluid rounded-4 shadow mb-4 w-100" style="max-height:450px; object-fit:cover;">
                <?php endif; ?>

                <div class="article-content lead text-dark lh-lg mb-5">
                    <?= nl2br(htmlspecialchars($article['content'])) ?>
                </div>

                <a href="/club/<?= $orgSlug ?>/news" class="btn btn-outline-dark rounded-pill fw-bold">
                    <i class="bi bi-arrow-left me-1"></i> Back to News Archive
                </a>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
                    <h5 class="fw-bold text-uppercase mb-3">Related Stories</h5>
                    <?php if (empty($relatedNews)): ?>
                        <p class="text-muted small mb-0">No other articles published yet.</p>
                    <?php else: ?>
                        <div class="d-grid gap-3">
                            <?php foreach ($relatedNews as $rel): ?>
                                <div class="border-bottom pb-2">
                                    <small class="text-muted d-block"><?= date('M j, Y', strtotime($rel['published_at'])) ?></small>
                                    <a href="/club/<?= $orgSlug ?>/news/<?= htmlspecialchars($rel['slug']) ?>" class="fw-bold text-dark text-decoration-none hover-primary">
                                        <?= htmlspecialchars($rel['title']) ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
