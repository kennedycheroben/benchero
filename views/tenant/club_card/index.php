<?php $this->layout('layout', ['title' => 'Digital Club Card — ' . htmlspecialchars($org['name'])]); ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Digital Club Card</h1>
            <p class="text-muted mb-0">Shareable mobile digital club card for your supporters, partners, and team members.</p>
        </div>
        <a href="/o/<?= htmlspecialchars($org['slug']) ?>/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!$has_pro): ?>
        <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 mb-4">
            <div class="card-body p-4 text-center">
                <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill mb-2">BENCHERO PRO FEATURE</span>
                <h3 class="fw-bold mb-2">Unlock Your Club's Digital Identity Card</h3>
                <p class="text-muted max-w-xl mx-auto mb-3">
                    Digital Club Cards and instant QR Code generation are available exclusively on <strong>Benchero Pro</strong> (KSh 20,000/year).
                </p>
                <a href="/o/<?= htmlspecialchars($org['slug']) ?>/billing" class="btn btn-primary btn-lg fw-bold px-4 rounded-3">
                    Upgrade to Benchero Pro Now
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4 justify-content-center">
        <!-- Digital Card Mobile Mockup -->
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden bg-dark text-white p-2">
                <div class="card-body p-4 text-center bg-gradient rounded-4">
                    <div class="mb-3">
                        <img src="<?= htmlspecialchars($org['logo_url'] ?: '/images/benchero_logo.png') ?>" alt="Logo" class="rounded-circle shadow-sm border border-2 border-white p-1" style="width: 90px; height: 90px; object-fit: contain; background: white;">
                    </div>

                    <h3 class="fw-extrabold mb-1"><?= htmlspecialchars($org['name']) ?></h3>
                    <p class="small text-info mb-3"><i class="bi bi-patch-check-fill me-1"></i>VERIFIED BENCHERO CLUB</p>

                    <div class="bg-white text-dark rounded-4 p-3 mb-4 shadow-sm">
                        <div class="mb-2">
                            <?= $qr_code_svg ?>
                        </div>
                        <span class="small text-muted fw-semibold">Scan to view official club profile</span>
                    </div>

                    <p class="small text-white-50 mb-3">
                        <?= htmlspecialchars(mb_strimwidth($org['description'] ?: 'Official digital identity on Benchero.', 0, 120, '...')) ?>
                    </p>

                    <div class="d-flex justify-content-center gap-3 small">
                        <?php if (!empty($org['contact_email'])): ?>
                            <span><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($org['contact_email']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($org['contact_phone'])): ?>
                            <span><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($org['contact_phone']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Share Controls & Links -->
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-share-fill text-primary me-2"></i>Share Your Club Identity</h5>
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-semibold">Public Digital Card URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="cardUrlInput" value="<?= htmlspecialchars($card_url) ?>" readonly>
                            <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('cardUrlInput').value); alert('Digital card link copied!');">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">Instant Social Sharing</h6>
                    <div class="d-grid gap-2 mb-4">
                        <a href="https://api.whatsapp.com/send?text=<?= urlencode('Check out our official Benchero Digital Club Card: ' . $card_url) ?>" target="_blank" class="btn btn-success fw-bold text-start">
                            <i class="bi bi-whatsapp me-2 fs-5 align-middle"></i> Share on WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($card_url) ?>" target="_blank" class="btn btn-primary fw-bold text-start">
                            <i class="bi bi-facebook me-2 fs-5 align-middle"></i> Share on Facebook
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode('Official Digital Identity for ' . $org['name']) ?>&url=<?= urlencode($card_url) ?>" target="_blank" class="btn btn-dark fw-bold text-start">
                            <i class="bi bi-twitter-x me-2 fs-5 align-middle"></i> Share on X (Twitter)
                        </a>
                        <a href="https://t.me/share/url?url=<?= urlencode($card_url) ?>&text=<?= urlencode($org['name'] . ' Digital Club Card') ?>" target="_blank" class="btn btn-info text-white fw-bold text-start">
                            <i class="bi bi-telegram me-2 fs-5 align-middle"></i> Share on Telegram
                        </a>
                    </div>

                    <a href="/club/<?= htmlspecialchars($org['slug']) ?>/card" target="_blank" class="btn btn-outline-secondary w-100 fw-bold">
                        <i class="bi bi-box-arrow-up-right me-1"></i> View Public Digital Card
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
