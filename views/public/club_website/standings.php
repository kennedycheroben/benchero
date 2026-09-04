<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">League Table</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">League Standings</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Official competition league table standings.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (empty($standings)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-table display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Standings Data Available</h4>
                <p class="text-muted mb-0">League standings will be computed automatically as match results are entered.</p>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark text-uppercase small">
                            <tr>
                                <th class="text-center" style="width:60px;">POS</th>
                                <th>TEAM</th>
                                <th class="text-center">P</th>
                                <th class="text-center">W</th>
                                <th class="text-center">D</th>
                                <th class="text-center">L</th>
                                <th class="text-center">GF</th>
                                <th class="text-center">GA</th>
                                <th class="text-center">GD</th>
                                <th class="text-center fw-bold">PTS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standings as $pos => $row): ?>
                                <tr class="<?= ($row['team_id'] ?? '') ? 'table-primary fw-bold' : '' ?>">
                                    <td class="text-center fw-bold fs-6"><?= $pos + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark text-uppercase"><?= htmlspecialchars($row['team_name']) ?></div>
                                    </td>
                                    <td class="text-center"><?= (int)$row['played'] ?></td>
                                    <td class="text-center text-success"><?= (int)$row['won'] ?></td>
                                    <td class="text-center text-muted"><?= (int)$row['drawn'] ?></td>
                                    <td class="text-center text-danger"><?= (int)$row['lost'] ?></td>
                                    <td class="text-center"><?= (int)$row['goals_for'] ?></td>
                                    <td class="text-center"><?= (int)$row['goals_against'] ?></td>
                                    <td class="text-center"><?= ((int)$row['goal_difference'] > 0 ? '+' : '') . (int)$row['goal_difference'] ?></td>
                                    <td class="text-center fw-black fs-5 text-dark"><?= (int)$row['points'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
