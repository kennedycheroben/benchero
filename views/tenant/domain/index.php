<?php
$tenant = $tenant ?? $org ?? [];
require __DIR__ . '/../../layouts/main.php';

$orgSlug = htmlspecialchars($org['slug']);
$bencheroUrl = url("/club/") . $orgSlug;
?>

<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Custom Domain Connection</h1>
            <p class="text-muted mb-0">
                Connect your club's own domain or subdomain (e.g. <code>www.myclub.com</code> or <code>myclub.co.ke</code>) to your Benchero public website.
            </p>
        </div>
        <a href="<?= url("/o/") ?><?= $orgSlug ?>/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($info)): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-5 me-2"></i>
                <div><?= htmlspecialchars($info) ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Benchero Site Reference Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase d-block" style="letter-spacing:0.05em;">Your Default Benchero Public Site</span>
                    <a href="<?= $bencheroUrl ?>" target="_blank" class="fw-bold text-primary fs-5 text-decoration-none">
                        <?= $bencheroUrl ?> <i class="bi bi-box-arrow-up-right small"></i>
                    </a>
                </div>
                <div class="text-md-end">
                    <span class="badge bg-secondary px-3 py-2 text-uppercase font-heading">Permanent Slug URL Always Active</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pro Entitlement Check -->
    <?php if (!$has_pro): ?>
        <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 mb-4">
            <div class="card-body p-4 text-center">
                <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill mb-2 font-heading">BENCHERO PRO EXCLUSIVE</span>
                <h3 class="fw-bold mb-2">Connect Your Club's Own Branded Domain</h3>
                <p class="text-muted max-w-xl mx-auto mb-3">
                    Custom domain integration is available exclusively on <strong>Benchero Pro</strong> (KSh 25,000/year). Connect your custom web address while your default Benchero URL remains permanently accessible!
                </p>
                <a href="<?= url("/o/") ?><?= $orgSlug ?>/billing" class="btn btn-primary btn-lg fw-bold px-4 rounded-3">
                    Upgrade to Benchero Pro Now
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($domain): ?>
        <!-- Current Domain Overview & Granular Status -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-globe me-2 text-primary"></i>Custom Domain Status</h5>
                    <div class="d-flex gap-2">
                        <?php if ($domain['activation_status'] === 'active'): ?>
                            <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i> ROUTING ACTIVE</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-hourglass-split me-1"></i> ROUTING PENDING</span>
                        <?php endif; ?>

                        <?php if ($domain['ssl_status'] === 'active'): ?>
                            <span class="badge bg-success px-3 py-2"><i class="bi bi-shield-lock me-1"></i> HTTPS ACTIVE</span>
                        <?php elseif ($domain['ssl_status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-shield-exclamation me-1"></i> HTTPS PENDING</span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-3 py-2"><i class="bi bi-shield me-1"></i> HTTPS NOT READY</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 align-items-center mb-4">
                    <div class="col-md-7">
                        <span class="text-muted small d-block">Registered Hostname</span>
                        <h3 class="fw-bold text-dark mb-0 font-monospace"><?= ($domain['ssl_status'] === 'active' ? 'https://' : '') . htmlspecialchars($domain['domain']) ?></h3>
                        <small class="text-muted">Normalized: <code><?= htmlspecialchars($domain['normalized_domain']) ?></code></small>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="d-flex flex-wrap justify-content-md-end gap-2">
                            <form action="<?= url("/o/") ?><?= $orgSlug ?>/domain/delete" method="POST" onsubmit="return confirm('Are you sure you want to disconnect this domain? Routing will immediately stop.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="domain_id" value="<?= htmlspecialchars($domain['id']) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash me-1"></i> Disconnect Domain
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Multi-Step Lifecycle Flow -->
                <div class="row g-4">
                    <!-- Step 1: Ownership Verification -->
                    <div class="col-lg-6">
                        <div class="card h-100 border rounded-3 <?= $domain['verification_status'] === 'verified' ? 'border-success bg-success bg-opacity-10' : 'border-warning bg-light' ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing:0.05em;">
                                        Step 1 — DNS Ownership Verification
                                    </h6>
                                    <?php if ($domain['verification_status'] === 'verified'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check2 me-1"></i> VERIFIED</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> PENDING</span>
                                    <?php endif; ?>
                                </div>

                                <p class="small text-muted mb-3">
                                    Add the following TXT record to prove you control this domain. Benchero verifies this cryptographic record before routing can be activated.
                                </p>

                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered table-sm bg-white small mb-0 align-middle">
                                        <tbody>
                                            <tr>
                                                <th class="table-light text-muted" style="width:25%;">Type</th>
                                                <td><span class="badge bg-secondary font-monospace">TXT</span></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light text-muted">Host / Name</th>
                                                <td>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <code id="txtHost">_benchero-verification.<?= htmlspecialchars($domain['normalized_domain']) ?></code>
                                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none copy-btn" data-target="txtHost" title="Copy Host">
                                                            <i class="bi bi-clipboard"></i>
                                                        </button>
                                                    </div>
                                                    <small class="text-muted d-block" style="font-size:0.75rem;">(or <code>_benchero-verification</code> depending on your DNS provider)</small>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="table-light text-muted">Value</th>
                                                <td>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <code id="txtVal" class="text-break">benchero-verification=<?= htmlspecialchars($domain['verification_token']) ?></code>
                                                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none copy-btn" data-target="txtVal" title="Copy Value">
                                                            <i class="bi bi-clipboard"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if (!empty($domain['last_verification_error'])): ?>
                                    <div class="alert alert-danger small py-2 mb-3">
                                        <i class="bi bi-x-circle me-1"></i><strong>Last check:</strong> <?= htmlspecialchars($domain['last_verification_error']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex gap-2">
                                    <form action="<?= url("/o/") ?><?= $orgSlug ?>/domain/verify" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="domain_id" value="<?= htmlspecialchars($domain['id']) ?>">
                                        <button type="submit" class="btn btn-primary fw-bold btn-sm">
                                            <i class="bi bi-arrow-repeat me-1"></i> Verify Ownership Now
                                        </button>
                                    </form>

                                    <form action="<?= url("/o/") ?><?= $orgSlug ?>/domain/regenerate" method="POST" onsubmit="return confirm('Regenerating will invalidate the previous verification token. Continue?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="domain_id" value="<?= htmlspecialchars($domain['id']) ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                            Regenerate Token
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Configure Routing & Activation -->
                    <div class="col-lg-6">
                        <div class="card h-100 border rounded-3 <?= $domain['activation_status'] === 'active' ? 'border-success bg-success bg-opacity-10' : 'border-secondary bg-light' ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 text-uppercase" style="letter-spacing:0.05em;">
                                        Step 2 — DNS Routing & Activation
                                    </h6>
                                    <?php if ($domain['activation_status'] === 'active'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check2 me-1"></i> ACTIVE</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-pause-circle me-1"></i> NOT ACTIVATED</span>
                                    <?php endif; ?>
                                </div>

                                <p class="small text-muted mb-3">
                                    Point your hostname to Benchero's server. Once verified, click Activate Domain to route incoming requests to your club profile.
                                </p>

                                <?php
                                    $cnameTarget = env('CUSTOM_DOMAIN_CNAME_TARGET', null);
                                    $aTarget = env('CUSTOM_DOMAIN_A_TARGET', null);
                                ?>

                                <?php if (!empty($cnameTarget) || !empty($aTarget)): ?>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-bordered table-sm bg-white small mb-0 align-middle">
                                            <tbody>
                                                <?php if (!empty($cnameTarget)): ?>
                                                    <tr>
                                                        <th class="table-light text-muted" style="width:25%;">CNAME</th>
                                                        <td>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span>Host: <code><?= str_starts_with($domain['normalized_domain'], 'www.') ? 'www' : htmlspecialchars($domain['normalized_domain']) ?></code></span>
                                                                <span class="mx-2">&rarr;</span>
                                                                <code id="cnameTarget"><?= htmlspecialchars($cnameTarget) ?></code>
                                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none copy-btn ms-2" data-target="cnameTarget" title="Copy Target">
                                                                    <i class="bi bi-clipboard"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php if (!empty($aTarget)): ?>
                                                    <tr>
                                                        <th class="table-light text-muted">A Record</th>
                                                        <td>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span>Host: <code>@</code> (Apex)</span>
                                                                <span class="mx-2">&rarr;</span>
                                                                <code id="aTarget"><?= htmlspecialchars($aTarget) ?></code>
                                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none copy-btn ms-2" data-target="aTarget" title="Copy IP">
                                                                    <i class="bi bi-clipboard"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-secondary py-2 px-3 small mb-3 border-0 bg-white">
                                        <i class="bi bi-info-circle me-1 text-primary"></i>
                                        <strong>Production Ingress Pending:</strong>
                                        DNS routing target is pending configuration by server administrator (<code>CUSTOM_DOMAIN_CNAME_TARGET</code>). Complete Step 1 ownership verification first.
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <?php if ($domain['activation_status'] !== 'active'): ?>
                                        <form action="<?= url("/o/") ?><?= $orgSlug ?>/domain/activate" method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="domain_id" value="<?= htmlspecialchars($domain['id']) ?>">
                                            <button type="submit" class="btn btn-success fw-bold btn-sm" <?= $domain['verification_status'] !== 'verified' ? 'disabled' : '' ?>>
                                                <i class="bi bi-power me-1"></i> Activate Domain Routing
                                            </button>
                                        </form>
                                        <?php if ($domain['verification_status'] !== 'verified'): ?>
                                            <small class="text-muted d-block" style="font-size:0.8rem;">* Ownership verification required first.</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="text-success small fw-semibold">
                                            <i class="bi bi-check-circle-fill me-1"></i> Domain is active and serving traffic!
                                        </div>
                                    <?php endif; ?>

                                    <!-- Live SSL / HTTPS Check -->
                                    <form action="<?= url("/o/") ?><?= $orgSlug ?>/domain/ssl" method="POST" class="ms-auto">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="domain_id" value="<?= htmlspecialchars($domain['id']) ?>">
                                        <button type="submit" class="btn btn-outline-info btn-sm">
                                            <i class="bi bi-shield-check me-1"></i> Check Live SSL Status
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Domain Replacement Section -->
                <div class="mt-4 pt-3 border-top">
                    <button class="btn btn-link p-0 text-decoration-none small text-muted" type="button" data-bs-toggle="collapse" data-bs-target="#replaceDomainCollapse">
                        <i class="bi bi-arrow-left-right me-1"></i> Need to change or replace this domain? Click here.
                    </button>
                    <div class="collapse mt-3" id="replaceDomainCollapse">
                        <div class="p-3 bg-light rounded-3">
                            <h6 class="fw-bold mb-2">Zero-Downtime Domain Replacement</h6>
                            <p class="small text-muted mb-3">
                                Entering a new domain will stage it for ownership verification. Your current domain (<code><?= htmlspecialchars($domain['domain']) ?></code>) will remain active and continue serving traffic until the new domain is verified and activated.
                            </p>
                            <form action="<?= url("/o/") ?><?= $orgSlug ?>/domain" method="POST">
                                <?= csrf_field() ?>
                                <div class="input-group">
                                    <input type="text" name="domain" class="form-control" placeholder="e.g. newclubdomain.co.ke" required>
                                    <button type="submit" class="btn btn-primary fw-bold">Stage New Domain</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Initial Domain Connection Form -->
        <div class="row g-4 justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 py-4 px-4">
                        <h4 class="fw-bold mb-1"><i class="bi bi-plus-circle me-2 text-primary"></i>Connect Custom Domain</h4>
                        <p class="text-muted small mb-0">Enter the registered domain or subdomain you wish to connect to your club site.</p>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0">
                        <form action="<?= url("/o/") ?><?= $orgSlug ?>/domain" method="POST">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label for="domain" class="form-label fw-bold">Domain Name</label>
                                <input type="text" 
                                       class="form-control form-control-lg font-monospace" 
                                       id="domain" 
                                       name="domain" 
                                       placeholder="e.g. www.cheetahsfc.co.ke or cheetahsfc.com" 
                                       <?= !$has_pro ? 'disabled' : '' ?> 
                                       required>
                                <div class="form-text">
                                    Enter only the hostname. Do not include <code>http://</code>, <code>https://</code>, or trailing slashes.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg fw-bold w-100 rounded-3" <?= !$has_pro ? 'disabled' : '' ?>>
                                Register Custom Domain & Get Verification Records
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var el = document.getElementById(targetId);
            if (el) {
                var text = el.innerText || el.textContent;
                navigator.clipboard.writeText(text).then(function() {
                    btn.innerHTML = '<i class="bi bi-check text-success"></i>';
                    setTimeout(function() {
                        btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                    }, 2000);
                });
            }
        });
    });
});
</script>
