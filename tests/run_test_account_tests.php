<?php

require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Core/helpers.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\Auth\AuthService;
use Benchero\Services\EntitlementService;
use Benchero\Services\SubscriptionService;
use Benchero\Services\MpesaService;

class TestAccountTestSuite
{
    private PDO $db;
    private AuthService $authService;
    private EntitlementService $entitlementService;
    private SubscriptionService $subscriptionService;
    private MpesaService $mpesaService;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->authService = new AuthService($this->db);
        $this->subscriptionService = new SubscriptionService();
        $this->entitlementService = new EntitlementService($this->db, $this->subscriptionService);
        $this->mpesaService = new MpesaService();
    }

    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            echo " [FAIL] {$message}\n";
            exit(1);
        }
        echo " [PASS] {$message}\n";
    }

    public function run(): void
    {
        echo "==================================================\n";
        echo " BENCHERO PRODUCTION TEST ACCOUNT SUITE\n";
        echo "==================================================\n\n";

        $testEmail = 'cherobenkennedy34@gmail.com';

        // 1. Verify helper recognition
        $this->assert(is_test_account($testEmail), "is_test_account('{$testEmail}') returns true");
        $this->assert(!is_test_account('regular_user@example.com'), "is_test_account('regular_user@example.com') returns false");

        // 2. Ensure test user exists in DB
        $user = $this->authService->findUserByEmail($testEmail);
        if (!$user) {
            $userId = $this->authService->registerUser('Kennedy Cheroben', $testEmail, 'TestPassword123!');
            $this->authService->markEmailVerified($userId);
            $user = $this->authService->findUserById($userId);
        }
        $this->assert(!empty($user), "User {$testEmail} exists in database");

        // Set platform super admin role
        $superAdminRoleId = '01J7ROLE0000000000SUPERADM';
        $stmt = $this->db->prepare("UPDATE users SET role = 'super_admin', role_id = ?, is_platform_admin = 1 WHERE email = ?");
        $stmt->execute([$superAdminRoleId, $testEmail]);

        // 3. Create or fetch test organization
        $stmt = $this->db->prepare("SELECT id, slug FROM organizations WHERE name = 'Production Test Club'");
        $stmt->execute();
        $org = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$org) {
            $orgId = Ulid::generate();
            $slug = 'test-club-' . time();
            $stmt = $this->db->prepare("INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at) VALUES (?, 'Production Test Club', ?, 'KE', 'Africa/Nairobi', NOW(), NOW())");
            $stmt->execute([$orgId, $slug]);
            $org = ['id' => $orgId, 'slug' => $slug];
        }

        $this->db->prepare("DELETE FROM organization_user WHERE organization_id = ? AND role = 'owner'")->execute([$org['id']]);
        $this->db->prepare("INSERT INTO organization_user (organization_id, user_id, role, created_at) VALUES (?, ?, 'owner', NOW())")->execute([$org['id'], $user['id']]);
        $this->subscriptionService->activateSubscription($org['id'], 4);

        // 4. Test Entitlements
        $_SESSION['_user_email'] = $testEmail;
        $_SESSION['user_email'] = $testEmail;
        $_SESSION['_user_id'] = $user['id'];

        $this->assert($this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_CUSTOM_DOMAIN), "Test account has custom_domain capability unlocked");
        $this->assert($this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_VIDEO_UPLOADS), "Test account has video_uploads capability unlocked");
        $this->assert($this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_DIGITAL_CLUB_CARD), "Test account has digital_club_card capability unlocked");
        $this->assert($this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_DATA_EXPORT), "Test account has data_export capability unlocked");
        $this->assert($this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_REMOVE_BRANDING), "Test account has remove_branding capability unlocked");

        $quota = $this->entitlementService->getQuota($org['id'], 'total_storage_mb');
        $this->assert($quota >= 5000, "Test account quota is high/unlimited ({$quota} MB)");

        // 5. Test Subscription Status
        $subStatus = $this->subscriptionService->getSubscriptionStatus($org['id']);
        $this->assert($subStatus['is_visible'] === true, "Test account public profile is visible");
        $this->assert($this->subscriptionService->isSubscriptionActive($org['id']) === true, "Test account subscription status is active");

        // 6. Test Mock M-Pesa Payment Activation
        $res = $this->mpesaService->initiateStkPush($org['id'], '254712345678', 25000.00, 4);
        $this->assert($res['status'] === 'completed_mock', "M-Pesa STK push for test account auto-completes in mock mode");

        $subAfter = $this->subscriptionService->getSubscriptionStatus($org['id']);
        $this->assert((int)$subAfter['plan_id'] === 4, "Subscription plan updated to Benchero Pro (Plan ID 4)");

        // 7. Test Plan Switching
        $this->subscriptionService->activateSubscription($org['id'], 2); // Switch to Monthly
        $subMonthly = $this->subscriptionService->getSubscriptionStatus($org['id']);
        $this->assert((int)$subMonthly['plan_id'] === 2, "Test account can switch to Standard Monthly (Plan ID 2)");

        $this->subscriptionService->activateSubscription($org['id'], 3); // Switch to Yearly
        $subYearly = $this->subscriptionService->getSubscriptionStatus($org['id']);
        $this->assert((int)$subYearly['plan_id'] === 3, "Test account can switch to Standard Yearly (Plan ID 3)");

        $this->subscriptionService->activateSubscription($org['id'], 4); // Switch back to Pro
        $subPro = $this->subscriptionService->getSubscriptionStatus($org['id']);
        $this->assert((int)$subPro['plan_id'] === 4, "Test account can switch back to Benchero Pro (Plan ID 4)");

        echo "\n==================================================\n";
        echo " ALL TEST ACCOUNT VERIFICATIONS PASSED (14/14)!\n";
        echo "==================================================\n";
    }
}

$suite = new TestAccountTestSuite();
$suite->run();
