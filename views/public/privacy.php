<?php $this->layout('layout', ['title' => $title]) ?>

<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4">
        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2 rounded-pill mb-3">Privacy & Compliance</span>
        <h1 class="display-5 fw-extrabold mb-2">Privacy Policy</h1>
        <p class="text-muted">Last Updated: September 3, 2026</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-secondary leading-relaxed max-w-4xl mx-auto">
            <h4 class="fw-bold text-dark mb-3">1. Overview & Data Protection Framework</h4>
            <p>Benchero is committed to protecting the privacy and security of personal data in compliance with the Kenya Data Protection Act, 2019, and applicable global privacy regulations. This policy outlines how we collect, process, and protect your information.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">2. Information We Collect</h4>
            <ul>
                <li><strong>Account Data:</strong> Name, email address, password hash, and account verification credentials.</li>
                <li><strong>Organization & Club Data:</strong> Club names, sports managed, season dates, team roster details, and venue locations.</li>
                <li><strong>Payment Data:</strong> M-Pesa transaction reference numbers and billing status records. We do not store raw payment PINs or private bank details.</li>
                <li><strong>Usage & Security Logs:</strong> IP addresses, session identifiers, and rate-limiting telemetry.</li>
            </ul>

            <h4 class="fw-bold text-dark mt-4 mb-3">3. How We Use Information</h4>
            <p>We use collected data strictly to operate the Benchero platform, authenticate users, enforce tenant isolation, process subscription payments, send transactional emails (e.g. verification, password reset), and publish public club match schedules.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">4. Player & Minor Data Protection</h4>
            <p>Benchero allows sports organizations to manage player rosters. Organization administrators are responsible for obtaining necessary parental or guardian consents when registering youth or minor players.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">5. Data Retention & Security</h4>
            <p>All data is stored in isolated multi-tenant databases utilizing prepared statements, CSRF protection, and session security headers. Inactive data is retained as needed for compliance and accounting purposes.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">6. Your Data Rights</h4>
            <p>Under the Kenya Data Protection Act, users and organizations have the right to request access to, correction of, or deletion of their personal information. To exercise these rights, email <a href="mailto:privacy@benchero.com" class="text-primary">privacy@benchero.com</a>.</p>
        </div>
    </div>
</section>
