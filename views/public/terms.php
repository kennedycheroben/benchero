<?php $this->layout('layout', ['title' => 'Terms of Service — Benchero']) ?>

<section class="py-5 bg-white border-bottom">
    <div class="container py-lg-4 text-center text-md-start">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
            <div>
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-2">Legal Agreement</span>
                <h1 class="display-5 fw-extrabold mb-1">Terms of Service</h1>
                <p class="text-muted mb-0">Last Updated: September 17, 2026</p>
            </div>
            <!-- Legal Documents Sub-Navigation -->
            <div class="nav nav-pills bg-light p-1 rounded-pill border d-inline-flex self-md-center">
                <a href="<?= url('/terms') ?>" class="nav-link active rounded-pill px-3 py-1-5 fw-semibold small">Terms of Service</a>
                <a href="<?= url('/privacy') ?>" class="nav-link text-secondary rounded-pill px-3 py-1-5 fw-semibold small">Privacy Policy</a>
                <a href="<?= url('/cookies') ?>" class="nav-link text-secondary rounded-pill px-3 py-1-5 fw-semibold small">Cookie Policy</a>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white text-secondary leading-relaxed max-w-4xl mx-auto">
            
            <h4 class="fw-bold text-dark mb-3">1. Acceptance of Terms</h4>
            <p>By registering for an account, accessing, or using the Benchero multi-tenant sports management software platform available at <code>https://benchero.co.ke</code> ("Platform"), you agree to be bound by these Terms of Service ("Terms"). If you do not agree to these Terms, you must immediately cease accessing or using the Platform.</p>
            <p>If you register for or use Benchero on behalf of a sports club, academy, school, league, association, or other entity ("Organization"), you represent and warrant that you have full authority to bind that Organization to these Terms. In such cases, references to "you" or "your" shall refer jointly to the individual user and the Organization.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">2. Definitions</h4>
            <p>For the purposes of these Terms, the following definitions apply:</p>
            <ul>
                <li><strong>Benchero ("we", "our", "us"):</strong> The multi-sport club management software platform operating at <code>https://benchero.co.ke</code>.</li>
                <li><strong>Platform:</strong> The web applications, administration dashboards, public club site generators, APIs, and associated infrastructure provided by Benchero.</li>
                <li><strong>Organization ("Tenant"):</strong> A sports club, academy, school, league, or team registered on the Platform.</li>
                <li><strong>Account:</strong> A registered user account created to access or manage an Organization.</li>
                <li><strong>Administrator:</strong> An authorized user granted administrative access to manage an Organization's profile, squads, content, and subscriptions.</li>
                <li><strong>User:</strong> Any administrator, coach, staff member, athlete, or visitor accessing the Platform.</li>
                <li><strong>Player:</strong> An athlete or squad member listed in an Organization's team rosters or public pages.</li>
                <li><strong>Public Club Website:</strong> The web pages automatically published by Benchero for an Organization (e.g. <code>https://benchero.co.ke/club/{slug}</code> or connected custom domains).</li>
                <li><strong>Subscription:</strong> A commercial access plan (Free Trial, Standard Monthly, Standard Yearly, or Benchero Pro) granting specific Platform capabilities.</li>
                <li><strong>Content & Media:</strong> Information, text, logos, photographs, graphics, match scores, video clips, and documents uploaded to or displayed on the Platform.</li>
                <li><strong>Payment:</strong> A financial transaction executed via supported payment providers (e.g., M-Pesa STK push) to initiate or extend a Subscription.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">3. About Benchero</h4>
            <p>Benchero is a multi-sport club management and digital presence software platform designed for sports organizations across Kenya and Africa. Depending on enabled features and active Subscription tiers, Organizations may utilize Benchero to manage and publish:</p>
            <ul>
                <li>Multi-sport categories, teams, and age-group squads;</li>
                <li>Player rosters, positions, profiles, and staff member roles;</li>
                <li>Seasons, match fixtures, live scores, results, and league standings;</li>
                <li>Club announcements, news articles, history timelines, and photo galleries;</li>
                <li>Official partners and sponsor logo displays;</li>
                <li>Public contact messaging channels for incoming fan/parent inquiries;</li>
                <li>Digital Club Cards and quick-response (QR) verification codes;</li>
                <li>Custom domain routing, media library assets, and structured data exports.</li>
            </ul>
            <p>Benchero provides the technical software infrastructure to enable these workflows but does not manage the day-to-day operations or field activities of individual Organizations.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">4. Eligibility and Authority</h4>
            <p>To register an Account and access Benchero, you must satisfy the following conditions:</p>
            <ul>
                <li>You must provide accurate, current, and complete registration information;</li>
                <li>You must maintain the confidentiality and security of your Account login credentials;</li>
                <li>You must not impersonate any person, entity, or sports organization, or create accounts using misleading information;</li>
                <li>Organization Administrators are fully responsible for managing and verifying access privileges granted to co-administrators or staff.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">5. Organization and Club Responsibilities</h4>
            <p>Organizations retain complete ownership and responsibility for the information and media they choose to upload, store, or publish through Benchero. Each Organization is explicitly responsible for:</p>
            <ul>
                <li>Ensuring the accuracy and truthfulness of player details, staff information, match fixtures, and competition results;</li>
                <li>Lawfully collecting and managing personal information regarding its players, coaches, and staff;</li>
                <li>Obtaining all necessary permissions, photo consents, and legal authorizations before uploading media or publishing player profiles;</li>
                <li>Appropriately safeguarding children's and minors' privacy in compliance with applicable laws;</li>
                <li>Ensuring uploaded photographs, videos, logos, and graphics do not violate copyright, trademark, or third-party privacy rights;</li>
                <li>Promptly reviewing and responding to messages sent by public visitors through the Organization's public contact form.</li>
            </ul>
            <p>Benchero provides software tools for publication, but the Organization remains the publisher and legal custodian of its published content.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">6. Account Security</h4>
            <p>You are responsible for maintaining the security of your Account password and for all activities that occur under your Account. You agree to immediately notify Benchero at <code>contact@benchero.co.ke</code> if you suspect unauthorized access or security breaches involving your Account.</p>
            <p>Benchero employs technical security safeguards, including password hashing algorithms, encrypted session handling, CSRF protections, rate limiting, and prepared database queries. However, no software platform can guarantee absolute immunity from security threats. We implement reasonable technical and organizational measures designed to protect the Platform and personal information.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">7. User-Generated Content</h4>
            <p>Users and Administrators may upload logos, photographs, video clips (where enabled under eligible plans), squad rosters, news articles, gallery items, sponsor graphics, and social profile links ("User Content"). You retain ownership of all User Content uploaded to your Account.</p>
            <p>By uploading User Content, you represent and warrant that you possess all necessary rights, title, licenses, and permissions to publish such material. You agree not to upload, store, or transmit any Content that:</p>
            <ul>
                <li>Is unlawful, fraudulent, defamatory, libelous, obscene, or racially offensive;</li>
                <li>Infringes on third-party intellectual property, privacy, or publicity rights;</li>
                <li>Contains executable scripts, malware, viruses, Trojan horses, or corrupt files;</li>
                <li>Exposes unauthorized personal sensitive information or violates minor protection standards;</li>
                <li>Is deceptive, misleading, or designed to bypass Platform security controls.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">8. Player and Minor Information</h4>
            <p>Benchero is frequently used by sports academies, schools, and youth clubs managing young athletes under 18 years of age ("Minors").</p>
            <p>Organizations are solely responsible for ensuring an appropriate legal basis under the <strong>Kenya Data Protection Act (2019)</strong> and obtaining explicit consent from parents or legal guardians before adding Minor players to team rosters, uploading Minor player photographs, or publishing Minor performance statistics.</p>
            <p>Organizations must prioritize the safety and privacy of children. Benchero does not directly solicit or collect personal information from children without Organization administration.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">9. Public Club Websites</h4>
            <p>Benchero automatically generates a public-facing club website for eligible Organizations (e.g. <code>https://benchero.co.ke/club/{slug}</code>).</p>
            <p>Private credentials, payment reference tokens, emergency contact numbers, and unpublished administrative notes remain restricted to authenticated Organization dashboards and are never displayed on public websites.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">10. Social Sharing</h4>
            <p>Benchero provides shareable public links and button shortcuts for sharing public club pages, news, and match updates across social networks such as WhatsApp, Facebook, X, Telegram, Instagram, and TikTok.</p>
            <p>Benchero does not directly post content to external social platform APIs without user interaction. Social sharing operates via URL redirection and link preview tags. Third-party social platforms govern shared content under their respective terms and privacy policies.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">11. Media Uploads & Storage Rules</h4>
            <p>Uploaded media assets are stored in tenant-isolated directories with strict MIME validation, image dimension verification, and execution protection scripts.</p>
            <ul>
                <li>Media upload storage limits are subject to your subscription plan quota (Free: 50 MB image storage; Pro: 5 GB media library storage + 2 GB video storage).</li>
                <li>Content containing illegal material, hate speech, explicit content, or copyrighted assets without license is strictly prohibited and subject to immediate removal and account termination.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">12. Intellectual Property</h4>
            <p>Benchero and its licensors retain all right, title, and interest in and to the Platform, software, source code, database architecture, visual designs, brand assets, logos, and trademarks. Nothing in these Terms grants users ownership of Benchero technology.</p>
            <p>Customers retain full ownership of their User Content. You grant Benchero a non-exclusive, worldwide, royalty-free license to host, store, process, display, and transmit your User Content solely as required to deliver and operate the Platform services for your Organization.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">13. Subscription Plans</h4>
            <p>Benchero provides structured Subscription plans to accommodate grassroots clubs, academies, and professional organizations:</p>
            <ul>
                <li><strong>Free Trial:</strong> A 14-day promotional trial granting temporary access to test platform capabilities for new Organizations.</li>
                <li><strong>Standard Monthly Plan (KSh 1,000/month):</strong> Includes core club management, sports, teams, players, staff, seasons, fixtures, results, standings, news, gallery, sponsors, and public website.</li>
                <li><strong>Standard Yearly Plan (KSh 10,000/year):</strong> Standard plan features with annual billing savings.</li>
                <li><strong>Benchero Pro Plan (KSh 20,000/year):</strong> Annual premium plan including custom domain connection, video uploads, expanded media storage quota, Digital Club Card, QR codes, structured data export, custom branding, and removal of Benchero branding.</li>
            </ul>
            <p>Pricing, plan features, and storage quotas are subject to prospective adjustment. Material pricing revisions will be communicated with reasonable advance notice.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">14. Billing and Payments</h4>
            <p>Commercial Subscriptions require payment in Kenya Shillings (KSh) via supported payment providers.</p>
            <ul>
                <li>Benchero <strong>never</strong> requests, collects, or stores a customer's M-Pesa PIN or private financial credentials.</li>
                <li>Payments are validated via secure callback notifications and transaction verification endpoints.</li>
                <li>Subscription activation or extension occurs automatically following successful payment confirmation.</li>
                <li>Unconfirmed, failed, or canceled STK push requests do not activate paid plan entitlements.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">15. Subscription Expiry</h4>
            <p>Upon the expiration of a paid Subscription plan without timely renewal:</p>
            <ul>
                <li>Paid features (such as custom domain routing or Pro tools) may be suspended;</li>
                <li>Public club websites display a professional locked status page ("This club website is temporarily unavailable");</li>
                <li>Private club data, rosters, and media files remain safely stored and protected in accordance with Benchero retention policies;</li>
                <li>Full public website visibility and plan entitlements are automatically restored immediately upon Subscription renewal.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">16. Pro Features</h4>
            <p>Pro plan features—such as custom domain integration, video upload storage, removal of Benchero footer branding, digital club cards, and CSV/JSON data exports—are subject to active Pro Subscription status and plan quota boundaries. Quotas are not unlimited.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">17. Custom Domains</h4>
            <p>Benchero Pro subscribers may connect a custom domain name (e.g., <code>www.myclub.co.ke</code>) to point to their Benchero public club website.</p>
            <ul>
                <li>The Customer is solely responsible for purchasing, registering, and renewing their domain name with an accredited domain registrar.</li>
                <li>Benchero provides DNS configuration instructions (such as CNAME, A, or TXT challenge records) but does not act as a domain registrar or sell domain names directly.</li>
                <li>Custom domain routing is subject to successful TXT ownership verification, DNS propagation, web server VirtualHost alias configuration, and automated SSL certificate issuance. Benchero is not responsible for domain expiration, registrar outages, or incorrect customer DNS configurations.</li>
                <li>Benchero reserves the right to disable custom domain routing if a Pro subscription expires or if a domain violates platform acceptable use policies.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">18. Acceptable Use</h4>
            <p>You agree not to misuse the Platform. Prohibited activities include, without limitation:</p>
            <ul>
                <li>Attempting to gain unauthorized access to other tenant accounts or Platform infrastructure;</li>
                <li>Uploading malicious code, trojans, web shells, or executable scripts;</li>
                <li>Engaging in automated scraping, denial-of-service (DoS) attacks, or API rate-limit abuse;</li>
                <li>Attempting to bypass payment verifications, subscription locks, or security controls;</li>
                <li>Using the Platform for fraudulent, deceptive, or illegal activities under Kenyan law.</li>
            </ul>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">19. Third-Party Services</h4>
            <p>Benchero integrates with trusted third-party service providers to deliver specialized capabilities, including payment gateways (M-Pesa STK push via Daraja / I&M Bank) and SMTP transactional email hosts. Third-party services operate under their own service terms and privacy practices.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">20. Service Availability</h4>
            <p>We strive to maintain high service availability for Benchero. However, service disruptions may occasionally occur due to scheduled maintenance, software updates, internet service provider outages, cloud infrastructure failures, or events beyond our reasonable control. Benchero does not guarantee uninterrupted or error-free operation.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">21. Suspension and Termination</h4>
            <p>Benchero reserves the right to restrict, suspend, or terminate Account access or public website visibility where reasonably necessary due to:</p>
            <ul>
                <li>Material or repeated violations of these Terms of Service;</li>
                <li>Non-payment of applicable Subscription fees following grace periods;</li>
                <li>Suspected fraudulent activity, illegal conduct, or security abuse;</li>
                <li>Court orders or lawful requests by Kenyan regulatory authorities.</li>
            </ul>
            <p>Where appropriate and lawful, Benchero will provide reasonable notice to allow Organizations to address compliance issues before permanent suspension.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">22. Data After Termination</h4>
            <p>Upon termination or cancellation of an Organization's Account, Benchero retains data in accordance with our Privacy Policy, legal accounting duties, and security requirements. Soft-deleted records and media files are permanently removed following standard retention schedules.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">23. Disclaimers</h4>
            <p>Benchero is a software management platform. Benchero does not guarantee sporting performance, match victories, league placements, sponsorship revenue, or fan engagement metrics. The software is provided "as is" and "as available."</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">24. Limitation of Liability</h4>
            <p>To the maximum extent permitted by applicable law in Kenya, Benchero and its operators shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including loss of data, loss of profits, or business interruption arising out of or in connection with your use of the Platform.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">25. Changes to Terms</h4>
            <p>We may update these Terms of Service from time to time. When material revisions are made, we will update the "Last Updated" date at the top of this document and notify Administrators through the Platform or via email where appropriate. Continued use of Benchero following updates constitutes acceptance of the revised Terms.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">26. Governing Law & Jurisdiction</h4>
            <p>These Terms of Service are governed by and construed in accordance with the laws of the <strong>Republic of Kenya</strong>. Any legal dispute, controversy, or claim arising out of or relating to these Terms or the Platform shall be subject to the jurisdiction of the competent courts of Kenya.</p>

            <hr class="my-4 opacity-25">

            <h4 class="fw-bold text-dark mb-3">27. Contact Information</h4>
            <p>If you have questions, feedback, or legal inquiries regarding these Terms of Service, please reach out to our team:</p>
            <div class="p-4 bg-light rounded-4 border">
                <div class="fw-bold text-dark mb-1">Benchero Legal Support</div>
                <div class="text-muted small mb-2">Multi-Sport Club Management Platform</div>
                <div class="small">Email: <a href="mailto:contact@benchero.co.ke" class="text-primary fw-semibold">contact@benchero.co.ke</a></div>
                <div class="small">Contact Channel: <a href="<?= url('/contact') ?>" class="text-primary fw-semibold">https://benchero.co.ke/contact</a></div>
            </div>

            <div class="mt-4 pt-3 border-top text-center text-muted small">
                <em>Disclaimer: These Terms of Service accurately describe Benchero's software operations and rights. They are provided for operational clarity and are not a substitute for formal legal advice.</em>
            </div>
        </div>
    </div>
</section>
