<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($tenant['name']) ?></h5>
                    <p class="text-muted small mb-0">Manage Sports</p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/dashboard" class="list-group-item list-group-item-action">Back to Dashboard</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <h2>Sports Catalogue</h2>
            <p class="text-muted">Activate the sports your organization competes in.</p>
            <hr>
            
            <div class="row mt-4">
                <?php foreach ($sports as $sport): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm <?= $sport['org_active'] ? 'border-primary' : '' ?>">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($sport['name']) ?></h5>
                                    <?php if ($sport['org_active']): ?>
                                        <span class="badge bg-primary">Active</span>
                                        <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams" class="btn btn-sm btn-link text-decoration-none">Manage Teams</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/sports/toggle">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="sport_id" value="<?= htmlspecialchars($sport['id']) ?>">
                                    
                                    <?php if ($sport['org_active']): ?>
                                        <input type="hidden" name="action" value="deactivate">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Deactivate</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="activate">
                                        <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
