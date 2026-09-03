<?php $this->layout('layout', ['title' => '419 Page Expired — Benchero']) ?>

<section class="py-5 bg-light min-vh-100 d-flex align-items-center text-center">
    <div class="container py-5">
        <div class="max-w-md mx-auto card border-0 shadow-sm rounded-4 p-5 bg-white">
            <span class="display-1 fw-extrabold text-warning mb-2">419</span>
            <h2 class="fw-bold mb-3">Session Expired</h2>
            <p class="text-muted mb-4">Your security token expired. Please refresh the page and try submitting again.</p>
            <div>
                <a href="javascript:history.back()" class="btn btn-primary btn-lg fw-bold rounded-3 px-4">
                    <i class="bi bi-arrow-left me-2"></i> Go Back & Refresh
                </a>
            </div>
        </div>
    </div>
</section>
