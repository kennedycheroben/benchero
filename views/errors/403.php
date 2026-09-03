<?php $this->layout('layout', ['title' => '403 Access Forbidden — Benchero']) ?>

<section class="py-5 bg-light min-vh-100 d-flex align-items-center text-center">
    <div class="container py-5">
        <div class="max-w-md mx-auto card border-0 shadow-sm rounded-4 p-5 bg-white">
            <span class="display-1 fw-extrabold text-danger mb-2">403</span>
            <h2 class="fw-bold mb-3">Access Forbidden</h2>
            <p class="text-muted mb-4">You do not have permission to view this resource or manage this club.</p>
            <div>
                <a href="<?= url('/organizations') ?>" class="btn btn-primary btn-lg fw-bold rounded-3 px-4">
                    My Organizations
                </a>
            </div>
        </div>
    </div>
</section>
