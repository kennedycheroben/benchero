<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">Club Leadership</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Coaches & Executive Board</h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Management team, technical bench, and medical support staff.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (empty($staff)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="bi bi-person-vcard display-4 text-muted mb-3"></i>
                <h4 class="fw-bold">No Staff Members Registered</h4>
                <p class="text-muted mb-0">Coaching bench and management staff will be displayed here.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($staff as $st): ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white h-100">
                            <div class="mb-3 mx-auto" style="width:110px; height:110px;">
                                <?php if (!empty($st['photo_url'])): ?>
                                    <img src="<?= htmlspecialchars($st['photo_url']) ?>" alt="Staff" class="w-100 h-100 rounded-circle object-fit-cover shadow-sm">
                                <?php else: ?>
                                    <div class="w-100 h-100 bg-club-primary text-white rounded-circle d-flex align-items-center justify-content-center border fs-1">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill small fw-bold w-auto mx-auto mb-2">
                                <?= htmlspecialchars($st['role']) ?>
                            </span>

                            <h4 class="fw-bold text-uppercase mb-2"><?= htmlspecialchars($st['name']) ?></h4>
                            
                            <?php if (!empty($st['bio'])): ?>
                                <p class="small text-muted mb-3"><?= htmlspecialchars($st['bio']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($st['email']) || !empty($st['phone'])): ?>
                                <div class="mt-auto pt-3 border-top small text-muted">
                                    <?php if (!empty($st['email'])): ?>
                                        <div><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($st['email']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
