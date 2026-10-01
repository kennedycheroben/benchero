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
                    <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/dashboard" class="list-group-item list-group-item-action">Dashboard</a>
                    <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/sports" class="list-group-item list-group-item-action">All Sports</a>
                    <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons" class="list-group-item list-group-item-action">Seasons</a>
                    <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams" class="list-group-item list-group-item-action">Teams</a>
                    <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/players" class="list-group-item list-group-item-action">Players</a>
                    <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures" class="list-group-item list-group-item-action">Fixtures</a>
                    <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/results" class="list-group-item list-group-item-action active">Results</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Results</h2>
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

            <?php if (empty($results)): ?>
                <div class="alert alert-info">
                    No results recorded for this season yet. Results appear here once a fixture is marked as completed.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Matchup</th>
                                <th>Score</th>
                                <th>Context</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= date('M j, Y', strtotime($r['scheduled_at_local'])) ?></div>
                                        <div class="text-muted small"><?= date('H:i', strtotime($r['scheduled_at_local'])) ?></div>
                                    </td>
                                    <td>
                                        <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($r['id']) ?>" class="text-decoration-none text-dark fw-bold">
                                            <?= htmlspecialchars($r['home_team_name']) ?> vs <?= htmlspecialchars($r['away_team_name']) ?>
                                        </a>
                                        <?php if (empty($r['home_team_id']) || empty($r['away_team_id'])): ?>
                                            <span class="badge bg-light text-muted border ms-1">External Opponent</span>
                                        <?php endif; ?>
                                        <?php if ($r['venue_name']): ?>
                                            <div class="text-muted small"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($r['venue_name']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="fw-bold fs-5">
                                            <?= (int)($r['home_score'] ?? 0) ?> &ndash; <?= (int)($r['away_score'] ?? 0) ?>
                                        </span>
                                        <?php if (!empty($r['result_notes'])): ?>
                                            <div class="text-muted small"><?= htmlspecialchars($r['result_notes']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars(ucfirst($r['competition_type'])) ?></div>
                                        <?php if ($r['competition_name']): ?>
                                            <div class="text-muted small"><?= htmlspecialchars($r['competition_name']) ?></div>
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
