<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($tenant['name']) ?></h5>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($sport['name']) ?></p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/dashboard" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/sports" class="list-group-item list-group-item-action">All Sports</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons" class="list-group-item list-group-item-action">Seasons</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams" class="list-group-item list-group-item-action active">Teams</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Teams</h2>
                <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/create" class="btn btn-primary">+ New Team</a>
                <?php endif; ?>
            </div>
            <hr>
            
            <?php if (empty($teams)): ?>
                <div class="alert alert-info">
                    No teams found for <?= htmlspecialchars($sport['name']) ?> yet.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($teams as $team): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">
                                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($team['id']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($team['name']) ?>
                                    </a>
                                    <?php if (!$team['is_active']): ?>
                                        <span class="badge bg-secondary ms-2">Inactive</span>
                                    <?php endif; ?>
                                </h5>
                                <small class="text-muted">
                                    <?= htmlspecialchars($team['team_type'] ?? 'No type set') ?>
                                </small>
                            </div>
                            
                            <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                                <div>
                                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($team['id']) ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    
                                    <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($team['id']) ?>/delete" class="d-inline" onsubmit="return confirm('Archive this team? Historical records will remain intact.');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Archive</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
