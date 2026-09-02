<?php $this->layout('layout', ['title' => 'Email Verification']); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold mb-4">Email Verification</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mb-4"><?= $this->e($error) ?></div>
                        <a href="<?= $this->url('/resend-verification') ?>" class="btn btn-outline-primary w-100">Request New Link</a>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success mb-4"><?= $this->e($success) ?></div>
                        <a href="<?= $this->url('/login') ?>" class="btn btn-primary btn-lg w-100">Go to Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
