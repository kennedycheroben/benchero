<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="row justify-content-center py-3">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h3 class="fw-bold mb-3"><i class="bi bi-person-plus text-primary me-2"></i>Add Staff Member / Official</h3>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-3 mb-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="<?= url('/o/' . urlencode($tenant['slug']) . '/staff') ?>" method="POST">
                <?= csrf_field() ?>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="e.g. John">
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required placeholder="e.g. Doe">
                    </div>

                    <div class="col-md-6">
                        <label for="role" class="form-label fw-semibold">Sports Role / Title <span class="text-danger">*</span></label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="Head Coach">Head Coach</option>
                            <option value="Assistant Coach">Assistant Coach</option>
                            <option value="Team Manager">Team Manager</option>
                            <option value="Goalkeeper Coach">Goalkeeper Coach</option>
                            <option value="Fitness Coach">Fitness Coach</option>
                            <option value="Physiotherapist">Physiotherapist</option>
                            <option value="Medical Staff">Medical Staff</option>
                            <option value="Team Secretary">Team Secretary</option>
                            <option value="Analyst">Analyst</option>
                            <option value="Media Officer">Media Officer</option>
                            <option value="Kit Manager">Kit Manager</option>
                            <option value="Club Administrator">Club Administrator</option>
                            <option value="General Manager">General Manager</option>
                            <option value="Owner">Owner</option>
                            <option value="Other Staff">Other Staff</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="team_id" class="form-label fw-semibold">Team Assignment</label>
                        <select id="team_id" name="team_id" class="form-select">
                            <option value="">-- All Club Teams --</option>
                            <?php if (!empty($teams)): ?>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?= htmlspecialchars($t['id']) ?>"><?= htmlspecialchars($t['name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="coach@club.co.ke">
                    </div>

                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">Phone / WhatsApp</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="+254 700 000 000">
                    </div>

                    <div class="col-12">
                        <label for="photo_url" class="form-label fw-semibold">Profile Photo URL</label>
                        <input type="url" id="photo_url" name="photo_url" class="form-control" placeholder="https://example.com/coach.jpg">
                    </div>

                    <div class="col-12">
                        <label for="bio" class="form-label fw-semibold">Biography / Coaching Qualifications</label>
                        <textarea id="bio" name="bio" class="form-control" rows="3" placeholder="Brief biography, licenses, or background..."></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/staff') ?>" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    <button type="submit" class="btn btn-primary fw-bold px-4 rounded-3">Save Staff Member</button>
                </div>
            </form>
        </div>
    </div>
</div>
