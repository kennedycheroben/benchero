<?php $this->layout('layout', ['title' => 'Players - ' . $sport['name']]); ?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($tenant['name']) ?></h5>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($sport['name']) ?></p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/dashboard') ?>" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/sports') ?>" class="list-group-item list-group-item-action">All Sports</a>
                    <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/seasons') ?>" class="list-group-item list-group-item-action">Seasons</a>
                    <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/teams') ?>" class="list-group-item list-group-item-action">Teams</a>
                    <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/players') ?>" class="list-group-item list-group-item-action active">Players</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Players</h2>
                <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                    <a href="<?= url('/o/' . htmlspecialchars($tenant['slug']) . '/s/' . htmlspecialchars($sport['slug']) . '/players/create') ?>" class="btn btn-primary">+ New Player</a>
                <?php endif; ?>
            </div>
            <hr>
            
            <?php if (empty($players)): ?>
                <div class="alert alert-info">
                    No players found for <?= htmlspecialchars($sport['name']) ?> yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
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
            <?php endif; ?>
        </div>
    </div>
</div>
