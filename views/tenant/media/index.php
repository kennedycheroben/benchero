<?php $this->layout('layout', ['title' => 'Centralized Media Library — ' . htmlspecialchars($org['name'])]); ?>

<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Centralized Media Library</h1>
            <p class="text-muted mb-0">Upload once, reuse everywhere across your public club website and team pages.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#uploadMediaModal">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Asset
            </button>
            <a href="/o/<?= htmlspecialchars($org['slug']) ?>/dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Storage Usage Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-hdd-stack me-2 text-primary"></i>Media Storage Quota</h6>
                <span class="small text-muted fw-semibold">
                    <?= htmlspecialchars($storageUsage['total_mb']) ?> MB used of <?= htmlspecialchars($storageUsage['total_quota_mb']) ?> MB capacity
                </span>
            </div>
            <div class="progress rounded-pill mb-2" style="height: 10px;">
                <?php 
                    $pct = min(100, round(($storageUsage['total_mb'] / max(1, $storageUsage['total_quota_mb'])) * 100));
                    $barClass = $pct > 85 ? 'bg-danger' : ($pct > 65 ? 'bg-warning' : 'bg-primary');
                ?>
                <div class="progress-bar <?= $barClass ?>" role="progressbar" style="width: <?= $pct ?>%;"></div>
            </div>
            <div class="d-flex justify-content-between small text-muted">
                <span>Photos: <?= htmlspecialchars($storageUsage['image_mb']) ?> MB</span>
                <span>Videos: <?= htmlspecialchars($storageUsage['video_mb']) ?> MB (Quota: <?= htmlspecialchars($storageUsage['video_quota_mb']) ?> MB)</span>
            </div>
        </div>
    </div>

    <!-- Category Tabs -->
    <ul class="nav nav-pills mb-4 gap-1">
        <li class="nav-item">
            <a class="nav-link <?= empty($currentCategory) ? 'active fw-bold' : '' ?>" href="/o/<?= htmlspecialchars($org['slug']) ?>/media">All Assets</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentCategory === 'logo' ? 'active fw-bold' : '' ?>" href="/o/<?= htmlspecialchars($org['slug']) ?>/media?category=logo">Club Crests</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentCategory === 'team' ? 'active fw-bold' : '' ?>" href="/o/<?= htmlspecialchars($org['slug']) ?>/media?category=team">Teams</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentCategory === 'player' ? 'active fw-bold' : '' ?>" href="/o/<?= htmlspecialchars($org['slug']) ?>/media?category=player">Players</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentCategory === 'news' ? 'active fw-bold' : '' ?>" href="/o/<?= htmlspecialchars($org['slug']) ?>/media?category=news">News</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentCategory === 'gallery' ? 'active fw-bold' : '' ?>" href="/o/<?= htmlspecialchars($org['slug']) ?>/media?category=gallery">Gallery</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentCategory === 'video' ? 'active fw-bold' : '' ?>" href="/o/<?= htmlspecialchars($org['slug']) ?>/media?category=video">Videos</a>
        </li>
    </ul>

    <!-- Media Grid -->
    <?php if (empty($mediaList)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center p-5">
            <i class="bi bi-images text-muted fs-1 mb-2"></i>
            <h5 class="fw-bold mb-1">No media files found</h5>
            <p class="text-muted small mb-3">Upload logos, team photos, match highlights, or news images to populate your library.</p>
            <div>
                <button class="btn btn-primary btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#uploadMediaModal">
                    Upload Your First File
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($mediaList as $item): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden media-card">
                        <div class="ratio ratio-4x3 bg-dark">
                            <?php if (str_starts_with($item['mime_type'], 'video/')): ?>
                                <video src="<?= htmlspecialchars($item['file_url']) ?>" controls class="object-fit-cover"></video>
                            <?php else: ?>
                                <img src="<?= htmlspecialchars($item['file_url']) ?>" alt="<?= htmlspecialchars($item['alt_text'] ?: $item['filename']) ?>" class="object-fit-cover">
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-secondary text-uppercase small" style="font-size: 10px;"><?= htmlspecialchars($item['category']) ?></span>
                                    <span class="small text-muted" style="font-size: 11px;"><?= round($item['file_size'] / 1024, 1) ?> KB</span>
                                </div>
                                <p class="small text-truncate mb-2 fw-semibold" title="<?= htmlspecialchars($item['filename']) ?>">
                                    <?= htmlspecialchars($item['filename']) ?>
                                </p>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($item['file_url']) ?>'); alert('Image URL copied to clipboard!');">
                                    <i class="bi bi-link-45deg me-1"></i> Copy URL
                                </button>
                                <form action="/o/<?= htmlspecialchars($org['slug']) ?>/media/<?= htmlspecialchars($item['id']) ?>/delete" method="POST" onsubmit="return confirm('Delete this asset?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadMediaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <form action="/o/<?= htmlspecialchars($org['slug']) ?>/media" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Upload to Media Library</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Asset Type</label>
                        <select class="form-select" name="type" id="assetTypeSelect" onchange="document.getElementById('videoNotice').style.display = (this.value === 'video' ? 'block' : 'none');">
                            <option value="image">Photo / Graphic Image (JPEG, PNG, WEBP, GIF)</option>
                            <option value="video" <?= !$hasVideoPro ? 'disabled' : '' ?>>Match / Training Video (MP4, WEBM, MOV) <?= !$hasVideoPro ? '— Pro Plan Required' : '' ?></option>
                        </select>
                    </div>

                    <div class="alert alert-warning small mb-3" id="videoNotice" style="display: none;">
                        <i class="bi bi-camera-video-fill me-1"></i> Videos must be under 50 MB and belong to your organization's match highlights or announcements.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Tag</label>
                        <select class="form-select" name="category">
                            <option value="general">General</option>
                            <option value="logo">Club Crest / Logo</option>
                            <option value="team">Team Photo</option>
                            <option value="player">Player Photo</option>
                            <option value="staff">Staff Photo</option>
                            <option value="news">News Article Image</option>
                            <option value="gallery">Gallery Photo</option>
                            <option value="sponsor">Sponsor Logo</option>
                            <option value="matches">Match Highlights</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select File</label>
                        <input type="file" class="form-control" name="file" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alt Text (Optional)</label>
                        <input type="text" class="form-control" name="alt_text" placeholder="Short description for accessibility">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>
