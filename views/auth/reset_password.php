<?php $this->layout('layout', ['title' => 'Set New Password — Benchero']) ?>

<section class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-dark mb-1">New Password</h3>
                        <p class="text-muted small">Set a new secure password for your Benchero account.</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small mb-4">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('/reset-password') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold small">New Password</label>
                            <input type="password" id="password" name="password" class="form-control form-control-lg" placeholder="At least 8 characters" required>
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label fw-semibold small">Confirm New Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-lg" placeholder="Repeat password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-3 mb-3">
                            Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
