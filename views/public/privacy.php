<?php $this->layout('layout', ['title' => 'Privacy Policy — Benchero']) ?>

<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4 text-center text-md-start">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
            <div>
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-2">Privacy & Governance</span>
                <h1 class="display-5 fw-extrabold mb-1">Privacy Policy</h1>
                <p class="text-muted mb-0">Last Updated: September 17, 2026</p>
            </div>
            <!-- Legal Documents Sub-Navigation -->
            <div class="nav nav-pills bg-light p-1 rounded-pill border d-inline-flex self-md-center">
                <a href="<?= url('/terms') ?>" class="nav-link text-secondary rounded-pill px-3 py-1-5 fw-semibold small">Terms of Service</a>
                <a href="<?= url('/privacy') ?>" class="nav-link active rounded-pill px-3 py-1-5 fw-semibold small">Privacy Policy</a>
                <a href="<?= url('/cookies') ?>" class="nav-link text-secondary rounded-pill px-3 py-1-5 fw-semibold small">Cookie Policy</a>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-secondary leading-relaxed max-w-4xl mx-auto">
            
            <h4 class="fw-bold text-dark mb-3">1. Introduction</h4>
            <p>Benchero ("we", "our", "us") operates the sports club management software platform available at <code>https://benchero.co.ke</code> ("Platform"). We are committed to processing personal data transparently, securely, and in compliance with applicable privacy laws.</p>
            <p>This Privacy Policy explains how personal and organizational information is collected, processed, stored, shared, and protected when administrators, coaches, staff members, athletes, parents, and public visitors interact with Benchero.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">2. Who Benchero Is</h4>
            <p>Benchero is a multi-tenant sports software provider based in Kenya, offering digital management dashboards, media libraries, squad management tools, and public web presences for sports clubs, academies, schools, and leagues across Kenya and Africa.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">3. Scope of this Policy</h4>
            <p>This Privacy Policy applies to all services, web presences, administrative dashboards, public club sites (e.g. <code>https://benchero.co.ke/club/{slug}</code>), and APIs provided under the Benchero domain and connected custom domains.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">4. Information We Collect Directly</h4>
            <p>Benchero collects information directly from users when registering accounts or interacting with the Platform:</p>
            <ul>
                <li><strong>Account Registration Data:</strong> Full name, verified email address, phone number, encrypted password hash, and account verification status.</li>
                <li><strong>Security & Technical Data:</strong> IP address, session identifiers, HTTP user-agent strings, rate-limiting counters, and security event logs.</li>
                <li><strong>Communication Data:</strong> Inquiries, support requests, and feedback submitted directly to Benchero via <code>contact@benchero.co.ke</code> or our contact forms.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">5. Information Organizations Upload</h4>
            <p>Organizations ("Tenants") use Benchero to manage their sports operations and may upload personal and squad information, including:</p>
            <ul>
                <li><strong>Organization Details:</strong> Club name, acronym, founding year, crest logo, venue locations, and social media links;</li>
                <li><strong>Player Rosters:</strong> Display names, first and last names, jersey numbers, positions, preferred foot/hand, nationality, date of birth, emergency contact phone numbers, and profile photographs;</li>
                <li><strong>Staff Members:</strong> Staff display names, technical roles (e.g., Head Coach, Physio), contact email, phone numbers, bios, and profile images;</li>
                <li><strong>Match & Competition Data:</strong> Seasons, match fixtures, scores, results, standings, news articles, and history timelines;</li>
                <li><strong>Media Assets:</strong> Photographs, hero cover images, partner/sponsor logos, and match video clips.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">6. How We Use Personal Data</h4>
            <p>We process personal data solely for legitimate operational purposes, including:</p>
            <ul>
                <li>Creating, authenticating, and maintaining user Accounts and tenant permissions;</li>
                <li>Generating and operating public club websites as configured by Organizations;</li>
                <li>Managing team rosters, match schedules, results, and digital club cards;</li>
                <li>Processing commercial Subscription payments and sending transactional invoices;</li>
                <li>Preventing fraud, platform abuse, rate-limit violations, and security incidents;</li>
                <li>Sending transactional system emails (email verification, password resets, security alerts);</li>
                <li>Complying with statutory accounting and legal obligations under Kenyan law.</li>
            </ul>
            <p>Benchero does <strong>not</strong> sell personal data or use customer data for third-party advertising networks.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">7. Legal Bases / Lawful Processing</h4>
            <p>Under the <strong>Kenya Data Protection Act (2019)</strong>, processing of personal data relies on the following lawful bases:</p>
            <ul>
                <li><strong>Contractual Performance:</strong> Processing necessary to fulfill our contract with you or your Organization to deliver software services;</li>
                <li><strong>Legitimate Interests:</strong> Operating a secure, reliable platform, preventing fraud, and facilitating sports administration;</li>
                <li><strong>Legal Obligation:</strong> Retaining financial transaction records for statutory accounting and tax compliance;</li>
                <li><strong>Consent:</strong> Where explicit consent has been obtained by Organizations or users for specific publications.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">8. Clubs as Data Controllers & Benchero's Role</h4>
            <p>Depending on the processing activity, roles are allocated as follows:</p>
            <ul>
                <li><strong>Data Controller:</strong> The sports Organization acts as the Data Controller for all player, staff, minor, and match data uploaded to its tenant space. The Organization determines what data is collected and published.</li>
                <li><strong>Data Processor:</strong> Benchero acts as a Data Processor processing Organization data strictly in accordance with the Organization's configuration and instructions.</li>
                <li><strong>Benchero as Controller:</strong> Benchero acts as a Data Controller for Account registration data, billing records, and technical security logs required to operate the Platform.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">9. Public Information vs. Private Data</h4>
            <p>Information explicitly published by Administrators on an Organization's public website (such as team names, player display names, match scores, news, and gallery photos) is public and accessible to internet visitors and search engines.</p>
            <p>Private data—including account passwords, session tokens, emergency contact numbers, unconfirmed payments, and internal administration notes—is strictly protected and never rendered publicly.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">10. Player and Minor Information</h4>
            <p>Sports organizations frequently manage youth squads and minor players (under 18 years of age). Organizations are responsible for ensuring parental or guardian consent before adding minor players to team rosters or publishing minor profile media.</p>
            <p>Benchero processes minor data strictly on behalf of the Organization. Organizations are advised to minimize the publication of unnecessary personal details regarding young athletes.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">11. Public Contact Messages</h4>
            <p>When a visitor submits a inquiry through an Organization's public website contact form, the message (including sender name, email, subject, and content) is stored in Benchero's database (`contact_messages`) and made available to authorized Organization Administrators. Benchero stores these messages to provide contact functionality but does not independently respond on behalf of the club.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">12. Payments & Financial Telemetry</h4>
            <p>Commercial Subscriptions are processed via M-Pesa STK push callbacks (via Daraja or I&M Bank payment integration).</p>
            <ul>
                <li>Benchero receives transaction reference numbers, receipt codes, payment phone numbers, transaction amounts, and subscription period statuses.</li>
                <li>Benchero <strong>never</strong> requests, collects, or stores M-Pesa PINs or private banking credentials.</li>
                <li>Payment transaction records are retained for financial accounting and audit compliance.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">13. Cookies and Session Management</h4>
            <p>Benchero utilizes essential session cookies (`PHPSESSID`, duration 480 minutes) and CSRF security tokens (`_csrf` / `csrf_token`) to maintain user authentication and form security. Benchero does <strong>not</strong> deploy third-party advertising or cross-site tracking cookies. For full details, please refer to our <a href="<?= url('/cookies') ?>" class="text-primary fw-semibold">Cookie Policy</a>.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">14. Email Communications</h4>
            <p>Benchero sends transactional emails via configured SMTP servers (`MAIL_HOST`) for account verification, password resets, and account security notifications. Benchero does not send unsolicited marketing emails.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">15. Third-Party Processors</h4>
            <p>Benchero engages trusted service providers to support Platform delivery:</p>
            <ul>
                <li><strong>Payment Providers:</strong> Safaricom M-Pesa / I&M Bank (for processing STK push payments);</li>
                <li><strong>Email Delivery:</strong> SMTP infrastructure (for delivering transactional notifications);</li>
                <li><strong>Hosting & Cloud Infrastructure:</strong> Secure server infrastructure located in monitored data centers.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">16. International Data Transfers</h4>
            <p>Where cloud infrastructure or service providers process data outside Kenya, Benchero ensures appropriate technical safeguards, encrypted channels, and contractual requirements are in place consistent with the Kenya Data Protection Act (2019).</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">17. Data Retention</h4>
            <p>Personal and tenant data is retained for the duration of an active Subscription or trial account. Upon account deletion or written termination request, Benchero soft-deletes and permanently purges personal records, squad rosters, and media files within 30 business days, excepting transaction records required for statutory tax and accounting laws.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">18. Data Subject Rights</h4>
            <p>Under the <strong>Kenya Data Protection Act (2019)</strong>, individuals have specific rights regarding their personal data:</p>
            <ul>
                <li><strong>Right to Be Informed:</strong> To know how your personal data is collected and processed;</li>
                <li><strong>Right of Access:</strong> To request a copy of personal data held about you;</li>
                <li><strong>Right to Rectification:</strong> To request correction of inaccurate or incomplete records;</li>
                <li><strong>Right to Erasure / Deletion:</strong> To request deletion of personal data subject to legal retention duties;</li>
                <li><strong>Right to Object / Restrict:</strong> To object to or restrict processing under specific conditions;</li>
                <li><strong>Right to Data Portability:</strong> To receive your data in a structured CSV or JSON format (available via Pro export tools).</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">19. Privacy Request Process</h4>
            <p>To exercise your data subject rights, please follow these steps:</p>
            <ol>
                <li>Send a written request to <a href="mailto:contact@benchero.co.ke" class="text-primary fw-semibold">contact@benchero.co.ke</a>;</li>
                <li>Specify your identity, account details, and the specific right you wish to exercise;</li>
                <li>Benchero may verify your identity before fulfilling the request;</li>
                <li>Benchero will review and respond to valid requests within statutory timeframes.</li>
            </ol>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">20. Security Measures & Technical Safeguards</h4>
            <p>Benchero implements rigorous technical and organizational security controls:</p>
            <ul>
                <li>HTTPS / TLS encryption for all data in transit;</li>
                <li>Bcrypt / Argon2 password hashing for user account security;</li>
                <li>Parameterized SQL prepared statements protecting against SQL injection;</li>
                <li>Strict MIME verification and automated `.htaccess` script execution protection in file upload directories;</li>
                <li>CSRF validation tokens on all state-modifying POST forms;</li>
                <li>Database tenant isolation separating Organization data spaces.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">21. Data Breaches & Incident Notification</h4>
            <p>In the event of a confirmed security incident compromising personal data, Benchero will investigate, mitigate the impact, and notify affected Users and relevant Kenyan supervisory authorities (ODPC) in accordance with statutory requirements under the Data Protection Act (2019).</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">22. Complaints & Supervisory Authority</h4>
            <p>If you have unresolved concerns about how your data is handled, you may contact Benchero at `contact@benchero.co.ke`. You also have the right to lodge a complaint with the <strong>Office of the Data Protection Commissioner (ODPC)</strong> of Kenya.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">23. Changes to Privacy Policy</h4>
            <p>We may update this Privacy Policy to reflect technical, legal, or operational updates. Material updates will be highlighted by updating the "Last Updated" date at the top of this document.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">24. Cookie Policy Reference</h4>
            <p>For detailed information on the specific technical session cookies used by Benchero, please review our dedicated <a href="<?= url('/cookies') ?>" class="text-primary fw-semibold">Cookie Policy</a>.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">25. Last Updated Date</h4>
            <p>This Privacy Policy was last reviewed and updated on <strong>September 17, 2026</strong>.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">26. Contact Information</h4>
            <p>For privacy inquiries or data rights requests, please contact our privacy channel:</p>
            <div class="p-4 bg-light rounded-4 border">
                <div class="fw-bold text-dark mb-1">Benchero Data Privacy Channel</div>
                <div class="text-muted small mb-2">Multi-Sport Club Management Platform</div>
                <div class="small">Email: <a href="mailto:contact@benchero.co.ke" class="text-primary fw-semibold">contact@benchero.co.ke</a></div>
                <div class="small">Contact Channel: <a href="<?= url('/contact') ?>" class="text-primary fw-semibold">https://benchero.co.ke/contact</a></div>
            </div>

            <div class="mt-4 pt-3 border-top text-center text-muted small">
                <em>Disclaimer: This Privacy Policy describes Benchero's data protection practices in accordance with Kenyan privacy principles. It is provided for operational transparency and is not a substitute for formal legal counsel.</em>
            </div>
        </div>
    </div>
</section>
