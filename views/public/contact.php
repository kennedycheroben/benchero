<?php $this->layout('layout', ['title' => $title]) ?>

<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4">
        <div class="max-w-3xl">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-3 py-2 rounded-pill mb-3">
                <i class="bi bi-chat-dots-fill me-1"></i> Support & Inquiries
            </span>
            <h1 class="display-5 fw-extrabold text-slate-900 mb-3">Contact the Benchero Sports Team</h1>
            <p class="lead text-slate-600 mb-0">
                Have questions about onboarding your club, M-Pesa billing, custom domains, or platform features? We're here to help sports organizations thrive.
            </p>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-lg-3">
        <div class="row g-4 align-items-start">
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <h3 class="h4 fw-bold text-slate-900 mb-4">Send Us a Direct Message</h3>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4 rounded-3 shadow-sm">
                            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                            <div>
                                <strong>Message Sent Successfully!</strong> Thank you for reaching out to Benchero. Our sports support team will get back to you shortly.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 rounded-3 shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= url('/contact') ?>" method="POST" class="needs-validation">
                        <?= csrf_field() ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold text-slate-700">Your Full Name <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" class="form-control form-control-lg" value="<?= htmlspecialchars($input['name'] ?? '') ?>" required placeholder="e.g. John Doe">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold text-slate-700">Email Address <span class="text-danger">*</span></label>
                                <input type="email" id="email" name="email" class="form-control form-control-lg" value="<?= htmlspecialchars($input['email'] ?? '') ?>" required placeholder="name@yourclub.com">
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label fw-semibold text-slate-700">Subject / Club Inquiry <span class="text-danger">*</span></label>
                                <input type="text" id="subject" name="subject" class="form-control form-control-lg" value="<?= htmlspecialchars($input['subject'] ?? '') ?>" required placeholder="e.g. Club Onboarding Assistance">
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label fw-semibold text-slate-700">Message Details <span class="text-danger">*</span></label>
                                <textarea id="message" name="message" rows="5" class="form-control" required placeholder="Describe your sports organization or question..."><?= htmlspecialchars($input['message'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 py-3 rounded-3 w-100 w-md-auto d-inline-flex align-items-center justify-content-center gap-2">
                                    <span>Send Inquiry</span>
                                    <i class="bi bi-send-fill"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar Info & Hours -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-slate-900 text-white mb-4">
                    <h4 class="fw-bold mb-3">Official Communication Channels</h4>
                    <p class="text-slate-400 small mb-4">
                        Benchero customer service and technical support representatives are ready to assist you.
                    </p>

                    <div class="d-grid gap-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 bg-primary rounded-3 text-white"><i class="bi bi-envelope-fill fs-5"></i></div>
                            <div>
                                <span class="text-slate-400 small d-block">Support Email</span>
                                <a href="mailto:support@benchero.com" class="text-white fw-bold text-decoration-none">support@benchero.com</a>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 bg-success rounded-3 text-white"><i class="bi bi-clock-fill fs-5"></i></div>
                            <div>
                                <span class="text-slate-400 small d-block">Operating Hours</span>
                                <span class="text-white fw-semibold">Monday – Saturday, 8:00 AM – 6:00 PM EAT</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-2 bg-info rounded-3 text-white"><i class="bi bi-geo-alt-fill fs-5"></i></div>
                            <div>
                                <span class="text-slate-400 small d-block">Headquarters</span>
                                <span class="text-white fw-semibold">Nairobi, Kenya</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold text-slate-900 mb-2">Need Immediate Club Access?</h5>
                    <p class="text-slate-600 small mb-3">
                        You don't need to wait for assistance to start using Benchero. Set up your club and try all features free for 14 days.
                    </p>
                    <a href="<?= url('/register') ?>" class="btn btn-outline-primary btn-sm fw-bold rounded-3">
                        Start 14-Day Free Trial &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
