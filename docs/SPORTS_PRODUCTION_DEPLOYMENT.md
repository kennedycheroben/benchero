# BENCHERO SPORTS PLATFORM — PRODUCTION DEPLOYMENT & TROUBLESHOOTING GUIDE

## 1. Environment Configuration

In production (`.env`), configure the following settings:

```env
APP_ENV=production
APP_DEBUG=false

# Sports Provider Configuration
SPORTS_PROVIDER=real # Options: real, mock
FOOTBALL_DATA_API_KEY=your_production_football_data_api_key_here

# News Provider Configuration
NEWS_PROVIDER=rss # Options: rss, newsapi, mock
NEWS_API_KEY=your_production_news_api_key_here # Optional if using NewsAPI
```

---

## 2. Production Deployment Steps

1. **Database Migration**:
   Run database migrations to ensure all sports tables and indexes exist:
   ```bash
   php bin/migrate.php
   ```

2. **Verify File & Directory Permissions**:
   Ensure `storage/cache`, `storage/logs`, and `storage/locks` are writable:
   ```bash
   chmod -R 0755 storage/cache storage/logs storage/locks
   ```

3. **Cron Job Setup**:
   Add background synchronization to the system crontab:
   ```cron
   # Synchronize live scores every minute
   * * * * * cd /path/to/benchero && php bin/sports_sync.php live >/dev/null 2>&1

   # Synchronize news every 15 minutes
   */15 * * * * cd /path/to/benchero && php bin/sports_sync.php news >/dev/null 2>&1

   # Synchronize fixtures & standings daily at midnight
   0 0 * * * cd /path/to/benchero && php bin/sports_sync.php standings >/dev/null 2>&1
   ```

4. **Initial Data Synchronization Test**:
   Execute manual synchronization to seed production database and cache:
   ```bash
   php bin/sports_sync.php all
   ```

---

## 3. Production Health & Diagnostic Monitoring

Check sync logs and status in the admin console or directly via MySQL:

```sql
-- View recent sync logs
SELECT provider, operation, status, duration_ms, records_processed, records_updated, error_message, created_at
FROM sports_sync_logs
ORDER BY id DESC
LIMIT 10;
```

---

## 4. Rollback Procedure

If an external sports provider experiences an extended global outage or rate limit block:

1. **Keep Public Sports UI Operational**:
   The platform automatically falls back to cached sports records and displays a user-friendly notice: *"Live scores may be temporarily delayed."*

2. **Disable Provider Sync (Emergency Toggle)**:
   Change `SPORTS_PROVIDER=mock` in `.env` to switch back to offline mock providers during testing or emergency maintenance:
   ```env
   SPORTS_PROVIDER=mock
   NEWS_PROVIDER=mock
   ```
   *(Note: Set `APP_ENV=development` when using mock providers for testing).*
