<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="container-fluid py-3">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-envelope-open text-primary me-2"></i>Contact Messages</h3>
            <p class="text-muted mb-0">View and respond to direct inquiries submitted by visitors on your official club website.</p>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Pills & Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/contact-messages?status=all') ?>" class="card border-0 shadow-sm rounded-4 text-decoration-none p-3 <?= $statusFilter === 'all' ? 'bg-primary text-white' : 'bg-white text-dark' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small fw-semibold text-uppercase opacity-75">All Messages</div>
                        <div class="display-6 fw-bold mt-1"><?= $counts['total'] ?></div>
                    </div>
                    <div class="fs-1 opacity-50"><i class="bi bi-inbox"></i></div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/contact-messages?status=unread') ?>" class="card border-0 shadow-sm rounded-4 text-decoration-none p-3 <?= $statusFilter === 'unread' ? 'bg-warning text-dark' : 'bg-white text-dark' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small fw-semibold text-uppercase text-muted">Unread</div>
                        <div class="display-6 fw-bold text-warning-emphasis mt-1"><?= $counts['unread'] ?></div>
                    </div>
                    <div class="fs-1 text-warning opacity-75"><i class="bi bi-envelope-exclamation"></i></div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/contact-messages?status=read') ?>" class="card border-0 shadow-sm rounded-4 text-decoration-none p-3 <?= $statusFilter === 'read' ? 'bg-success text-white' : 'bg-white text-dark' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small fw-semibold text-uppercase opacity-75">Read</div>
                        <div class="display-6 fw-bold mt-1"><?= $counts['read'] ?></div>
                    </div>
                    <div class="fs-1 text-success opacity-75"><i class="bi bi-envelope-check"></i></div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="<?= url('/o/' . urlencode($tenant['slug']) . '/contact-messages?status=archived') ?>" class="card border-0 shadow-sm rounded-4 text-decoration-none p-3 <?= $statusFilter === 'archived' ? 'bg-secondary text-white' : 'bg-white text-dark' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small fw-semibold text-uppercase opacity-75">Archived</div>
                        <div class="display-6 fw-bold mt-1"><?= $counts['archived'] ?></div>
                    </div>
                    <div class="fs-1 text-secondary opacity-75"><i class="bi bi-archive"></i></div>
                </div>
            </a>
        </div>
    </div>

    <!-- Messages List -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0">Inquiries Received (<?= htmlspecialchars(ucfirst($statusFilter)) ?>)</h5>
            <span class="badge bg-light text-dark border"><?= count($messages) ?> records</span>
        </div>

        <?php if (empty($messages)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-envelope-x display-3 mb-3 text-secondary d-block"></i>
                <h5 class="fw-bold text-dark mb-1">No contact messages found</h5>
                <p class="mb-0">When supporters or prospective players send a message via your contact page, they will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">Status</th>
                            <th>Sender</th>
                            <th>Subject & Preview</th>
                            <th>Received Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="<?= $msg['status'] === 'unread' ? 'table-warning-subtle fw-semibold' : '' ?>">
                                <td class="text-center">
                                    <?php if ($msg['status'] === 'unread'): ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-envelope-fill"></i> New</span>
                                    <?php elseif ($msg['status'] === 'read'): ?>
                                        <span class="badge bg-light text-muted border"><i class="bi bi-envelope-open"></i> Read</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><i class="bi bi-archive"></i> Archived</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($msg['name']) ?></div>
                                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="small text-decoration-none text-primary">
                                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($msg['email']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($msg['subject'] ?: '(No Subject)') ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 380px;">
                                        <?= htmlspecialchars(mb_strimwidth($msg['message'], 0, 100, '...')) ?>
                                    </div>
                                </td>
                                <td class="small text-muted">
                                    <i class="bi bi-clock me-1"></i><?= date('M j, Y H:i', strtotime($msg['created_at'])) ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3 me-1" data-bs-toggle="modal" data-bs-target="#viewMsgModal<?= htmlspecialchars($msg['id']) ?>">
                                        <i class="bi bi-eye me-1"></i> View Message
                                    </button>

                                    <!-- Status Toggle Form -->
                                    <form action="<?= url('/o/' . urlencode($tenant['slug']) . '/contact-messages/' . urlencode($msg['id']) . '/status') ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <?php if ($msg['status'] === 'unread'): ?>
                                            <input type="hidden" name="status" value="read">
                                            <button type="submit" class="btn btn-sm btn-outline-success rounded-3 me-1" title="Mark as Read">
                                                <i class="bi bi-check2"></i> Mark Read
                                            </button>
                                        <?php elseif ($msg['status'] === 'read'): ?>
                                            <input type="hidden" name="status" value="unread">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-3 me-1" title="Mark as Unread">
                                                <i class="bi bi-envelope"></i> Unread
                                            </button>
                                        <?php endif; ?>
                                    </form>

                                    <!-- Delete Form -->
                                    <form action="<?= url('/o/' . urlencode($tenant['slug']) . '/contact-messages/' . urlencode($msg['id']) . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Remove this message?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" title="Delete Message">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- View Message Modal -->
                            <div class="modal fade" id="viewMsgModal<?= htmlspecialchars($msg['id']) ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content rounded-4 border-0 shadow">
                                        <div class="modal-header border-bottom bg-light">
                                            <h5 class="modal-title fw-bold">
                                                <i class="bi bi-envelope-paper text-primary me-2"></i>
                                                <?= htmlspecialchars($msg['subject'] ?: 'Contact Message') ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="row g-3 mb-3 bg-light rounded-3 p-3 border">
                                                <div class="col-md-6">
                                                    <span class="text-muted small d-block">Sender Name</span>
                                                    <strong class="text-dark fs-6"><?= htmlspecialchars($msg['name']) ?></strong>
                                                </div>
                                                <div class="col-md-6">
                                                    <span class="text-muted small d-block">Sender Email</span>
                                                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="fw-bold text-primary text-decoration-none">
                                                        <?= htmlspecialchars($msg['email']) ?>
                                                    </a>
                                                </div>
                                                <div class="col-md-12 border-top pt-2 mt-2">
                                                    <span class="text-muted small me-2">Received Date:</span>
                                                    <span class="fw-semibold text-dark"><?= date('F j, Y \a\t g:i A', strtotime($msg['created_at'])) ?></span>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label text-muted small fw-bold text-uppercase">Message Content</label>
                                                <div class="p-3 bg-white border rounded-3 text-dark" style="white-space: pre-wrap; font-size: 0.95rem; min-height: 120px;">
                                                    <?= htmlspecialchars($msg['message']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top bg-light">
                                            <a href="mailto:<?= htmlspecialchars($msg['email']) ?>?subject=Re:%20<?= urlencode($msg['subject']) ?>" class="btn btn-primary fw-semibold rounded-3">
                                                <i class="bi bi-reply-fill me-1"></i> Reply via Email
                                            </a>
                                            <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
