<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary">
    <div class="container">
        <h2 class="fw-extrabold mb-1 fs-3">Upcoming Match Fixtures</h2>
        <p class="text-white-50 small mb-0">Upcoming schedule and kick-off details displayed in local timezone.</p>
    </div>
</div>

<div class="container py-4">
    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form method="GET" action="<?= url('/sports/fixtures') ?>" class="row g-3 align-items-center">
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
                <button type="submit" class="btn btn-primary fw-bold px-4 rounded-3 flex-grow-1">Filter Fixtures</button>
                <a href="<?= url('/sports/fixtures') ?>" class="btn btn-outline-secondary rounded-3">Reset</a>
            </div>
        </form>
    </div>

    <!-- Fixtures List -->
    <?php if (!empty($fixtures)): ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-slate-900">Upcoming Fixtures</span>
                <span class="badge bg-info text-dark"><?= count($fixtures) ?> Scheduled</span>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($fixtures as $fix): ?>
                    <li class="list-group-item p-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-info bg-opacity-10 text-info fw-bold px-2 py-1 rounded-2">
                                <?= $this->e($fix['competition']) ?>
                            </span>
                            <span class="small text-muted fw-semibold">
                                <i class="bi bi-clock me-1"></i><?= date('D, M j, Y - H:i', strtotime($fix['start_time'])) ?> (EAT)
                            </span>
                        </div>
                        <div class="row align-items-center py-2">
                            <div class="col-5 text-end fs-5 fw-extrabold text-slate-900">
                                <?= $this->e($fix['home_team']) ?>
                            </div>
                            <div class="col-2 text-center">
                                <span class="px-3 py-2 bg-light border text-muted rounded-pill fw-bold fs-6">
                                    VS
                                </span>
                            </div>
                            <div class="col-5 text-start fs-5 fw-extrabold text-slate-900">
                                <?= $this->e($fix['away_team']) ?>
                            </div>
                        </div>
                        <?php if (!empty($fix['venue'])): ?>
                            <div class="text-center small text-muted mt-2">
                                <i class="bi bi-geo-alt me-1"></i><?= $this->e($fix['venue']) ?>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <i class="bi bi-calendar-event fs-1 text-muted mb-3"></i>
            <h4 class="fw-bold text-slate-900 mb-2">No Fixtures Scheduled</h4>
            <p class="text-muted mb-4">No upcoming match fixtures match your current filter parameters.</p>
            <a href="<?= url('/sports/fixtures') ?>" class="btn btn-outline-primary fw-semibold rounded-3 mx-auto">Clear Filters</a>
        </div>
    <?php endif; ?>
</div>
