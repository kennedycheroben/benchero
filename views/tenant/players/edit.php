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
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/players" class="list-group-item list-group-item-action active">Players</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <h2>Edit Player: <?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) ?></h2>
            <hr>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/players/<?= htmlspecialchars($player['id']) ?>">
                        <?= csrf_field() ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required value="<?= htmlspecialchars($player['first_name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required value="<?= htmlspecialchars($player['last_name']) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display Name (Optional)</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" value="<?= htmlspecialchars($player['display_name'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth (Optional)</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($player['date_of_birth'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio (Optional)</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($player['bio'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= $player['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Active (Available for rosters)</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/players" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
