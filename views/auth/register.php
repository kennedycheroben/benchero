<?php $this->layout('layout', ['title' => 'Register']); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h2 class="fw-bold text-center mb-4">Create an Account</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mb-4"><?= $this->e($error) ?></div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success mb-4"><?= $this->e($success) ?></div>
                    <?php else: ?>
                        <form action="<?= $this->url('/register') ?>" method="POST">
                            <?= csrf_field() ?>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Full Name</label>
                                <input type="text" id="name" name="name" class="form-control form-control-lg" value="<?= isset($name) ? $this->e($name) : '' ?>" required placeholder="John Doe">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control form-control-lg" value="<?= isset($email) ? $this->e($email) : '' ?>" required placeholder="name@example.com">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label fw-semibold">Password</label>
                                    <input type="password" id="password" name="password" class="form-control form-control-lg" required minlength="8" placeholder="At least 8 chars">
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-lg" required placeholder="Repeat password">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">Create Account</button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="<?= $this->url('/login') ?>" class="text-decoration-none">Already have an account? Log in</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
