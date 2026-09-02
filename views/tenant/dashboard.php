<?php require __DIR__ . '/../layouts/main.php'; ?>

<div class="container mt-5">
    <div class="row">
        <!-- Sidebar Navigation (Placeholder) -->
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($tenant['name']) ?></h5>
                    <p class="text-muted small mb-0">Role: <?= htmlspecialchars(ucfirst($role)) ?></p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/dashboard" class="list-group-item list-group-item-action active">Dashboard</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/sports" class="list-group-item list-group-item-action">Manage Sports</a>
                    <a href="#" class="list-group-item list-group-item-action disabled">Settings</a>
                </div>
            </div>
            
            <form method="POST" action="/logout">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger w-100">Log Out</button>
            </form>
        </div>

        <!-- Main Dashboard Content -->
        <div class="col-md-9">
            <h2>Welcome to <?= htmlspecialchars($tenant['name']) ?></h2>
            <hr>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Subscription Status</h5>
                            <?php if ($subscription): ?>
                                <p><strong>Status:</strong> <span class="badge bg-<?= $subscription['status'] === 'active' ? 'success' : ($subscription['status'] === 'trialing' ? 'info' : 'warning') ?>"><?= htmlspecialchars(ucfirst($subscription['status'])) ?></span></p>
                                <p><strong>Billing Interval:</strong> <?= htmlspecialchars(ucfirst($subscription['billing_interval'])) ?></p>
                                <?php if ($subscription['status'] === 'trialing' && $subscription['trial_ends_at']): ?>
                                    <p><strong>Trial Ends:</strong> <?= htmlspecialchars($subscription['trial_ends_at']) ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted">No subscription found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Tenant Details</h5>
                            <p><strong>Slug:</strong> <?= htmlspecialchars($tenant['slug']) ?></p>
                            <p><strong>Country:</strong> <?= htmlspecialchars($tenant['country']) ?></p>
                            <p><strong>Timezone:</strong> <?= htmlspecialchars($tenant['timezone']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
