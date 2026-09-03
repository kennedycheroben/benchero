<?php $this->layout('layout', ['title' => 'Create Organization']); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white p-3">
                    <h5 class="mb-0 fw-bold">Create Your Organization</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger mb-4">
                            <?= $this->e($_SESSION['error']) ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted mb-4">Welcome to Benchero. To get started, please set up your sports organization details.</p>
                    
                    <form method="POST" action="<?= $this->url('/onboarding') ?>">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Organization Name</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" required placeholder="e.g. Acme Sports FC">
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label fw-semibold">Organization URL Slug</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">/o/</span>
                                <input type="text" class="form-control" id="slug" name="slug" required pattern="[a-z0-9\-]+" title="Lowercase letters, numbers, and hyphens only" placeholder="acme-sports">
                            </div>
                            <div class="form-text">This will be your unique organization link.</div>
                        </div>

                        <div class="mb-3">
                            <label for="country" class="form-label fw-semibold">Country Code</label>
                            <input type="text" class="form-control form-control-lg" id="country" name="country" required maxlength="2" placeholder="e.g. KE">
                        </div>

                        <div class="mb-4">
                            <label for="timezone" class="form-label fw-semibold">Timezone</label>
                            <input type="text" class="form-control form-control-lg" id="timezone" name="timezone" required value="Africa/Nairobi">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Create Organization</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
