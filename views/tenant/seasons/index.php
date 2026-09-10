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
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons" class="list-group-item list-group-item-action active">Seasons</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams" class="list-group-item list-group-item-action">Teams</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Seasons</h2>
                <?php if (in_array($role, ['owner', 'admin'])): ?>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons/create" class="btn btn-primary">+ New Season</a>
                <?php endif; ?>
            </div>
            <hr>
            
            <?php if (empty($seasons)): ?>
                <div class="alert alert-info">
                    No seasons found for <?= htmlspecialchars($sport['name']) ?> yet.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($seasons as $season): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">
                                    <?= htmlspecialchars($season['name']) ?>
                                    <?php if ($season['is_current']): ?>
                                        <span class="badge bg-success ms-2">Current</span>
                                    <?php endif; ?>
                                    <?php
                                        $now = date('Y-m-d');
                                        if ($season['ends_on'] < $now) {
                                            echo '<span class="badge bg-secondary ms-1">Completed</span>';
                                        } elseif ($season['starts_on'] > $now) {
                                            echo '<span class="badge bg-info ms-1">Upcoming</span>';
                                        } else {
                                            echo '<span class="badge bg-primary ms-1">Active</span>';
                                        }
                                    ?>
                                </h5>
                                <small class="text-muted">
                                    <?= htmlspecialchars($season['starts_on']) ?> to <?= htmlspecialchars($season['ends_on']) ?>
                                </small>
                            </div>
                            
                            <?php if (in_array($role, ['owner', 'admin'])): ?>
                                <div>
                                    <?php if (!$season['is_current']): ?>
                                        <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons/<?= htmlspecialchars($season['id']) ?>/current" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-success">Set Current</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons/<?= htmlspecialchars($season['id']) ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    
                                    <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons/<?= htmlspecialchars($season['id']) ?>/delete" class="d-inline" onsubmit="return confirm('Archive this season?');">
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
