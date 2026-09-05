    <!-- Optional Sponsors Strip in Footer -->
    <?php if (!empty($settings['show_sponsors_in_footer']) && !empty($sponsors)): ?>
        <section class="py-4 bg-white border-top border-bottom">
            <div class="container text-center">
                <small class="text-uppercase fw-bold text-muted letter-spacing-2 mb-3 d-block" style="font-size:0.75rem;">Official Partners & Sponsors</small>
                <div class="d-flex flex-wrap align-items-center justify-content-center gap-4">
                    <?php foreach ($sponsors as $sponsor): ?>
                        <a href="<?= htmlspecialchars($sponsor['website_url'] ?: '#') ?>" target="_blank" class="text-decoration-none opacity-75 opacity-100-hover transition">
                            <img src="<?= htmlspecialchars($sponsor['logo_url']) ?>" alt="<?= htmlspecialchars($sponsor['name']) ?>" height="36" class="object-fit-contain">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Main Footer -->
    <footer class="bg-club-header text-white pt-5 pb-4 border-top border-secondary">
        <div class="container-fluid px-lg-5">
            <div class="row g-4">
                <!-- Club Info -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <?php if (!empty($org['logo_url'])): ?>
                            <img src="<?= htmlspecialchars($org['logo_url']) ?>" alt="<?= htmlspecialchars($org['name']) ?>" height="42" class="bg-white rounded p-1">
                        <?php endif; ?>
                        <h4 class="fw-bold mb-0 text-uppercase"><?= htmlspecialchars($org['name']) ?></h4>
                    </div>
                    <p class="text-white-50 small mb-3">
                        <?= htmlspecialchars($org['description'] ?: ($org['name'] . ' is an official sports organization. Driven by passion, community, and performance.')) ?>
                    </p>
                    <?php if (!empty($socialLinks)): ?>
                        <div class="d-flex gap-2">
                            <?php foreach ($socialLinks as $platform => $url): ?>
                                <?php if ($url): ?>
                                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle p-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">
                                        <i class="bi bi-<?= $platform === 'x' ? 'twitter-x' : $platform ?>"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Navigation Quick Links -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold mb-3 text-uppercase text-club-accent">Navigation</h5>
                    <div class="row g-2">
                        <div class="col-6">
                            <ul class="list-unstyled mb-0 d-grid gap-2 small">
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>" class="text-white-50 text-decoration-none hover-white">Home</a></li>
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>/about" class="text-white-50 text-decoration-none hover-white">About Us</a></li>
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>/teams" class="text-white-50 text-decoration-none hover-white">Teams</a></li>
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>/players" class="text-white-50 text-decoration-none hover-white">Players</a></li>
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>/staff" class="text-white-50 text-decoration-none hover-white">Staff</a></li>
                            </ul>
                        </div>
                        <div class="col-6">
                            <ul class="list-unstyled mb-0 d-grid gap-2 small">
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>/fixtures" class="text-white-50 text-decoration-none hover-white">Fixtures</a></li>
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>/results" class="text-white-50 text-decoration-none hover-white">Results</a></li>
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>/standings" class="text-white-50 text-decoration-none hover-white">Standings</a></li>
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>/news" class="text-white-50 text-decoration-none hover-white">News</a></li>
                                <li><a href="/club/<?= htmlspecialchars($org['slug']) ?>/gallery" class="text-white-50 text-decoration-none hover-white">Gallery</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Contact & Location -->
                <div class="col-lg-4 col-md-12">
                    <h5 class="fw-bold mb-3 text-uppercase text-club-accent">Contact Office</h5>
                    <ul class="list-unstyled text-white-50 small d-grid gap-2 mb-3">
                        <?php if (!empty($org['contact_email'])): ?>
                            <li><i class="bi bi-envelope me-2 text-white"></i><?= htmlspecialchars($org['contact_email']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($org['contact_phone'])): ?>
                            <li><i class="bi bi-telephone me-2 text-white"></i><?= htmlspecialchars($org['contact_phone']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($org['address'])): ?>
                            <li><i class="bi bi-geo-alt me-2 text-white"></i><?= htmlspecialchars($org['address']) ?></li>
                        <?php endif; ?>
                    </ul>
                    <a href="/club/<?= htmlspecialchars($org['slug']) ?>/contact" class="btn btn-outline-light btn-sm rounded-pill px-3">
                        <i class="bi bi-chat-dots me-1"></i> Send Direct Message
                    </a>
                </div>
            </div>

            <hr class="my-4 border-secondary">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-white-50">
                <div>
                    &copy; <?= date('Y') ?> <strong><?= htmlspecialchars($org['name']) ?></strong>. All rights reserved.
                </div>
                <div>
                    Powered by <a href="https://benchero.co.ke" target="_blank" class="text-white text-decoration-none fw-bold"><img src="<?= url('/images/benchero_logo.png') ?>" alt="Benchero" height="18" class="me-1 rounded-1 align-text-bottom">BENCHERO</a> — Sports SaaS Platform
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
</body>
</html>
