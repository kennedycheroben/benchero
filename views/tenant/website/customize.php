<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid py-3 max-w-5xl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="display-6 fw-bold mb-0">Website Customization & Branding</h2>
            <p class="text-muted mb-0">Configure your official club crest, color palette, hero banner, and contact details.</p>
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

    <form action="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/customize" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

        <!-- Identity & Crest -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Club Crest & Identity</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Club Logo / Crest Upload</label>
                    <input type="file" name="logo_file" class="form-control rounded-3" accept="image/*">
                    <input type="hidden" name="logo_url" value="<?= htmlspecialchars($org['logo_url'] ?? '') ?>">
                    <?php if (!empty($org['logo_url'])): ?>
                        <div class="mt-2">
                            <img src="<?= htmlspecialchars($org['logo_url']) ?>" height="50" class="border p-1 bg-white rounded">
                            <small class="text-muted ms-2">Current Crest</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Club Description / Tagline</label>
                    <textarea name="description" rows="2" class="form-control rounded-3" placeholder="United by passion. Driven by victory."><?= htmlspecialchars($org['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Color Palette & Styles -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-palette text-warning me-2"></i>Brand Color Palette & Styling</h5>
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <label class="form-label fw-semibold">Primary Color</label>
                    <input type="color" name="primary_color" class="form-control form-control-color w-100 rounded-3" value="<?= htmlspecialchars($settings['primary_color'] ?? '#0d6efd') ?>">
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label fw-semibold">Secondary Color</label>
                    <input type="color" name="secondary_color" class="form-control form-control-color w-100 rounded-3" value="<?= htmlspecialchars($settings['secondary_color'] ?? '#1e293b') ?>">
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label fw-semibold">Accent Color</label>
                    <input type="color" name="accent_color" class="form-control form-control-color w-100 rounded-3" value="<?= htmlspecialchars($settings['accent_color'] ?? '#ffc107') ?>">
                </div>

                <div class="col-md-3 col-6">
                    <label class="form-label fw-semibold">Text Color</label>
                    <input type="color" name="text_color" class="form-control form-control-color w-100 rounded-3" value="<?= htmlspecialchars($settings['text_color'] ?? '#0f172a') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Header Theme Style</label>
                    <select name="header_style" class="form-select rounded-3">
                        <option value="dark" <?= ($settings['header_style'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>Dark Solid Header</option>
                        <option value="light" <?= ($settings['header_style'] ?? '') === 'light' ? 'selected' : '' ?>>Light Clean Header</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Button Corners Style</label>
                    <select name="button_style" class="form-select rounded-3">
                        <option value="pill" <?= ($settings['button_style'] ?? 'pill') === 'pill' ? 'selected' : '' ?>>Pill Rounded</option>
                        <option value="square" <?= ($settings['button_style'] ?? '') === 'square' ? 'selected' : '' ?>>Sharp Square</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Hero Section Config -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-image text-info me-2"></i>Homepage Hero Banner</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Hero Title Override</label>
                    <input type="text" name="hero_title" class="form-control rounded-3" value="<?= htmlspecialchars($settings['hero_title'] ?? '') ?>" placeholder="<?= htmlspecialchars($tenant['name']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Hero Subtitle</label>
                    <input type="text" name="hero_subtitle" class="form-control rounded-3" value="<?= htmlspecialchars($settings['hero_subtitle'] ?? '') ?>" placeholder="Official Sports Club Website">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Hero Call-To-Action Button Text</label>
                    <input type="text" name="hero_cta_text" class="form-control rounded-3" value="<?= htmlspecialchars($settings['hero_cta_text'] ?? '') ?>" placeholder="View Fixtures">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Hero CTA Button Link URL</label>
                    <input type="text" name="hero_cta_url" class="form-control rounded-3" value="<?= htmlspecialchars($settings['hero_cta_url'] ?? '') ?>" placeholder="/club/<?= htmlspecialchars($tenant['slug']) ?>/fixtures">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Hero Background Image</label>
                    <input type="file" name="hero_image_file" class="form-control rounded-3" accept="image/*">
                    <input type="hidden" name="hero_image_url" value="<?= htmlspecialchars($settings['hero_image_url'] ?? '') ?>">
                    <?php if (!empty($settings['hero_image_url'])): ?>
                        <div class="mt-2">
                            <img src="<?= htmlspecialchars($settings['hero_image_url']) ?>" style="height:80px; object-fit:cover;" class="rounded border">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Contact & Footer Config -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt text-danger me-2"></i>Contact Details & Footer</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Official Contact Email</label>
                    <input type="email" name="contact_email" class="form-control rounded-3" value="<?= htmlspecialchars($org['contact_email'] ?? '') ?>" placeholder="contact@club.com">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Phone Number</label>
                    <input type="text" name="contact_phone" class="form-control rounded-3" value="<?= htmlspecialchars($org['contact_phone'] ?? '') ?>" placeholder="+254 700 000 000">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Grounds / Physical Address</label>
                    <input type="text" name="address" class="form-control rounded-3" value="<?= htmlspecialchars($org['address'] ?? '') ?>" placeholder="Kenyatta Stadium, Nairobi">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Google Maps Link</label>
                    <input type="url" name="map_link" class="form-control rounded-3" value="<?= htmlspecialchars($settings['map_link'] ?? '') ?>" placeholder="https://maps.google.com/?q=...">
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold px-5">
                <i class="bi bi-check-lg me-1"></i> Save Customization & Branding
            </button>
        </div>
    </form>
</div>
