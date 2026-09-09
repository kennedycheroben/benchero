<?php $this->layout('layout', ['title' => 'Cookie Policy — Benchero']) ?>

<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4">
        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2 rounded-pill mb-3">Cookies & Security</span>
        <h1 class="display-5 fw-extrabold mb-2">Cookie Policy</h1>
        <p class="text-muted">Last Updated: September 9, 2026</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-secondary leading-relaxed max-w-4xl mx-auto">
            
            <h4 class="fw-bold text-dark mb-3">1. What Are Cookies?</h4>
            <p>Cookies are small text files stored on your browser or device when you visit web pages. Benchero uses cookies to maintain secure user sessions, prevent Cross-Site Request Forgery (CSRF) attacks, and preserve user preferences.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">2. Essential & Strictly Necessary Cookies</h4>
            <p>These cookies are required for the basic functionality and security of the Benchero software platform:</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm small align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cookie Name</th>
                            <th>Purpose</th>
                            <th>Duration</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>PHPSESSID</code></td>
                            <td>Maintains authenticated user session and tenant context</td>
                            <td>Session (480 mins)</td>
                            <td>Essential</td>
                        </tr>
                        <tr>
                            <td><code>_csrf</code> / <code>csrf_token</code></td>
                            <td>Protects forms against Cross-Site Request Forgery (CSRF) attacks</td>
                            <td>Session</td>
                            <td>Security</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h4 class="fw-bold text-dark mt-4 mb-3">3. Cookie Management & Consent</h4>
            <p>Because Benchero only uses essential session and security cookies necessary to deliver account services and tenant protection, third-party tracking or advertising cookies are not deployed on public club websites.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">4. Contact Information</h4>
            <p>If you have questions about our Cookie Policy, please contact <a href="mailto:contact@benchero.co.ke" class="text-primary">contact@benchero.co.ke</a>.</p>
        </div>
    </div>
</section>
