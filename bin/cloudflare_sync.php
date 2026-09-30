<?php

/**
 * Benchero Cloudflare for SaaS Custom Hostnames Status Reconciliation CLI
 * 
 * Periodically polls Cloudflare for SaaS API to reconcile custom hostname
 * verification and edge SSL certificate provisioning statuses.
 * 
 * Usage:
 *   php bin/cloudflare_sync.php [options]
 * 
 * Options:
 *   --domain=<domain>     Sync a specific custom hostname
 *   --org=<id|slug>       Sync custom hostnames for a specific organization
 *   --auto-activate       Automatically activate verified domains once Cloudflare SSL is active
 *   --dry-run             Simulate changes without persisting to the database
 *   --force               Check all domains regardless of last check timestamp
 *   -h, --help            Show this help documentation
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Services\Cloudflare\CloudflareCustomHostnameService;
use Benchero\Services\DomainService;

$opts = getopt('h', ['domain:', 'org:', 'auto-activate', 'dry-run', 'force', 'help']);
$isHelp = isset($opts['h']) || isset($opts['help']) || (isset($argv[1]) && in_array($argv[1], ['--help', '-h', 'help'], true));

if ($isHelp) {
    echo "====================================================================\n";
    echo "BENCHERO CLOUDFLARE FOR SAAS STATUS RECONCILIATION CLI\n";
    echo "====================================================================\n\n";
    echo "Usage:\n";
    echo "  php bin/cloudflare_sync.php [options]\n\n";
    echo "Options:\n";
    echo "  --domain=<domain>     Reconcile a specific custom domain\n";
    echo "  --org=<id|slug>       Reconcile domains for an organization\n";
    echo "  --auto-activate       Auto-activate verified domains once Cloudflare SSL is active\n";
    echo "  --dry-run             Preview status changes without database updates\n";
    echo "  --force               Check all domains regardless of recent check timestamp\n";
    echo "  -h, --help            Display this help message\n\n";
    exit(0);
}

$targetDomain = $opts['domain'] ?? null;
$targetOrg = $opts['org'] ?? null;
$autoActivate = isset($opts['auto-activate']);
$dryRun = isset($opts['dry-run']);
$force = isset($opts['force']);

$db = Database::getConnection();
$cfService = new CloudflareCustomHostnameService();
$domainService = new DomainService($db, null, null, null, $cfService);

echo "====================================================================\n";
echo "BENCHERO CLOUDFLARE CUSTOM HOSTNAMES RECONCILIATION\n";
echo "Timestamp : " . date('Y-m-d H:i:s T') . "\n";
echo "CF Enabled: " . ($cfService->isEnabled() ? "YES" : "NO (Mock/Bypassed mode)") . "\n";
echo "Mode      : " . ($dryRun ? "DRY RUN (no DB writes)" : "LIVE RECONCILIATION") . "\n";
echo "====================================================================\n\n";

// Build query for target domains
$query = "
    SELECT cd.*, o.name AS org_name, o.slug AS org_slug
    FROM custom_domains cd
    JOIN organizations o ON o.id = cd.organization_id
    WHERE cd.deleted_at IS NULL
";
$params = [];

if ($targetDomain) {
    $query .= " AND cd.normalized_domain = ?";
    $params[] = strtolower(trim($targetDomain));
} elseif ($targetOrg) {
    $query .= " AND (o.id = ? OR o.slug = ?)";
    $params[] = $targetOrg;
    $params[] = $targetOrg;
} else {
    // Reconcile all non-deleted custom domains that have a Cloudflare hostname ID or are pending verification
    $query .= " AND (cd.cloudflare_custom_hostname_id IS NOT NULL OR cd.verification_status = 'verified')";
}

$query .= " ORDER BY cd.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($domains)) {
    echo "No matching custom domain records found.\n";
    exit(0);
}

echo "Found " . count($domains) . " domain record(s) to inspect.\n\n";

$stats = [
    'checked' => 0,
    'updated' => 0,
    'active' => 0,
    'pending' => 0,
    'errors' => 0,
    'auto_activated' => 0,
];

foreach ($domains as $domain) {
    $stats['checked']++;
    $domainId = $domain['id'];
    $normalized = $domain['normalized_domain'];
    $orgSlug = $domain['org_slug'];
    $cfId = $domain['cloudflare_custom_hostname_id'];
    $oldStatus = $domain['cloudflare_status'] ?? 'none';
    $oldSslStatus = $domain['cloudflare_ssl_status'] ?? 'none';

    echo "--- [{$stats['checked']}/" . count($domains) . "] {$normalized} ({$orgSlug}) ---\n";
    echo "  Current CF Status: {$oldStatus} | SSL: {$oldSslStatus}\n";

    if (!$cfService->isEnabled()) {
        echo "  Cloudflare integration is disabled in .env (CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED=false).\n";
        echo "  Skipping external API check.\n\n";
        continue;
    }

    // If domain is verified but has no Cloudflare hostname ID, attempt creation
    if (empty($cfId) && $domain['verification_status'] === 'verified') {
        echo "  Domain is verified but has no Cloudflare Hostname ID. Attempting provisioning...\n";
        if (!$dryRun) {
            $createRes = $cfService->createCustomHostname($normalized);
            if ($createRes['success'] && !empty($createRes['id'])) {
                $cfId = $createRes['id'];
                $updStmt = $db->prepare("
                    UPDATE custom_domains 
                    SET cloudflare_custom_hostname_id = ?,
                        cloudflare_status = ?,
                        cloudflare_ssl_status = ?,
                        cloudflare_created_at = NOW(),
                        cloudflare_last_checked_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $updStmt->execute([
                    $cfId,
                    $createRes['status'] ?? 'pending',
                    $createRes['ssl_status'] ?? 'pending',
                    $domainId
                ]);
                echo "  [SUCCESS] Created Cloudflare Custom Hostname: {$cfId}\n";
                $stats['updated']++;
            } else {
                $errMsg = $createRes['error'] ?? 'Unknown provisioning failure';
                echo "  [ERROR] Provisioning failed: {$errMsg}\n";
                $updErr = $db->prepare("UPDATE custom_domains SET cloudflare_last_error = ?, updated_at = NOW() WHERE id = ?");
                $updErr->execute([$errMsg, $domainId]);
                $stats['errors']++;
                echo "\n";
                continue;
            }
        } else {
            echo "  [DRY-RUN] Would create Cloudflare Custom Hostname for {$normalized}\n";
        }
    }

    if (empty($cfId)) {
        echo "  No Cloudflare Hostname ID assigned. Skipping.\n\n";
        continue;
    }

    // Query live Cloudflare Custom Hostname status
    $statusRes = $cfService->getCustomHostnameStatus($cfId);
    if (!$statusRes['success']) {
        $errMsg = $statusRes['error'] ?? 'Unknown error';
        echo "  [ERROR] Failed to query Cloudflare API: {$errMsg}\n";
        $stats['errors']++;
        if (!$dryRun) {
            $updErr = $db->prepare("UPDATE custom_domains SET cloudflare_last_error = ?, cloudflare_last_checked_at = NOW(), updated_at = NOW() WHERE id = ?");
            $updErr->execute([$errMsg, $domainId]);
        }
        echo "\n";
        continue;
    }

    $newStatus = $statusRes['hostname_status'];
    $newSslStatus = $statusRes['ssl_status'];
    $isActive = $statusRes['is_active'];

    echo "  Live CF Status   : {$newStatus} | SSL: {$newSslStatus} " . ($isActive ? "[FULLY ACTIVE]" : "[PENDING]") . "\n";

    if ($isActive) {
        $stats['active']++;
    } else {
        $stats['pending']++;
    }

    $statusChanged = ($oldStatus !== $newStatus || $oldSslStatus !== $newSslStatus);

    if ($statusChanged || $force) {
        echo "  Status changed: ({$oldStatus}/{$oldSslStatus}) -> ({$newStatus}/{$newSslStatus})\n";
        if (!$dryRun) {
            $mappedSslStatus = \Benchero\Services\Cloudflare\CloudflareCustomHostnameService::mapCfSslStatusToBenchero($newSslStatus);
            $updStmt = $db->prepare("
                UPDATE custom_domains
                SET cloudflare_status = ?,
                    cloudflare_ssl_status = ?,
                    ssl_status = ?,
                    ssl_ready_at = IF(? = 1 AND ssl_ready_at IS NULL, NOW(), ssl_ready_at),
                    cloudflare_last_checked_at = NOW(),
                    cloudflare_last_error = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updStmt->execute([
                $newStatus,
                $newSslStatus,
                $mappedSslStatus,
                $isActive ? 1 : 0,
                $domainId
            ]);
            $stats['updated']++;
            echo "  [SUCCESS] Database record updated.\n";
        } else {
            echo "  [DRY-RUN] Would update database record.\n";
        }
    } else {
        echo "  Status unchanged.\n";
        if (!$dryRun) {
            $db->prepare("UPDATE custom_domains SET cloudflare_last_checked_at = NOW() WHERE id = ?")->execute([$domainId]);
        }
    }

    // Auto-activation check
    if ($autoActivate && $isActive && $domain['activation_status'] !== 'active' && $domain['verification_status'] === 'verified') {
        echo "  Eligible for auto-activation. Attempting activation...\n";
        if (!$dryRun) {
            try {
                $actRes = $domainService->activateDomain($domain['organization_id'], $domainId);
                echo "  [SUCCESS] Domain auto-activated: " . ($actRes['message'] ?? 'OK') . "\n";
                $stats['auto_activated']++;
            } catch (\Throwable $e) {
                echo "  [WARNING] Auto-activation failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo "  [DRY-RUN] Would auto-activate domain {$normalized}\n";
        }
    }

    echo "\n";
}

echo "====================================================================\n";
echo "RECONCILIATION SUMMARY\n";
echo "====================================================================\n";
echo "Total Inspected : {$stats['checked']}\n";
echo "Records Updated : {$stats['updated']}\n";
echo "Fully Active    : {$stats['active']}\n";
echo "Pending         : {$stats['pending']}\n";
echo "Errors          : {$stats['errors']}\n";
if ($autoActivate) {
    echo "Auto-Activated  : {$stats['auto_activated']}\n";
}
echo "====================================================================\n";

exit($stats['errors'] > 0 && $stats['checked'] === $stats['errors'] ? 1 : 0);
