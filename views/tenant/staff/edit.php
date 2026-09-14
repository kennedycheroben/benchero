<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="row justify-content-center py-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h3 class="fw-bold mb-3"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Staff Member</h3>

            <form action="<?= url('/o/' . urlencode($tenant['slug']) . '/staff/' . urlencode($member['id'])) ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required value="<?= htmlspecialchars($member['first_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required value="<?= htmlspecialchars($member['last_name']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="role" class="form-label fw-semibold">Sports Role / Title <span class="text-danger">*</span></label>
                        <select id="role" name="role" class="form-select" required>
                            <?php 
                                $roles = ['Head Coach', 'Assistant Coach', 'Team Manager', 'Goalkeeper Coach', 'Fitness Coach', 'Physiotherapist', 'Medical Staff', 'Team Secretary', 'Analyst', 'Media Officer', 'Kit Manager', 'Club Administrator', 'General Manager', 'Owner', 'Other Staff'];
                                foreach ($roles as $r):
                            ?>
                                <option value="<?= $r ?>" <?= $member['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="team_id" class="form-label fw-semibold">Team Assignment</label>
                        <select id="team_id" name="team_id" class="form-select">
                            <option value="">-- All Club Teams --</option>
                            <?php if (!empty($teams)): ?>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?= htmlspecialchars($t['id']) ?>" <?= ($member['team_id'] ?? '') === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($member['email'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">Phone / WhatsApp</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($member['phone'] ?? '') ?>">
                    </div>

                    <div class="col-12">
                        <label for="photo_file" class="form-label fw-semibold">Upload Staff Profile Photo File</label>
                        <input type="file" id="photo_file" name="photo_file" class="form-control" accept="image/png,image/jpeg,image/webp">
                        <input type="hidden" name="photo_url" value="<?= htmlspecialchars($member['photo_url'] ?? '') ?>">
                        <div class="form-text">PNG, JPG, WEBP formats. Max file size: 2 MB.</div>
                        <?php if (!empty($member['photo_url'])): ?>
                            <div class="mt-2 d-flex align-items-center">
                                <span class="me-2 small text-muted">Current Photo:</span>
                                <img src="<?= htmlspecialchars($member['photo_url']) ?>" alt="Staff Photo" class="rounded border" style="height: 40px; width: 40px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label for="bio" class="form-label fw-semibold">Biography / Coaching Qualifications</label>
                        <textarea id="bio" name="bio" class="form-control" rows="3"><?= htmlspecialchars($member['bio'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff') ?>" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary fw-bold px-4 rounded-3">Update Staff Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
