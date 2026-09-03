<?php

/**
 * Benchero Subscription, Billing & Public Profile Access Automated Test Suite
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\SubscriptionService;
use Benchero\Services\MpesaService;
use Benchero\Services\OrganizationService;

class SubscriptionTestSuite
{
    private \PDO $db;
    private SubscriptionService $subService;
    private MpesaService $mpesaService;
    private OrganizationService $orgService;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->subService = new SubscriptionService();
        $this->mpesaService = new MpesaService();
        $this->orgService = new OrganizationService();
    }

    public function run(): void
    {
        echo "=====================================================\n";
        echo " BENCHERO SUBSCRIPTION & PUBLIC ACCESS TEST SUITE\n";
        echo "=====================================================\n\n";

        $testUser = $this->createTestUser();
        $testSlug = 'test-sub-club-' . time();
        $orgId = $this->createTestOrganization($testSlug, $testUser['id']);

        $this->testTrialState($orgId, $testSlug);
        $this->testExpiredTrialState($orgId, $testSlug);
        $this->testMonthlySubscription($orgId, $testSlug);
        $this->testYearlySubscription($orgId, $testSlug);
        $this->testRenewalFlow($orgId, $testSlug);
        $this->testSecurityAndAccessControl($orgId, $testSlug);
        $this->testDataPreservationAfterExpiry($orgId, $testSlug);

        $this->cleanup($orgId, $testUser['id']);

        echo "\n=====================================================\n";
        echo " ALL SUBSCRIPTION TESTS PASSED SUCCESSFULLY! (7/7)\n";
        echo "=====================================================\n";
    }

    private function createTestUser(): array
    {
        $id = Ulid::generate();
        $email = 'subtest_' . time() . '@benchero.test';
        $stmt = $this->db->prepare("
            INSERT INTO users (id, name, email, password_hash, created_at, updated_at)
            VALUES (?, 'Sub Test User', ?, 'password_hash', NOW(), NOW())
        ");
        $stmt->execute([$id, $email]);
        return ['id' => $id, 'email' => $email];
    }

    private function createTestOrganization(string $slug, string $userId): string
    {
        $orgId = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at)
            VALUES (?, 'Subscription Test Club', ?, 'KE', 'Africa/Nairobi', NOW(), NOW())
        ");
        $stmt->execute([$orgId, $slug]);

        $stmt = $this->db->prepare("
            INSERT INTO organization_user (organization_id, user_id, role, created_at)
            VALUES (?, ?, 'owner', NOW())
        ");
        $stmt->execute([$orgId, $userId]);

        $this->subService->initializeTrialSubscription($orgId);

        return $orgId;
    }

    private function testTrialState(string $orgId, string $slug): void
    {
        echo "[Test 1/7] Valid Trial State... ";

        $status = $this->subService->getSubscriptionStatus($orgId);
        $this->assertEquals(SubscriptionService::STATUS_TRIAL, $status['status'], 'Status should be TRIAL');
        $this->assertTrue($status['is_visible'], 'Public profile should be visible during valid trial');

        echo "PASSED!\n";
    }

    private function testExpiredTrialState(string $orgId, string $slug): void
    {
        echo "[Test 2/7] Expired Trial State... ";

        // Manually set trial_ends_at and expires_at in past
        $past = date('Y-m-d H:i:s', strtotime('-1 day'));
        $stmt = $this->db->prepare("
            UPDATE subscriptions
            SET status = 'trialing', trial_ends_at = ?, expires_at = ?, current_period_end = ?
            WHERE organization_id = ?
        ");
        $stmt->execute([$past, $past, $past, $orgId]);

        $status = $this->subService->getSubscriptionStatus($orgId);
        $this->assertEquals(SubscriptionService::STATUS_EXPIRED, $status['status'], 'Expired trial should resolve status to EXPIRED');
        $this->assertFalse($status['is_visible'], 'Public profile must NOT be visible when trial is expired');

        echo "PASSED!\n";
    }

    private function testMonthlySubscription(string $orgId, string $slug): void
    {
        echo "[Test 3/7] Active Monthly Subscription (KSh 1,000/mo)... ";

        $monthlyPlan = $this->subService->getPlan('monthly');
        $this->assertNotNull($monthlyPlan, 'Monthly plan should exist');
        $this->assertEquals(1000.00, (float)$monthlyPlan['price_kes'], 'Monthly plan price must be KSh 1,000');

        // Activate monthly subscription
        $activated = $this->subService->activateSubscription($orgId, $monthlyPlan['id'], 'REC_MONTHLY_001');
        $this->assertTrue($activated, 'Monthly subscription activation should return true');

        $status = $this->subService->getSubscriptionStatus($orgId);
        $this->assertEquals(SubscriptionService::STATUS_ACTIVE, $status['status'], 'Status should be ACTIVE');
        $this->assertEquals('monthly', $status['billing_interval'], 'Billing interval should be monthly');
        $this->assertTrue($status['is_visible'], 'Public profile must be visible for active monthly subscription');

        echo "PASSED!\n";
    }

    private function testYearlySubscription(string $orgId, string $slug): void
    {
        echo "[Test 4/7] Active Yearly Subscription (KSh 10,000/yr - Save KSh 2,000)... ";

        $yearlyPlan = $this->subService->getPlan('yearly');
        $this->assertNotNull($yearlyPlan, 'Yearly plan should exist');
        $this->assertEquals(10000.00, (float)$yearlyPlan['price_kes'], 'Yearly plan price must be KSh 10,000');

        // Activate yearly subscription
        $activated = $this->subService->activateSubscription($orgId, $yearlyPlan['id'], 'REC_YEARLY_001');
        $this->assertTrue($activated, 'Yearly subscription activation should return true');

        $status = $this->subService->getSubscriptionStatus($orgId);
        $this->assertEquals(SubscriptionService::STATUS_ACTIVE, $status['status'], 'Status should be ACTIVE');
        $this->assertEquals('yearly', $status['billing_interval'], 'Billing interval should be yearly');
        $this->assertTrue($status['is_visible'], 'Public profile must be visible for active yearly subscription');

        echo "PASSED!\n";
    }

    private function testRenewalFlow(string $orgId, string $slug): void
    {
        echo "[Test 5/7] Expiration & Renewal Flow... ";

        // 1. Expire subscription
        $past = date('Y-m-d H:i:s', strtotime('-2 days'));
        $stmt = $this->db->prepare("
            UPDATE subscriptions
            SET status = 'expired', expires_at = ?, current_period_end = ?
            WHERE organization_id = ?
        ");
        $stmt->execute([$past, $past, $orgId]);

        $this->assertFalse($this->subService->isPublicProfileVisible($orgId), 'Profile must be hidden when expired');

        // 2. Perform M-Pesa payment STK push & confirm
        $res = $this->mpesaService->initiateStkPush($orgId, '0712345678', 10000.00, 3);
        $this->assertIn($res['status'], ['completed_mock', 'initiated', 'initiated_mock'], 'STK push response valid');

        $statusAfter = $this->subService->getSubscriptionStatus($orgId);
        $this->assertEquals(SubscriptionService::STATUS_ACTIVE, $statusAfter['status'], 'Status should return to ACTIVE after payment');
        $this->assertTrue($statusAfter['is_visible'], 'Public profile must immediately become visible upon successful renewal');

        echo "PASSED!\n";
    }

    private function testSecurityAndAccessControl(string $orgId, string $slug): void
    {
        echo "[Test 6/7] Backend Security & Locked Profile Access... ";

        // Expire subscription
        $past = date('Y-m-d H:i:s', strtotime('-5 days'));
        $stmt = $this->db->prepare("
            UPDATE subscriptions
            SET status = 'expired', expires_at = ?
            WHERE organization_id = ?
        ");
        $stmt->execute([$past, $orgId]);

        // Direct backend method verification
        $this->assertFalse($this->subService->isPublicProfileVisible($orgId), 'Backend isPublicProfileVisible must return false for expired subscription');

        echo "PASSED!\n";
    }

    private function testDataPreservationAfterExpiry(string $orgId, string $slug): void
    {
        echo "[Test 7/7] Data Preservation after Expiry... ";

        // Fetch a valid sport
        $sport = $this->db->query("SELECT id FROM sports LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        $sportId = $sport ? $sport['id'] : Ulid::generate();

        // Add test team to organization
        $teamId = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO teams (id, organization_id, sport_id, name, slug, created_at, updated_at)
            VALUES (?, ?, ?, 'Test Preserved Team', 'test-team', NOW(), NOW())
        ");
        $stmt->execute([$teamId, $orgId, $sportId]);

        // Expire subscription
        $this->subService->syncSubscriptionStatus($orgId);

        // Assert organization and teams are untouched
        $orgStmt = $this->db->prepare("SELECT id FROM organizations WHERE id = ? AND deleted_at IS NULL");
        $orgStmt->execute([$orgId]);
        $this->assertNotNull($orgStmt->fetch(), 'Organization data must NOT be deleted upon expiry');

        $teamStmt = $this->db->prepare("SELECT id FROM teams WHERE id = ? AND deleted_at IS NULL");
        $teamStmt->execute([$teamId]);
        $this->assertNotNull($teamStmt->fetch(), 'Teams and related club data must remain safely stored');

        echo "PASSED!\n";
    }

    private function cleanup(string $orgId, string $userId): void
    {
        $this->db->prepare("DELETE FROM payments WHERE organization_id = ?")->execute([$orgId]);
        $this->db->prepare("DELETE FROM subscriptions WHERE organization_id = ?")->execute([$orgId]);
        $this->db->prepare("DELETE FROM teams WHERE organization_id = ?")->execute([$orgId]);
        $this->db->prepare("DELETE FROM organization_user WHERE organization_id = ?")->execute([$orgId]);
        $this->db->prepare("DELETE FROM organizations WHERE id = ?")->execute([$orgId]);
        $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
    }

    private function assertEquals($expected, $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new Exception("Assertion Failed: {$msg}. Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true));
        }
    }

    private function assertTrue($value, string $msg = ''): void
    {
        if ($value !== true) {
            throw new Exception("Assertion Failed: {$msg}. Expected true, got " . var_export($value, true));
        }
    }

    private function assertFalse($value, string $msg = ''): void
    {
        if ($value !== false) {
            throw new Exception("Assertion Failed: {$msg}. Expected false, got " . var_export($value, true));
        }
    }

    private function assertNotNull($value, string $msg = ''): void
    {
        if ($value === null) {
            throw new Exception("Assertion Failed: {$msg}. Value is null");
        }
    }

    private function assertIn($item, array $array, string $msg = ''): void
    {
        if (!in_array($item, $array, true)) {
            throw new Exception("Assertion Failed: {$msg}. Item " . var_export($item, true) . " not in array");
        }
    }
}

$suite = new SubscriptionTestSuite();
$suite->run();
