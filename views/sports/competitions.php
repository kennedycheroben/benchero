<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary">
    <div class="container">
        <h2 class="fw-extrabold mb-1 fs-3">Sports Leagues & Competitions</h2>
        <p class="text-white-50 small mb-0">Discover top international tournaments and grassroots local leagues.</p>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <?php foreach ($competitions as $comp): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 benchero-interactive-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-pill">
                            <?= ucfirst($this->e($comp['sport'] ?? 'Football')) ?>
                        </span>
                        <span class="small text-muted fw-bold"><?= $this->e($comp['country']) ?></span>
                    </div>
                    <h4 class="fw-bold text-slate-900 mb-2"><?= $this->e($comp['name']) ?></h4>
                    <p class="small text-muted mb-4">View official standings table, match results, upcoming fixtures, and league news.</p>
                    <a href="<?= url('/sports/c/' . $comp['slug']) ?>" class="btn btn-outline-primary fw-semibold rounded-3 w-100 mt-auto">
                        View Competition Hub <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
