<?php $this->layout('layout', ['title' => 'Custom Domain Connection — ' . htmlspecialchars($org['name'])]); ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Custom Domain Connection</h1>
            <p class="text-muted mb-0">Connect your custom domain (e.g. <code>www.myclub.co.ke</code>) to your Benchero public website.</p>
        </div>
        <a href="/o/<?= htmlspecialchars($org['slug']) ?>/dashboard" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!$has_pro): ?>
        <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 mb-4">
            <div class="card-body p-4 text-center">
                <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill mb-2">BENCHERO PRO FEATURE</span>
                <h3 class="fw-bold mb-2">Connect Your Club's Own Domain</h3>
                <p class="text-muted max-w-xl mx-auto mb-3">
                    Custom domain integration is available exclusively on <strong>Benchero Pro</strong> (KSh 20,000/year). Connect <code>www.yourclub.co.ke</code> while keeping your original Benchero link active!
                </p>
                <a href="/o/<?= htmlspecialchars($org['slug']) ?>/billing" class="btn btn-primary btn-lg fw-bold px-4 rounded-3">
                    Upgrade to Benchero Pro Now
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-globe me-2 text-primary"></i>Domain Configuration</h5>
                </div>
                <div class="card-body">
                    <?php if ($domain): ?>
                        <div class="p-3 bg-light rounded-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small d-block">Connected Domain</span>
                                    <h4 class="fw-bold mb-0">https://<?= htmlspecialchars($domain['domain']) ?></h4>
                                </div>
                                <div>
                                    <?php if ($domain['status'] === 'active'): ?>
                                        <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i> ACTIVE & VERIFIED</span>
                                    <?php elseif ($domain['status'] === 'pending' || $domain['status'] === 'verifying'): ?>
                                        <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-hourglass-split me-1"></i> PENDING VERIFICATION</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-3 py-2"><i class="bi bi-x-circle me-1"></i> <?= strtoupper(htmlspecialchars($domain['status'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mb-4">
                            <form action="/o/<?= htmlspecialchars($org['slug']) ?>/domain/verify" method="POST">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                                <button type="submit" class="btn btn-success fw-bold px-4">
                                    <i class="bi bi-arrow-repeat me-1"></i> Verify DNS Records Now
                                </button>
                            </form>

                            <form action="/o/<?= htmlspecialchars($org['slug']) ?>/domain/delete" method="POST" onsubmit="return confirm('Are you sure you want to disconnect this domain?');">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                                <button type="submit" class="btn btn-outline-danger">
                                    Disconnect Domain
                                </button>
                            </form>
                        </div>

                    <?php else: ?>
                        <form action="/o/<?= htmlspecialchars($org['slug']) ?>/domain" method="POST" class="mb-3">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                            <div class="mb-3">
                                <label for="domain" class="form-label fw-semibold">Enter Custom Domain Name</label>
                                <input type="text" class="form-control form-control-lg" id="domain" name="domain" placeholder="e.g. www.cheetahsfc.co.ke" <?= !$has_pro ? 'disabled' : '' ?> required>
                                <div class="form-text">You must own this domain through your registrar (e.g. Truehost, Safaricom, GoDaddy).</div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg fw-bold w-100" <?= !$has_pro ? 'disabled' : '' ?>>
                                Connect Custom Domain
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-info-circle me-2 text-info"></i>DNS Setup Instructions</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Log in to your domain registrar's control panel and add the following DNS records:
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm small align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Host / Name</th>
                                    <th>Target Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-secondary">CNAME</span></td>
                                    <td><code>www</code></td>
                                    <td><code>cname.benchero.co.ke</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-secondary">A</span></td>
                                    <td><code>@</code></td>
                                    <td><code>154.56.40.10</code></td>
                                </tr>
                                <?php if ($domain && !empty($domain['verification_token'])): ?>
                                    <tr>
                                        <td><span class="badge bg-info">TXT</span></td>
                                        <td><code>_benchero-challenge</code></td>
                                        <td><code>benchero-verification=<?= htmlspecialchars($domain['verification_token']) ?></code></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-light border small text-muted mb-0">
                        <i class="bi bi-clock me-1 text-warning"></i> DNS propagation typically takes between 15 minutes and 24 hours depending on your registrar.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
