<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/dashboard"><?= htmlspecialchars($tenant['name']) ?></a></li>
            <li class="breadcrumb-item"><a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams"><?= htmlspecialchars($sport['name']) ?> Teams</a></li>
            <li class="breadcrumb-item"><a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($team['id']) ?>"><?= htmlspecialchars($team['name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Roster: <?= htmlspecialchars($season['name']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0"><?= htmlspecialchars($team['name']) ?> Roster</h1>
            <p class="text-muted mb-0">Season: <?= htmlspecialchars($season['name']) ?></p>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Current Roster</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($roster)): ?>
                        <p class="text-muted mb-0">No players assigned to this roster yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Player</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                        <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                                            <th class="text-end">Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roster as $r): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($r['jersey_number'] ?? '-') ?></td>
                                            <td>
                                                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/players/<?= htmlspecialchars($r['player_id']) ?>" class="fw-bold text-decoration-none">
                                                    <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($r['position'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($r['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($r['status'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                                                <td class="text-end">
                                                    <!-- Edit Modal Trigger -->
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['id'] ?>">
                                                        Edit
                                                    </button>
                                                    
                                                    <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($team['id']) ?>/rosters/<?= htmlspecialchars($season['id']) ?>/assignments/<?= htmlspecialchars($r['id']) ?>/delete" class="d-inline" onsubmit="return confirm('Archive this roster assignment? Historical records will remain intact.');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                                        <div class="modal fade" id="editModal<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($team['id']) ?>/rosters/<?= htmlspecialchars($season['id']) ?>/assignments/<?= htmlspecialchars($r['id']) ?>">
                                                        <?= csrf_field() ?>
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Assignment</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <p class="fw-bold"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Jersey Number</label>
                                                                <input type="text" class="form-control" name="jersey_number" value="<?= htmlspecialchars($r['jersey_number'] ?? '') ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Position (Generic)</label>
                                                                <input type="text" class="form-control" name="position" value="<?= htmlspecialchars($r['position'] ?? '') ?>" placeholder="e.g. Goalkeeper, Forward, Point Guard">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Status</label>
                                                                <select class="form-select" name="status">
                                                                    <option value="active" <?= $r['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                                                    <option value="injured" <?= $r['status'] === 'injured' ? 'selected' : '' ?>>Injured</option>
                                                                    <option value="suspended" <?= $r['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                                                    <option value="inactive" <?= $r['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Assign Player</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($availablePlayers)): ?>
                            <p class="text-muted small">No available players to assign. <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/players/create">Create a new player</a> first.</p>
                        <?php else: ?>
                            <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($team['id']) ?>/rosters/<?= htmlspecialchars($season['id']) ?>">
                                <?= csrf_field() ?>
                                
                                <div class="mb-3">
                                    <label for="player_id" class="form-label">Select Player *</label>
                                    <select class="form-select" id="player_id" name="player_id" required>
                                        <option value="">-- Choose Player --</option>
                                        <?php foreach ($availablePlayers as $p): ?>
                                            <option value="<?= htmlspecialchars($p['id']) ?>">
                                                <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="jersey_number" class="form-label">Jersey Number</label>
                                    <input type="text" class="form-control" id="jersey_number" name="jersey_number">
                                </div>

                                <div class="mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="position" name="position" placeholder="e.g. Goalkeeper">
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Add to Roster</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
