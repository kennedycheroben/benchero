<?php $this->layout('layout', ['title' => $title, 'description' => $description]) ?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary">
    <div class="container d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-danger text-uppercase fw-bold px-3 py-2 rounded-2 animate-pulse">
                    <i class="bi bi-broadcast me-1"></i> LIVE NOW
                </span>
                <h1 class="fw-extrabold mb-0 fs-3">Live Sports Match Center</h1>
            </div>
            <p class="text-white-50 small mb-0">Real-time match scores and status updates.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-white-50" id="liveRefreshCounter">Auto-refreshing in <strong id="timerSecs">30</strong>s</span>
            <button class="btn btn-sm btn-outline-light rounded-pill px-3 fw-semibold" id="btnManualRefresh">
                <i class="bi bi-arrow-repeat me-1"></i> Refresh
            </button>
        </div>
    </div>
</div>

<div class="container py-4">
    <?php if (!empty($isStale)): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
            <div>
                <strong>Notice:</strong> Live scores may be temporarily delayed. Last synchronized at <?= $this->e($updatedAt) ?>.
            </div>
        </div>
    <?php endif; ?>

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
                <h4 class="fw-bold text-slate-900 mb-2">No Live Matches Right Now</h4>
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
    let secondsLeft = 30;
    const timerElem = document.getElementById('timerSecs');
    const btnRefresh = document.getElementById('btnManualRefresh');

    function fetchLiveScores() {
        fetch('<?= url('/api/sports/live') ?>')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    renderLiveScores(data.data);
                }
            })
            .catch(err => console.error("Error fetching live scores:", err));
    }

    function renderLiveScores(matches) {
        const container = document.getElementById('liveScoresContainer');
        if (!matches || matches.length === 0) {
            container.innerHTML = `
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
                    <h4 class="fw-bold text-slate-900 mb-2">No Live Matches Right Now</h4>
                    <p class="text-muted mb-4">There are currently no active matches in progress.</p>
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
                            <span class="badge bg-danger rounded-pill px-3 py-1 fw-extrabold">${escapeHtml(m.minute)}</span>
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
        container.innerHTML = html;
    }

    function escapeHtml(str) {
        return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    setInterval(() => {
        secondsLeft--;
        if (secondsLeft <= 0) {
            secondsLeft = 30;
            fetchLiveScores();
        }
        if (timerElem) timerElem.textContent = secondsLeft;
    }, 1000);

    if (btnRefresh) {
        btnRefresh.addEventListener('click', function() {
            secondsLeft = 30;
            if (timerElem) timerElem.textContent = secondsLeft;
            fetchLiveScores();
        });
    }
});
</script>
