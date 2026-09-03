<?php $this->layout('layout', ['title' => 'Create Account — Benchero']); ?>

<div class="container py-5 min-vh-100 d-flex align-items-center">
    <div class="row justify-content-center w-100">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 rounded-pill mb-2">BENCHERO</span>
                        <h2 class="fw-bold mb-1">Create Your Account</h2>
                        <p class="text-muted small">Start managing your sports club and teams today.</p>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mb-4 small"><?= $this->e($error) ?></div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success mb-4 small"><?= $this->e($success) ?></div>
                    <?php else: ?>
                        <form action="<?= $this->url('/register') ?>" method="POST">
                            <?= csrf_field() ?>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold small">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control form-control-lg" value="<?= isset($name) ? $this->e($name) : '' ?>" required placeholder="John Doe">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold small">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control form-control-lg" value="<?= isset($email) ? $this->e($email) : '' ?>" required placeholder="name@club.com">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label fw-semibold small">Password</label>
                                    <input type="password" id="password" name="password" class="form-control form-control-lg" required minlength="8" placeholder="At least 8 chars">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="password_confirmation" class="form-label fw-semibold small">Confirm Password</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-lg" required placeholder="Repeat password">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-3 mb-3">Start Free Trial</button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="<?= $this->url('/login') ?>" class="small text-decoration-none text-muted">Already have an account? <span class="fw-bold text-primary">Log in</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
