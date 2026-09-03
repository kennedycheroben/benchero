<?php $this->layout('layout', ['title' => $title]) ?>

<section class="py-5 bg-dark text-white border-bottom">
    <div class="container py-lg-4">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <span class="badge bg-primary px-3 py-2 rounded-pill text-uppercase fw-bold mb-2">Sports Club</span>
                <h1 class="display-4 fw-extrabold mb-1"><?= htmlspecialchars($org['name']) ?></h1>
                <p class="text-white-50 mb-0"><i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($org['country'] ?? 'KE') ?> | Official Club Page on Benchero</p>
            </div>
            <a href="<?= url('/register') ?>" class="btn btn-outline-light btn-lg fw-bold rounded-3">
                Powered by Benchero
            </a>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-trophy text-primary me-2"></i>Active Sports</h5>
                    <?php if (empty($sports)): ?>
                        <p class="text-muted small mb-0">No active sports currently published.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($sports as $sp): ?>
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fs-7 fw-semibold">
                                    <?= htmlspecialchars($sp['name']) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-info me-2"></i>Club Details</h5>
                    <ul class="list-unstyled text-muted small d-grid gap-2 mb-0">
                        <li><strong>Platform Status:</strong> Verified Benchero Organization</li>
                        <li><strong>Timezone:</strong> <?= htmlspecialchars($org['timezone'] ?? 'Africa/Nairobi') ?></li>
                        <li><strong>Member Since:</strong> <?= date('F Y', strtotime($org['created_at'])) ?></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Fixtures & Results Schedule</h5>
                        <span class="badge bg-light text-dark border">Public View</span>
                    </div>

                    <?php if (empty($fixtures)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-calendar-x display-4 mb-2 d-block"></i>
                            <p class="mb-0">No public fixtures scheduled yet for <?= htmlspecialchars($org['name']) ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date & Sport</th>
                                        <th>Matchup</th>
                                        <th class="text-center">Score / Status</th>
                                        <th>Venue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fixtures as $f): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= date('M j, Y H:i', strtotime($f['scheduled_at'])) ?></div>
                                                <span class="badge bg-secondary-subtle text-secondary fs-8"><?= htmlspecialchars($f['sport_name']) ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars($f['home_team_name']) ?> vs <?= htmlspecialchars($f['away_team_name']) ?>
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars(ucfirst($f['competition_type'])) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($f['status'] === 'completed' && $f['home_score'] !== null): ?>
                                                    <span class="badge bg-dark fs-6 px-3 py-1 fw-bold">
                                                        <?= (int)$f['home_score'] ?> - <?= (int)$f['away_score'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">
                                                        <?= htmlspecialchars(ucfirst($f['status'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small text-muted"><?= htmlspecialchars($f['venue_name'] ?: 'TBD') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
