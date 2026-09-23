<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary">
    <div class="container">
        <h1 class="fw-extrabold mb-1 fs-3 text-white">Benchero Club Directory</h1>
        <p class="text-white-50 small mb-0">Discover sports clubs, academies, and community teams powered by Benchero.</p>
    </div>
</div>

<div class="container py-4">
    <?php if (!empty($clubs)): ?>
        <div class="row g-4">
            <?php foreach ($clubs as $club): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 benchero-interactive-card d-flex flex-column">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <?php if (!empty($club['logo_url'])): ?>
                                <img src="<?= $this->e($club['logo_url']) ?>" alt="<?= $this->e($club['name']) ?>" height="48" width="48" class="rounded-3 object-fit-cover">
                            <?php else: ?>
                                <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 48px; height: 48px;">
                                    <?= strtoupper(substr($club['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h5 class="fw-bold text-slate-900 mb-0"><?= $this->e($club['name']) ?></h5>
                                <span class="small text-muted">
                                    <i class="bi bi-geo-alt me-1"></i><?= $this->e($club['country'] ?: 'Kenya') ?>
                                </span>
                            </div>
                        </div>
                        <p class="small text-muted flex-grow-1 mb-3">
                            Official digital home for rosters, upcoming match fixtures, results, and club announcements.
                        </p>
                        <a href="<?= url('/club/' . $club['slug']) ?>" class="btn btn-outline-primary fw-bold rounded-3 w-100 mt-auto">
                            Visit Public Club Page <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-buildings fs-1 text-muted mb-3"></i>
            <h4 class="fw-bold text-slate-900 mb-2">No Registered Clubs Found</h4>
            <p class="text-muted mb-4">Be the first to build your club's digital home on Benchero!</p>
            <a href="<?= url('/register') ?>" class="btn btn-primary fw-bold rounded-3 mx-auto">Create Your Club</a>
        </div>
    <?php endif; ?>
</div>
