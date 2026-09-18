<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary">
    <div class="container d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-2">
                <?= ucfirst($this->e($competition['sport'] ?? 'Football')) ?> &bull; <?= $this->e($competition['country'] ?? 'International') ?>
            </span>
            <h1 class="fw-extrabold mb-1 display-6 text-white"><?= $this->e($competition['name']) ?></h1>
            <p class="text-white-50 small mb-0">Official League Hub, Standings Table & Match Schedule</p>
        </div>
        <div>
            <a href="<?= url('/sports/competitions') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">
                &larr; All Competitions
            </a>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <!-- Standings Table & Matches -->
        <div class="col-lg-8">
            
            <!-- Standings Table -->
            <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden">
                <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold text-slate-900 mb-0">
                        <i class="bi bi-list-ol text-primary me-2"></i> Standings Table
                    </h5>
                    <span class="badge bg-secondary">Season 2025/2026</span>
                </div>
                <?php if (!empty($standings)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th class="ps-3 text-center" style="width: 40px;">#</th>
                                    <th>Team</th>
                                    <th class="text-center">P</th>
                                    <th class="text-center">W</th>
                                    <th class="text-center">D</th>
                                    <th class="text-center">L</th>
                                    <th class="text-center">GD</th>
                                    <th class="text-center pe-3 fw-bold text-dark">PTS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($standings as $st): ?>
                                    <tr class="<?= $st['position'] <= 4 ? 'table-success bg-opacity-10' : '' ?>">
                                        <td class="ps-3 text-center fw-bold text-muted"><?= $st['position'] ?></td>
                                        <td class="fw-bold text-slate-900"><?= $this->e($st['team']) ?></td>
                                        <td class="text-center text-muted"><?= $st['played'] ?></td>
                                        <td class="text-center"><?= $st['won'] ?></td>
                                        <td class="text-center text-muted"><?= $st['drawn'] ?></td>
                                        <td class="text-center text-muted"><?= $st['lost'] ?></td>
                                        <td class="text-center text-muted"><?= $st['gd'] > 0 ? '+' . $st['gd'] : $st['gd'] ?></td>
                                        <td class="text-center pe-3 fw-extrabold fs-6 text-primary"><?= $st['points'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center text-muted">
                        Standings for this competition are currently being updated.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Results -->
            <?php if (!empty($recentResults)): ?>
                <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden">
                    <div class="card-header bg-white p-3 border-bottom">
                        <h5 class="fw-bold text-slate-900 mb-0">Recent Matches</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentResults as $res): ?>
                            <li class="list-group-item p-3">
                                <div class="row align-items-center">
                                    <div class="col-5 text-end fw-bold"><?= $this->e($res['home_team']) ?></div>
                                    <div class="col-2 text-center">
                                        <span class="px-3 py-1 bg-dark text-white rounded-pill fw-bold small">
                                            <?= $this->e($res['home_score']) ?> - <?= $this->e($res['away_score']) ?>
                                        </span>
                                    </div>
                                    <div class="col-5 text-start fw-bold"><?= $this->e($res['away_team']) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar: SaaS Pitch -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 text-white bg-dark p-4 sticky-top" style="top: 90px;">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="32" class="rounded-2">
                    <span class="fw-extrabold fs-4 tracking-tight">BENCHERO</span>
                </div>
                <h4 class="fw-bold mb-2">Are you managing a team in this league?</h4>
                <p class="text-white-50 small mb-4">
                    Take control of your club operations with Benchero. Launch your club's official public page, manage player rosters, track fixtures, and publish match reports effortlessly.
                </p>
                <a href="<?= url('/register') ?>" class="btn btn-primary fw-bold py-2 rounded-3 w-100">
                    Get Started Free
                </a>
            </div>
        </div>
    </div>
</div>
