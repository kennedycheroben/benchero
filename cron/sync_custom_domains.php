<?php
/**
 * Benchero Custom Domains / Cloudflare Status Reconciliation Cron Script
 * Forwarder to bin/cloudflare_sync.php for automated cPanel/system crontab execution.
 * 
 * Recommended cron schedule: Every 5 or 10 minutes:
 *   * /5 * * * * php /opt/lampp/htdocs/benchero/cron/sync_custom_domains.php --auto-activate >/dev/null 2>&1
 */
require_once __DIR__ . '/../bin/cloudflare_sync.php';
