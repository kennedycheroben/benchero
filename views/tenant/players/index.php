<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/sports') ?>"><?= htmlspecialchars($sport['name']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Players</li>
                </ol>
            </nav>
            <h2 class="h3 fw-bold mb-0">Players Roster (<?= htmlspecialchars($sport['name']) ?>)</h2>
        </div>
        <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
            <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/players/create') ?>" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-person-plus-fill"></i>
                <span>+ New Player</span>
            </a>
        <?php endif; ?>
    </div>
    <hr class="my-3">
    
    <?php if (empty($players)): ?>
        <div class="alert alert-info shadow-sm rounded-3">
            <i class="bi bi-info-circle me-2"></i>No players found for <?= htmlspecialchars($sport['name']) ?> yet. Add your first player above.
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Player Name</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $player): ?>
                            <tr>
                                <td>
                                    <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/players/' . htmlspecialchars($player['id'])) ?>" class="text-decoration-none fw-bold">
                                        <?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) ?>
                                    </a>
                                    <?php if (!empty($player['display_name'])): ?>
                                        <div class="text-muted small">aka <?= htmlspecialchars($player['display_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($player['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                                        <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/players/' . htmlspecialchars($player['id']) . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                        <form method="POST" action="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/players/' . htmlspecialchars($player['id']) . '/delete') ?>" class="d-inline" onsubmit="return confirm('Archive this player? Historical assignments will remain intact.');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Archive</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
