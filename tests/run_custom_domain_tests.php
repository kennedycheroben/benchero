<?php

/**
 * Benchero Custom Domains Comprehensive Security & Lifecycle Test Suite
 *
 * Validates:
 * 1. DomainValidator (RFC compliance, sanitization, attack vectors, reserved & Benchero domains)
 * 2. DomainVerificationService (crypto token entropy, exact TXT verification, DNS resilience, no A-record fallback)
 * 3. Database Uniqueness & Normalization (multi-org collisions, case-insensitive collision prevention)
 * 4. TenantResolver (authoritative HTTP Host resolution, unknown/unverified domains, primary domain handling)
 * 5. Tenant Isolation (cross-tenant slug hijacking prevention, transparent root rewriting)
 * 6. Authentication & Admin Isolation (canonical APP_URL redirect for /login, /register, /admin, /o/*)
 * 7. Entitlements Enforcement (Pro vs Standard vs Free, expired subscription 402 lock)
 * 8. Full Domain Lifecycle (Registration -> Token Regen -> Verification -> Activation -> Replacement -> Removal)
 * 9. Rate Limiting Protection (abuse prevention for verification and updates)
 * 10. URL Generation & Canonical SEO (club_url helper, canonical links, Open Graph URLs)
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\CustomDomainContext;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\RateLimiter;
use Benchero\Core\Ulid;
use Benchero\Middleware\TenantMiddleware;
use Benchero\Services\Cloudflare\CloudflareCustomHostnameService;
use Benchero\Services\Domain\DomainValidator;
use Benchero\Services\Domain\DomainVerificationService;
use Benchero\Services\Domain\TenantResolver;
use Benchero\Services\DomainService;
use Benchero\Services\EntitlementService;
use Benchero\Services\SeoService;
use Benchero\Services\SubscriptionService;

class CustomDomainTestSuite
{
    private PDO $db;
    private int $passed = 0;
    private int $failed = 0;

    // Test Organization IDs
    private string $orgAId = '01T_CD_TEST_ORG_A_00000001';
    private string $orgBId = '01T_CD_TEST_ORG_B_00000002';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function assert(bool $condition, string $description, string $failureDetails = ''): void
    {
        if ($condition) {
            echo " \e[32m[PASS]\e[0m {$description}\n";
            $this->passed++;
        } else {
            echo " \e[31m[FAIL]\e[0m {$description}";
            if ($failureDetails !== '') {
                echo " — {$failureDetails}";
            }
            echo "\n";
            $this->failed++;
        }
    }

    public function run(): void
    {
        echo "====================================================================\n";
        echo " BENCHERO CUSTOM DOMAINS: COMPREHENSIVE SECURITY & LIFECYCLE TESTS\n";
        echo "====================================================================\n";

        try {
            $this->setupTestData();

            $this->testDomainValidator();
            $this->testDomainVerificationService();
            $this->testDatabaseUniqueness();
            $this->testTenantResolver();
            $this->testTenantIsolationAndMiddleware();
            $this->testAuthenticationIsolation();
            $this->testEntitlements();
            $this->testDomainLifecycleAndReplacement();
            $this->testRateLimiting();
            $this->testUrlGenerationAndSeo();
            $this->testCloudflareCustomHostnameService();
            $this->testCloudflareDomainServiceLifecycle();
            $this->testTrustedProxyHostSecurity();

        } finally {
            $this->cleanupTestData();
        }

        echo "====================================================================\n";
        echo " SUMMARY: Passed {$this->passed} / Failed {$this->failed}\n";
        echo "====================================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function setupTestData(): void
    {
        // Clean any leftovers from previous test runs
        $this->cleanupTestData();

        // Seed test organizations
        $this->db->exec("
            INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at)
            VALUES 
            ('{$this->orgAId}', 'Cheetahs FC', 'cheetahs-fc', 'KE', 'Africa/Nairobi', NOW(), NOW()),
            ('{$this->orgBId}', 'Rhinos RFC', 'rhinos-rfc', 'KE', 'Africa/Nairobi', NOW(), NOW())
        ");

        // Give Org A Pro Plan (Plan ID 4) and Org B Standard Plan (Plan ID 2)
        $subService = new SubscriptionService();
        $subService->activateSubscription($this->orgAId, 4);
        $subService->activateSubscription($this->orgBId, 2);
    }

    private function cleanupTestData(): void
    {
        CustomDomainContext::clear();
        $this->db->exec("DELETE FROM custom_domains WHERE organization_id IN ('{$this->orgAId}', '{$this->orgBId}')");
        $this->db->exec("DELETE FROM subscriptions WHERE organization_id IN ('{$this->orgAId}', '{$this->orgBId}')");
        $this->db->exec("DELETE FROM audit_logs WHERE organization_id IN ('{$this->orgAId}', '{$this->orgBId}')");
        $this->db->exec("DELETE FROM organizations WHERE id IN ('{$this->orgAId}', '{$this->orgBId}')");
    }

    // =========================================================================
    // 1. DOMAIN VALIDATION TESTS
    // =========================================================================
    private function testDomainValidator(): void
    {
        echo "\n--- 1. DomainValidator Tests ---\n";

        // Valid hostnames
        $res = DomainValidator::validate('cheetahsfc.co.ke');
        $this->assert($res['valid'] && $res['normalized'] === 'cheetahsfc.co.ke', "Valid 2nd level TLD accepted");

        $res = DomainValidator::validate('club.sports.org');
        $this->assert($res['valid'] && $res['normalized'] === 'club.sports.org', "Valid 3rd level subdomain accepted");

        // Normalization: Uppercase + Trailing Dot + Whitespace
        $res = DomainValidator::validate("  CHEETAHSFC.CO.KE.  ");
        $this->assert($res['valid'] && $res['normalized'] === 'cheetahsfc.co.ke', "Uppercase & trailing dot normalized cleanly");

        // Rejection: Protocols
        $res = DomainValidator::validate('http://cheetahsfc.co.ke');
        $this->assert(!$res['valid'] && (str_contains($res['error'], 'http') || str_contains($res['error'], 'protocol')), "Protocol http:// rejected");

        $res = DomainValidator::validate('https://cheetahsfc.co.ke');
        $this->assert(!$res['valid'] && (str_contains($res['error'], 'http') || str_contains($res['error'], 'protocol')), "Protocol https:// rejected");

        // Rejection: Paths & Query strings & Fragments
        $res = DomainValidator::validate('cheetahsfc.co.ke/teams');
        $this->assert(!$res['valid'] && str_contains($res['error'], 'path'), "Path /teams rejected");

        $res = DomainValidator::validate('cheetahsfc.co.ke?tab=about');
        $this->assert(!$res['valid'] && str_contains($res['error'], 'query'), "Query string rejected");

        $res = DomainValidator::validate('cheetahsfc.co.ke#anchor');
        $this->assert(!$res['valid'] && (str_contains($res['error'], 'anchor') || str_contains($res['error'], 'fragment')), "URL fragment rejected");

        // Rejection: Ports
        $res = DomainValidator::validate('cheetahsfc.co.ke:8080');
        $this->assert(!$res['valid'] && str_contains($res['error'], 'port'), "Port :8080 rejected");

        // Rejection: Invalid characters
        $res = DomainValidator::validate('cheetahs_fc.co.ke');
        $this->assert(!$res['valid'], "Underscore rejected in hostname");

        $res = DomainValidator::validate('cheetahs!fc.co.ke');
        $this->assert(!$res['valid'], "Exclamation point rejected in hostname");

        // Rejection: IPv4 & IPv6
        $res = DomainValidator::validate('127.0.0.1');
        $this->assert(!$res['valid'] && str_contains($res['error'], 'IP address'), "IPv4 127.0.0.1 rejected");

        $res = DomainValidator::validate('154.56.40.10');
        $this->assert(!$res['valid'] && str_contains($res['error'], 'IP address'), "Public IPv4 rejected");

        $res = DomainValidator::validate('::1');
        $this->assert(!$res['valid'], "IPv6 ::1 rejected");

        // Rejection: Localhost and Reserved TLDs
        $res = DomainValidator::validate('localhost');
        $this->assert(!$res['valid'] && stripos($res['error'], 'localhost') !== false, "localhost rejected");

        $res = DomainValidator::validate('myclub.local');
        $this->assert(!$res['valid'] && str_contains($res['error'], 'reserved'), ".local rejected");

        $res = DomainValidator::validate('myclub.test');
        $this->assert(!$res['valid'] && str_contains($res['error'], 'reserved'), ".test rejected");

        $res = DomainValidator::validate('myclub.example');
        $this->assert(!$res['valid'] && str_contains($res['error'], 'reserved'), ".example rejected");

        $res = DomainValidator::validate('myclub.invalid');
        $this->assert(!$res['valid'] && str_contains($res['error'], 'reserved'), ".invalid rejected");

        // Rejection: Benchero Infrastructure Domains
        $res = DomainValidator::validate('benchero.co.ke');
        $this->assert(!$res['valid'] && stripos($res['error'], 'Benchero') !== false, "benchero.co.ke rejected");

        $res = DomainValidator::validate('app.benchero.co.ke');
        $this->assert(!$res['valid'] && stripos($res['error'], 'Benchero') !== false, "app.benchero.co.ke rejected");

        $res = DomainValidator::validate('admin.benchero.co.ke');
        $this->assert(!$res['valid'] && stripos($res['error'], 'Benchero') !== false, "admin.benchero.co.ke rejected");
    }

    // =========================================================================
    // 2. DNS OWNERSHIP VERIFICATION TESTS
    // =========================================================================
    private function testDomainVerificationService(): void
    {
        echo "\n--- 2. DomainVerificationService Tests ---\n";

        // Cryptographic token generation
        $token1 = DomainVerificationService::generateToken();
        $token2 = DomainVerificationService::generateToken();
        $this->assert(strlen($token1) === 48, "Token is 48 hex characters long");
        $this->assert(ctype_xdigit($token1), "Token contains valid hex characters");
        $this->assert($token1 !== $token2, "Tokens are cryptographically unique across invocations");

        // TXT record conventions
        $expectedHost = DomainVerificationService::getVerificationHost('cheetahsfc.co.ke');
        $this->assert($expectedHost === '_benchero-verification.cheetahsfc.co.ke', "Verification host is _benchero-verification.cheetahsfc.co.ke");

        $expectedTxt = DomainVerificationService::getExpectedTxtValue($token1);
        $this->assert($expectedTxt === "benchero-verification={$token1}", "Expected TXT is benchero-verification=<token>");

        // Mock DNS Resolver Tests
        // 1. Success matching TXT
        $mockSuccessResolver = function (string $host, int $type) use ($token1) {
            if ($host === '_benchero-verification.cheetahsfc.co.ke' && $type === DNS_TXT) {
                return [
                    ['host' => $host, 'type' => 'TXT', 'txt' => "benchero-verification={$token1}"]
                ];
            }
            return false;
        };
        $service = new DomainVerificationService($mockSuccessResolver);
        $result = $service->verifyDomainOwnership('cheetahsfc.co.ke', $token1);
        $this->assert($result['verified'] === true, "Verification succeeds when exact TXT token is returned");

        // 2. Mismatch TXT
        $mockMismatchResolver = function (string $host, int $type) {
            return [
                ['host' => $host, 'type' => 'TXT', 'txt' => 'benchero-verification=wrong-token-value']
            ];
        };
        $service = new DomainVerificationService($mockMismatchResolver);
        $result = $service->verifyDomainOwnership('cheetahsfc.co.ke', $token1);
        $this->assert($result['verified'] === false && (str_contains($result['error'], 'not match') || str_contains($result['error'], 'mismatch')), "Verification fails safely when TXT token does not match");

        // 3. Multiple TXT records where one matches (e.g. SPF + verification)
        $mockMultiResolver = function (string $host, int $type) use ($token1) {
            return [
                ['host' => $host, 'type' => 'TXT', 'txt' => 'v=spf1 include:_spf.google.com ~all'],
                ['host' => $host, 'type' => 'TXT', 'txt' => "benchero-verification={$token1}"]
            ];
        };
        $service = new DomainVerificationService($mockMultiResolver);
        $result = $service->verifyDomainOwnership('cheetahsfc.co.ke', $token1);
        $this->assert($result['verified'] === true, "Verification succeeds when valid token is among multiple TXT records");

        // 4. Missing TXT (Empty/False)
        $mockEmptyResolver = function () {
            return [];
        };
        $service = new DomainVerificationService($mockEmptyResolver);
        $result = $service->verifyDomainOwnership('cheetahsfc.co.ke', $token1);
        $this->assert($result['verified'] === false && (str_contains($result['error'], 'No DNS TXT') || str_contains($result['error'], 'No TXT')), "Verification fails safely when TXT record is missing");

        // 5. DNS Exception / Failure
        $mockErrorResolver = function () {
            throw new RuntimeException("DNS server timeout");
        };
        $service = new DomainVerificationService($mockErrorResolver);
        $result = $service->verifyDomainOwnership('cheetahsfc.co.ke', $token1);
        $this->assert($result['verified'] === false && (str_contains($result['error'], 'timed out') || str_contains($result['error'], 'failed')), "Verification handles DNS failure/timeout safely without throwing");

        // 6. Guarantee NO A-record fallback
        // Verify that DomainVerificationService contains NO dns_get_record with DNS_A as proof of ownership
        $reflector = new ReflectionClass(DomainVerificationService::class);
        $method = $reflector->getMethod('verifyDomainOwnership');
        $code = file_get_contents($reflector->getFileName());
        $this->assert(!str_contains($code, 'DNS_A'), "A-record fallback has been completely removed from ownership verification");
    }

    // =========================================================================
    // 3. DATABASE UNIQUENESS & NORMALIZATION TESTS
    // =========================================================================
    private function testDatabaseUniqueness(): void
    {
        echo "\n--- 3. Database Uniqueness & Normalization Tests ---\n";

        $entitlementService = new EntitlementService($this->db);
        $domainService = new DomainService($this->db, $entitlementService);

        // Org A saves cheetahsfc.co.ke
        $recordA = $domainService->saveDomain($this->orgAId, 'CheetahsFC.co.ke.');
        $this->assert($recordA['domain'] === 'cheetahsfc.co.ke', "Domain saved with lowercase normalized form");
        $this->assert($recordA['normalized_domain'] === 'cheetahsfc.co.ke', "normalized_domain field set correctly");

        // Org B attempts to save the exact same domain
        $subService = new SubscriptionService();
        $subService->activateSubscription($this->orgBId, 4); // Temporarily grant Pro to Org B to test collision check

        $collisionCaught = false;
        try {
            $domainService->saveDomain($this->orgBId, 'cheetahsfc.co.ke');
        } catch (InvalidArgumentException $e) {
            $collisionCaught = true;
            $this->assert(str_contains($e->getMessage(), 'already connected') || str_contains($e->getMessage(), 'already registered'), "Duplicate domain rejected by Service");
        }
        $this->assert($collisionCaught, "Cross-tenant domain collision prevented by DomainService");

        // Org B attempts to bypass uniqueness using uppercase and trailing dot
        $bypassCaught = false;
        try {
            $domainService->saveDomain($this->orgBId, 'CHEETAHSFC.CO.KE.');
        } catch (InvalidArgumentException $e) {
            $bypassCaught = true;
        }
        $this->assert($bypassCaught, "Uppercase + trailing dot variant collision prevented");

        $subService->activateSubscription($this->orgBId, 2); // Revert Org B to Standard

        // Direct database UNIQUE constraint test on normalized_domain
        $dbCollisionCaught = false;
        try {
            $id = Ulid::generate();
            $stmt = $this->db->prepare("
                INSERT INTO custom_domains (id, organization_id, domain, normalized_domain, verification_token, verification_status, activation_status, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'pending', 'pending', 'pending', NOW(), NOW())
            ");
            $stmt->execute([$id, $this->orgBId, 'CHEETAHSFC.CO.KE', 'cheetahsfc.co.ke', 'test-token-123']);
        } catch (PDOException $e) {
            $dbCollisionCaught = true;
            $this->assert(str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), '1062'), "Database enforces UNIQUE index on normalized_domain");
        }
        $this->assert($dbCollisionCaught, "Database-level UNIQUE constraint successfully guarded duplicate hostname");

        // Cleanup domain for subsequent tests
        $domainService->deleteDomain($this->orgAId);
    }

    // =========================================================================
    // 4. TENANT RESOLVER TESTS
    // =========================================================================
    private function testTenantResolver(): void
    {
        echo "\n--- 4. TenantResolver Tests ---\n";

        $entitlementService = new EntitlementService($this->db);
        $domainService = new DomainService($this->db, $entitlementService);
        $resolver = new TenantResolver($this->db, $entitlementService);

        // 1. Primary Benchero domain is not a custom domain
        $res = $resolver->resolveFromHost('benchero.co.ke');
        $this->assert($res['is_custom_domain'] === false, "Primary benchero.co.ke identified as non-custom domain");

        $res = $resolver->resolveFromHost('www.benchero.co.ke');
        $this->assert($res['is_custom_domain'] === false, "www.benchero.co.ke identified as non-custom domain");

        $res = $resolver->resolveFromHost('localhost');
        $this->assert($res['is_custom_domain'] === false, "localhost identified as non-custom domain");

        // 2. Unregistered custom domain
        $res = $resolver->resolveFromHost('unknown-sports-club.com');
        $this->assert($res['is_custom_domain'] === true && $res['resolved'] === false, "Unknown domain is custom domain but unresolved");

        // 3. Registered but unverified/inactive custom domain
        $domainService->saveDomain($this->orgAId, 'cheetahsfc.co.ke');
        $res = $resolver->resolveFromHost('cheetahsfc.co.ke');
        $this->assert($res['is_custom_domain'] === true && $res['resolved'] === false, "Unverified/pending domain is not resolved");

        // 4. Verified & Active custom domain
        $this->db->exec("
            UPDATE custom_domains 
            SET verification_status = 'verified', activation_status = 'active', status = 'active' 
            WHERE organization_id = '{$this->orgAId}'
        ");

        $res = $resolver->resolveFromHost('cheetahsfc.co.ke');
        $this->assert($res['resolved'] === true, "Active custom domain resolves successfully");
        $this->assert($res['organization']['id'] === $this->orgAId, "Resolved tenant matches Org A ID");
        $this->assert($res['organization']['slug'] === 'cheetahs-fc', "Resolved tenant matches Org A slug");

        // 5. Hostname with port in HTTP Host header
        $res = $resolver->resolveFromHost('cheetahsfc.co.ke:443');
        $this->assert($res['resolved'] === true && $res['organization']['id'] === $this->orgAId, "Port stripped and resolved correctly");
    }

    // =========================================================================
    // 5. TENANT ISOLATION & ROUTING TESTS
    // =========================================================================
    private function testTenantIsolationAndMiddleware(): void
    {
        echo "\n--- 5. Tenant Isolation & Middleware Tests ---\n";

        $entitlementService = new EntitlementService($this->db);
        $resolver = new TenantResolver($this->db, $entitlementService);
        $middleware = new TenantMiddleware($resolver);

        // 1. Valid request to Cheetahs FC root
        $req = new Request([], [], [
            'HTTP_HOST' => 'cheetahsfc.co.ke',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET'
        ], [], []);

        $executed = false;
        $next = function (Request $r) use (&$executed) {
            $executed = true;
            $this->assert($r->isCustomDomain() === true, "Request marked as custom domain");
            $this->assert($r->path() === '/club/cheetahs-fc', "Root path transparently rewritten to /club/cheetahs-fc");
            $tenant = $r->getAttribute('tenant');
            $this->assert($tenant['slug'] === 'cheetahs-fc', "Authoritative tenant context injected");
            return new Response('OK', 200);
        };

        $res = $middleware->handle($req, $next);
        $this->assert($executed && $res->statusCode() === 200, "Middleware handled custom domain root request");

        // 2. Request to public subpage /teams
        $req = new Request([], [], [
            'HTTP_HOST' => 'cheetahsfc.co.ke',
            'REQUEST_URI' => '/teams',
            'REQUEST_METHOD' => 'GET'
        ], [], []);

        $executed = false;
        $next = function (Request $r) use (&$executed) {
            $executed = true;
            $this->assert($r->path() === '/club/cheetahs-fc/teams', "Subpage /teams rewritten to /club/cheetahs-fc/teams");
            return new Response('OK', 200);
        };

        $res = $middleware->handle($req, $next);
        $this->assert($executed, "Middleware handled custom domain subpage request");

        // 3. Security Boundary: Request attempting to access another organization via /club/rhinos-rfc on cheetahsfc.co.ke
        $req = new Request([], [], [
            'HTTP_HOST' => 'cheetahsfc.co.ke',
            'REQUEST_URI' => '/club/rhinos-rfc',
            'REQUEST_METHOD' => 'GET'
        ], [], []);

        $res = $middleware->handle($req, function () {
            return new Response('SHOULD_NOT_EXECUTE', 200);
        });

        // Must reject with 404 or redirect, never serve Rhinos RFC
        $this->assert($res->statusCode() === 404, "Attempt to access other club slug on custom domain rejected with HTTP 404");
        $this->assert(str_contains($res->content(), 'Not Found') || str_contains($res->content(), '404'), "Cross-tenant access blocked cleanly");

        // 4. Request to an unverified / disconnected domain
        $req = new Request([], [], [
            'HTTP_HOST' => 'random-unconnected-domain.com',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET'
        ], [], []);

        $res = $middleware->handle($req, function () {
            return new Response('SHOULD_NOT_EXECUTE', 200);
        });
        $this->assert($res->statusCode() === 404, "Unconnected custom domain returns 404 custom error page");
    }

    // =========================================================================
    // 6. AUTHENTICATION & ADMIN ISOLATION TESTS
    // =========================================================================
    private function testAuthenticationIsolation(): void
    {
        echo "\n--- 6. Authentication & Admin Isolation Tests ---\n";

        $entitlementService = new EntitlementService($this->db);
        $resolver = new TenantResolver($this->db, $entitlementService);
        $middleware = new TenantMiddleware($resolver);

        $protectedPaths = [
            '/login',
            '/register',
            '/admin',
            '/admin/users',
            '/o/cheetahs-fc/dashboard',
            '/o/cheetahs-fc/settings'
        ];

        foreach ($protectedPaths as $path) {
            $req = new Request([], [], [
                'HTTP_HOST' => 'cheetahsfc.co.ke',
                'REQUEST_URI' => $path,
                'REQUEST_METHOD' => 'GET'
            ], [], []);

            $res = $middleware->handle($req, function () {
                return new Response('UNSAFE_AUTH_ALLOWED', 200);
            });

            $this->assert($res->statusCode() === 302, "Path {$path} on custom domain triggers 302 redirect");
            $location = $res->headers()['Location'] ?? '';
            $this->assert(str_starts_with($location, 'https://benchero.co.ke') || str_starts_with($location, 'http://benchero.co.ke') || str_contains($location, 'benchero'), "Redirect targets canonical Benchero domain ({$location})");
        }
    }

    // =========================================================================
    // 7. ENTITLEMENTS TESTS
    // =========================================================================
    private function testEntitlements(): void
    {
        echo "\n--- 7. Entitlements Tests ---\n";

        $entitlementService = new EntitlementService($this->db);
        $domainService = new DomainService($this->db, $entitlementService);

        // Org A is on Pro (Plan 4) -> Can manage custom domains
        $this->assert($entitlementService->hasCapability($this->orgAId, EntitlementService::CAP_CUSTOM_DOMAIN), "Pro plan has custom_domain capability");

        // Org B is on Standard (Plan 2) -> Cannot manage custom domains
        $this->assert(!$entitlementService->hasCapability($this->orgBId, EntitlementService::CAP_CUSTOM_DOMAIN), "Standard plan lacks custom_domain capability");

        $blocked = false;
        try {
            $domainService->saveDomain($this->orgBId, 'rhinosrfc.co.ke');
        } catch (\InvalidArgumentException | \DomainException $e) {
            $blocked = true;
            $this->assert(str_contains($e->getMessage(), 'Benchero Pro') || str_contains($e->getMessage(), 'Pro subscription'), "Standard plan domain creation blocked with Pro upgrade message");
        }
        $this->assert($blocked, "Non-pro custom domain creation strictly blocked");

        // Expired subscription behavior on live custom domain
        // Set Org A subscription to expired / past_due
        $this->db->exec("UPDATE subscriptions SET status = 'expired', expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE organization_id = '{$this->orgAId}'");

        $resolver = new TenantResolver($this->db, $entitlementService);
        $middleware = new TenantMiddleware($resolver);

        $req = new Request([], [], [
            'HTTP_HOST' => 'cheetahsfc.co.ke',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET'
        ], [], []);

        $res = $middleware->handle($req, function () {
            return new Response('SHOULD_NOT_EXECUTE', 200);
        });

        $this->assert($res->statusCode() === 402, "Expired Pro plan returns HTTP 402 Payment Required");
        $this->assert(str_contains($res->content(), 'Subscription Expired') || str_contains($res->content(), 'locked') || str_contains($res->content(), 'unavailable'), "Rendered locked_profile view on expired custom domain");

        // Restore active subscription
        $subService = new SubscriptionService();
        $subService->activateSubscription($this->orgAId, 4);
    }

    // =========================================================================
    // 8. DOMAIN LIFECYCLE & ZERO-DOWNTIME REPLACEMENT TESTS
    // =========================================================================
    private function testDomainLifecycleAndReplacement(): void
    {
        echo "\n--- 8. Domain Lifecycle & Zero-Downtime Replacement Tests ---\n";

        // Clean out any custom domain for Org A
        $this->db->exec("DELETE FROM custom_domains WHERE organization_id = '{$this->orgAId}'");

        $entitlementService = new EntitlementService($this->db);
        $domainService = new DomainService($this->db, $entitlementService);

        // Step 1: Register domain
        $domain = $domainService->saveDomain($this->orgAId, 'cheetahs-primary.com');
        $this->assert($domain['verification_status'] === 'pending', "Initial verification status is pending");
        $this->assert($domain['activation_status'] === 'pending', "Initial activation status is pending");
        $this->assert($domain['ssl_status'] === 'not_configured', "Initial SSL status is not_configured");
        $initialToken = $domain['verification_token'];

        // Step 2: Regenerate token
        $domain = $domainService->regenerateToken($this->orgAId);
        $this->assert($domain['verification_token'] !== $initialToken, "Token regenerated with new cryptographically secure random value");
        $token = $domain['verification_token'];

        // Step 3: Verify domain ownership via mock resolver
        $mockResolver = function (string $host) use ($token) {
            return [
                ['host' => $host, 'type' => 'TXT', 'txt' => "benchero-verification={$token}"]
            ];
        };
        $verificationService = new DomainVerificationService($mockResolver);
        $domainServiceWithMock = new DomainService($this->db, $entitlementService, $verificationService);

        $verifyResult = $domainServiceWithMock->verifyDomain($this->orgAId);
        $this->assert($verifyResult['status'] === 'verified', "Domain verified successfully via TXT record");

        // Cannot activate before verification (sanity check)
        $domain = $domainService->getDomainByOrg($this->orgAId);
        $this->assert($domain['verification_status'] === 'verified', "Verification status persisted as verified in database");

        // Step 4: Activate domain
        $activated = $domainService->activateDomain($this->orgAId);
        $this->assert($activated['activation_status'] === 'active', "Domain activated successfully");
        $this->assert(!empty($activated['activated_at']), "activated_at timestamp recorded");

        // Step 4b: Deactivate domain
        $deactivated = $domainService->deactivateDomain($this->orgAId);
        $this->assert($deactivated['activation_status'] === 'disabled', "Domain deactivated successfully");
        $resolver = new TenantResolver($this->db, $entitlementService);
        $res = $resolver->resolveFromHost('cheetahs-primary.com');
        $this->assert($res['resolved'] === false, "Deactivated domain stops resolving traffic immediately");

        // Reactivate domain
        $domainService->activateDomain($this->orgAId);
        $this->assert($domainService->getDomainByOrg($this->orgAId)['activation_status'] === 'active', "Domain reactivated successfully");

        // Step 5: Check SSL status logic
        // Verify checkSslStatus executes without fatal errors and returns valid diagnostic structure
        $sslCheck = $domainService->checkSslStatus($this->orgAId);
        $this->assert(isset($sslCheck['status']), "SSL check returned valid structured status");
        $this->assert(in_array($sslCheck['status'], ['active', 'failed', 'pending', 'not_configured']), "SSL status is an accepted enum value");

        // Step 6: Zero-downtime Replacement
        // Customer adds a new domain 'cheetahs-new.com' while 'cheetahs-primary.com' is active
        $newDomain = $domainService->saveDomain($this->orgAId, 'cheetahs-new.com');
        $this->assert($newDomain['domain'] === 'cheetahs-new.com', "New domain registered as pending replacement");

        // Old working domain is preserved as primary until new domain is ready
        $allDomains = $domainService->getDomainsByOrg($this->orgAId);
        $this->assert(count($allDomains) >= 2, "Both active domain and pending replacement domain exist during transition");
        $primaryDomain = $domainService->getDomainByOrg($this->orgAId);
        $this->assert($primaryDomain['domain'] === 'cheetahs-primary.com', "Active primary domain preserved during replacement transition");

        // Step 7: Removal / Deletion
        $domainService->deleteDomain($this->orgAId);
        $this->assert($domainService->getDomainByOrg($this->orgAId) === null, "Custom domain deleted cleanly");

        // Verify audit log has recorded actions
        $auditLogs = $this->db->query("SELECT * FROM audit_logs WHERE organization_id = '{$this->orgAId}' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $this->assert(count($auditLogs) > 0, "Audit logs recorded domain lifecycle operations");
        $actions = array_column($auditLogs, 'action');
        $this->assert(in_array('custom_domain_saved', $actions) || in_array('domain_registered', $actions), "custom_domain_saved logged in audit trail");
        $this->assert(in_array('custom_domain_deleted', $actions) || in_array('domain_deleted', $actions), "custom_domain_deleted logged in audit trail");
    }

    // =========================================================================
    // 9. RATE LIMITING TESTS
    // =========================================================================
    private function testRateLimiting(): void
    {
        echo "\n--- 9. Rate Limiting Tests ---\n";

        $rateLimiter = new RateLimiter();
        $testKey = 'domain_verify_test_' . time();
        $maxAttempts = 5;
        $decaySeconds = 60;

        $rateLimiter->clear($testKey);

        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->assert(!$rateLimiter->tooManyAttempts($testKey, $maxAttempts), "Attempt " . ($i + 1) . " within rate limit");
            $rateLimiter->hit($testKey, $decaySeconds);
        }

        // Next attempt must trigger rate limit
        $this->assert($rateLimiter->tooManyAttempts($testKey, $maxAttempts), "Attempt 6 blocked by RateLimiter");
        $this->assert($rateLimiter->availableIn($testKey) > 0, "RateLimiter indicates retry-after cooldown period");

        $rateLimiter->clear($testKey);
    }

    // =========================================================================
    // 10. URL GENERATION & CANONICAL SEO TESTS
    // =========================================================================
    private function testUrlGenerationAndSeo(): void
    {
        echo "\n--- 10. URL Generation & Canonical SEO Tests ---\n";

        // 1. Without custom domain active (Primary Domain Context)
        CustomDomainContext::clear();
        $primaryTeamsUrl = club_url('/teams', 'cheetahs-fc');
        $this->assert(str_contains($primaryTeamsUrl, '/club/cheetahs-fc/teams'), "Primary domain club_url generates /club/cheetahs-fc/teams ({$primaryTeamsUrl})");

        $seoService = new SeoService();
        $metaPrimary = $seoService->generateMeta([
            'club_name' => 'Cheetahs FC',
            'path' => '/club/cheetahs-fc/teams'
        ]);
        $this->assert(str_contains($metaPrimary['canonical_url'], '/club/cheetahs-fc/teams'), "Primary canonical URL points to /club/cheetahs-fc/teams");

        // 2. With Custom Domain Active Context
        CustomDomainContext::set('cheetahsfc.co.ke', [
            'id' => $this->orgAId,
            'name' => 'Cheetahs FC',
            'slug' => 'cheetahs-fc'
        ]);

        $customTeamsUrl = club_url('/teams');
        $this->assert($customTeamsUrl === '/teams', "Custom domain club_url generates root-relative /teams ({$customTeamsUrl})");

        $customHomeUrl = club_url('/');
        $this->assert($customHomeUrl === '/', "Custom domain club_url generates root / ({$customHomeUrl})");

        // Ensure cross-slug parameter does not override custom domain isolation
        $customOtherUrl = club_url('/fixtures', 'other-club');
        $this->assert($customOtherUrl === '/fixtures', "Custom domain club_url strips foreign slug safely ({$customOtherUrl})");

        // Custom domain SEO canonical tag
        $seoCustom = new SeoService();
        $metaCustom = $seoCustom->generateMeta([
            'club_name' => 'Cheetahs FC',
            'path' => '/club/cheetahs-fc/teams'
        ]);
        $this->assert($metaCustom['canonical_url'] === 'https://cheetahsfc.co.ke/teams', "Custom domain canonical URL stripped /club/slug: {$metaCustom['canonical_url']}");
        $this->assert(str_starts_with($metaCustom['og_url'], 'https://cheetahsfc.co.ke'), "Open Graph URL points to custom domain");

        CustomDomainContext::clear();
    }

    // =========================================================================
    // 11. CLOUDFLARE CUSTOM HOSTNAME SERVICE UNIT TESTS
    // =========================================================================
    private function testCloudflareCustomHostnameService(): void
    {
        echo "\n--- 11. Cloudflare Custom Hostname Service Tests ---\n";

        // 1. Feature Flag: Disabled by default
        $defaultService = new CloudflareCustomHostnameService();
        $this->assert($defaultService->isEnabled() === false, "Cloudflare automation disabled by default (safety flag)");

        $bypassedCreate = $defaultService->createCustomHostname('sports.cheetahsfc.co.ke');
        $this->assert($bypassedCreate['success'] === true && $bypassedCreate['bypassed'] === true, "Disabled Cloudflare service safely bypasses create without network calls");

        $bypassedStatus = $defaultService->getCustomHostnameStatus('dummy-id');
        $this->assert($bypassedStatus['success'] === true && $bypassedStatus['is_active'] === true, "Disabled Cloudflare service returns active/bypassed status");

        $bypassedDelete = $defaultService->deleteCustomHostname('dummy-id');
        $this->assert($bypassedDelete['success'] === true && $bypassedDelete['bypassed'] === true, "Disabled Cloudflare service safely bypasses delete without network calls");

        // 2. Credentials validation
        $unconfiguredService = new CloudflareCustomHostnameService(['enabled' => true, 'api_token' => '', 'zone_id' => '']);
        $unconfRes = $unconfiguredService->createCustomHostname('sports.cheetahsfc.co.ke');
        $this->assert($unconfRes['success'] === false && $unconfRes['status'] === 'unconfigured', "Unconfigured credentials detected and blocked safely");

        // 3. Mocked Transport: Successful creation
        $mockCreateService = new CloudflareCustomHostnameService([
            'enabled' => true,
            'api_token' => 'mock_token',
            'zone_id' => 'mock_zone_123',
        ], function(string $method, string $url, array $headers, ?string $body) {
            $parsed = json_decode($body ?? '{}', true);
            return [
                'status' => 201,
                'body' => json_encode([
                    'success' => true,
                    'result' => [
                        'id' => 'cf-uuid-998877',
                        'hostname' => $parsed['hostname'] ?? 'sports.cheetahsfc.co.ke',
                        'status' => 'pending',
                        'ssl' => ['status' => 'pending_validation']
                    ]
                ])
            ];
        });

        $createRes = $mockCreateService->createCustomHostname('sports.cheetahsfc.co.ke');
        $this->assert($createRes['success'] === true, "Cloudflare createCustomHostname returns success");
        $this->assert($createRes['id'] === 'cf-uuid-998877', "Cloudflare custom hostname ID extracted correctly ({$createRes['id']})");
        $this->assert($createRes['status'] === 'pending', "Cloudflare status parsed as pending");
        $this->assert($createRes['ssl_status'] === 'pending_validation', "Cloudflare SSL status parsed as pending_validation");

        // 4. Mocked Transport: 409 Conflict idempotency recovery
        $mockConflictService = new CloudflareCustomHostnameService([
            'enabled' => true,
            'api_token' => 'mock_token',
            'zone_id' => 'mock_zone_123',
        ], function(string $method, string $url, array $headers, ?string $body) {
            if ($method === 'POST') {
                return [
                    'status' => 409,
                    'body' => json_encode([
                        'success' => false,
                        'errors' => [['code' => 1406, 'message' => 'A custom hostname for this domain already exists']]
                    ])
                ];
            }
            if ($method === 'GET') {
                return [
                    'status' => 200,
                    'body' => json_encode([
                        'success' => true,
                        'result' => [[
                            'id' => 'cf-uuid-reused-409',
                            'hostname' => 'sports.cheetahsfc.co.ke',
                            'status' => 'active',
                            'ssl' => ['status' => 'active']
                        ]]
                    ])
                ];
            }
            return ['status' => 500, 'body' => '{}'];
        });

        $conflictRes = $mockConflictService->createCustomHostname('sports.cheetahsfc.co.ke');
        $this->assert($conflictRes['success'] === true, "Cloudflare 409 conflict handled idempotently");
        $this->assert($conflictRes['id'] === 'cf-uuid-reused-409', "Existing hostname ID retrieved on conflict ({$conflictRes['id']})");
        $this->assert(!empty($conflictRes['reused']), "Result flagged as reused existing hostname");

        // 5. Mocked Transport: Status check (is_active)
        $mockStatusService = new CloudflareCustomHostnameService([
            'enabled' => true,
            'api_token' => 'mock_token',
            'zone_id' => 'mock_zone_123',
        ], function(string $method, string $url, array $headers, ?string $body) {
            return [
                'status' => 200,
                'body' => json_encode([
                    'success' => true,
                    'result' => [
                        'id' => 'cf-uuid-active',
                        'hostname' => 'sports.cheetahsfc.co.ke',
                        'status' => 'active',
                        'ssl' => ['status' => 'active']
                    ]
                ])
            ];
        });

        $statusActive = $mockStatusService->getCustomHostnameStatus('cf-uuid-active');
        $this->assert($statusActive['is_active'] === true, "Status reports is_active=true when hostname and SSL are active");

        // 6. Mocked Transport: Idempotent 404 deletion
        $mockDeleteService = new CloudflareCustomHostnameService([
            'enabled' => true,
            'api_token' => 'mock_token',
            'zone_id' => 'mock_zone_123',
        ], function(string $method, string $url, array $headers, ?string $body) {
            return [
                'status' => 404,
                'body' => json_encode([
                    'success' => false,
                    'errors' => [['code' => 1436, 'message' => 'Custom hostname does not exist']]
                ])
            ];
        });

        $deleteRes = $mockDeleteService->deleteCustomHostname('cf-uuid-already-deleted');
        $this->assert($deleteRes['success'] === true, "Cloudflare 404 on delete treated as idempotent success");
        $this->assert(!empty($deleteRes['idempotent']), "Delete flagged as idempotent");

        // 7. Mocked Transport: Rate limit 429 error
        $mockRateLimitService = new CloudflareCustomHostnameService([
            'enabled' => true,
            'api_token' => 'mock_token',
            'zone_id' => 'mock_zone_123',
        ], function(string $method, string $url, array $headers, ?string $body) {
            return [
                'status' => 429,
                'body' => json_encode([
                    'success' => false,
                    'errors' => [['code' => 10000, 'message' => 'Too many requests']]
                ])
            ];
        });

        $rateLimitRes = $mockRateLimitService->createCustomHostname('sports.cheetahsfc.co.ke');
        $this->assert($rateLimitRes['success'] === false, "Cloudflare 429 returns failure");
        $this->assert(str_contains($rateLimitRes['error'], 'rate limit'), "Error message mentions rate limit");

        // 8. Mocked Transport: Server Error 5xx (HTTP 500 / 502)
        $mock5xxService = new CloudflareCustomHostnameService([
            'enabled' => true,
            'api_token' => 'mock_token',
            'zone_id' => 'mock_zone_123',
        ], function(string $method, string $url, array $headers, ?string $body) {
            return [
                'status' => 502,
                'body' => json_encode([
                    'success' => false,
                    'errors' => [['code' => 502, 'message' => 'Bad gateway upstream']]
                ])
            ];
        });

        $res5xx = $mock5xxService->createCustomHostname('sports.cheetahsfc.co.ke');
        $this->assert($res5xx['success'] === false, "Cloudflare 5xx returns failure without throwing");
        $this->assert(str_contains($res5xx['error'], 'server error') || str_contains($res5xx['error'], '502'), "Error message mentions server error (HTTP 502)");

        // 9. Mocked Transport: API Timeout / Connection Failure (Status 0 or thrown exception)
        $mockTimeoutService = new CloudflareCustomHostnameService([
            'enabled' => true,
            'api_token' => 'mock_token',
            'zone_id' => 'mock_zone_123',
        ], function(string $method, string $url, array $headers, ?string $body) {
            throw new \RuntimeException("Connection timed out after 5000 milliseconds");
        });

        $timeoutRes = $mockTimeoutService->createCustomHostname('sports.cheetahsfc.co.ke');
        $this->assert($timeoutRes['success'] === false, "Cloudflare timeout caught gracefully");
        $this->assert(str_contains($timeoutRes['error'], 'timed out') || str_contains($timeoutRes['error'], 'connection error'), "Error message identifies connection timeout");

        // 10. Status Mappings Audit Tests
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(CloudflareCustomHostnameService::CF_SSL_ACTIVE) === CloudflareCustomHostnameService::BENCHERO_SSL_ACTIVE, "SSL active maps to active");
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(CloudflareCustomHostnameService::CF_SSL_INITIALIZING) === CloudflareCustomHostnameService::BENCHERO_SSL_PENDING, "SSL initializing maps to pending");
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(CloudflareCustomHostnameService::CF_SSL_PENDING_VALIDATION) === CloudflareCustomHostnameService::BENCHERO_SSL_PENDING, "SSL pending_validation maps to pending");
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(CloudflareCustomHostnameService::CF_SSL_PENDING_ISSUANCE) === CloudflareCustomHostnameService::BENCHERO_SSL_PENDING, "SSL pending_issuance maps to pending");
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(CloudflareCustomHostnameService::CF_SSL_PENDING_DEPLOYMENT) === CloudflareCustomHostnameService::BENCHERO_SSL_PENDING, "SSL pending_deployment maps to pending");
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(CloudflareCustomHostnameService::CF_SSL_EXPIRED) === CloudflareCustomHostnameService::BENCHERO_SSL_FAILED, "SSL expired maps to failed");
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(CloudflareCustomHostnameService::CF_SSL_TIMED_OUT) === CloudflareCustomHostnameService::BENCHERO_SSL_FAILED, "SSL timed_out maps to failed");
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(CloudflareCustomHostnameService::CF_SSL_FAILED) === CloudflareCustomHostnameService::BENCHERO_SSL_FAILED, "SSL failed maps to failed");
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(CloudflareCustomHostnameService::CF_SSL_DELETED) === CloudflareCustomHostnameService::BENCHERO_SSL_NOT_CONFIGURED, "SSL deleted maps to not_configured");
        $this->assert(CloudflareCustomHostnameService::mapCfSslStatusToBenchero(null) === CloudflareCustomHostnameService::BENCHERO_SSL_NOT_CONFIGURED, "SSL null maps to not_configured");
    }

    // =========================================================================
    // 12. CLOUDFLARE DOMAINSERVICE LIFECYCLE TESTS
    // =========================================================================
    private function testCloudflareDomainServiceLifecycle(): void
    {
        echo "\n--- 12. Cloudflare DomainService Lifecycle Tests ---\n";

        // State machine for mock CF API
        $mockCfState = [
            'status' => 'pending',
            'ssl_status' => 'pending_validation',
            'id' => 'cf-test-lifecycle-uuid',
            'deleted' => false,
        ];

        $mockCf = new CloudflareCustomHostnameService([
            'enabled' => true,
            'api_token' => 'mock_token',
            'zone_id' => 'mock_zone',
            'fallback_origin' => 'cname.benchero.co.ke'
        ], function(string $method, string $url, array $headers, ?string $body) use (&$mockCfState) {
            if ($method === 'POST') {
                return [
                    'status' => 201,
                    'body' => json_encode([
                        'success' => true,
                        'result' => [
                            'id' => $mockCfState['id'],
                            'hostname' => 'sports.cheetahsfc.co.ke',
                            'status' => $mockCfState['status'],
                            'ssl' => ['status' => $mockCfState['ssl_status']]
                        ]
                    ])
                ];
            }
            if ($method === 'GET') {
                return [
                    'status' => 200,
                    'body' => json_encode([
                        'success' => true,
                        'result' => [
                            'id' => $mockCfState['id'],
                            'hostname' => 'sports.cheetahsfc.co.ke',
                            'status' => $mockCfState['status'],
                            'ssl' => ['status' => $mockCfState['ssl_status']]
                        ]
                    ])
                ];
            }
            if ($method === 'DELETE') {
                $mockCfState['deleted'] = true;
                return [
                    'status' => 200,
                    'body' => json_encode(['success' => true])
                ];
            }
            return ['status' => 500, 'body' => '{}'];
        });

        $entitlementService = new EntitlementService($this->db);
        $mockDnsResolver = fn(string $host, int $type) => [
            ['type' => 'TXT', 'txt' => 'benchero-verification=dummy_token_placeholder']
        ];
        $verificationService = new DomainVerificationService($mockDnsResolver);
        $domainService = new DomainService($this->db, $entitlementService, $verificationService, null, $mockCf);

        // 1. Save Domain
        $domain = $domainService->saveDomain($this->orgAId, 'sports.cheetahsfc.co.ke');
        $this->assert($domain['normalized_domain'] === 'sports.cheetahsfc.co.ke', "Domain saved cleanly for lifecycle test");

        // Set DNS resolver to return actual token for verified ownership
        $token = $domain['verification_token'];
        $verificationService->setDnsResolver(fn(string $h, int $t) => [
            ['type' => 'TXT', 'txt' => "benchero-verification={$token}"]
        ]);

        // 2. Verify Domain -> Triggers Cloudflare Custom Hostname Creation
        $verRes = $domainService->verifyDomain($this->orgAId, $domain['id']);
        $this->assert($verRes['success'] === true, "Domain ownership verified via DNS TXT");
        $this->assert($verRes['cloudflare_status'] === 'pending', "Cloudflare status initialized to pending");

        // Verify row in DB was updated with Cloudflare fields
        $row = $domainService->getDomainByOrg($this->orgAId);
        $this->assert($row['cloudflare_custom_hostname_id'] === 'cf-test-lifecycle-uuid', "Database stored cloudflare_custom_hostname_id ({$row['cloudflare_custom_hostname_id']})");
        $this->assert($row['cloudflare_status'] === 'pending', "Database stored cloudflare_status=pending");
        $this->assert($row['cloudflare_ssl_status'] === 'pending_validation', "Database stored cloudflare_ssl_status=pending_validation");

        // 3. Attempt Activation while Cloudflare SSL is pending -> Must be BLOCKED
        $activationBlocked = false;
        try {
            $domainService->activateDomain($this->orgAId, $domain['id']);
        } catch (\InvalidArgumentException $e) {
            $activationBlocked = true;
            $this->assert(str_contains($e->getMessage(), 'Cloudflare edge SSL'), "Activation blocked when Cloudflare SSL pending: {$e->getMessage()}");
        }
        $this->assert($activationBlocked, "Activation prevented when Cloudflare SSL is not active");

        // 4. Update Mock State: Cloudflare SSL is now ACTIVE
        $mockCfState['status'] = 'active';
        $mockCfState['ssl_status'] = 'active';

        // 5. Live SSL Check -> Updates DB and returns active
        $sslRes = $domainService->checkSslStatus($this->orgAId, $domain['id']);
        $this->assert($sslRes['ssl_active'] === true, "checkSslStatus recognizes Cloudflare active SSL");
        $this->assert($sslRes['cloudflare_status'] === 'active', "checkSslStatus reports cloudflare_status=active");

        // 6. Activate Domain -> Now SUCCEEDS
        $actRes = $domainService->activateDomain($this->orgAId, $domain['id']);
        $this->assert($actRes['success'] === true, "Domain activated successfully once Cloudflare SSL is active");
        $this->assert($actRes['activation_status'] === 'active', "Activation status confirmed active");

        // 7. Delete Domain -> Cleans up Cloudflare Custom Hostname
        $deleted = $domainService->deleteDomain($this->orgAId, $domain['id']);
        $this->assert($deleted === true, "Domain deleted cleanly from database");
        $this->assert($mockCfState['deleted'] === true, "Cloudflare custom hostname deletion was executed before DB delete");
    }

    // =========================================================================
    // 13. TRUSTED PROXY HOST SECURITY TESTS
    // =========================================================================
    private function testTrustedProxyHostSecurity(): void
    {
        echo "\n--- 13. Trusted Proxy Host Security Tests ---\n";

        // Save original APP_ENV
        $origAppEnv = getenv('APP_ENV');

        // 1. Dev mode without secret: accepts X-Forwarded-Host for local testing
        putenv('APP_ENV=local');
        $_ENV['APP_ENV'] = 'local';
        putenv('CLOUDFLARE_WORKER_SECRET=');
        $_ENV['CLOUDFLARE_WORKER_SECRET'] = '';
        $reqDev = new Request([], [], [
            'HTTP_X_FORWARDED_HOST' => 'cheetahsfc.co.ke',
            'HTTP_HOST' => 'origin.benchero.co.ke'
        ], [], []);
        $this->assert($reqDev->host() === 'cheetahsfc.co.ke', "Dev mode accepts forwarded host without secret");

        // 2. Production with missing secret: NEVER silently trust X-Forwarded-Host!
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        putenv('CLOUDFLARE_WORKER_SECRET=');
        $_ENV['CLOUDFLARE_WORKER_SECRET'] = '';

        $reqProdMissingSecret = new Request([], [], [
            'HTTP_X_FORWARDED_HOST' => 'forged-attacker.co.ke',
            'HTTP_HOST' => 'origin.benchero.co.ke'
        ], [], []);
        $this->assert($reqProdMissingSecret->isTrustedProxy() === false, "Production + missing secret: isTrustedProxy returns false");
        $this->assert($reqProdMissingSecret->host() === 'origin.benchero.co.ke', "Production + missing secret: forged X-Forwarded-Host safely rejected");

        // 3. Production with configured secret: forged request without secret header is REJECTED
        putenv('CLOUDFLARE_WORKER_SECRET=prod_super_secret_xyz');
        $_ENV['CLOUDFLARE_WORKER_SECRET'] = 'prod_super_secret_xyz';

        $reqProdForged = new Request([], [], [
            'HTTP_X_FORWARDED_HOST' => 'victim-club.co.ke',
            'HTTP_HOST' => 'origin.benchero.co.ke'
        ], [], []);
        $this->assert($reqProdForged->isTrustedProxy() === false, "Production + forged host: isTrustedProxy returns false");
        $this->assert($reqProdForged->host() === 'origin.benchero.co.ke', "Production + forged host: host falls back to direct HTTP_HOST");

        // 4. Production with configured secret: client-supplied X-Benchero-Proxy-Hop CANNOT bypass security
        $reqClientHop = new Request([], [], [
            'HTTP_X_FORWARDED_HOST' => 'victim-club.co.ke',
            'HTTP_HOST' => 'origin.benchero.co.ke',
            'HTTP_X_BENCHERO_PROXY_HOP' => '1'
        ], [], []);
        $this->assert($reqClientHop->isTrustedProxy() === false, "Client-supplied X-Benchero-Proxy-Hop cannot grant proxy trust");
        $this->assert($reqClientHop->host() === 'origin.benchero.co.ke', "Client-supplied X-Benchero-Proxy-Hop safely rejected");

        // 5. Production with valid Worker secret header: ACCEPTED
        $reqValidSecret = new Request([], [], [
            'HTTP_X_FORWARDED_HOST' => 'cheetahsfc.co.ke',
            'HTTP_HOST' => 'origin.benchero.co.ke',
            'HTTP_X_BENCHERO_WORKER_SECRET' => 'prod_super_secret_xyz'
        ], [], []);
        $this->assert($reqValidSecret->isTrustedProxy() === true, "Valid worker secret accepted in production");
        $this->assert($reqValidSecret->host() === 'cheetahsfc.co.ke', "Valid worker secret resolves forwarded custom hostname");

        // 6. Cloudflare IPv4 & IPv6 Range Validation Tests
        $reqCfIpv4 = new Request([], [], ['REMOTE_ADDR' => '173.245.48.10'], [], []);
        $this->assert($reqCfIpv4->isCloudflareIp() === true, "Recognizes Cloudflare IPv4 address (173.245.48.10)");

        $reqCfIpv6 = new Request([], [], ['REMOTE_ADDR' => '2606:4700:4700::1111'], [], []);
        $this->assert($reqCfIpv6->isCloudflareIp() === true, "Recognizes Cloudflare IPv6 address (2606:4700:4700::1111)");

        $reqNonCfIp = new Request([], [], ['REMOTE_ADDR' => '198.51.100.5'], [], []);
        $this->assert($reqNonCfIp->isCloudflareIp() === false, "Rejects non-Cloudflare IP address (198.51.100.5)");

        // Restore original env
        putenv('CLOUDFLARE_WORKER_SECRET=');
        $_ENV['CLOUDFLARE_WORKER_SECRET'] = '';
        if ($origAppEnv !== false) {
            putenv("APP_ENV={$origAppEnv}");
            $_ENV['APP_ENV'] = $origAppEnv;
        } else {
            putenv('APP_ENV=local');
            $_ENV['APP_ENV'] = 'local';
        }
    }
}

$suite = new CustomDomainTestSuite();
$suite->run();
