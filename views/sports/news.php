<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary">
    <div class="container">
        <h1 class="fw-extrabold mb-1 fs-3 text-white">Sports News & Headlines</h1>
        <p class="text-white-50 small mb-0">Latest sports updates, match summaries, and tournament reports.</p>
    </div>
</div>

<div class="container py-4">
    <!-- Category Tabs -->
    <div class="d-flex align-items-center gap-2 mb-4 overflow-x-auto pb-2">
        <a href="<?= url('/sports/news') ?>" class="btn btn-sm <?= empty($activeSport) ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 fw-bold">All Sports</a>
        <a href="<?= url('/sports/news?sport=football') ?>" class="btn btn-sm <?= $activeSport === 'football' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 fw-bold">Football</a>
        <a href="<?= url('/sports/news?sport=basketball') ?>" class="btn btn-sm <?= $activeSport === 'basketball' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 fw-bold">Basketball</a>
        <a href="<?= url('/sports/news?sport=rugby') ?>" class="btn btn-sm <?= $activeSport === 'rugby' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3 fw-bold">Rugby</a>
    </div>

    <?php if (!empty($newsArticles)): ?>
        <div class="row g-4">
            <?php foreach ($newsArticles as $article): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden benchero-interactive-card">
                        <?php if (!empty($article['image_url'])): ?>
                            <img src="<?= $this->e($article['image_url']) ?>" class="card-img-top" alt="<?= $this->e(html_entity_decode($article['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>" style="height: 200px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=800&auto=format&fit=crop&q=80'">
                        <?php else: ?>
                            <div class="bg-dark text-white p-4 text-center d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-newspaper fs-1 text-primary"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1 rounded-2">
                                    <?= $this->e($article['category']) ?>
                                </span>
                                <span class="small text-muted"><?= date('M j, Y', strtotime($article['published_at'])) ?></span>
                            </div>
                            <h5 class="card-title fw-bold text-slate-900 mb-2">
                                <a href="<?= url('/sports/news/' . $article['slug']) ?>" class="text-dark text-decoration-none">
                                    <?= $this->e(html_entity_decode($article['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted small flex-grow-1 mb-3">
                                <?= $this->e(html_entity_decode($article['summary'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>
                            </p>
                            <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-auto">
                                <span class="small text-muted fw-semibold"><?= $this->e($article['source']) ?></span>
                                <a href="<?= url('/sports/news/' . $article['slug']) ?>" class="btn btn-sm btn-outline-primary fw-semibold rounded-3">
                                    Read Article <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-newspaper fs-1 text-muted mb-3"></i>
            <h4 class="fw-bold text-slate-900 mb-2">No News Available</h4>
            <p class="text-muted mb-4">No sports news articles currently available for this selection.</p>
            <a href="<?= url('/sports/news') ?>" class="btn btn-outline-primary fw-semibold rounded-3 mx-auto">View All News</a>
        </div>
    <?php endif; ?>
</div>
