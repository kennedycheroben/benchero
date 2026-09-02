<?php $this->layout('layout', ['title' => 'Log In']); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold text-center mb-4">Welcome Back</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mb-4"><?= $this->e($error) ?></div>
                    <?php endif; ?>

                    <form action="<?= $this->url('/login') ?>" method="POST">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control form-control-lg" value="<?= isset($email) ? $this->e($email) : '' ?>" required autofocus placeholder="name@example.com">
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input type="password" id="password" name="password" class="form-control form-control-lg" required placeholder="password">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">Log In</button>
                    </form>

                    <div class="d-flex justify-content-between text-sm mt-3">
                        <a href="<?= $this->url('/register') ?>" class="text-decoration-none">Create an account</a>
                        <a href="<?= $this->url('/resend-verification') ?>" class="text-decoration-none">Resend verification email</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
