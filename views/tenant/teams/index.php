<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/sports') ?>"><?= htmlspecialchars($sport['name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Teams</li>
                </ol>
            </nav>
            <h2 class="h3 fw-bold mb-0">Teams & Squads (<?= htmlspecialchars($sport['name']) ?>)</h2>
        </div>
        <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
            <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/teams/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-plus-circle-fill"></i>
                <span>+ New Team</span>
            </a>
        <?php endif; ?>
    </div>
    <hr class="my-3">
    
    <?php if (empty($teams)): ?>
        <div class="alert alert-info shadow-sm rounded-3">
            <i class="bi bi-info-circle me-2"></i>No teams found for <?= htmlspecialchars($sport['name']) ?> yet. Create your first squad above.
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="list-group list-group-flush">
                <?php foreach ($teams as $team): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h5 class="mb-1">
                                <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/teams/' . htmlspecialchars($team['id'])) ?>" class="text-decoration-none fw-bold">
                                    <?= htmlspecialchars($team['name']) ?>
                                </a>
                                <?php if (!$team['is_active']): ?>
                                    <span class="badge bg-secondary ms-2">Inactive</span>
                                <?php endif; ?>
                            </h5>
                            <small class="text-muted">
                                <?= htmlspecialchars($team['team_type'] ?? 'General Squad') ?>
                            </small>
                        </div>
                        
                        <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                            <div class="d-flex align-items-center gap-2">
                                <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/teams/' . htmlspecialchars($team['id']) . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                
                                <form method="POST" action="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/teams/' . htmlspecialchars($team['id']) . '/delete') ?>" class="d-inline" onsubmit="return confirm('Archive this team? Historical records will remain intact.');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Archive</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
