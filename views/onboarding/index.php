<?php $this->layout('layout', ['title' => 'Create Organization']); ?>

<?php
    $oldInput = $_SESSION['old_onboarding_input'] ?? [];
    unset($_SESSION['old_onboarding_input']);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4">
                    <h4 class="mb-0 fw-bold"><i class="bi bi-shield-plus me-2"></i>Create Your Organization</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $this->e($_SESSION['error']) ?>
                            <?php unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted mb-4">Welcome to Benchero. To get started, please set up your sports organization details.</p>
                    
                    <form method="POST" action="<?= $this->url('/onboarding') ?>">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Organization Name</label>
                            <input type="text" class="form-control form-control-lg rounded-3" id="name" name="name" value="<?= htmlspecialchars($oldInput['name'] ?? '') ?>" required placeholder="e.g. Acme Sports FC">
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label fw-semibold">Organization URL Slug</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light fw-bold text-muted">/o/</span>
                                <input type="text" class="form-control rounded-end-3" id="slug" name="slug" value="<?= htmlspecialchars($oldInput['slug'] ?? '') ?>" pattern="[a-z0-9\-]+" title="Lowercase letters, numbers, and hyphens only" placeholder="acme-sports">
                            </div>
                            <div class="form-text text-muted">Your unique web link. Automatically generated as you type.</div>
                        </div>

                        <div class="mb-3">
                            <label for="country" class="form-label fw-semibold">Country Code</label>
                            <input type="text" class="form-control form-control-lg rounded-3" id="country" name="country" value="<?= htmlspecialchars($oldInput['country'] ?? 'KE') ?>" required maxlength="2" placeholder="e.g. KE">
                        </div>

                        <div class="mb-4">
                            <label for="timezone" class="form-label fw-semibold">Timezone</label>
                            <input type="text" class="form-control form-control-lg rounded-3" id="timezone" name="timezone" value="<?= htmlspecialchars($oldInput['timezone'] ?? 'Africa/Nairobi') ?>" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-3">Create Organization</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    let userHasEditedSlug = false;

    if (slugInput.value) {
        userHasEditedSlug = true;
    }

    slugInput.addEventListener('input', function() {
        userHasEditedSlug = true;
    });

    nameInput.addEventListener('input', function() {
        if (!userHasEditedSlug) {
            slugInput.value = nameInput.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    });
});
</script>
