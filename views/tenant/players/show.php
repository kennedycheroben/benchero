<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/dashboard"><?= htmlspecialchars($tenant['name']) ?></a></li>
            <li class="breadcrumb-item"><a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/players"><?= htmlspecialchars($sport['name']) ?> Players</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">
                <?= htmlspecialchars($player['first_name'] . ' ' . $player['last_name']) ?>
                <?php if (!$player['is_active']): ?>
                    <span class="badge bg-secondary ms-2 align-middle fs-6">Inactive</span>
                <?php endif; ?>
            </h1>
            <?php if (!empty($player['display_name'])): ?>
                <p class="text-muted mb-0">aka <?= htmlspecialchars($player['display_name']) ?></p>
            <?php endif; ?>
        </div>
        <?php if (in_array($request->getAttribute('tenant_role'), ['owner', 'admin', 'manager'])): ?>
            <div>
                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/players/<?= htmlspecialchars($player['id']) ?>/edit" class="btn btn-outline-primary">Edit Profile</a>
            </div>
        <?php endif; ?>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" href="#">Profile</a>
        </li>
        <li class="nav-item">
            <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Statistics <span class="badge bg-light text-dark ms-1">Coming Soon</span></a>
        </li>
    </ul>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Biography</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($player['bio'])): ?>
                        <p><?= nl2br(htmlspecialchars($player['bio'])) ?></p>
                    <?php else: ?>
                        <p class="text-muted fst-italic">No bio provided.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Roster History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($assignments)): ?>
                        <p class="text-muted mb-0">No roster assignments yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Season</th>
                                        <th>Team</th>
                                        <th>Jersey</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($a['season_name']) ?></td>
                                            <td>
                                                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/s/<?= htmlspecialchars($sport['slug']) ?>/teams/<?= htmlspecialchars($a['team_id']) ?>">
                                                    <?= htmlspecialchars($a['team_name']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($a['jersey_number'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($a['position'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($a['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($a['status'])) ?></span>
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

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Information</h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Date of Birth
                        <span class="fw-bold"><?= $player['date_of_birth'] ? date('M j, Y', strtotime($player['date_of_birth'])) : 'N/A' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Created
                        <span class="fw-bold"><?= date('M j, Y', strtotime($player['created_at'])) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
