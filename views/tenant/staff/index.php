<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Club Staff & Officials</h2>
        <p class="text-muted small">Manage coaches, managers, assistant coaches, and officials.</p>
    </div>
    <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff/create') ?>" class="btn btn-primary fw-bold">
        <i class="bi bi-person-plus me-1"></i> Add Staff Member
    </a>
</div>

<?php if (empty($staff)): ?>
    <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
        <i class="bi bi-person-vcard text-muted display-4 mb-3"></i>
        <h4 class="fw-bold">No Staff Members Found</h4>
        <p class="text-muted max-w-md mx-auto">Add your head coach, assistant coaches, and team personnel to organize your club leadership.</p>
        <div class="mt-3">
            <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff/create') ?>" class="btn btn-primary fw-bold">
                Add First Staff Member
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Role / Title</th>
                        <th>Date Added</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $member): ?>
                        <tr>
                            <td class="fw-bold"><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= htmlspecialchars($member['role']) ?></span></td>
                            <td class="small text-muted"><?= date('M j, Y', strtotime($member['created_at'])) ?></td>
                            <td class="text-end">
                                <form action="<?= url('/o/' . urlencode($tenant['slug']) . '/staff/' . urlencode($member['id']) . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this staff member?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
