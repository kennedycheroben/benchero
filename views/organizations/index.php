<?php $this->layout('layout', ['title' => 'Select Organization']); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="fw-bold mb-4">Select Organization</h2>
            
            <div class="list-group mb-4">
                <?php foreach ($organizations as $org): ?>
                    <a href="<?= $this->url('/o/' . $this->e($org['slug']) . '/dashboard') ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                        <div>
                            <h5 class="mb-1 fw-bold"><?= $this->e($org['name']) ?></h5>
                            <small class="text-muted">/o/<?= $this->e($org['slug']) ?></small>
                        </div>
                        <span class="badge bg-primary rounded-pill"><?= $this->e(ucfirst($org['role'])) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 text-center">
                <a href="<?= $this->url('/onboarding') ?>" class="btn btn-outline-secondary">Create Another Organization</a>
            </div>
        </div>
    </div>
</div>
