<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid py-3 max-w-4xl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="display-6 fw-bold mb-0">Navigation & Page Visibility</h2>
            <p class="text-muted mb-0">Control which pages are visible in your public website header navigation bar.</p>
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

    <form action="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/navigation" method="POST">
        <?= csrf_field() ?>

        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-compass text-warning me-2"></i>Public Page Visibility Toggles</h5>
            <p class="text-muted small mb-4">Toggling a page to hidden will hide it from the website navigation. No club data will be deleted.</p>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">Visible</th>
                            <th>Page Route</th>
                            <th>Navigation Label</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $pageVis = $settings['page_visibility'] ?? $defaultVisibility;
                            $labels = $settings['navigation_labels'] ?? $defaultLabels;
                        ?>
                        <?php foreach ($defaultVisibility as $pageKey => $defVis): ?>
                            <?php 
                                $isVis = $pageVis[$pageKey] ?? true;
                                $lbl = $labels[$pageKey] ?? ucfirst($pageKey);
                            ?>
                            <tr>
                                <td>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input fs-5" type="checkbox" name="visibility[<?= $pageKey ?>]" value="1" <?= $isVis ? 'checked' : '' ?>>
                                    </div>
                                </td>
                                <td>
                                    <code class="fw-bold text-primary">/club/<?= htmlspecialchars($tenant['slug']) ?>/<?= $pageKey ?></code>
                                </td>
                                <td>
                                    <input type="text" name="labels[<?= $pageKey ?>]" class="form-control form-control-sm rounded-2" value="<?= htmlspecialchars($lbl) ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-warning btn-lg rounded-pill fw-bold px-5 text-dark">
                <i class="bi bi-check-lg me-1"></i> Save Navigation Settings
            </button>
        </div>
    </form>
</div>
