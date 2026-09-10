<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="display-6 fw-bold mb-0">Website Readiness & Overview</h2>
            <p class="text-muted mb-0">Manage your official sports club website status, branding, and content readiness.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/customize" class="btn btn-primary fw-bold rounded-3">
                <i class="bi bi-paint-bucket me-1"></i> Customize Website
            </a>
            <a href="/club/<?= htmlspecialchars($tenant['slug']) ?>" target="_blank" class="btn btn-outline-dark fw-bold rounded-3">
                <i class="bi bi-globe me-1"></i> View Live Website <i class="bi bi-box-arrow-up-right small ms-1"></i>
            </a>
        </div>
    </div>

    <!-- Website Completion Readiness Widget -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <div class="row align-items-center g-4">
            <div class="col-lg-4 text-center border-end-lg">
                <div class="text-muted small fw-bold text-uppercase mb-1">Website Readiness Score</div>
                <div class="display-3 fw-black text-<?= $readiness['score'] >= 80 ? 'success' : ($readiness['score'] >= 50 ? 'warning' : 'danger') ?>">
                    <?= $readiness['score'] ?>%
                </div>
                <span class="badge bg-<?= $readiness['is_ready'] ? 'success' : 'warning' ?> px-3 py-2 rounded-pill font-heading text-uppercase">
                    <?= $readiness['is_ready'] ? 'READY TO PUBLISH' : 'ACTION NEEDED' ?>
                </span>
            </div>
            <div class="col-lg-8">
                <h5 class="fw-bold mb-3"><i class="bi bi-card-checklist text-primary me-2"></i>Website Setup Checklist</h5>
                <div class="row g-3">
                    <?php foreach ($readiness['checklist'] as $item): ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($item['passed']): ?>
                                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <?php else: ?>
                                        <i class="bi bi-dash-circle text-danger fs-5"></i>
                                    <?php endif; ?>
                                    <span class="small font-semibold <?= $item['passed'] ? 'text-dark' : 'text-muted' ?>"><?= htmlspecialchars($item['label']) ?></span>
                                </div>
                                <?php if (!$item['passed']): ?>
                                    <a href="<?= $item['action_url'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2 small font-semibold">Fix</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- CMS Quick Navigation Modules -->
    <h4 class="fw-bold mb-3">Website Management Modules</h4>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="bg-primary-subtle text-primary p-3 rounded-3 mb-3 w-auto d-inline-block">
                    <i class="bi bi-palette fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Branding & Customization</h5>
                <p class="small text-muted mb-4">Set club crest, hero banner, tagline, primary/secondary colors, header style, and footer contact info.</p>
                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/customize" class="btn btn-outline-primary rounded-3 fw-bold mt-auto">Customize Identity</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="bg-success-subtle text-success p-3 rounded-3 mb-3 w-auto d-inline-block">
                    <i class="bi bi-layout-split fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Homepage Section Builder</h5>
                <p class="small text-muted mb-4">Enable, disable, and re-order homepage content blocks (Hero, Fixtures, Teams, News, Sponsors, etc.).</p>
                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/homepage" class="btn btn-outline-success rounded-3 fw-bold mt-auto">Organize Homepage</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="bg-warning-subtle text-dark p-3 rounded-3 mb-3 w-auto d-inline-block">
                    <i class="bi bi-sliders fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Navigation & Page Visibility</h5>
                <p class="small text-muted mb-4">Control which sub-pages are visible in the website navigation bar (About, Players, Fixtures, News, Gallery, etc.).</p>
                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/navigation" class="btn btn-outline-warning rounded-3 fw-bold mt-auto">Manage Navigation</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="bg-info-subtle text-info p-3 rounded-3 mb-3 w-auto d-inline-block">
                    <i class="bi bi-brush fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Website Themes</h5>
                <p class="small text-muted mb-4">Choose between 3 layouts: Modern Sport, Classic Club, or Dynamic Athletic.</p>
                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/themes" class="btn btn-outline-info rounded-3 fw-bold mt-auto">Select Theme</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="bg-danger-subtle text-danger p-3 rounded-3 mb-3 w-auto d-inline-block">
                    <i class="bi bi-clock-history fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Club History & Milestones</h5>
                <p class="small text-muted mb-4">Create a timeline of championships, founded dates, and honors.</p>
                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/history" class="btn btn-outline-danger rounded-3 fw-bold mt-auto">Manage Timeline</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <div class="bg-secondary-subtle text-secondary p-3 rounded-3 mb-3 w-auto d-inline-block">
                    <i class="bi bi-folder2-open fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2">Media Library</h5>
                <p class="small text-muted mb-4">Centralized tenant photo storage for logos, match snapshots, and banners.</p>
                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/media" class="btn btn-outline-secondary rounded-3 fw-bold mt-auto">Open Media Library</a>
            </div>
        </div>
    </div>
</div>
