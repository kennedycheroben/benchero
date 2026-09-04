<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid py-3 max-w-4xl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="display-6 fw-bold mb-0">Homepage Section Builder</h2>
            <p class="text-muted mb-0">Enable, disable, and re-order sections displayed on your club's public home page.</p>
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

    <form action="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/homepage" method="POST">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-layers text-success me-2"></i>Homepage Sections Manager</h5>

            <div class="d-grid gap-3">
                <?php foreach ($sections as $idx => $sec): ?>
                    <div class="p-3 border rounded-3 bg-light d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <input type="hidden" name="sections[<?= $idx ?>][id]" value="<?= htmlspecialchars($sec['id']) ?>">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input fs-4" type="checkbox" name="sections[<?= $idx ?>][is_visible]" value="1" <?= !empty($sec['is_visible']) ? 'checked' : '' ?>>
                            </div>
                            <div>
                                <span class="badge bg-secondary text-uppercase mb-1 small" style="font-size:0.7rem;"><?= htmlspecialchars($sec['section_type']) ?></span>
                                <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($sec['title'] ?: ucfirst($sec['section_type'])) ?></h6>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted fw-semibold">Order:</label>
                            <input type="number" name="sections[<?= $idx ?>][display_order]" value="<?= (int)$sec['display_order'] ?>" class="form-control form-control-sm rounded-2 text-center" style="width:70px;">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-success btn-lg rounded-pill fw-bold px-5">
                <i class="bi bi-check-lg me-1"></i> Save Homepage Layout
            </button>
        </div>
    </form>
</div>
