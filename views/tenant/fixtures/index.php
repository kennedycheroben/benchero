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
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams" class="list-group-item list-group-item-action">Teams</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/players" class="list-group-item list-group-item-action">Players</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures" class="list-group-item list-group-item-action active">Fixtures</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Fixtures</h2>
                <?php if (in_array($role, ['owner', 'admin', 'manager'])): ?>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/create<?= $seasonId ? "?season_id={$seasonId}" : '' ?>" class="btn btn-primary">+ New Fixture</a>
                <?php endif; ?>
            </div>
            
            <form method="GET" class="mb-4 d-flex gap-2 align-items-end">
                <div>
                    <label class="form-label text-muted small mb-1">Filter by Season</label>
                    <select name="season_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($seasons as $s): ?>
                            <option value="<?= htmlspecialchars($s['id']) ?>" <?= $s['id'] === $seasonId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?> <?= $s['is_current'] ? '(Current)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            
            <hr>
            
            <?php if (empty($fixtures)): ?>
                <div class="alert alert-info">
                    No fixtures scheduled for this season yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date / Time</th>
                                <th>Matchup</th>
                                <th>Context</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fixtures as $f): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= date('M j, Y', strtotime($f['scheduled_at_local'])) ?></div>
                                        <div class="text-muted small"><?= date('H:i', strtotime($f['scheduled_at_local'])) ?></div>
                                    </td>
                                    <td>
                                        <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($f['id']) ?>" class="text-decoration-none text-dark fw-bold">
                                            <?= htmlspecialchars($f['home_team_name']) ?> vs <?= htmlspecialchars($f['away_team_name']) ?>
                                        </a>
                                        <?php if ($f['venue_name']): ?>
                                            <div class="text-muted small"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($f['venue_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars(ucfirst($f['competition_type'])) ?></div>
                                        <?php if ($f['competition_name']): ?>
                                            <div class="text-muted small"><?= htmlspecialchars($f['competition_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $badgeClass = 'bg-secondary';
                                            if ($f['status'] === 'scheduled') $badgeClass = 'bg-primary';
                                            if ($f['status'] === 'completed') $badgeClass = 'bg-success';
                                            if ($f['status'] === 'postponed') $badgeClass = 'bg-warning text-dark';
                                            if ($f['status'] === 'cancelled') $badgeClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($f['status'])) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($f['id']) ?>" class="btn btn-sm btn-outline-secondary">View</a>
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
