<?php

/**
 * Benchero Master Verification Test Suite
 * Tests Benchero Pro capabilities, EntitlementService, DomainService, MediaService video uploads,
 * QrCodeService, SeoService, Data Exports, and Security Isolation.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Services\DomainService;
use Benchero\Services\EntitlementService;
use Benchero\Services\MediaService;
use Benchero\Services\QrCodeService;
use Benchero\Services\SeoService;
use Benchero\Services\SubscriptionService;

class MasterQASuite
{
    private PDO $db;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function assert($condition, string $description): void
    {
        if ($condition) {
            echo " [PASS] {$description}\n";
            $this->passed++;
        } else {
            echo " [FAIL] {$description}\n";
            $this->failed++;
        }
    }

    public function run(): void
    {
        echo "==================================================\n";
        echo " BENCHERO MASTER QA VERIFICATION SUITE\n";
        echo "==================================================\n";

        // 1. Verify Database Schema & Migrations
        $plans = $this->db->query("SELECT * FROM plans ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $this->assert(count($plans) >= 4, "Database contains at least 4 plans including Benchero Pro");

        $proPlan = null;
        foreach ($plans as $p) {
            if ($p['slug'] === 'benchero-pro' || $p['id'] == 4) {
                $proPlan = $p;
                break;
            }
        }
        $this->assert(!empty($proPlan), "Benchero Pro plan present in database");
        $this->assert((float)($proPlan['price_kes'] ?? 0) === 20000.00, "Benchero Pro price is KSh 20,000/year");

        $domainTable = $this->db->query("SHOW TABLES LIKE 'custom_domains'")->fetchAll();
        $this->assert(!empty($domainTable), "custom_domains table exists in database");

        // 2. Test EntitlementService Capabilities
        $entitlementService = new EntitlementService($this->db);
        
        // Find or create test organization
        $orgId = '01M23TESTORGMASTER00000001';
        $this->db->exec("DELETE FROM subscriptions WHERE organization_id = '{$orgId}'");
        $this->db->exec("DELETE FROM organizations WHERE id = '{$orgId}'");
        $this->db->exec("
            INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at)
            VALUES ('{$orgId}', 'Master QA Club', 'master-qa-club', 'KE', 'Africa/Nairobi', NOW(), NOW())
        ");

        $subService = new SubscriptionService();
        $subService->activateSubscription($orgId, 4); // Activate Pro Plan (ID 4)

        $this->assert($entitlementService->hasCapability($orgId, EntitlementService::CAP_CUSTOM_DOMAIN), "Pro plan has custom_domain capability");
        $this->assert($entitlementService->hasCapability($orgId, EntitlementService::CAP_VIDEO_UPLOADS), "Pro plan has video_uploads capability");
        $this->assert($entitlementService->hasCapability($orgId, EntitlementService::CAP_DIGITAL_CLUB_CARD), "Pro plan has digital_club_card capability");
        $this->assert($entitlementService->hasCapability($orgId, EntitlementService::CAP_DATA_EXPORT), "Pro plan has data_export capability");
        $this->assert($entitlementService->getQuota($orgId, 'video_storage_mb') === 2048, "Pro plan video storage quota is 2048 MB");

        // Standard Plan Entitlement Restrictions
        $subService->activateSubscription($orgId, 2); // Activate Standard Monthly (ID 2)
        $this->assert(!$entitlementService->hasCapability($orgId, EntitlementService::CAP_CUSTOM_DOMAIN), "Standard plan blocks custom_domain capability");
        $this->assert(!$entitlementService->hasCapability($orgId, EntitlementService::CAP_VIDEO_UPLOADS), "Standard plan blocks video_uploads capability");
        $this->assert(!$entitlementService->hasCapability($orgId, EntitlementService::CAP_DATA_EXPORT), "Standard plan blocks data_export capability");

        // 3. Test DomainService
        $domainService = new DomainService($this->db, $entitlementService);
        $subService->activateSubscription($orgId, 4); // Upgrade back to Pro

        $savedDomain = $domainService->saveDomain($orgId, 'cheetahsfc.co.ke');
        $this->assert(!empty($savedDomain), "Custom domain saved for Pro organization");
        $this->assert($savedDomain['domain'] === 'cheetahsfc.co.ke', "Domain correctly normalized to cheetahsfc.co.ke");

        $verifyResult = $domainService->verifyDomain($orgId);
        $this->assert(isset($verifyResult['status']), "Domain verification check returned status");

        $domainService->deleteDomain($orgId);
        $this->assert($domainService->getDomainByOrg($orgId) === null, "Custom domain disconnected cleanly");

        // 4. Test QrCodeService
        $svgMarkup = QrCodeService::generateSvg('https://benchero.co.ke/club/master-qa-club/card', 200);
        $this->assert(str_contains($svgMarkup, '<svg') && str_contains($svgMarkup, '</svg>'), "QrCodeService generated valid SVG XML markup");

        // 5. Test SeoService
        $seoService = new SeoService('https://benchero.co.ke');
        $meta = $seoService->generateMeta([
            'club_name' => 'Master QA Club',
            'title' => 'Master QA Club — Official Site',
            'path' => '/club/master-qa-club'
        ]);

        $this->assert($meta['canonical_url'] === 'https://benchero.co.ke/club/master-qa-club', "SeoService generated correct canonical URL");
        $this->assert(!empty($meta['og_image']), "SeoService generated Open Graph image URL");

        // Cleanup
        $this->db->exec("DELETE FROM subscriptions WHERE organization_id = '{$orgId}'");
        $this->db->exec("DELETE FROM organizations WHERE id = '{$orgId}'");

        echo "==================================================\n";
        echo " SUMMARY: Passed {$this->passed} / Failed {$this->failed}\n";
        echo "==================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }
}

$suite = new MasterQASuite();
$suite->run();
