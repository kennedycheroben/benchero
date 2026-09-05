<?php $this->layout('layout', ['title' => 'Log In — Benchero']); ?>

<div class="container py-5 min-vh-100 d-flex align-items-center">
    <div class="row justify-content-center w-100">
        <div class="col-md-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero Logo" height="56" class="mb-3 rounded-3 shadow-sm">
                        <h2 class="fw-bold mb-1">Welcome Back</h2>
                        <p class="text-muted small">Log in to manage your club and teams.</p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mb-4 small"><?= $this->e($error) ?></div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success mb-4 small"><?= $this->e($success) ?></div>
                    <?php endif; ?>

                    <form action="<?= $this->url('/login') ?>" method="POST">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control form-control-lg" value="<?= isset($email) ? $this->e($email) : '' ?>" required autofocus placeholder="name@club.com">
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label fw-semibold small mb-0">Password</label>
                                <a href="<?= $this->url('/forgot-password') ?>" class="small text-decoration-none text-primary">Forgot password?</a>
                            </div>
                            <input type="password" id="password" name="password" class="form-control form-control-lg" required placeholder="••••••••">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-3 mb-3">Log In</button>
                    </form>

                    <div class="d-flex flex-column gap-2 text-center small mt-3">
                        <div>Don't have an account? <a href="<?= $this->url('/register') ?>" class="fw-bold text-decoration-none">Create a club account</a></div>
                        <div>Need email verification? <a href="<?= $this->url('/verify-email/resend') ?>" class="text-decoration-none text-muted">Resend link</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
