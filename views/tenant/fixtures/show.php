<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/dashboard"><?= htmlspecialchars($tenant['name']) ?></a></li>
            <li class="breadcrumb-item"><a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures"><?= htmlspecialchars($sport['name']) ?> Fixtures</a></li>
            <li class="breadcrumb-item active" aria-current="page">Match Details</li>
        </ol>
    </nav>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="text-center mb-4">
                <div class="text-muted small mb-2 text-uppercase fw-bold">
                    <?= htmlspecialchars($fixture['season_name']) ?> | 
                    <?= htmlspecialchars(ucfirst($fixture['competition_type'])) ?>
                    <?= $fixture['competition_name'] ? ' - ' . htmlspecialchars($fixture['competition_name']) : '' ?>
                </div>
                <div class="d-flex justify-content-center align-items-center gap-4">
                    <div class="text-end" style="flex: 1;">
                        <h2 class="mb-0">
                            <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($fixture['home_team_id']) ?>" class="text-dark text-decoration-none">
                                <?= htmlspecialchars($fixture['home_team_name']) ?>
                            </a>
                        </h2>
                        <span class="text-muted small">Home</span>
                    </div>
                    <div class="fw-bold fs-3 text-muted">VS</div>
                    <div class="text-start" style="flex: 1;">
                        <h2 class="mb-0">
                            <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($fixture['away_team_id']) ?>" class="text-dark text-decoration-none">
                                <?= htmlspecialchars($fixture['away_team_name']) ?>
                            </a>
                        </h2>
                        <span class="text-muted small">Away</span>
                    </div>
                </div>
                
                <div class="mt-4">
                    <?php
                        $badgeClass = 'bg-secondary';
                        if ($fixture['status'] === 'scheduled') $badgeClass = 'bg-primary';
                        if ($fixture['status'] === 'completed') $badgeClass = 'bg-success';
                        if ($fixture['status'] === 'postponed') $badgeClass = 'bg-warning text-dark';
                        if ($fixture['status'] === 'cancelled') $badgeClass = 'bg-danger';
                    ?>
                    <span class="badge <?= $badgeClass ?> fs-6"><?= htmlspecialchars(ucfirst($fixture['status'])) ?></span>
                </div>
            </div>
            
            <hr>
            
            <div class="row text-center mt-4">
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase mb-1">Date & Time</h6>
                    <p class="mb-0 fw-bold"><?= date('l, F j, Y', strtotime($fixture['scheduled_at_local'])) ?></p>
                    <p class="text-muted small"><?= date('H:i', strtotime($fixture['scheduled_at_local'])) ?> (Local Time)</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase mb-1">Venue</h6>
                    <p class="mb-0 fw-bold"><?= htmlspecialchars($fixture['venue_name'] ?: 'TBD') ?></p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted text-uppercase mb-1">Actions</h6>
                    <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                        <?php if (in_array($fixture['status'], ['scheduled', 'postponed'])): ?>
                            <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($fixture['id']) ?>/edit" class="btn btn-sm btn-outline-primary w-100 mb-2">Edit Details</a>
                            
                            <!-- Status Update Dropdown -->
                            <div class="dropdown w-100">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                    Change Status
                                </button>
                                <ul class="dropdown-menu w-100">
                                    <li>
                                        <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($fixture['id']) ?>/status">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="postponed">
                                            <button type="submit" class="dropdown-item">Postpone</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($fixture['id']) ?>/status">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" class="dropdown-item text-danger">Cancel</button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($fixture['id']) ?>/status">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="dropdown-item text-success" onclick="return confirm('Mark as completed? This locks the fixture identity.')">Mark Completed</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary w-100 disabled" disabled>Fixture Locked</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($fixture['notes'])): ?>
                <hr>
                <h6 class="text-muted text-uppercase mb-2">Notes</h6>
                <p class="mb-0 text-muted small"><?= nl2br(htmlspecialchars($fixture['notes'])) ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="#">Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Result <span class="badge bg-light text-dark ms-1">Coming Soon</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Lineups <span class="badge bg-light text-dark ms-1">Coming Soon</span></a>
        </li>
    </ul>
    
    <div class="card shadow-sm border-0 bg-light">
        <div class="card-body text-center p-5 text-muted">
            <i class="bi bi-clock-history fs-1 mb-3 d-block"></i>
            <h5>Result & Events Not Yet Available</h5>
            <p class="mb-0">This module will be activated in a future update.</p>
        </div>
    </div>
</div>
