<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h3 class="fw-bold mb-3">Add Staff Member</h3>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="<?= url('/o/' . urlencode($tenant['slug']) . '/staff') ?>" method="POST">
                <?= csrf_field() ?>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="Coach">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required placeholder="Smith">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="role" class="form-label fw-semibold">Role / Title <span class="text-danger">*</span></label>
                    <select id="role" name="role" class="form-select" required>
                        <option value="Head Coach">Head Coach</option>
                        <option value="Assistant Coach">Assistant Coach</option>
                        <option value="Team Manager">Team Manager</option>
                        <option value="Physical Trainer">Physical Trainer</option>
                        <option value="Administrator">Administrator</option>
                        <option value="Official">Official</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff') ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Save Staff Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
