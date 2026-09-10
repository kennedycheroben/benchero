<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid py-3 max-w-5xl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="display-6 fw-bold mb-0">Club Website Themes</h2>
            <p class="text-muted mb-0">Select a design layout for your public club website. All themes dynamically use your club data.</p>
        </div>
        <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/website" class="btn btn-outline-secondary rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Back to Website Overview
        </a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success rounded-4 border-0 shadow-sm p-3 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <form action="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/themes" method="POST">
        <?= csrf_field() ?>

        <div class="row g-4 mb-4">
            <?php $activeTheme = $settings['theme_id'] ?? 'modern_sport'; ?>
            <?php foreach ($themes as $th): ?>
                <?php $isSelected = ($activeTheme === $th['id']); ?>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 <?= $isSelected ? 'border border-3 border-primary shadow-lg' : '' ?>">
                        <div class="p-4 text-white text-center" style="background-color: <?= $th['preview_color'] ?>;">
                            <span class="badge bg-white text-dark text-uppercase px-3 py-1 rounded-pill small fw-bold mb-2"><?= htmlspecialchars($th['badge']) ?></span>
                            <h3 class="fw-black text-uppercase mb-0"><?= htmlspecialchars($th['name']) ?></h3>
                        </div>
                        <div class="card-body p-4 d-flex flex-column bg-white">
                            <p class="small text-muted mb-4"><?= htmlspecialchars($th['description']) ?></p>
                            
                            <div class="form-check text-center mt-auto p-3 bg-light rounded-3">
                                <input class="form-check-input fs-5 float-none me-2" type="radio" name="theme_id" id="theme_<?= $th['id'] ?>" value="<?= $th['id'] ?>" <?= $isSelected ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-dark font-heading text-uppercase" for="theme_<?= $th['id'] ?>">
                                    <?= $isSelected ? 'Active Theme' : 'Select Theme' ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold px-5">
                <i class="bi bi-brush me-1"></i> Apply Theme Changes
            </button>
        </div>
    </form>
</div>
