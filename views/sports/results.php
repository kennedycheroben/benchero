<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary">
    <div class="container">
        <h1 class="fw-extrabold mb-1 fs-3 text-white">Sports Match Results</h1>
        <p class="text-white-50 small mb-0">Browse archives of recent finished match scores and results.</p>
    </div>
</div>

<div class="container py-4">
    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form method="GET" action="<?= url('/sports/results') ?>" class="row g-3 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Filter by Sport</label>
                <select name="sport" class="form-select rounded-3">
                    <option value="">All Sports</option>
                    <option value="football" <?= $activeSport === 'football' ? 'selected' : '' ?>>Football</option>
                    <option value="basketball" <?= $activeSport === 'basketball' ? 'selected' : '' ?>>Basketball</option>
                    <option value="volleyball" <?= $activeSport === 'volleyball' ? 'selected' : '' ?>>Volleyball</option>
                    <option value="rugby" <?= $activeSport === 'rugby' ? 'selected' : '' ?>>Rugby</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1">Filter by Date</label>
                <input type="date" name="date" class="form-control rounded-3" value="<?= $this->e($activeDate ?? '') ?>">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2 mt-md-4">
                <button type="submit" class="btn btn-primary fw-bold px-4 rounded-3 flex-grow-1">Filter Results</button>
                <a href="<?= url('/sports/results') ?>" class="btn btn-outline-secondary rounded-3">Reset</a>
            </div>
        </form>
    </div>

    <!-- Results List -->
    <?php if (!empty($results)): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-slate-900">Finished Matches</span>
                <span class="badge bg-secondary"><?= count($results) ?> Matches</span>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($results as $res): ?>
                    <li class="list-group-item p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-2 py-1 rounded-2">
                                <?= $this->e($res['competition']) ?>
                            </span>
                            <span class="small text-muted fw-semibold">
                                <i class="bi bi-clock me-1"></i><?= date('M j, Y - H:i', strtotime($res['start_time'])) ?>
                            </span>
                        </div>
                        <!-- Desktop Row -->
                        <div class="row align-items-center py-2 match-row-desktop">
                            <div class="col-5 text-end fs-5 fw-extrabold text-slate-900">
                                <?= $this->e($res['home_team']) ?>
                            </div>
                            <div class="col-2 text-center">
                                <span class="px-3 py-2 bg-dark text-white rounded-pill fw-extrabold fs-5 text-nowrap">
                                    <?= $this->e($res['home_score']) ?> - <?= $this->e($res['away_score']) ?>
                                </span>
                                <div class="small text-muted mt-1 fw-bold">Full Time</div>
                            </div>
                            <div class="col-5 text-start fs-5 fw-extrabold text-slate-900">
                                <?= $this->e($res['away_team']) ?>
                            </div>
                        </div>
                        <!-- Mobile Stack -->
                        <div class="match-row-mobile py-2">
                            <div class="match-mobile-team">
                                <div class="match-mobile-team-info">
                                    <span class="badge bg-light text-muted border me-1">H</span>
                                    <span class="match-mobile-team-name text-slate-900"><?= $this->e($res['home_team']) ?></span>
                                </div>
                                <div class="match-mobile-score text-slate-900"><?= $this->e($res['home_score']) ?></div>
                            </div>
                            <div class="match-mobile-team">
                                <div class="match-mobile-team-info">
                                    <span class="badge bg-light text-muted border me-1">A</span>
                                    <span class="match-mobile-team-name text-slate-900"><?= $this->e($res['away_team']) ?></span>
                                </div>
                                <div class="match-mobile-score text-slate-900"><?= $this->e($res['away_score']) ?></div>
                            </div>
                            <div class="text-center mt-1">
                                <span class="badge bg-secondary" style="font-size: 0.75rem;">Full Time</span>
                            </div>
                        </div>
                        <?php if (!empty($res['venue'])): ?>
                            <div class="text-center small text-muted mt-2">
                                <i class="bi bi-geo-alt me-1"></i><?= $this->e($res['venue']) ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-search fs-1 text-muted mb-3"></i>
            <h4 class="fw-bold text-slate-900 mb-2">No Results Found</h4>
            <p class="text-muted mb-4">No finished match results match your current filter criteria.</p>
            <a href="<?= url('/sports/results') ?>" class="btn btn-outline-primary fw-semibold rounded-3 mx-auto">Clear Filters</a>
        </div>
    <?php endif; ?>
</div>
