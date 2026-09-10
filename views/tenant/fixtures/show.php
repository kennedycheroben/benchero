<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/o/' . urlencode($tenant['slug']) . '/dashboard') ?>"><?= htmlspecialchars($tenant['name']) ?></a></li>
            <li class="breadcrumb-item"><a href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/fixtures') ?>"><?= htmlspecialchars($sport['name']) ?> Fixtures</a></li>
            <li class="breadcrumb-item active" aria-current="page">Match Details</li>
        </ol>
    </nav>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-4">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="text-muted small mb-2 text-uppercase fw-bold">
                    <?= htmlspecialchars($fixture['season_name']) ?> | 
                    <?= htmlspecialchars(ucfirst($fixture['competition_type'])) ?>
                    <?= $fixture['competition_name'] ? ' - ' . htmlspecialchars($fixture['competition_name']) : '' ?>
                </div>
                
                <div class="d-flex justify-content-center align-items-center gap-3 gap-md-5 my-4">
                    <div class="text-end" style="flex: 1;">
                        <h2 class="mb-1 fw-extrabold text-dark">
                            <?= htmlspecialchars($fixture['home_team_name']) ?>
                        </h2>
                        <span class="badge bg-light text-dark border">Home</span>
                    </div>

                    <?php if ($fixture['status'] === 'completed' && $fixture['home_score'] !== null): ?>
                        <div class="bg-dark text-white px-4 py-2 rounded-4 fw-extrabold fs-2 shadow-sm">
                            <?= (int)$fixture['home_score'] ?> - <?= (int)$fixture['away_score'] ?>
                        </div>
                    <?php else: ?>
                        <div class="fw-bold fs-3 text-muted">VS</div>
                    <?php endif; ?>

                    <div class="text-start" style="flex: 1;">
                        <h2 class="mb-1 fw-extrabold text-dark">
                            <?= htmlspecialchars($fixture['away_team_name']) ?>
                        </h2>
                        <span class="badge bg-light text-dark border">Away</span>
                    </div>
                </div>
                
                <div class="mt-3">
                    <?php
                        $badgeClass = 'bg-secondary';
                        if ($fixture['status'] === 'scheduled') $badgeClass = 'bg-primary';
                        if ($fixture['status'] === 'completed') $badgeClass = 'bg-success';
                        if ($fixture['status'] === 'postponed') $badgeClass = 'bg-warning text-dark';
                        if ($fixture['status'] === 'cancelled') $badgeClass = 'bg-danger';
                    ?>
                    <span class="badge <?= $badgeClass ?> px-3 py-2 fs-6 rounded-pill"><?= htmlspecialchars(ucfirst($fixture['status'])) ?></span>
                </div>
            </div>
            
            <hr class="my-4 opacity-25">
            
            <div class="row text-center g-3">
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase small fw-bold mb-1">Date & Time</h6>
                    <p class="mb-0 fw-bold"><?= date('l, F j, Y', strtotime($fixture['scheduled_at_local'])) ?></p>
                    <p class="text-muted small mb-0"><?= date('H:i', strtotime($fixture['scheduled_at_local'])) ?> (Local Time)</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase small fw-bold mb-1">Venue</h6>
                    <p class="mb-0 fw-bold"><?= htmlspecialchars($fixture['venue_name'] ?: 'TBD') ?></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase small fw-bold mb-1">Actions</h6>
                    <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                        <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/fixtures/' . urlencode($fixture['id']) . '/edit') ?>" class="btn btn-sm btn-outline-primary w-100 mb-2">Edit Details</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($fixture['notes'])): ?>
                <hr class="my-4 opacity-25">
                <h6 class="text-muted text-uppercase small fw-bold mb-2">Notes</h6>
                <p class="mb-0 text-muted small"><?= nl2br(htmlspecialchars($fixture['notes'])) ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Match Score / Result Card -->
    <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
        <div class="card shadow-sm border-0 rounded-4 bg-white p-4">
            <h4 class="fw-bold mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>Record Match Result</h4>
            <form action="<?= url('/o/' . urlencode($tenant['slug']) . '/s/' . urlencode($sport['slug']) . '/fixtures/' . urlencode($fixture['id']) . '/result') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="row g-3 align-items-center mb-3">
                    <div class="col-md-5 text-md-end">
                        <label for="home_score" class="form-label fw-bold text-dark mb-1"><?= htmlspecialchars($fixture['home_team_name']) ?> Score</label>
                        <input type="number" id="home_score" name="home_score" class="form-control form-control-lg text-center" min="0" value="<?= htmlspecialchars($fixture['home_score'] ?? 0) ?>" required>
                    </div>
                    <div class="col-md-2 text-center fw-bold fs-4 text-muted">
                        -
                    </div>
                    <div class="col-md-5">
                        <label for="away_score" class="form-label fw-bold text-dark mb-1"><?= htmlspecialchars($fixture['away_team_name']) ?> Score</label>
                        <input type="number" id="away_score" name="away_score" class="form-control form-control-lg text-center" min="0" value="<?= htmlspecialchars($fixture['away_score'] ?? 0) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="result_notes" class="form-label fw-semibold text-muted small">Result Notes / Scorers (Optional)</label>
                    <textarea id="result_notes" name="result_notes" class="form-control" rows="2" placeholder="e.g. John Doe 12', Jane Smith 45'"><?= htmlspecialchars($fixture['result_notes'] ?? '') ?></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        Save Match Result
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
