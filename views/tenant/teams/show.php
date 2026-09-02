<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container mt-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/dashboard"><?= htmlspecialchars($tenant['name']) ?></a></li>
            <li class="breadcrumb-item"><a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams"><?= htmlspecialchars($sport['name']) ?> Teams</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($team['name']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">
                <?= htmlspecialchars($team['name']) ?>
                <?php if (!$team['is_active']): ?>
                    <span class="badge bg-secondary ms-2 align-middle fs-6">Inactive</span>
                <?php endif; ?>
            </h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($team['team_type'] ?? 'No type set') ?></p>
        </div>
        <?php if (in_array($request->getAttribute('tenant_role'), ['owner', 'admin', 'manager'])): ?>
            <div>
                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($team['id']) ?>/edit" class="btn btn-outline-primary">Edit Team Settings</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sub-navigation -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="#">Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Players <span class="badge bg-light text-dark ms-1">Coming Soon</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Staff <span class="badge bg-light text-dark ms-1">Coming Soon</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Fixtures <span class="badge bg-light text-dark ms-1">Coming Soon</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Results <span class="badge bg-light text-dark ms-1">Coming Soon</span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Statistics <span class="badge bg-light text-dark ms-1">Coming Soon</span></a>
        </li>
    </ul>

    <!-- Overview Content -->
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Team Details</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($team['description'])): ?>
                        <p><?= nl2br(htmlspecialchars($team['description'])) ?></p>
                    <?php else: ?>
                        <p class="text-muted fst-italic">No description provided.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Information</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Sport
                        <span class="fw-bold"><?= htmlspecialchars($sport['name']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Type
                        <span class="fw-bold"><?= htmlspecialchars($team['team_type'] ?? 'N/A') ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Created
                        <span class="fw-bold"><?= date('M j, Y', strtotime($team['created_at'])) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
