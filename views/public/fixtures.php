<?php $this->layout('layout', ['title' => $title]) ?>

<div class="container mt-5">
    <h2><?= htmlspecialchars($org['name']) ?> - <?= htmlspecialchars($sport['name']) ?> Fixtures</h2>
    
    <?php if (!empty($seasons)): ?>
        <form method="GET" class="mb-4">
            <label for="season_id" class="form-label">Season:</label>
            <select name="season_id" id="season_id" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
                <?php foreach ($seasons as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id'] === $seasonId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>

    <?php if (empty($fixtures)): ?>
        <div class="alert alert-info">No fixtures scheduled for this season.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Competition</th>
                        <th>Home Team</th>
                        <th>Away Team</th>
                        <th>Venue</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fixtures as $fixture): ?>
                        <tr>
                            <td><?= htmlspecialchars($fixture['scheduled_at_local']) ?></td>
                            <td>
                                <?= htmlspecialchars(ucfirst($fixture['competition_type'])) ?>
                                <?= $fixture['competition_name'] ? '<br><small class="text-muted">'.htmlspecialchars($fixture['competition_name']).'</small>' : '' ?>
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($fixture['home_team_name']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($fixture['away_team_name']) ?></td>
                            <td><?= htmlspecialchars($fixture['venue_name'] ?? 'TBD') ?></td>
                            <td>
                                <?php if ($fixture['status'] === 'scheduled'): ?>
                                    <span class="badge bg-primary">Scheduled</span>
                                <?php elseif ($fixture['status'] === 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php elseif ($fixture['status'] === 'postponed'): ?>
                                    <span class="badge bg-warning text-dark">Postponed</span>
                                <?php elseif ($fixture['status'] === 'cancelled'): ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
