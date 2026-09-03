<?php $this->layout('layout', ['title' => '404 Page Not Found — Benchero']) ?>

<section class="py-5 bg-light min-vh-100 d-flex align-items-center text-center">
    <div class="container py-5">
        <div class="max-w-md mx-auto card border-0 shadow-sm rounded-4 p-5 bg-white">
            <span class="display-1 fw-extrabold text-primary mb-2">404</span>
            <h2 class="fw-bold mb-3">Page Not Found</h2>
            <p class="text-muted mb-4">The page or club route you were looking for does not exist or has been moved.</p>
            <div>
                <a href="<?= url('/') ?>" class="btn btn-primary btn-lg fw-bold rounded-3 px-4">
                    <i class="bi bi-house me-2"></i> Return Home
                </a>
            </div>
        </div>
    </div>
</section>
