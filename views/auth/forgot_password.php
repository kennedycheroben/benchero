<?php $this->layout('layout', ['title' => 'Forgot Password — Benchero']) ?>

<section class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-dark mb-1">Reset Password</h3>
                        <p class="text-muted small">Enter your email and we'll send a password recovery link.</p>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success small mb-4">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small mb-4">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('/forgot-password') ?>" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold small">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control form-control-lg" placeholder="name@club.com" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-3 mb-3">
                            Send Reset Link
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="<?= url('/login') ?>" class="small text-decoration-none text-muted">
                            <i class="bi bi-arrow-left me-1"></i> Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
