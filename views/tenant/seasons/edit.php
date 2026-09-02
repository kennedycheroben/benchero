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
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons" class="list-group-item list-group-item-action active">Seasons</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams" class="list-group-item list-group-item-action">Teams</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <h2>Edit Season: <?= htmlspecialchars($season['name']) ?></h2>
            <hr>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons/<?= htmlspecialchars($season['id']) ?>">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Season Name</label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($season['name']) ?>">
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label for="starts_on" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="starts_on" name="starts_on" required value="<?= htmlspecialchars($season['starts_on']) ?>">
                            </div>
                            <div class="col">
                                <label for="ends_on" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="ends_on" name="ends_on" required value="<?= htmlspecialchars($season['ends_on']) ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/seasons" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
