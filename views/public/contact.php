<?php $this->layout('layout', ['title' => $title]) ?>

<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4">
        <div class="max-w-2xl mx-auto">
            <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-3">Get in Touch</span>
            <h1 class="display-5 fw-extrabold mb-3">Contact Benchero Support</h1>
            <p class="text-muted">Have a question about club setup, billing, or features? Send us a message and our team will respond shortly.</p>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                            <div>
                                <strong>Message Sent Successfully!</strong> Thank you for reaching out to Benchero. We will get back to you soon.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('/contact') ?>" method="POST">
                        <?= csrf_field() ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Your Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control form-control-lg" value="<?= htmlspecialchars($input['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="form-control form-control-lg" value="<?= htmlspecialchars($input['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                                <input type="text" id="subject" name="subject" class="form-control form-control-lg" value="<?= htmlspecialchars($input['subject'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                                <textarea id="message" name="message" rows="5" class="form-control" required><?= htmlspecialchars($input['message'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 py-3 rounded-3 w-100 w-md-auto">
                                    Send Message <i class="bi bi-send ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
