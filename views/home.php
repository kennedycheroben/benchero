<?php $this->layout('layout', ['title' => $title]) ?>

<div class="container py-5 text-center">
    <h1 class="display-4 fw-bold mb-3"><?= $this->e(env('BRAND_NAME', 'Teamora')) ?></h1>
    <p class="lead text-muted mb-4"><?= $this->e(env('BRAND_TAGLINE', 'One platform. Every team.')) ?></p>
    <a href="/register" class="btn btn-primary btn-lg px-5">Start Free Trial</a>
    <a href="/features" class="btn btn-outline-secondary btn-lg px-5 ms-3">Explore Features</a>
</div>
