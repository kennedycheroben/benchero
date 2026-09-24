# BENCHERO SPORTS PLATFORM — PRODUCTION DEPLOYMENT & CRON SCHEDULING GUIDE

## 1. Environment Configuration

In production (`.env`), configure the following settings:

```env
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Africa/Nairobi
APP_CACHE_VERSION=1.0

# Sports Provider Configuration (multi-provider routing is automatically active)
SPORTS_PROVIDER=football-data
FOOTBALL_DATA_API_KEY=your_production_football_data_api_key_here
API_FOOTBALL_API_KEY=your_production_api_football_api_key_here

# News Provider Configuration
NEWS_PROVIDER=rss
```

> **Security Rule**: Never check `.env` into git or expose credentials in deployment scripts.

---

## 2. Deployment Architecture: Two Distinct Phases

It is critical to distinguish between **Deployment-Time Execution** and **Server-Level Scheduled Execution**:

### A. Deployment-Time (Run Once Per Deployment)
cPanel deployment hooks (e.g. `.cpanel.yml`) only execute during code deployments. **`.cpanel.yml` does NOT configure or register recurring cron jobs.**

Post-deployment commands:
```bash
# 1. Run migrations (creates tables, indexes, and repairs integrity)
php bin/migrate.php

# 2. Set directory permissions (writable by both CLI cron user and PHP-FPM web user)
chmod -R 0775 storage/cache storage/logs storage/locks

# 3. Seed / prime initial sports dataset
php bin/sports_sync.php all
```

### B. Server-Level Cron Configuration (cPanel or System Crontab)
Production synchronization requires recurring server-level cron jobs installed via the **cPanel Cron Jobs UI** (or via `crontab -e` if SSH access is available).

---

## 3. Scheduled Cron Configuration & API Quota Calculations

API request limits are critical. Blindly polling every minute will quickly exhaust free-tier API quotas.

### API Quota Budgeting:
1. **API-Football (Free Tier = 100 requests/day)**:
   - Targeted fixtures (FKF 382, CAF 12, 20): 1 run/day = 3 API calls
   - Targeted results (FKF 382, CAF 12, 20): 2 runs/day = 6 API calls
   - Standings (FKF, CAF): 1 run/day = 3 API calls
   - Live scores (match days, e.g. 14:00-22:00 every 15 min): 32 API calls
   - **Total Estimated Consumption**: ~44 API calls/day (well within 100 requests/day limit).
   - *(Note: If subscribed to a paid API-Football plan with higher limits, live polling frequency can be increased to every 2–5 minutes).*

2. **Football-Data.org (Free Tier = 10 requests/minute)**:
   - Results & Fixtures: 2-3 runs/day = ~6 calls
   - Standings (European top 5): 1 run/day = 5 calls
   - Live scores: every 5 minutes during match days = ~12 calls/hour (well below 10/min burst limit).

3. **RSS News (No External API Quota)**:
   - Polled every 15–30 minutes safely.

---

### Recommended Production Crontab Schedule

In cPanel **Cron Jobs**, replace `/home/username/public_html` and `/usr/local/bin/php` with your server's actual paths:

```cron
# -----------------------------------------------------------------------------
# BENCHERO SPORTS SYNCHRONIZATION SCHEDULE (Quota-Optimized)
# -----------------------------------------------------------------------------

# 1. LIVE MATCHES: Every 30 minutes (Quota-safe: 48 req/day)
# WARNING: If on free API-Football (100 req/day), do NOT poll every 5 minutes (288 calls/day exceeds quota).
# Use */30 * * * * for free tier; upgrade to */5 * * * * ONLY if on a paid tier (7,500+ req/day).
*/30 * * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php live >/dev/null 2>&1

# 2. RESULTS: Twice daily (Morning 07:30 and Evening 23:30 EAT)
30 7,23 * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php results >/dev/null 2>&1

# 3. FIXTURES: Once daily at 04:00 EAT
0 4 * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php fixtures >/dev/null 2>&1

# 4. STANDINGS: Once daily at 05:00 EAT
0 5 * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php standings >/dev/null 2>&1

# 5. NEWS: Every 30 minutes
*/30 * * * * /usr/local/bin/php /home/username/public_html/bin/sports_sync.php news >/dev/null 2>&1
```

---

## 4. CLI Interface Reference

The canonical entrypoint for all sports operations is `bin/sports_sync.php`:

```bash
# Individual operations
php bin/sports_sync.php live        # Sync active live scores and cleanup stale live matches
php bin/sports_sync.php fixtures    # Sync scheduled matches (including FKF and CAF targeted)
php bin/sports_sync.php results     # Sync finished match results
php bin/sports_sync.php standings   # Sync league standings per authoritative provider
php bin/sports_sync.php news        # Sync latest sports wire articles
php bin/sports_sync.php all         # Run all operations sequentially

# Legacy option flag format also supported
php bin/sports_sync.php --type=live
php bin/sync_sports.php --type=live # Backward-compatible forwarder
```

---

## 5. Cache Invalidation & File Permissions

1. **Mutex Locks**:
   `bin/sports_sync.php` acquires a file lock at `storage/locks/sports_sync.lock` to prevent overlapping runs.
2. **Cache Auto-Flushing**:
   Every successful synchronization cycle flushes sports caches automatically (`$cacheService->flushSportsCache()`).
3. **Deployment Cache Busting**:
   If sports models or frontend representations change during a release, increment `APP_CACHE_VERSION` in `.env` (e.g. `APP_CACHE_VERSION=1.1`). This instantly invalidates all previously serialized filesystem cache files without deleting files manually.
4. **Ownership Requirements**:
   In cPanel / Apache environments, ensure that both the CLI cron user and the web server user belong to the same group and have write access:
   ```bash
   chmod -R 0775 storage/cache storage/logs storage/locks
   ```

---

## 6. Health & Diagnostic Monitoring

### Public Health Endpoint
The platform exposes a diagnostic probe at `https://benchero.co.ke/health`:
```json
{
  "status": "ok",
  "database": "connected",
  "sports_sync": {
    "status": "healthy",
    "last_successful_sync": "2026-09-24 21:30:00",
    "recent_error": null,
    "scheduler_status": "active"
  }
}
```
* Status values: `healthy` (synced < 30m ago), `stale` (synced > 30m ago), `uninitialized`.
* Scheduler status: `active` (< 30m), `delayed` (30m - 2h), `inactive` (> 2h).

### Direct SQL Inspection
```sql
-- Check last 10 synchronization log entries
SELECT provider, operation, status, duration_ms, records_processed, records_updated, error_message, created_at
FROM sports_sync_logs
ORDER BY id DESC
LIMIT 10;

-- Verify no matches stuck in LIVE older than 150 minutes
SELECT id, competition_id, home_score, away_score, status, start_time
FROM sports_matches
WHERE status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED')
  AND start_time < DATE_SUB(NOW(), INTERVAL 150 MINUTE);
```
