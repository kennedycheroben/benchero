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
            <h2>Edit Fixture</h2>
            <hr>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($fixture['id']) ?>">
                        <?= csrf_field() ?>
                        
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Context</h5>
                            <div class="mb-3">
                                <label class="form-label">Season *</label>
                                <select name="season_id" class="form-select" required>
                                    <?php foreach ($seasons as $s): ?>
                                        <option value="<?= htmlspecialchars($s['id']) ?>" <?= $fixture['season_id'] === $s['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Competition Type *</label>
                                    <select name="competition_type" class="form-select" required>
                                        <option value="league" <?= $fixture['competition_type'] === 'league' ? 'selected' : '' ?>>League</option>
                                        <option value="cup" <?= $fixture['competition_type'] === 'cup' ? 'selected' : '' ?>>Cup</option>
                                        <option value="tournament" <?= $fixture['competition_type'] === 'tournament' ? 'selected' : '' ?>>Tournament</option>
                                        <option value="friendly" <?= $fixture['competition_type'] === 'friendly' ? 'selected' : '' ?>>Friendly</option>
                                        <option value="playoff" <?= $fixture['competition_type'] === 'playoff' ? 'selected' : '' ?>>Playoff</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Competition Name (Optional)</label>
                                    <input type="text" name="competition_name" class="form-control" value="<?= htmlspecialchars($fixture['competition_name'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Matchup</h5>
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">Home Team *</label>
                                    <select name="home_team_id" class="form-select" required>
                                        <?php foreach ($teams as $t): ?>
                                            <option value="<?= htmlspecialchars($t['id']) ?>" <?= $fixture['home_team_id'] === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-center justify-content-center fw-bold text-muted">
                                    VS
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">Away Team *</label>
                                    <select name="away_team_id" class="form-select" required>
                                        <?php foreach ($teams as $t): ?>
                                            <option value="<?= htmlspecialchars($t['id']) ?>" <?= $fixture['away_team_id'] === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Logistics</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date & Time (Local Time) *</label>
                                    <input type="datetime-local" name="scheduled_at" class="form-control" required value="<?= htmlspecialchars($fixture['scheduled_at_input']) ?>">
                                    <div class="form-text">Your org timezone is <?= htmlspecialchars($tenant['timezone'] ?? 'UTC') ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Venue (Optional)</label>
                                    <input type="text" name="venue_name" class="form-control" value="<?= htmlspecialchars($fixture['venue_name'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($fixture['notes'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($fixture['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
