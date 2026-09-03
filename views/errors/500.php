<?php $this->layout('layout', ['title' => '500 Server Error — Benchero']) ?>

<section class="py-5 bg-light min-vh-100 d-flex align-items-center text-center">
    <div class="container py-5">
        <div class="max-w-md mx-auto card border-0 shadow-sm rounded-4 p-5 bg-white">
            <span class="display-1 fw-extrabold text-secondary mb-2">500</span>
            <h2 class="fw-bold mb-3">Server Error</h2>
            <p class="text-muted mb-4">Something went wrong on our servers. Our technical team has been notified.</p>
            <div>
                <a href="<?= url('/') ?>" class="btn btn-primary btn-lg fw-bold rounded-3 px-4">
                    Return Home
                </a>
            </div>
        </div>
    </div>
</section>
