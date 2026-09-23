<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/sports') ?>"><?= htmlspecialchars($sport['name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Seasons</li>
                </ol>
            </nav>
            <h2 class="h3 fw-bold mb-0">Seasons (<?= htmlspecialchars($sport['name']) ?>)</h2>
        </div>
        <?php if (in_array($role, ['owner', 'admin'])): ?>
            <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/seasons/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-calendar-plus-fill"></i>
                <span>+ New Season</span>
            </a>
        <?php endif; ?>
    </div>
    <hr class="my-3">
    
    <?php if (empty($seasons)): ?>
        <div class="alert alert-info shadow-sm rounded-3">
            <i class="bi bi-info-circle me-2"></i>No seasons found for <?= htmlspecialchars($sport['name']) ?> yet. Create your first season above.
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="list-group list-group-flush">
                <?php foreach ($seasons as $season): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center p-3 flex-wrap gap-2">
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
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!$season['is_current']): ?>
                                    <form method="POST" action="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/seasons/' . htmlspecialchars($season['id']) . '/current') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success">Set Current</button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/seasons/' . htmlspecialchars($season['id']) . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                
                                <form method="POST" action="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/seasons/' . htmlspecialchars($season['id']) . '/delete') ?>" class="d-inline" onsubmit="return confirm('Archive this season?');">
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
