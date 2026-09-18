<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<div class="container py-5">
    <div class="row g-5">
        <!-- Article Content -->
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= url('/sports') ?>">Sports</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('/sports/news') ?>">News</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= $this->e($article['category']) ?></li>
                </ol>
            </nav>

            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 rounded-2 mb-3">
                <?= $this->e($article['category']) ?>
            </span>

            <h1 class="display-5 fw-extrabold text-slate-900 mb-3">
                <?= $this->e($article['title']) ?>
            </h1>

            <div class="d-flex align-items-center gap-3 text-muted small border-bottom pb-3 mb-4">
                <span><i class="bi bi-building me-1"></i> Source: <strong><?= $this->e($article['source']) ?></strong></span>
                <span><i class="bi bi-clock me-1"></i> <?= date('F j, Y - H:i', strtotime($article['published_at'])) ?></span>
            </div>

            <?php if (!empty($article['image_url'])): ?>
                <div class="rounded-4 overflow-hidden mb-4 shadow-sm">
                    <img src="<?= $this->e($article['image_url']) ?>" alt="<?= $this->e($article['title']) ?>" class="img-fluid w-100" style="max-height: 420px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=800&auto=format&fit=crop&q=80'">
                </div>
            <?php endif; ?>

            <div class="lead fw-medium text-slate-900 mb-4 p-4 bg-light rounded-4 border-start border-4 border-primary">
                <?= $this->e($article['summary']) ?>
            </div>

            <div class="fs-5 text-slate-700 lh-lg mb-5">
                <p><?= nl2br($this->e($article['content'] ?? $article['summary'])) ?></p>
            </div>

            <?php if (!empty($article['source_url'])): ?>
                <div class="p-4 bg-light rounded-4 border mb-5 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                    <div>
                        <h6 class="fw-bold text-slate-900 mb-1">Original Source Attribution</h6>
                        <p class="small text-muted mb-0">This summary was aggregated from <?= $this->e($article['source']) ?>.</p>
                    </div>
                    <a href="<?= $this->e($article['source_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm fw-bold rounded-3">
                        Visit Source Site <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Related Articles -->
            <?php if (!empty($relatedNews)): ?>
                <h4 class="fw-bold text-slate-900 mb-3 border-top pt-4">Related Stories</h4>
                <div class="row g-3">
                    <?php foreach ($relatedNews as $rel): ?>
                        <div class="col-md-6">
                            <div class="p-3 bg-white border rounded-3 h-100">
                                <span class="badge bg-secondary bg-opacity-10 text-dark small fw-bold mb-1"><?= $this->e($rel['category']) ?></span>
                                <h6 class="fw-bold mb-1">
                                    <a href="<?= url('/sports/news/' . $rel['slug']) ?>" class="text-dark text-decoration-none">
                                        <?= $this->e($rel['title']) ?>
                                    </a>
                                </h6>
                                <p class="small text-muted mb-0"><?= $this->e($rel['source']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar CTA -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 text-white bg-dark p-4 sticky-top" style="top: 90px;">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="32" class="rounded-2">
                    <span class="fw-extrabold fs-4 tracking-tight">BENCHERO</span>
                </div>
                <h4 class="fw-bold mb-2">Build Your Sports Club</h4>
                <p class="text-white-50 small mb-4">
                    Empower your team or academy with Benchero's modern sports management platform. Schedule matches, manage players, log scores, and publish your official club site.
                </p>
                <a href="<?= url('/register') ?>" class="btn btn-primary fw-bold py-2 rounded-3 w-100 mb-2">
                    Start Free Trial
                </a>
                <a href="<?= url('/about') ?>" class="btn btn-outline-light btn-sm fw-semibold rounded-3 w-100">
                    Learn More
                </a>
            </div>
        </div>
    </div>
</div>
