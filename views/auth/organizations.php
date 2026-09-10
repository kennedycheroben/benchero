<?php $this->layout('layout', ['title' => 'Select Organization']); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold text-center mb-4">Select Organization</h2>
                    
                    <div class="list-group mb-4">
                        <?php foreach ($organizations as $org): ?>
                            <a href="<?= $this->url('/o/' . $this->e($org['slug']) . '/dashboard') ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h5 class="mb-0 fw-bold"><?= $this->e($org['name']) ?></h5>
                                    <small class="text-muted">Role: <?= $this->e(ucfirst($org['role'] ?? 'Member')) ?></small>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= $this->url('/onboarding') ?>" class="btn btn-outline-primary">+ Create New Organization</a>
                        <form action="<?= $this->url('/logout') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-link text-muted text-decoration-none">Log Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
