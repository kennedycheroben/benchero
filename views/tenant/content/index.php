<?php $this->layout('layout', ['title' => 'Content & Media Management — Benchero']) ?>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <span class="badge bg-info text-dark px-3 py-2 rounded-pill text-uppercase fw-bold mb-1">Club Media</span>
            <h2 class="display-6 fw-bold mb-0">Content & Media Center</h2>
            <p class="text-muted mb-0">Publish news, announcements, matchday photos, and sponsor logos on your public website.</p>
        </div>
        <a href="<?= url("/club/") ?><?= htmlspecialchars($tenant['slug']) ?>" target="_blank" class="btn btn-outline-primary fw-bold rounded-3">
            <i class="bi bi-box-arrow-up-right me-1"></i> Preview Public Page
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills nav-fill bg-white shadow-sm p-2 rounded-4 mb-4" id="contentTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link fw-bold rounded-3 <?= $tab === 'news' ? 'active' : '' ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/content?tab=news') ?>">
                <i class="bi bi-newspaper me-1"></i> News & Announcements
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold rounded-3 <?= $tab === 'gallery' ? 'active' : '' ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/content?tab=gallery') ?>">
                <i class="bi bi-images me-1"></i> Photo Gallery
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-bold rounded-3 <?= $tab === 'sponsors' ? 'active' : '' ?>" href="<?= url('/o/' . urlencode($tenant['slug']) . '/content?tab=sponsors') ?>">
                <i class="bi bi-award me-1"></i> Club Sponsors
            </a>
        </li>
    </ul>

    <!-- Tab 1: News & Announcements -->
    <?php if ($tab === 'news'): ?>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square text-primary me-2"></i>Publish News Article</h5>
                    <form method="POST" action="<?= url('/o/' . urlencode($tenant['slug']) . '/content/news') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Article Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="e.g. Pre-season training session underway">
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label fw-semibold">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="General">General</option>
                                <option value="Match Report">Match Report</option>
                                <option value="Announcement">Announcement</option>
                                <option value="Squad Update">Squad Update</option>
                                <option value="Event">Event</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="image_file" class="form-label fw-semibold">Feature Image File Upload</label>
                            <input type="file" class="form-control" id="image_file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif">
                            <div class="form-text">Upload news article image (Max size: 2 MB).</div>
                        </div>
                        <div class="mb-3">
                            <label for="excerpt" class="form-label fw-semibold">Short Summary / Excerpt</label>
                            <input type="text" class="form-control" id="excerpt" name="excerpt" placeholder="Short description for preview card">
                        </div>
                        <div class="mb-4">
                            <label for="content" class="form-label fw-semibold">Full Article Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="6" required placeholder="Write full article here..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 py-2">
                            <i class="bi bi-send me-1"></i> Publish Article
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Published Articles (<?= count($news) ?>)</h5>
                    </div>
                    <?php if (empty($news)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-newspaper display-4 mb-2 d-block"></i>
                            <p class="mb-0">No published news articles yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($news as $item): ?>
                                <div class="list-group-item p-4">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <span class="badge bg-primary-subtle text-primary mb-1"><?= htmlspecialchars($item['category']) ?></span>
                                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($item['title']) ?></h5>
                                            <p class="text-muted small mb-2"><?= htmlspecialchars($item['excerpt'] ?: substr(strip_tags($item['content']), 0, 120)) ?></p>
                                            <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M j, Y H:i', strtotime($item['published_at'])) ?></small>
                                        </div>
                                        <form method="POST" action="<?= url('/o/' . urlencode($tenant['slug']) . '/content/news/' . urlencode($item['id']) . '/delete') ?>" onsubmit="return confirm('Delete this news article?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-3"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <!-- Tab 2: Photo Gallery -->
    <?php elseif ($tab === 'gallery'): ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-3"><i class="bi bi-upload text-info me-2"></i>Add Gallery Photo</h5>
                    <form method="POST" action="<?= url('/o/' . urlencode($tenant['slug']) . '/content/gallery') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Photo Title / Caption</label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Victory Celebration">
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label fw-semibold">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="Matchday">Matchday</option>
                                <option value="Training">Training</option>
                                <option value="Events">Events</option>
                                <option value="Club">Club & Facilities</option>
                                <option value="Players">Players</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="image_file" class="form-label fw-semibold">Upload Image File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="image_file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif" required>
                            <div class="form-text">Upload gallery photo (Max size: 2 MB).</div>
                        </div>
                        <button type="submit" class="btn btn-info text-white w-100 fw-bold rounded-3 py-2">
                            <i class="bi bi-plus-lg me-1"></i> Add Photo to Gallery
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-4">Gallery Items (<?= count($gallery) ?>)</h5>
                    <?php if (empty($gallery)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-images display-4 mb-2 d-block"></i>
                            <p class="mb-0">No gallery photos added yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($gallery as $img): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100 position-relative">
                                        <img src="<?= htmlspecialchars($img['image_url']) ?>" class="card-img-top" style="height: 160px; object-fit: cover;" alt="<?= htmlspecialchars($img['title'] ?? 'Gallery') ?>">
                                        <div class="card-body p-2 d-flex justify-content-between align-items-center bg-light">
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($img['category']) ?></span>
                                            <form method="POST" action="<?= url('/o/' . urlencode($tenant['slug']) . '/content/gallery/' . urlencode($img['id']) . '/delete') ?>" onsubmit="return confirm('Delete this image?');" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm text-danger p-0 border-0"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <!-- Tab 3: Sponsors -->
    <?php elseif ($tab === 'sponsors'): ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-3"><i class="bi bi-award text-warning me-2"></i>Add Sponsor / Partner</h5>
                    <form method="POST" action="<?= url('/o/' . urlencode($tenant['slug']) . '/content/sponsors') ?>" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Sponsor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. Safaricom / KCB Bank">
                        </div>
                        <div class="mb-3">
                            <label for="sponsor_level" class="form-label fw-semibold">Sponsor Tier / Level</label>
                            <select class="form-select" id="sponsor_level" name="sponsor_level">
                                <option value="Title Sponsor">Title Sponsor</option>
                                <option value="Main Partner">Main Partner</option>
                                <option value="Official Partner">Official Partner</option>
                                <option value="Kit Sponsor">Kit Sponsor</option>
                                <option value="Equipment Partner">Equipment Partner</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="logo_file" class="form-label fw-semibold">Upload Logo Image File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/png,image/jpeg,image/webp" required>
                            <div class="form-text">Upload sponsor logo (Max size: 2 MB).</div>
                        </div>
                        <div class="mb-4">
                            <label for="website_url" class="form-label fw-semibold">Website Link (Optional)</label>
                            <input type="url" class="form-control" id="website_url" name="website_url" placeholder="https://sponsor-website.com">
                        </div>
                        <button type="submit" class="btn btn-warning text-dark w-100 fw-bold rounded-3 py-2">
                            <i class="bi bi-plus-lg me-1"></i> Add Sponsor
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-4">Sponsors & Official Partners (<?= count($sponsors) ?>)</h5>
                    <?php if (empty($sponsors)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-award display-4 mb-2 d-block"></i>
                            <p class="mb-0">No sponsors or partners added yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($sponsors as $s): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="card border border-light-subtle shadow-sm rounded-4 p-3 text-center bg-white h-100 d-flex flex-column align-items-center justify-content-between">
                                        <img src="<?= htmlspecialchars($s['logo_url']) ?>" style="max-height: 70px; max-width: 140px; object-fit: contain;" alt="<?= htmlspecialchars($s['name']) ?>" class="mb-2">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($s['name']) ?></h6>
                                            <span class="badge bg-warning-subtle text-dark small mb-2"><?= htmlspecialchars($s['sponsor_level']) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between w-100 align-items-center mt-2 pt-2 border-top">
                                            <?php if ($s['website_url']): ?>
                                                <a href="<?= htmlspecialchars($s['website_url']) ?>" target="_blank" class="small text-decoration-none text-muted"><i class="bi bi-link-45deg me-1"></i>Website</a>
                                            <?php else: ?>
                                                <span></span>
                                            <?php endif; ?>
                                            <form method="POST" action="<?= url('/o/' . urlencode($tenant['slug']) . '/content/sponsors/' . urlencode($s['id']) . '/delete') ?>" onsubmit="return confirm('Remove sponsor?');" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm text-danger border-0 p-0"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
