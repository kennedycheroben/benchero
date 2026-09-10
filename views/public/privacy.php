<?php $this->layout('layout', ['title' => 'Privacy Policy — Benchero']) ?>

<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4">
        <span class="badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2 rounded-pill mb-3">Privacy & Governance</span>
        <h1 class="display-5 fw-extrabold mb-2">Privacy Policy</h1>
        <p class="text-muted">Last Updated: September 9, 2026</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-secondary leading-relaxed max-w-4xl mx-auto">
            
            <h4 class="fw-bold text-dark mb-3">1. Introduction & Scope</h4>
            <p>Benchero ("we", "our", "us") operates the sports club management software platform available at <code>https://benchero.co.ke</code>. This Privacy Policy details how we collect, process, store, and protect personal and organizational information. This policy applies to all registered club administrators, coaches, staff members, players, and public visitors using our platform.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">2. Legal Framework & Compliance</h4>
            <p>Benchero complies with the <strong>Kenya Data Protection Act (2019)</strong> and applicable regional regulations across Africa. We act as a Data Processor for sports organization data uploaded by club administrators, and as a Data Controller for account management and security telemetry.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">3. Information We Collect</h4>
            <ul>
                <li><strong>Account Registration Information:</strong> Full name, verified email address, phone number, encrypted password hash, and organization association.</li>
                <li><strong>Organization & Club Data:</strong> Club names, acronyms, crest logos, founded year, description, venue locations, and social media profile handles.</li>
                <li><strong>Player & Staff Information:</strong> Display names, nationality, positions, jersey numbers, date of birth, emergency contacts, preferred foot/hand, and uploaded profile photos.</li>
                <li><strong>Media Library Assets:</strong> Photos, match graphics, trophy images, sponsor logos, and match video clips uploaded by organization administrators.</li>
                <li><strong>Payment & Billing Telemetry:</strong> M-Pesa transaction reference codes, payment phone numbers (for STK push), amount paid, and subscription period status. We do NOT collect or store M-Pesa PINs or private financial credentials.</li>
                <li><strong>Technical & Security Telemetry:</strong> IP addresses, browser user-agent strings, session identifiers, rate-limiting counters, and security audit logs.</li>
            </ul>

            <h4 class="fw-bold text-dark mt-4 mb-3">4. Protection of Youth & Minor Players</h4>
            <p>Sports organizations frequently manage youth and academy squads. Club administrators are solely responsible for ensuring appropriate parental or legal guardian consent before adding minor players (under 18 years of age) to team rosters, uploading minor player photos, or publishing youth player statistics.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">5. Public Club Websites & Social Sharing</h4>
            <p>Benchero automatically generates a public website for eligible sports organizations (e.g., <code>https://benchero.co.ke/club/your-club</code>). Information published by administrators to the public site (match fixtures, final scores, player rosters, news articles, and gallery photos) is intended for public consumption and may be indexed by web search engines or shared via social media (WhatsApp, Facebook, X, Telegram).</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">6. Storage, Media Library & Security Controls</h4>
            <p>Uploaded media is stored in tenant-isolated file directories with strict MIME validation, file signature verification, and automated <code>.htaccess</code> script execution protection. All database queries utilize parameterized prepared statements, session security headers (HTTPOnly, Secure, SameSite=Lax), and active CSRF validation to protect your data against unauthorized access.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">7. Data Retention & Account Deletion</h4>
            <p>We retain organization data as long as your subscription or trial account remains registered. Upon account termination or written deletion request, Benchero soft-deletes and permanently removes personal records, player rosters, and media files within 30 business days, excluding records required for accounting or legal compliance.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">8. Data Access & Rights</h4>
            <p>Under the Data Protection Act 2019, users have the right to request access to their stored personal data, request correction of inaccurate records, or request export of their organization data. Authorized administrators on eligible plans can download CSV/JSON data exports directly from the administration dashboard.</p>

            <h4 class="fw-bold text-dark mt-4 mb-3">9. Contact Information</h4>
            <p>For privacy inquiries, data subject rights requests, or security concerns, contact our Data Protection Office:</p>
            <div class="p-3 bg-light rounded-3 border">
                <strong>Benchero Data Privacy Officer</strong><br>
                Email: <code>contact@benchero.co.ke</code><br>
                Website: <a href="https://benchero.co.ke/contact">https://benchero.co.ke/contact</a>
            </div>
        </div>
    </div>
</section>
