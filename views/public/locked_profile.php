<?php $this->layout('layout', ['title' => 'Profile Temporarily Unavailable — Benchero']) ?>

<head>
    <meta name="robots" content="noindex, nofollow">
</head>

<section class="py-5 bg-light min-vh-75 d-flex align-items-center">
    <div class="container text-center py-5">
        <div class="max-w-xl mx-auto bg-white p-5 rounded-4 shadow-sm border border-light">
            <div class="mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle p-4 mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-shield-lock display-5"></i>
                </div>
            </div>

            <h1 class="h2 fw-extrabold text-dark mb-3">This profile is temporarily unavailable</h1>

            <p class="text-muted lead fs-6 mb-4 px-md-3">
                This club's Benchero profile is currently unavailable because its subscription has ended. The profile may become available again when the club returns to Benchero.
            </p>

            <div class="pt-2">
                <a href="<?= url('/') ?>" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-compass me-2"></i>Explore Benchero
                </a>
            </div>

            <div class="mt-4 pt-3 border-top text-muted small">
                <span class="fw-semibold text-secondary">Benchero</span> — Platform for Clubs, Teams & Sports Operations
            </div>
        </div>
    </div>
</section>
