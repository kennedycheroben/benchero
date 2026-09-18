<?php $this->layout('layout', ['title' => 'Cookie Policy — Benchero']) ?>

<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4 text-center text-md-start">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
            <div>
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-2">Cookies & Security</span>
                <h1 class="display-5 fw-extrabold mb-1">Cookie Policy</h1>
                <p class="text-muted mb-0">Last Updated: September 17, 2026</p>
            </div>
            <!-- Legal Documents Sub-Navigation -->
            <div class="nav nav-pills bg-light p-1 rounded-pill border d-inline-flex self-md-center">
                <a href="<?= url('/terms') ?>" class="nav-link text-secondary rounded-pill px-3 py-1-5 fw-semibold small">Terms of Service</a>
                <a href="<?= url('/privacy') ?>" class="nav-link text-secondary rounded-pill px-3 py-1-5 fw-semibold small">Privacy Policy</a>
                <a href="<?= url('/cookies') ?>" class="nav-link active rounded-pill px-3 py-1-5 fw-semibold small">Cookie Policy</a>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-secondary leading-relaxed max-w-4xl mx-auto">
            
            <h4 class="fw-bold text-dark mb-3">1. What Are Cookies?</h4>
            <p>Cookies are small text files placed on your browser or device when you visit a website. Benchero uses cookies strictly to deliver secure user sessions, maintain organization tenant context, and prevent Cross-Site Request Forgery (CSRF) security vulnerabilities.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">2. Essential & Security Cookies Used</h4>
            <p>Benchero only deploys strictly necessary technical session cookies required for software functionality and security:</p>
            
            <div class="table-responsive my-4">
                <table class="table table-bordered align-middle small mb-0">
                    <thead class="table-light text-dark">
                        <tr>
                            <th scope="col">Cookie Name</th>
                            <th scope="col">Purpose & Function</th>
                            <th scope="col">Lifespan</th>
                            <th scope="col">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>PHPSESSID</code></td>
                            <td>Maintains authenticated user session state, login status, and tenant Organization context across page navigation.</td>
                            <td>480 minutes (8 hours) or until browser session closes</td>
                            <td>Essential / First-Party</td>
                        </tr>
                        <tr>
                            <td><code>_csrf</code> / <code>csrf_token</code></td>
                            <td>Protects forms and API endpoints against Cross-Site Request Forgery (CSRF) security attacks by validating request origin.</td>
                            <td>Session duration</td>
                            <td>Security / First-Party</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">3. Absence of Third-Party Tracking Cookies</h4>
            <p>Benchero does <strong>not</strong> deploy third-party advertising cookies, cross-site profiling tags, or marketing trackers on our public web pages or on public club websites generated for our Organizations.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">4. Managing Cookies in Your Browser</h4>
            <p>You can control, block, or delete cookies through your browser settings. However, because Benchero relies exclusively on essential session cookies for authentication and CSRF security, disabling cookies will prevent you from logging into your account or submitting secure forms on the Platform.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">5. Contact Information</h4>
            <p>If you have questions regarding our Cookie Policy, please reach out to our team:</p>
            <div class="p-4 bg-light rounded-4 border">
                <div class="fw-bold text-dark mb-1">Benchero Support</div>
                <div class="text-muted small mb-2">Multi-Sport Club Management Platform</div>
                <div class="small">Email: <a href="mailto:contact@benchero.co.ke" class="text-primary fw-semibold">contact@benchero.co.ke</a></div>
                <div class="small">Contact Channel: <a href="<?= url('/contact') ?>" class="text-primary fw-semibold">https://benchero.co.ke/contact</a></div>
            </div>

            <div class="mt-4 pt-3 border-top text-center text-muted small">
                <em>Disclaimer: This Cookie Policy describes the technical cookies utilized by Benchero. It is provided for operational clarity and is not a substitute for formal legal advice.</em>
            </div>
        </div>
    </div>
</section>
