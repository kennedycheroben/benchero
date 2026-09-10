<?php
// Standalone Mobile Digital Club Card Layout
$seo = $seo ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seo['title'] ?? ($org['name'] . ' — Digital Club Card')) ?></title>

    <meta name="description" content="<?= htmlspecialchars($seo['description'] ?? '') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($seo['canonical_url'] ?? '') ?>">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($seo['og_title'] ?? '') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seo['og_description'] ?? '') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($seo['og_image'] ?? '') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($seo['og_url'] ?? '') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/benchero-motion.css') ?>">
    <style>
        body { background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, sans-serif; color: white; padding: 20px; }
        .card-container { max-width: 440px; width: 100%; border-radius: 24px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
        .qr-wrapper { background: white; border-radius: 18px; padding: 15px; display: inline-block; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

<div class="card-container benchero-page-content p-4 text-center">
    <div class="mb-3">
        <img src="<?= htmlspecialchars($org['logo_url'] ?: '/images/benchero_logo.png') ?>" alt="<?= htmlspecialchars($org['name']) ?>" class="rounded-circle shadow p-1 bg-white" style="width: 100px; height: 100px; object-fit: contain;">
    </div>

    <h2 class="fw-extrabold mb-1"><?= htmlspecialchars($org['name']) ?></h2>
    <span class="badge bg-primary px-3 py-2 rounded-pill mb-3">OFFICIAL DIGITAL CLUB CARD</span>

    <div class="qr-wrapper mb-3">
        <?= $qr_code_svg ?>
    </div>

    <p class="small text-white-50 mb-3">
        <?= htmlspecialchars($org['description'] ?: 'Official digital presence powered by Benchero.') ?>
    </p>

    <div class="d-flex justify-content-center gap-3 mb-4 text-center">
        <?php if (!empty($org['contact_email'])): ?>
            <a href="mailto:<?= htmlspecialchars($org['contact_email']) ?>" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-envelope me-1"></i> Email</a>
        <?php endif; ?>
        <?php if (!empty($org['contact_phone'])): ?>
            <a href="tel:<?= htmlspecialchars($org['contact_phone']) ?>" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-telephone me-1"></i> Call</a>
        <?php endif; ?>
        <a href="/club/<?= htmlspecialchars($org['slug']) ?>" class="btn btn-primary btn-sm rounded-pill px-3"><i class="bi bi-globe me-1"></i> Full Website</a>
    </div>

    <!-- Web Share API / Copy Link -->
    <div class="d-flex gap-2 justify-content-center">
        <button type="button" class="btn btn-sm btn-light rounded-pill px-3 fw-bold" onclick="if(navigator.share){ navigator.share({title:'<?= htmlspecialchars($org['name']) ?> Card', url:window.location.href}); } else { navigator.clipboard.writeText(window.location.href); alert('Link copied!'); }">
            <i class="bi bi-share me-1"></i> Share Card
        </button>
    </div>

    <?php if (empty($org['hide_benchero_branding'])): ?>
        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 small text-white-50">
            Powered by <a href="https://benchero.co.ke" target="_blank" class="text-info text-decoration-none fw-bold">BENCHERO</a>
        </div>
    <?php endif; ?>
</div>

    <script src="<?= url('/assets/js/benchero-motion.js') ?>" defer></script>
</body>
</html>
