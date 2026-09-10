<?php require __DIR__ . '/layouts/theme_header.php'; ?>

<section class="bg-club-header text-white py-5">
    <div class="container text-center py-4">
        <span class="badge bg-club-primary text-white text-uppercase px-3 py-2 rounded-pill mb-2">Connect</span>
        <h1 class="display-4 fw-black text-uppercase mb-2">Contact <?= htmlspecialchars($org['name']) ?></h1>
        <p class="lead text-white-50 max-w-xl mx-auto mb-0">Reach out to club management, visit our office, or send an inquiry.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success shadow-sm rounded-4 p-4 mb-4 border-0 border-start border-success border-5">
                <h5 class="fw-bold mb-1"><i class="bi bi-check-circle-fill me-2"></i>Message Sent Successfully!</h5>
                <p class="mb-0 text-dark">Thank you for reaching out to <?= htmlspecialchars($org['name']) ?>. Our club administration will get back to you shortly.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger shadow-sm rounded-4 p-4 mb-4 border-0 border-start border-danger border-5">
                <h5 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error</h5>
                <p class="mb-0 text-dark"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <div class="row g-5">
            <!-- Contact Details -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h4 class="fw-bold text-uppercase mb-3">Club Office Info</h4>
                    <ul class="list-unstyled d-grid gap-3 mb-0">
                        <?php if (!empty($org['contact_email'])): ?>
                            <li class="d-flex align-items-center gap-3">
                                <div class="bg-light p-3 rounded-circle text-club-primary"><i class="bi bi-envelope fs-4"></i></div>
                                <div><strong class="d-block text-dark text-uppercase small">Email Address</strong><span class="text-muted"><?= htmlspecialchars($org['contact_email']) ?></span></div>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($org['contact_phone'])): ?>
                            <li class="d-flex align-items-center gap-3">
                                <div class="bg-light p-3 rounded-circle text-club-primary"><i class="bi bi-telephone fs-4"></i></div>
                                <div><strong class="d-block text-dark text-uppercase small">Phone Number</strong><span class="text-muted"><?= htmlspecialchars($org['contact_phone']) ?></span></div>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($org['address'])): ?>
                            <li class="d-flex align-items-center gap-3">
                                <div class="bg-light p-3 rounded-circle text-club-primary"><i class="bi bi-geo-alt fs-4"></i></div>
                                <div><strong class="d-block text-dark text-uppercase small">Physical Address</strong><span class="text-muted"><?= htmlspecialchars($org['address']) ?></span></div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <?php if (!empty($settings['map_link'])): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                        <h5 class="fw-bold text-uppercase mb-2"><i class="bi bi-map text-club-primary me-2"></i>Grounds / Map Location</h5>
                        <a href="<?= htmlspecialchars($settings['map_link']) ?>" target="_blank" class="btn btn-outline-dark rounded-pill fw-bold">
                            View on Google Maps <i class="bi bi-box-arrow-up-right ms-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-lg rounded-4 p-4 bg-white">
                    <h4 class="fw-bold text-uppercase mb-3">Send Us A Message</h4>
                    <form action="/club/<?= $orgSlug ?>/contact" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Your Full Name</label>
                            <input type="text" name="name" class="form-control rounded-3 p-3" value="<?= htmlspecialchars($formData['name'] ?? '') ?>" placeholder="John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control rounded-3 p-3" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" placeholder="name@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Subject</label>
                            <input type="text" name="subject" class="form-control rounded-3 p-3" value="<?= htmlspecialchars($formData['subject'] ?? '') ?>" placeholder="General Inquiry / Trials / Tickets">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase">Message</label>
                            <textarea name="message" rows="5" class="form-control rounded-3 p-3" placeholder="Write your message here..." required><?= htmlspecialchars($formData['message'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-club-primary btn-lg rounded-pill w-100 fw-bold">Submit Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/layouts/theme_footer.php'; ?>
