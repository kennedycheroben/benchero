# BENCHERO SPORTS PLATFORM — ADAPTIVE REAL-TIME ARCHITECTURE & DEPLOYMENT GUIDE

## 1. What "Real-Time" Means in Benchero

In Benchero, **"real-time"** refers to **near-real-time server-side provider synchronization**:
- Upstream sports providers (API-Football, Football-Data.org) update match states and events approximately every 15–60 seconds.
- Benchero's backend synchronizes with authoritative providers every **15–30 seconds** when live matches are in progress.
- Browsers never connect to external sports providers directly. Instead, website visitors query Benchero's internal `/api/sports/live` endpoint every **15–20 seconds**, reading from shared server-side memory and database caches.
- **1 provider call serves hundreds of concurrent Benchero users**, preventing API quota exhaustion and guaranteeing sub-second page response times.

> **Truthfulness Rule**: If provider sync is delayed or quota is exhausted, Benchero truthfully labels data as `DATA DELAYED` or `PROVIDER DEGRADED` with relative timestamps (e.g., "Updated 4 minutes ago"). The system never claims to be "live" or "real-time" when provider data has expired, and never manufactures fake or synthetic scores.

---

## 2. Synchronization Frequencies & Lifecycle

| Stage | Mode | Provider Sync Frequency | Browser Refresh Interval | Condition |
| :--- | :--- | :--- | :--- | :--- |
| **Active Live Matches** | `LIVE_ACTIVE` | **15–30 seconds** | 15–20 seconds | >= 1 match currently `LIVE`, `IN_PLAY`, `HT`, or `PAUSED` |
| **Upcoming Kickoff** | `PREMATCH_WINDOW` | **60 seconds** | 20 seconds | Match starting within 15 minutes |
| **Match Finished Grace**| `POSTMATCH_GRACE` | **60 seconds** | 20 seconds | Match finished within last 15 minutes (verifying final scores) |
| **Idle / Off-Hours** | `IDLE_DISCOVERY` | **15–30 minutes** | 60 seconds (or paused) | No matches live or approaching kickoff |
| **Quota Backoff** | `DEGRADED_BACKOFF` | **Paused (5 min)** | 30–60 seconds | Upstream HTTP 429 or quota remaining <= 10 |

---

## 3. Provider Routing & Authority Matrix

Benchero uses a strict, provider-isolated routing model. Competitions are routed exclusively to authoritative providers to prevent identity collision and duplicate sync requests:

### Kenyan & African Regional Competitions → API-Football
* **FKF Premier League** (`fkf-premier-league` / league ID: 382)
* **Kenya Super League** (`kenya-super-league`)
* **CAF Champions League** (`caf-champions-league` / league ID: 12)
* **CAF Confederation Cup** (`caf-confederation-cup` / league ID: 20)
* **Africa Cup of Nations & Qualifiers** (`africa-cup-of-nations`, `afcon`)
* **African Nations Championship** (`chan`)

### European Tier-One Competitions → Football-Data.org
* **English Premier League** (`premier-league` / code: `PL`)
* **UEFA Champions League** (`uefa-champions-league` / code: `CL`)
* **Spanish La Liga** (`la-liga`, `primera-division` / code: `PD`)
* **German Bundesliga** (`bundesliga` / code: `BL1`)
* **Italian Serie A** (`serie-a` / code: `SA`)
* **French Ligue 1** (`ligue-1` / code: `FL1`)

> **Standings Isolation**: Standings tables are strictly isolated per competition. African standings never query Football-Data; European standings never query API-Football.

---

## 4. API Quota Management & Backoff Architecture

### Header Telemetry & Budgeting
* **API-Football**: Headers `x-ratelimit-requests-remaining` and limit counters are captured on every cURL response.
  * Free Tier: 100 requests/day.
  * Adaptive engine operates in `IDLE_DISCOVERY` (1 run/15m) when no matches are active, consuming only ~4–8 calls during quiet hours.
  * When remaining quota drops below `SPORTS_API_QUOTA_MIN_REMAINING` (default: 10), the engine automatically enters `DEGRADED_BACKOFF` for 300 seconds to preserve quota.
* **Football-Data.org**: Header `x-requests-available-minute` is parsed. Burst rate is limited to 10 calls/minute on the free tier.
* **HTTP 429 Handling**: Automatically logs the event, sets `backoff_until = NOW() + 300s`, and notifies `/health`.
* **Zero Fake Data on Error**: If a provider fails, network times out, or quota is exhausted, Benchero retains the last verified real scores, marks freshness as stale/degraded, and displays an honest notice to users.

---

## 5. Production Scheduler Setup

Benchero supports two execution models depending on the hosting environment:

### Option A: Standard cPanel Hosting (1-Minute Cron Burst Runner)
cPanel crontab cannot execute more frequently than once per minute (`* * * * *`). To achieve **15–30 second near-real-time updates** without requiring a persistent daemon:
* The `php bin/sports_sync.php adaptive --max-seconds=55` command runs once a minute.
* If live matches exist, it runs a burst loop inside the PHP process, checking and syncing every 15–30 seconds for up to 55 seconds, then cleanly exits before the next cron fires.
* Kernel file locking (`storage/locks/sports_sync_live.lock`) guarantees processes never overlap.

#### Recommended cPanel Crontab:
```cron
# 1. ADAPTIVE LIVE BURST LOOP: Every minute (adaptive: syncs every 15-30s if live; exits early if idle)
* * * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php adaptive --max-seconds=55 >/dev/null 2>&1

# 2. RESULTS: Twice daily (Morning 07:30 and Evening 23:30 EAT)
30 7,23 * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php results >/dev/null 2>&1

# 3. FIXTURES: Once daily at 04:00 EAT
0 4 * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php fixtures >/dev/null 2>&1

# 4. STANDINGS: Once daily at 05:00 EAT
0 5 * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php standings >/dev/null 2>&1

# 5. NEWS: Every 30 minutes
*/30 * * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php news >/dev/null 2>&1
```

### Option B: VPS / Dedicated Server (Supervisor / Systemd Persistent Worker)
For true background daemon execution without cron wakeups, run the persistent worker:
```bash
php bin/sports_sync.php worker
```

Example Supervisor Configuration (`/etc/supervisor/conf.d/benchero-sports.conf`):
```ini
[program:benchero-sports-worker]
process_name=%(program_name)s
command=/usr/bin/php /var/www/benchero/bin/sports_sync.php worker
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/benchero/storage/logs/sports_worker.log
```

---

## 6. CLI Diagnostics & Quota Inspection

### Inspect Actual Observed API Usage:
```bash
php bin/sports_sync.php quota
```
Outputs:
1. Daily quota consumption and remaining requests per provider.
2. Breakdown of today's actual HTTP requests by provider and action (status 2xx, 429, errors, latency).
3. Current Adaptive Live Engine state, optimal interval, active live match count, and upcoming kickoffs.

### Manual / Diagnostic Commands:
```bash
# Force immediate live provider synchronization (bypasses adaptive interval)
php bin/sports_sync.php live --force

# Sync upcoming fixtures
php bin/sports_sync.php fixtures

# Sync finished match results
php bin/sports_sync.php results

# Sync standings
php bin/sports_sync.php standings

# Run comprehensive diagnostic verification suite
php tests/run_sports_adaptive_and_quota_tests.php
```

---

## 7. Health & Monitoring Endpoints

### Endpoint: `GET /health`
Returns JSON system health status:
```json
{
  "status": "ok",
  "database": "healthy",
  "sports_sync": "healthy",
  "scheduler": "observed_active",
  "scheduler_note": "Observed activity based on recorded synchronizations; does not inspect cron daemon directly.",
  "providers": {
    "API-Football": {
      "configured": true,
      "status": "available",
      "requests_used_today": 14,
      "requests_remaining_today": 86,
      "daily_limit": 100,
      "last_successful_live_sync": "2026-09-24 23:40:00",
      "last_live_data_age": 28,
      "last_error": null
    },
    "Football-Data": {
      "configured": true,
      "status": "available",
      "last_successful_sync": "2026-09-24 23:38:15",
      "data_age_seconds": 133,
      "last_error": null
    }
  }
}
```

> **Security Assurance**: The `/health` endpoint and all CLI outputs sanitize error messages, redacting tokens, keys, and credentials completely.

---

## 8. Step-by-Step Production Deployment Procedure

Follow these exact steps when deploying to production:

```bash
# 1. Connect to production server via SSH
cd /opt/lampp/htdocs/benchero # (or your cPanel path)

# 2. Check git status to ensure working tree is clean
git status

# 3. Pull deployment branch
git pull origin main

# 4. Run database migrations (applies Migration 039 for quota and telemetry tables)
php bin/migrate.php

# 5. Ensure storage directories and file locks are writable
chmod -R 0775 storage/cache storage/logs storage/locks

# 6. Verify environment configuration in .env (do not commit .env)
# Ensure SPORTS_LIVE_ADAPTIVE_ENABLED=true and non-empty API credentials

# 7. Run initial diagnostic and adaptive test suite
php tests/run_sports_adaptive_and_quota_tests.php

# 8. Check current quota and provider states
php bin/sports_sync.php quota

# 9. Verify live page and API endpoint responses
curl -s http://localhost/benchero/health
curl -s http://localhost/benchero/api/sports/live | jq .meta

# 10. Install crontab jobs in cPanel or system crontab
# Verify crontab via: crontab -l
```
