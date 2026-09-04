<?php require __DIR__ . '/../layouts/main.php'; ?>

<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="display-6 fw-bold mb-0">Tenant Media Library</h2>
            <p class="text-muted mb-0">Upload and manage isolated photo assets for your club crest, hero banners, and match galleries.</p>
        </div>
        <button type="button" class="btn btn-primary fw-bold rounded-3" data-bs-toggle="modal" data-bs-target="#uploadMediaModal">
            <i class="bi bi-cloud-upload me-1"></i> Upload Image
        </button>
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

    <!-- Media Grid -->
    <?php if (empty($mediaList)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <i class="bi bi-images display-3 text-muted mb-3"></i>
            <h4 class="fw-bold">No Media Files Uploaded</h4>
            <p class="text-muted mb-0">Upload images to populate your club crest, hero banners, and photo gallery.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($mediaList as $media): ?>
                <div class="col-md-3 col-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white h-100 position-relative group">
                        <img src="<?= htmlspecialchars($media['file_url']) ?>" alt="<?= htmlspecialchars($media['alt_text'] ?: 'Media') ?>" class="w-100 object-fit-cover" style="height:180px;">
                        <div class="p-3 bg-white">
                            <span class="badge bg-secondary-subtle text-secondary small fw-bold text-uppercase mb-1"><?= htmlspecialchars($media['category']) ?></span>
                            <div class="text-truncate small fw-semibold text-dark"><?= htmlspecialchars($media['filename']) ?></div>
                            <small class="text-muted d-block mb-2"><?= round($media['file_size'] / 1024, 1) ?> KB</small>
                            
                            <form action="/o/<?= htmlspecialchars($tenant['slug']) ?>/media/<?= htmlspecialchars($media['id']) ?>/delete" method="POST" onsubmit="return confirm('Delete media file?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill w-100 fw-bold">
                                    <i class="bi bi-trash me-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Upload Media Modal -->
<div class="modal fade" id="uploadMediaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold"><i class="bi bi-cloud-upload text-primary me-2"></i>Upload Image Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/o/<?= htmlspecialchars($tenant['slug']) ?>/media" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select File (JPEG, PNG, WEBP, GIF — max 5MB)</label>
                        <input type="file" name="file" class="form-control rounded-3" accept="image/*" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category" class="form-select rounded-3">
                            <option value="general">General Asset</option>
                            <option value="branding">Crest / Branding</option>
                            <option value="hero">Homepage Hero</option>
                            <option value="gallery">Photo Gallery</option>
                            <option value="news">News Cover</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alt Text (Optional)</label>
                        <input type="text" name="alt_text" class="form-control rounded-3" placeholder="Image description for accessibility">
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-light rounded-pill fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4">Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>
