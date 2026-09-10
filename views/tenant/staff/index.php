<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Club Staff & Officials</h2>
        <p class="text-muted small mb-0">Manage coaches, team managers, physios, and administrative staff.</p>
    </div>
    <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff/create') ?>" class="btn btn-primary fw-bold rounded-3">
        <i class="bi bi-person-plus me-1"></i> Add Staff Member
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
        <?php unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($staff)): ?>
    <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
        <i class="bi bi-person-vcard text-muted display-4 mb-3"></i>
        <h4 class="fw-bold">No Staff Members Added</h4>
        <p class="text-muted max-w-md mx-auto">Add head coaches, assistant coaches, team managers, and medical staff to organize your club management team.</p>
        <div class="mt-3">
            <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff/create') ?>" class="btn btn-primary fw-bold rounded-3">
                <i class="bi bi-plus-lg me-1"></i> Add First Staff Member
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Staff Member</th>
                        <th>Role / Title</th>
                        <th>Assigned Team</th>
                        <th>Contact Info</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $member): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!empty($member['photo_url'])): ?>
                                        <img src="<?= htmlspecialchars($member['photo_url']) ?>" class="rounded-circle object-fit-cover" width="42" height="42" alt="Photo">
                                    <?php else: ?>
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:42px; height:42px;">
                                            <?= strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></div>
                                        <?php if (!empty($member['bio'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars(substr($member['bio'], 0, 50)) ?>...</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary px-3 py-2 fw-semibold rounded-pill">
                                    <?= htmlspecialchars($member['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($member['team_name'])): ?>
                                    <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-people me-1"></i><?= htmlspecialchars($member['team_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">All Club Teams</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?php if (!empty($member['email'])): ?><div><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($member['email']) ?></div><?php endif; ?>
                                <?php if (!empty($member['phone'])): ?><div><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($member['phone']) ?></div><?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff/' . urlencode($member['id']) . '/edit') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="<?= url('/o/' . urlencode($tenant['slug']) . '/staff/' . urlencode($member['id']) . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Remove this staff member?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-3"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
