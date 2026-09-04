<?php $this->layout('layout', ['title' => 'Edit Club Profile — Benchero']) ?>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <span class="badge bg-primary px-3 py-2 rounded-pill text-uppercase fw-bold mb-1">Club Management</span>
            <h2 class="display-6 fw-bold mb-0">Club Profile & Branding</h2>
            <p class="text-muted mb-0">Configure your club details, logo, cover image, colors, and public contact information.</p>
        </div>
        <a href="/club/<?= htmlspecialchars($tenant['slug']) ?>" target="_blank" class="btn btn-outline-primary fw-bold rounded-3">
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

    <form method="POST" action="/o/<?= htmlspecialchars($tenant['slug']) ?>/profile" enctype="multipart/form-data" class="needs-validation">
        <?= csrf_field() ?>

        <div class="row g-4">
            <!-- Basic Details Card -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-shield-shaded text-primary me-2"></i>General Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Club Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?= htmlspecialchars($org['name'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="country" class="form-label fw-semibold">Country Code</label>
                                <input type="text" class="form-control text-uppercase" id="country" name="country" maxlength="2" value="<?= htmlspecialchars($org['country'] ?? 'KE') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="founded_year" class="form-label fw-semibold">Founded Year</label>
                                <input type="number" class="form-control" id="founded_year" name="founded_year" placeholder="e.g. 1995" value="<?= htmlspecialchars($org['founded_year'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label for="timezone" class="form-label fw-semibold">Timezone</label>
                                <input type="text" class="form-control" id="timezone" name="timezone" value="<?= htmlspecialchars($org['timezone'] ?? 'Africa/Nairobi') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="club_colors" class="form-label fw-semibold">Club Colors</label>
                                <input type="text" class="form-control" id="club_colors" name="club_colors" placeholder="e.g. Navy Blue & Gold" value="<?= htmlspecialchars($org['club_colors'] ?? '') ?>">
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label fw-semibold">Club Bio / Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Tell fans, players, and sponsors about your club's history and mission..."><?= htmlspecialchars($org['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branding & Media Card -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-image text-info me-2"></i>Logo & Banner Images</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="logo_file" class="form-label fw-semibold">Upload Club Logo File</label>
                                <input type="file" class="form-control" id="logo_file" name="logo_file" accept="image/png,image/jpeg,image/webp">
                                <input type="hidden" name="logo_url" value="<?= htmlspecialchars($org['logo_url'] ?? '') ?>">
                                <div class="form-text">PNG, JPG, WEBP formats. Max file size: 2 MB (2,097,152 bytes).</div>
                                <?php if (!empty($org['logo_url'])): ?>
                                    <div class="mt-2 d-flex align-items-center">
                                        <span class="me-2 small text-muted">Current Logo:</span>
                                        <img src="<?= htmlspecialchars($org['logo_url']) ?>" alt="Club Logo" class="rounded border" style="height: 40px; width: 40px; object-fit: contain;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="cover_url" class="form-label fw-semibold">Hero Banner Cover URL</label>
                                <input type="url" class="form-control" id="cover_url" name="cover_url" placeholder="https://example.com/cover.jpg" value="<?= htmlspecialchars($org['cover_url'] ?? '') ?>">
                                <div class="form-text">Wide ratio (e.g. 1920x600) cover photo URL for top header.</div>
                            </div>
                            <div class="col-12">
                                <label for="featured_video_url" class="form-label fw-semibold">Featured Video Link (YouTube / Vimeo)</label>
                                <input type="url" class="form-control" id="featured_video_url" name="featured_video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= htmlspecialchars($org['featured_video_url'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact & Social Links Sidebar -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-telephone text-success me-2"></i>Contact Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="contact_email" class="form-label fw-semibold">Public Email</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="info@club.co.ke" value="<?= htmlspecialchars($org['contact_email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="contact_phone" class="form-label fw-semibold">Public Phone / WhatsApp</label>
                            <input type="text" class="form-control" id="contact_phone" name="contact_phone" placeholder="+254 700 000 000" value="<?= htmlspecialchars($org['contact_phone'] ?? '') ?>">
                        </div>
                        <div class="mb-0">
                            <label for="address" class="form-label fw-semibold">Home Stadium / Address</label>
                            <input type="text" class="form-control" id="address" name="address" placeholder="Nairobi Stadium, Kenya" value="<?= htmlspecialchars($org['address'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold mb-0"><i class="bi bi-share text-warning me-2"></i>Social Media Links</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="facebook" class="form-label small fw-semibold"><i class="bi bi-facebook text-primary me-1"></i> Facebook URL</label>
                            <input type="url" class="form-control form-control-sm" id="facebook" name="facebook" placeholder="https://facebook.com/yourclub" value="<?= htmlspecialchars($socialLinks['facebook'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="instagram" class="form-label small fw-semibold"><i class="bi bi-instagram text-danger me-1"></i> Instagram URL</label>
                            <input type="url" class="form-control form-control-sm" id="instagram" name="instagram" placeholder="https://instagram.com/yourclub" value="<?= htmlspecialchars($socialLinks['instagram'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="x" class="form-label small fw-semibold"><i class="bi bi-twitter-x text-dark me-1"></i> X (Twitter) URL</label>
                            <input type="url" class="form-control form-control-sm" id="x" name="x" placeholder="https://x.com/yourclub" value="<?= htmlspecialchars($socialLinks['x'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="tiktok" class="form-label small fw-semibold"><i class="bi bi-tiktok text-dark me-1"></i> TikTok URL</label>
                            <input type="url" class="form-control form-control-sm" id="tiktok" name="tiktok" placeholder="https://tiktok.com/@yourclub" value="<?= htmlspecialchars($socialLinks['tiktok'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="youtube" class="form-label small fw-semibold"><i class="bi bi-youtube text-danger me-1"></i> YouTube Channel URL</label>
                            <input type="url" class="form-control form-control-sm" id="youtube" name="youtube" placeholder="https://youtube.com/@yourclub" value="<?= htmlspecialchars($socialLinks['youtube'] ?? '') ?>">
                        </div>
                        <div class="mb-0">
                            <label for="whatsapp" class="form-label small fw-semibold"><i class="bi bi-whatsapp text-success me-1"></i> WhatsApp Group/Contact</label>
                            <input type="url" class="form-control form-control-sm" id="whatsapp" name="whatsapp" placeholder="https://chat.whatsapp.com/..." value="<?= htmlspecialchars($socialLinks['whatsapp'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold rounded-3 shadow">
                    <i class="bi bi-check2-circle me-1"></i> Save Profile Changes
                </button>
            </div>
        </div>
    </form>
</div>
