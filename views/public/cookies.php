<?php $this->layout('layout', ['title' => 'Cookie Policy — Benchero']) ?>

<div class="bg-light py-5 border-bottom">
    <div class="container">
        <h1 class="display-5 fw-bold mb-2" style="max-width: 800px; color: black;">Cookie Policy</h1>
        <p class="lead text-muted mb-0"style="max-width: 800px; color: black;">How Benchero uses cookies to keep your account secure and provide a reliable experience.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                
                <h3 class="fw-bold mb-3"><i class="bi bi-cookie text-warning me-2"></i>1. What Are Cookies?</h3>
                <p class="text-black">Cookies are small text files placed on your browser or device when you visit websites. They help websites remember your session, login status, and security preferences so you don't have to re-enter credentials on every page.</p>

                <h3 class="fw-bold mt-4 mb-3"><i class="bi bi-shield-check text-success me-2"></i>2. Essential Cookies We Use</h3>
                <p class="text-black">Benchero uses essential, first-party cookies necessary for core authentication and platform security. Without these cookies, the application cannot function properly.</p>

                <div class="table-responsive my-3">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Cookie Name</th>
                                <th>Category</th>
                                <th>Purpose</th>
                                <th>Expiration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>benchero_session</code></td>
                                <td><span class="badge bg-success">Essential</span></td>
                                <td>Maintains secure user login session and tenant context.</td>
                                <td>8 Hours (Session)</td>
                            </tr>
                            <tr>
                                <td><code>_csrf</code></td>
                                <td><span class="badge bg-success">Essential Security</span></td>
                                <td>Protects forms against Cross-Site Request Forgery (CSRF) attacks.</td>
                                <td>Session</td>
                            </tr>
                            <tr>
                                <td><code>benchero_cookie_consent</code></td>
                                <td><span class="badge bg-info text-dark">Preferences</span></td>
                                <td>Remembers your cookie notification acknowledgment choice.</td>
                                <td>1 Year</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="fw-bold mt-4 mb-3"><i class="bi bi-eye-slash text-danger me-2"></i>3. No Unnecessary Tracking Cookies</h3>
                <p class="text-muted">Benchero does <strong>NOT</strong> use invasive third-party tracking cookies, advertising networks, or user profiling scripts. We prioritize user privacy and data security.</p>

                <h3 class="fw-bold mt-4 mb-3"><i class="bi bi-sliders text-primary me-2"></i>4. Managing Your Cookie Preferences</h3>
                <p class="text-muted">You can control or disable cookies through your browser settings. However, disabling essential cookies (such as <code>benchero_session</code>) will prevent you from logging into your Benchero dashboard or managing your sports club.</p>

                <div class="mt-4 p-4 rounded-3 bg-light border">
                    <h5 class="fw-bold mb-2">Have Questions?</h5>
                    <p class="text-muted mb-0">If you have any questions regarding our cookie policy or privacy practices, please contact our support team at <a href="mailto:contact@benchero.co.ke" class="fw-bold text-decoration-none">contact@benchero.co.ke</a>.</p>
                </div>

            </div>
        </div>
    </div>
</div>
