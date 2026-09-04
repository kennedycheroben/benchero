<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid py-3 max-w-5xl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="display-6 fw-bold mb-0">Club History & Timeline Milestones</h2>
            <p class="text-muted mb-0">Record founding dates, league championships, trophies, and major club achievements.</p>
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

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger rounded-4 border-0 shadow-sm p-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Add / Edit Form -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-danger me-2"></i>Add / Edit Milestone</h5>
                <form action="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/history" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <input type="hidden" name="id" value="">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Year / Date</label>
                        <input type="text" name="year_date" class="form-control rounded-3" placeholder="2018 or July 2020" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Milestone Title</label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="Club Founded / Regional Championship" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category" class="form-select rounded-3">
                            <option value="Milestone">General Milestone</option>
                            <option value="Championship">Championship / Trophy</option>
                            <option value="Foundation">Club Foundation</option>
                            <option value="Expansion">Expansion / Stadium</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="3" class="form-control rounded-3" placeholder="Details about this historical milestone..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Optional Image</label>
                        <input type="file" name="image_file" class="form-control rounded-3" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Display Order</label>
                        <input type="number" name="display_order" class="form-control rounded-3" value="0">
                    </div>

                    <button type="submit" class="btn btn-danger rounded-pill fw-bold w-100 py-2">
                        <i class="bi bi-check-lg me-1"></i> Save Milestone
                    </button>
                </form>
            </div>
        </div>

        <!-- Milestones List -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white p-3 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Recorded Timeline Milestones</h5>
                </div>

                <?php if (empty($history)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-clock-history display-4 mb-2 d-block"></i>
                        <p class="mb-0">No history milestones created yet.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($history as $h): ?>
                            <div class="list-group-item p-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary px-2 py-1 rounded-pill small fw-bold me-2"><?= htmlspecialchars($h['year_date']) ?></span>
                                    <strong class="text-dark text-uppercase"><?= htmlspecialchars($h['title']) ?></strong>
                                    <p class="small text-muted mb-0 mt-1"><?= htmlspecialchars($h['description']) ?></p>
                                </div>

                                <form action="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/history/<?= htmlspecialchars($h['id']) ?>/delete" method="POST" onsubmit="return confirm('Delete milestone?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-2">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
