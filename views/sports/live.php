<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary">
    <div class="container d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span id="liveStatusBadge" class="badge <?= !empty($isStale) ? 'bg-warning text-dark' : (!empty($liveMatches) ? 'bg-danger text-white' : 'bg-secondary text-white') ?> text-uppercase fw-bold px-3 py-2 rounded-2">
                    <i class="bi <?= !empty($isStale) ? 'bi-clock-history' : (!empty($liveMatches) ? 'bi-broadcast animate-pulse' : 'bi-calendar-check') ?> me-1"></i>
                    <span id="liveStatusBadgeText"><?= !empty($isStale) ? 'DATA DELAYED' : (!empty($liveMatches) ? 'LIVE DATA' : 'MATCH CENTER') ?></span>
                </span>
                <h1 class="fw-extrabold mb-0 fs-3">Live Sports Match Center</h1>
            </div>
            <p class="text-white-50 small mb-0" id="liveFreshnessSubtitle">
                <?php if (!empty($isStale)): ?>
                    <span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Data delayed — last sync <?= $this->e($updatedAt) ?></span>
                <?php elseif (isset($dataAgeSeconds) && $dataAgeSeconds !== null): ?>
                    Updated <?= (int)$dataAgeSeconds ?>s ago &bull; Verified server-side cache
                <?php else: ?>
                    Real-time match scores and status updates.
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="small text-white-50" id="liveRefreshCounter">Auto-refreshing in <strong id="timerSecs">20</strong>s</span>
            <button class="btn btn-sm btn-outline-light rounded-pill px-3 fw-semibold" id="btnManualRefresh" aria-label="Refresh live scores">
                <i class="bi bi-arrow-repeat me-1" id="refreshIcon"></i> Refresh
            </button>
        </div>
    </div>
</div>

<div class="container py-4">
    <div id="liveAlertContainer">
        <?php if (!empty($isDegraded)): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                <div>
                    <strong>Notice:</strong> Live data temporarily unavailable or upstream provider degraded. Retaining last verified real scores.
                </div>
            </div>
        <?php elseif (!empty($isStale)): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                <div>
                    <strong>Notice:</strong> Live scores may be temporarily delayed. Last synchronized at <?= $this->e($updatedAt) ?>.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="liveScoresContainer" aria-live="polite">
        <?php if (!empty($liveMatches)): ?>
            <div class="row g-4">
                <?php foreach ($liveMatches as $match): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden benchero-interactive-card">
                            <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between p-3 border-0">
                                <span class="fw-bold small text-light text-truncate me-2"><?= $this->e($match['competition']) ?></span>
                                <span class="badge bg-danger rounded-pill px-3 py-1 fw-extrabold"><?= $this->e(!empty($match['minute']) ? $match['minute'] : 'LIVE') ?></span>
                            </div>
                            <div class="card-body p-4 bg-white">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2 text-dark font-weight-bold fs-5 flex-grow-1">
                                        <div class="fw-extrabold text-slate-900"><?= $this->e($match['home_team']) ?></div>
                                    </div>
                                    <div class="fs-4 fw-extrabold text-primary ms-2"><?= $this->e($match['home_score']) ?></div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-2 text-dark font-weight-bold fs-5 flex-grow-1">
                                        <div class="fw-extrabold text-slate-900"><?= $this->e($match['away_team']) ?></div>
                                    </div>
                                    <div class="fs-4 fw-extrabold text-primary ms-2"><?= $this->e($match['away_score']) ?></div>
                                </div>
                                <?php if (!empty($match['venue'])): ?>
                                    <div class="small text-muted border-top pt-2 mt-2">
                                        <i class="bi bi-geo-alt me-1"></i><?= $this->e($match['venue']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
                <h4 class="fw-bold text-slate-900 mb-2">No Matches Currently Live</h4>
                <p class="text-muted mb-4">There are currently no active matches in progress. Check today's upcoming fixtures or recent results.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= url('/sports/fixtures') ?>" class="btn btn-primary fw-semibold rounded-3">View Upcoming Fixtures</a>
                    <a href="<?= url('/sports/results') ?>" class="btn btn-outline-secondary fw-semibold rounded-3">View Recent Results</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseInterval = 20;
    let currentInterval = baseInterval;
    let secondsLeft = currentInterval;
    let isFetching = false;
    let isTabHidden = false;
    let consecutiveErrors = 0;

    const timerElem = document.getElementById('timerSecs');
    const refreshCounterElem = document.getElementById('liveRefreshCounter');
    const btnRefresh = document.getElementById('btnManualRefresh');
    const refreshIcon = document.getElementById('refreshIcon');
    const badgeElem = document.getElementById('liveStatusBadge');
    const badgeTextElem = document.getElementById('liveStatusBadgeText');
    const subtitleElem = document.getElementById('liveFreshnessSubtitle');
    const alertContainer = document.getElementById('liveAlertContainer');
    const scoresContainer = document.getElementById('liveScoresContainer');

    function formatRelativeTime(seconds) {
        if (seconds === null || seconds === undefined || isNaN(seconds)) return 'just now';
        if (seconds < 10) return 'just now';
        if (seconds < 60) return seconds + ' seconds ago';
        const mins = Math.floor(seconds / 60);
        return mins === 1 ? '1 minute ago' : mins + ' minutes ago';
    }

    function updateBadgeAndNotice(meta, matchCount) {
        const isStale = !!(meta && (meta.stale || meta.is_stale));
        const isDegraded = !!(meta && meta.degraded);
        const ageSecs = meta && meta.age_seconds !== undefined && meta.age_seconds !== null ? meta.age_seconds : null;
        const updatedAt = meta && meta.last_updated ? meta.last_updated : '';

        // Badge State
        badgeElem.className = 'badge text-uppercase fw-bold px-3 py-2 rounded-2 ';
        if (isDegraded) {
            badgeElem.className += 'bg-danger text-white';
            badgeTextElem.textContent = 'PROVIDER DEGRADED';
            if (badgeElem.querySelector('i')) badgeElem.querySelector('i').className = 'bi bi-wifi-off me-1';
        } else if (isStale) {
            badgeElem.className += 'bg-warning text-dark';
            badgeTextElem.textContent = 'DATA DELAYED';
            if (badgeElem.querySelector('i')) badgeElem.querySelector('i').className = 'bi bi-clock-history me-1';
        } else if (matchCount > 0) {
            badgeElem.className += 'bg-danger text-white';
            badgeTextElem.textContent = 'LIVE DATA';
            if (badgeElem.querySelector('i')) badgeElem.querySelector('i').className = 'bi bi-broadcast me-1 animate-pulse';
        } else {
            badgeElem.className += 'bg-secondary text-white';
            badgeTextElem.textContent = 'MATCH CENTER';
            if (badgeElem.querySelector('i')) badgeElem.querySelector('i').className = 'bi bi-calendar-check me-1';
        }

        // Subtitle State
        if (isDegraded) {
            subtitleElem.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Live data temporarily unavailable &bull; Retaining last known real scores</span>';
        } else if (isStale) {
            subtitleElem.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-circle me-1"></i>Data delayed — last update ' + (ageSecs !== null ? formatRelativeTime(ageSecs) : updatedAt) + '</span>';
        } else if (ageSecs !== null) {
            subtitleElem.innerHTML = 'Updated ' + formatRelativeTime(ageSecs) + ' &bull; Shared server cache';
        } else {
            subtitleElem.textContent = 'Real-time match scores and status updates.';
        }

        // Alert banners
        if (isDegraded) {
            alertContainer.innerHTML = `
                <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                    <div>
                        <strong>Notice:</strong> Live data temporarily unavailable from sports provider. Showing last verified scores.
                    </div>
                </div>
            `;
        } else if (isStale) {
            alertContainer.innerHTML = `
                <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                    <div>
                        <strong>Notice:</strong> Live scores may be temporarily delayed. Last synchronized at ${escapeHtml(updatedAt)}.
                    </div>
                </div>
            `;
        } else {
            alertContainer.innerHTML = '';
        }
    }

    function fetchLiveScores() {
        if (isFetching) return;
        isFetching = true;
        if (refreshIcon) refreshIcon.classList.add('animate-spin');

        fetch('<?= url('/api/sports/live') ?>')
            .then(res => {
                if (!res.ok) throw new Error('HTTP error ' + res.status);
                return res.json();
            })
            .then(resData => {
                consecutiveErrors = 0;
                currentInterval = baseInterval;

                if (resData && resData.success) {
                    const matches = resData.data || [];
                    const meta = resData.meta || {};
                    renderLiveScores(matches);
                    updateBadgeAndNotice(meta, matches.length);
                }
            })
            .catch(err => {
                console.error("Live sync retrieval notice:", err);
                consecutiveErrors++;
                // Exponential backoff up to 120s
                currentInterval = Math.min(120, baseInterval * Math.pow(2, Math.min(consecutiveErrors, 3)));
                subtitleElem.innerHTML = '<span class="text-danger"><i class="bi bi-cloud-slash me-1"></i>Live data temporarily unavailable</span>';
            })
            .finally(() => {
                isFetching = false;
                secondsLeft = currentInterval;
                if (refreshIcon) refreshIcon.classList.remove('animate-spin');
                if (timerElem) timerElem.textContent = secondsLeft;
            });
    }

    function renderLiveScores(matches) {
        if (!matches || matches.length === 0) {
            scoresContainer.innerHTML = `
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
                    <h4 class="fw-bold text-slate-900 mb-2">No Matches Currently Live</h4>
                    <p class="text-muted mb-4">There are currently no active matches in progress. Check today's upcoming fixtures or recent results.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="<?= url('/sports/fixtures') ?>" class="btn btn-primary fw-semibold rounded-3">View Upcoming Fixtures</a>
                        <a href="<?= url('/sports/results') ?>" class="btn btn-outline-secondary fw-semibold rounded-3">View Recent Results</a>
                    </div>
                </div>
            `;
            return;
        }

        let html = '<div class="row g-4">';
        matches.forEach(m => {
            html += `
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden benchero-interactive-card">
                        <div class="card-header bg-dark text-white d-flex align-items-center justify-content-between p-3 border-0">
                            <span class="fw-bold small text-light text-truncate me-2">${escapeHtml(m.competition)}</span>
                            <span class="badge bg-danger rounded-pill px-3 py-1 fw-extrabold">${escapeHtml(m.minute || 'LIVE')}</span>
                        </div>
                        <div class="card-body p-4 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="fw-extrabold text-slate-900 fs-5">${escapeHtml(m.home_team)}</div>
                                <div class="fs-4 fw-extrabold text-primary ms-2">${m.home_score}</div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="fw-extrabold text-slate-900 fs-5">${escapeHtml(m.away_team)}</div>
                                <div class="fs-4 fw-extrabold text-primary ms-2">${m.away_score}</div>
                            </div>
                            ${m.venue ? `<div class="small text-muted border-top pt-2 mt-2"><i class="bi bi-geo-alt me-1"></i>${escapeHtml(m.venue)}</div>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        scoresContainer.innerHTML = html;
    }

    function escapeHtml(str) {
        return (str || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Pause timer and network calls when tab is hidden, resume immediately when visible
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            isTabHidden = true;
            if (refreshCounterElem) refreshCounterElem.innerHTML = '<span class="text-white-50"><i class="bi bi-pause-circle me-1"></i>Paused (tab hidden)</span>';
        } else {
            isTabHidden = false;
            if (refreshCounterElem) refreshCounterElem.innerHTML = 'Auto-refreshing in <strong id="timerSecs">' + secondsLeft + '</strong>s';
            // Immediate refresh upon tab return
            fetchLiveScores();
        }
    });

    setInterval(() => {
        if (isTabHidden) return;

        secondsLeft--;
        if (secondsLeft <= 0) {
            fetchLiveScores();
        } else {
            const dynamicTimer = document.getElementById('timerSecs');
            if (dynamicTimer) dynamicTimer.textContent = secondsLeft;
        }
    }, 1000);

    if (btnRefresh) {
        btnRefresh.addEventListener('click', function() {
            fetchLiveScores();
        });
    }
});
</script>
