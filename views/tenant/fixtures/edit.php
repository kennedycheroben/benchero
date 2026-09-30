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
                    <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures" class="list-group-item list-group-item-action active">Fixtures</a>
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
                    <form method="POST" action="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($fixture['id']) ?>">
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
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0 fw-bold">Home Side *</label>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="home_type" id="home_type_internal" value="internal" <?= empty($fixture['home_opponent_name']) ? 'checked' : '' ?> onchange="toggleSide('home', 'internal')">
                                            <label class="btn btn-outline-secondary" for="home_type_internal">Club Team</label>

                                            <input type="radio" class="btn-check" name="home_type" id="home_type_external" value="external" <?= !empty($fixture['home_opponent_name']) ? 'checked' : '' ?> onchange="toggleSide('home', 'external')">
                                            <label class="btn btn-outline-secondary" for="home_type_external">External</label>
                                        </div>
                                    </div>
                                    <div id="home_internal_container" style="<?= !empty($fixture['home_opponent_name']) ? 'display: none;' : '' ?>">
                                        <select name="home_team_id" id="home_team_id" class="form-select">
                                            <option value="">-- Select Home Team --</option>
                                            <?php foreach ($teams as $t): ?>
                                                <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($fixture['home_team_id'] ?? '') === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div id="home_external_container" style="<?= empty($fixture['home_opponent_name']) ? 'display: none;' : '' ?>">
                                        <input type="text" name="home_opponent_name" id="home_opponent_name" class="form-control" placeholder="e.g. AFC Leopards" value="<?= htmlspecialchars($fixture['home_opponent_name'] ?? '') ?>" maxlength="255">
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-center justify-content-center fw-bold text-muted" style="padding-top: 1.5rem;">
                                    VS
                                </div>
                                <div class="col-md-5 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0 fw-bold">Away Side *</label>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="away_type" id="away_type_internal" value="internal" <?= empty($fixture['away_opponent_name']) ? 'checked' : '' ?> onchange="toggleSide('away', 'internal')">
                                            <label class="btn btn-outline-secondary" for="away_type_internal">Club Team</label>

                                            <input type="radio" class="btn-check" name="away_type" id="away_type_external" value="external" <?= !empty($fixture['away_opponent_name']) ? 'checked' : '' ?> onchange="toggleSide('away', 'external')">
                                            <label class="btn btn-outline-secondary" for="away_type_external">External</label>
                                        </div>
                                    </div>
                                    <div id="away_internal_container" style="<?= !empty($fixture['away_opponent_name']) ? 'display: none;' : '' ?>">
                                        <select name="away_team_id" id="away_team_id" class="form-select">
                                            <option value="">-- Select Away Team --</option>
                                            <?php foreach ($teams as $t): ?>
                                                <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($fixture['away_team_id'] ?? '') === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div id="away_external_container" style="<?= empty($fixture['away_opponent_name']) ? 'display: none;' : '' ?>">
                                        <input type="text" name="away_opponent_name" id="away_opponent_name" class="form-control" placeholder="e.g. Gor Mahia" value="<?= htmlspecialchars($fixture['away_opponent_name'] ?? '') ?>" maxlength="255">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                        function toggleSide(side, type) {
                            var intDiv = document.getElementById(side + '_internal_container');
                            var extDiv = document.getElementById(side + '_external_container');
                            if (type === 'external') {
                                intDiv.style.display = 'none';
                                extDiv.style.display = 'block';
                            } else {
                                intDiv.style.display = 'block';
                                extDiv.style.display = 'none';
                            }
                        }
                        </script>

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
                        <a href="<?= url("/o/") ?><?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/fixtures/<?= htmlspecialchars($fixture['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
