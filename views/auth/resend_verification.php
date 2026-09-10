<?php $this->layout('layout', ['title' => 'Resend Verification']); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold text-center mb-4">Resend Verification Email</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mb-4"><?= $this->e($error) ?></div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success mb-4"><?= $this->e($success) ?></div>
                    <?php else: ?>
                        <form action="<?= $this->url('/resend-verification') ?>" method="POST">
                            <?= csrf_field() ?>
                            
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control form-control-lg" required placeholder="name@example.com">
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">Resend Verification Link</button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="<?= $this->url('/login') ?>" class="text-decoration-none">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
