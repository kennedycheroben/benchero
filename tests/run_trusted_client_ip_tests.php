<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\RateLimiter;
use Benchero\Controllers\AuthController;
use Benchero\Controllers\HomeController;

class TrustedClientIpTestSuite
{
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];
    private \PDO $pdo;
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->rateLimiter = new RateLimiter($this->pdo);
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo " [PASS] {$message}\n";
        } else {
            $this->failed++;
            $this->errors[] = $message;
            echo " [FAIL] {$message}\n";
        }
    }

    public function run(): void
    {
        echo "====================================================================\n";
        echo " BENCHERO P0 #3: TRUSTED CLIENT IP / CLOUDFLARE PROXY TEST SUITE\n";
        echo "====================================================================\n\n";

        $this->testDirectClient();
        $this->testDirectAttackerSpoofingCfHeader();
        $this->testTrustedCloudflareProxy();
        $this->testTrustedProxyMalformedCfHeader();
        $this->testIpv6Handling();
        $this->testCidrBoundaries();
        $this->testXForwardedForAndRealIpSpoofing();
        $this->testRateLimiterIntegrationWithTrustedCloudflare();
        $this->testDirectRequestRateLimiting();
        $this->testTrustedProxyIpsConfiguration();
        $this->testHomeControllerContactRateLimiting();
        $this->testClientIpAndIpMethodParity();

        echo "\n====================================================================\n";
        echo " SUMMARY: Passed {$this->passed} / Failed {$this->failed}\n";
        echo "====================================================================\n";

        if ($this->failed > 0) {
            echo "\nFailures:\n";
            foreach ($this->errors as $err) {
                echo " - {$err}\n";
            }
            exit(1);
        }
    }

    /**
     * Test 1 — Direct client (no forwarding headers)
     */
    private function testDirectClient(): void
    {
        echo "--- Test 1: Direct Client (no forwarding headers) ---\n";
        $request = new Request([], [], ['REMOTE_ADDR' => '203.0.113.10'], [], []);

        $this->assert(
            $request->clientIp() === '203.0.113.10',
            "Direct client with no forwarding headers resolves to REMOTE_ADDR (203.0.113.10)"
        );
        $this->assert(
            $request->isCloudflareIp() === false,
            "Direct client IP 203.0.113.10 is not identified as Cloudflare proxy"
        );
        $this->assert(
            $request->isTrustedProxyIp() === false,
            "Direct client IP 203.0.113.10 is not a trusted proxy"
        );
    }

    /**
     * Test 2 — Direct attacker spoofing CF header
     */
    private function testDirectAttackerSpoofingCfHeader(): void
    {
        echo "\n--- Test 2: Direct Attacker Spoofing CF Header ---\n";
        $request = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_CF_CONNECTING_IP' => '198.51.100.20'
            ],
            [],
            []
        );

        $this->assert(
            $request->clientIp() === '203.0.113.10',
            "Attacker CF-Connecting-IP header is ignored when REMOTE_ADDR is untrusted"
        );
        $this->assert(
            $request->clientIp() !== '198.51.100.20',
            "Spoofed CF-Connecting-IP (198.51.100.20) is strictly NOT returned"
        );
    }

    /**
     * Test 3 — Trusted Cloudflare proxy
     */
    private function testTrustedCloudflareProxy(): void
    {
        echo "\n--- Test 3: Trusted Cloudflare Proxy ---\n";
        // 173.245.48.10 is in 173.245.48.0/20 (Cloudflare IPv4)
        $request = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '173.245.48.10',
                'HTTP_CF_CONNECTING_IP' => '198.51.100.20'
            ],
            [],
            []
        );

        $this->assert(
            $request->isCloudflareIp() === true,
            "Peer 173.245.48.10 is recognized as Cloudflare proxy"
        );
        $this->assert(
            $request->isTrustedProxyIp() === true,
            "Peer 173.245.48.10 is recognized as trusted proxy"
        );
        $this->assert(
            $request->clientIp() === '198.51.100.20',
            "Trusted Cloudflare proxy forwards validated client IP (198.51.100.20)"
        );

        // Another Cloudflare range: 108.162.192.5 in 108.162.192.0/18
        $request2 = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '108.162.192.5',
                'HTTP_CF_CONNECTING_IP' => '203.0.113.99'
            ],
            [],
            []
        );
        $this->assert(
            $request2->clientIp() === '203.0.113.99',
            "Cloudflare range 108.162.192.0/18 resolves client IP (203.0.113.99)"
        );
    }

    /**
     * Test 4 — Trusted proxy but malformed CF header
     */
    private function testTrustedProxyMalformedCfHeader(): void
    {
        echo "\n--- Test 4: Trusted Proxy with Malformed CF Header ---\n";
        $requestMalformed = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '173.245.48.10',
                'HTTP_CF_CONNECTING_IP' => 'not-an-ip'
            ],
            [],
            []
        );

        $this->assert(
            $requestMalformed->clientIp() === '173.245.48.10',
            "Malformed CF-Connecting-IP falls back safely to REMOTE_ADDR (173.245.48.10)"
        );

        // Empty header
        $requestEmpty = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '173.245.48.10',
                'HTTP_CF_CONNECTING_IP' => ''
            ],
            [],
            []
        );
        $this->assert(
            $requestEmpty->clientIp() === '173.245.48.10',
            "Empty CF-Connecting-IP falls back safely to REMOTE_ADDR (173.245.48.10)"
        );

        // Header containing invalid characters / SQL injection attempt
        $requestInjection = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '173.245.48.10',
                'HTTP_CF_CONNECTING_IP' => "1.2.3.4' OR 1=1--"
            ],
            [],
            []
        );
        $this->assert(
            $requestInjection->clientIp() === '173.245.48.10',
            "Injected CF-Connecting-IP falls back safely to REMOTE_ADDR"
        );
    }

    /**
     * Test 5 — IPv6 Handling
     */
    private function testIpv6Handling(): void
    {
        echo "\n--- Test 5: IPv6 Handling ---\n";

        // Direct IPv6 client (untrusted)
        $reqDirectIpv6 = new Request(
            [],
            [],
            ['REMOTE_ADDR' => '2001:db8::1'],
            [],
            []
        );
        $this->assert(
            $reqDirectIpv6->clientIp() === '2001:db8::1',
            "Direct IPv6 client resolves to REMOTE_ADDR (2001:db8::1)"
        );

        // Direct IPv6 client attempting to spoof CF header
        $reqDirectIpv6Spoof = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '2001:db8::1',
                'HTTP_CF_CONNECTING_IP' => '198.51.100.20'
            ],
            [],
            []
        );
        $this->assert(
            $reqDirectIpv6Spoof->clientIp() === '2001:db8::1',
            "Direct IPv6 spoofing CF-Connecting-IP is safely rejected"
        );

        // Trusted Cloudflare IPv6 proxy with IPv4 visitor
        // 2606:4700:4700::1111 in 2606:4700::/32
        $reqCfIpv6 = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '2606:4700:4700::1111',
                'HTTP_CF_CONNECTING_IP' => '198.51.100.50'
            ],
            [],
            []
        );
        $this->assert(
            $reqCfIpv6->isCloudflareIp() === true,
            "IPv6 peer 2606:4700:4700::1111 is recognized as Cloudflare proxy"
        );
        $this->assert(
            $reqCfIpv6->clientIp() === '198.51.100.50',
            "Cloudflare IPv6 proxy correctly resolves client IPv4 address"
        );

        // Trusted Cloudflare IPv6 proxy with IPv6 visitor
        $reqCfIpv6WithIpv6Client = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '2400:cb00:2048:1::c629:d7a2',
                'HTTP_CF_CONNECTING_IP' => '2001:db8:85a3::8a2e:370:7334'
            ],
            [],
            []
        );
        $this->assert(
            $reqCfIpv6WithIpv6Client->clientIp() === '2001:db8:85a3::8a2e:370:7334',
            "Cloudflare IPv6 proxy correctly resolves client IPv6 address"
        );
    }

    /**
     * Test 6 — CIDR Boundary Validation
     */
    private function testCidrBoundaries(): void
    {
        echo "\n--- Test 6: CIDR Boundary Validation ---\n";
        // Cloudflare IPv4 range: 173.245.48.0/20
        // Network: 173.245.48.0 to 173.245.63.255

        // Inside boundary: 173.245.48.0 (first address)
        $reqStart = new Request([], [], ['REMOTE_ADDR' => '173.245.48.0', 'HTTP_CF_CONNECTING_IP' => '1.1.1.1'], [], []);
        $this->assert(
            $reqStart->isCloudflareIp() === true,
            "173.245.48.0 is inside 173.245.48.0/20"
        );
        $this->assert(
            $reqStart->clientIp() === '1.1.1.1',
            "173.245.48.0 clientIp resolves forwarded header"
        );

        // Inside boundary: 173.245.63.255 (last address)
        $reqEnd = new Request([], [], ['REMOTE_ADDR' => '173.245.63.255', 'HTTP_CF_CONNECTING_IP' => '1.1.1.1'], [], []);
        $this->assert(
            $reqEnd->isCloudflareIp() === true,
            "173.245.63.255 is inside 173.245.48.0/20"
        );
        $this->assert(
            $reqEnd->clientIp() === '1.1.1.1',
            "173.245.63.255 clientIp resolves forwarded header"
        );

        // Outside boundary: 173.245.47.255 (one below network start)
        $reqBelow = new Request([], [], ['REMOTE_ADDR' => '173.245.47.255', 'HTTP_CF_CONNECTING_IP' => '1.1.1.1'], [], []);
        $this->assert(
            $reqBelow->isCloudflareIp() === false,
            "173.245.47.255 is strictly outside 173.245.48.0/20"
        );
        $this->assert(
            $reqBelow->clientIp() === '173.245.47.255',
            "173.245.47.255 ignores spoofed header and returns REMOTE_ADDR"
        );

        // Outside boundary: 173.245.64.0 (one above network end)
        $reqAbove = new Request([], [], ['REMOTE_ADDR' => '173.245.64.0', 'HTTP_CF_CONNECTING_IP' => '1.1.1.1'], [], []);
        $this->assert(
            $reqAbove->isCloudflareIp() === false,
            "173.245.64.0 is strictly outside 173.245.48.0/20"
        );
        $this->assert(
            $reqAbove->clientIp() === '173.245.64.0',
            "173.245.64.0 ignores spoofed header and returns REMOTE_ADDR"
        );

        // IPv6 Boundary: 2606:4700::/32
        $reqV6Inside = new Request([], [], ['REMOTE_ADDR' => '2606:4700:ffff:ffff:ffff:ffff:ffff:ffff'], [], []);
        $this->assert(
            $reqV6Inside->isCloudflareIp() === true,
            "2606:4700:ffff:ffff:ffff:ffff:ffff:ffff is inside 2606:4700::/32"
        );

        $reqV6Outside = new Request([], [], ['REMOTE_ADDR' => '2606:4701::1'], [], []);
        $this->assert(
            $reqV6Outside->isCloudflareIp() === false,
            "2606:4701::1 is strictly outside 2606:4700::/32"
        );
    }

    /**
     * Test 7 — X-Forwarded-For and X-Real-IP spoofing
     */
    private function testXForwardedForAndRealIpSpoofing(): void
    {
        echo "\n--- Test 7: X-Forwarded-For and X-Real-IP Spoofing ---\n";
        $request = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_X_FORWARDED_FOR' => '198.51.100.20, 10.0.0.1',
                'HTTP_X_REAL_IP' => '198.51.100.30'
            ],
            [],
            []
        );

        $this->assert(
            $request->clientIp() === '203.0.113.10',
            "Untrusted client cannot spoof client IP via X-Forwarded-For or X-Real-IP"
        );

        // Even when sent by trusted Cloudflare, arbitrary X-Forwarded-For is not blindly trusted over CF-Connecting-IP
        $requestCf = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '173.245.48.10',
                'HTTP_CF_CONNECTING_IP' => '198.51.100.55',
                'HTTP_X_FORWARDED_FOR' => '10.99.99.99, 198.51.100.55'
            ],
            [],
            []
        );
        $this->assert(
            $requestCf->clientIp() === '198.51.100.55',
            "Trusted Cloudflare proxy prefers validated CF-Connecting-IP over ambiguous X-Forwarded-For list"
        );
    }

    /**
     * Test 8 — Rate limiter integration with trusted Cloudflare
     */
    private function testRateLimiterIntegrationWithTrustedCloudflare(): void
    {
        echo "\n--- Test 8: Rate Limiter Integration with Trusted Cloudflare ---\n";
        $cfProxyIp = '173.245.48.10';
        $clientIp = '198.51.100.77';

        // Clear any previous limits
        $this->rateLimiter->clear("login_{$clientIp}");
        $this->rateLimiter->clear("login_{$cfProxyIp}");
        $this->rateLimiter->clear("login_email_victim_p03@example.com");

        $authController = new AuthController(null, null, null, $this->pdo);

        $request = new Request(
            [],
            [
                'email' => 'victim_p03@example.com',
                'password' => 'wrong_password_test'
            ],
            [
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => $cfProxyIp,
                'HTTP_CF_CONNECTING_IP' => $clientIp
            ],
            [],
            []
        );

        $response = $authController->login($request);

        // Verify that rate limiting was recorded for client IP, NOT proxy IP
        $stmtClient = $this->pdo->prepare("SELECT attempts FROM rate_limits WHERE rate_key = ?");
        $stmtClient->execute(["login_{$clientIp}"]);
        $clientAttempts = (int)$stmtClient->fetchColumn();

        $stmtProxy = $this->pdo->prepare("SELECT attempts FROM rate_limits WHERE rate_key = ?");
        $stmtProxy->execute(["login_{$cfProxyIp}"]);
        $proxyAttempts = (int)$stmtProxy->fetchColumn();

        $this->assert(
            $clientAttempts >= 1,
            "Rate limiter recorded attempt for resolved client IP (login_{$clientIp})"
        );
        $this->assert(
            $proxyAttempts === 0,
            "Rate limiter did NOT record attempt for Cloudflare proxy IP (login_{$cfProxyIp})"
        );

        // Clean up
        $this->rateLimiter->clear("login_{$clientIp}");
        $this->rateLimiter->clear("login_{$cfProxyIp}");
        $this->rateLimiter->clear("login_email_victim_p03@example.com");
    }

    /**
     * Test 9 — Direct-request rate limiting
     */
    private function testDirectRequestRateLimiting(): void
    {
        echo "\n--- Test 9: Direct-Request Rate Limiting ---\n";
        $attackerPeer = '203.0.113.88';
        $spoofedIp = '198.51.100.99';

        $this->rateLimiter->clear("login_{$attackerPeer}");
        $this->rateLimiter->clear("login_{$spoofedIp}");
        $this->rateLimiter->clear("login_email_victim_direct_p03@example.com");

        $authController = new AuthController(null, null, null, $this->pdo);

        $request = new Request(
            [],
            [
                'email' => 'victim_direct_p03@example.com',
                'password' => 'wrong_password_test'
            ],
            [
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => $attackerPeer,
                'HTTP_CF_CONNECTING_IP' => $spoofedIp
            ],
            [],
            []
        );

        $response = $authController->login($request);

        $stmtAttacker = $this->pdo->prepare("SELECT attempts FROM rate_limits WHERE rate_key = ?");
        $stmtAttacker->execute(["login_{$attackerPeer}"]);
        $attackerAttempts = (int)$stmtAttacker->fetchColumn();

        $stmtSpoofed = $this->pdo->prepare("SELECT attempts FROM rate_limits WHERE rate_key = ?");
        $stmtSpoofed->execute(["login_{$spoofedIp}"]);
        $spoofedAttempts = (int)$stmtSpoofed->fetchColumn();

        $this->assert(
            $attackerAttempts >= 1,
            "Rate limiter recorded attempt on actual REMOTE_ADDR (login_{$attackerPeer})"
        );
        $this->assert(
            $spoofedAttempts === 0,
            "Rate limiter ignored spoofed CF-Connecting-IP (login_{$spoofedIp})"
        );

        // Clean up
        $this->rateLimiter->clear("login_{$attackerPeer}");
        $this->rateLimiter->clear("login_{$spoofedIp}");
        $this->rateLimiter->clear("login_email_victim_direct_p03@example.com");
    }

    /**
     * Test TRUSTED_PROXY_IPS custom configuration override
     */
    private function testTrustedProxyIpsConfiguration(): void
    {
        echo "\n--- Test: Custom TRUSTED_PROXY_IPS Configuration Override ---\n";

        // Test with custom proxy range
        $customRanges = ['192.0.2.0/24'];
        $requestCustom = new Request([], [], ['REMOTE_ADDR' => '192.0.2.15'], [], []);
        $this->assert(
            $requestCustom->isTrustedProxyIp(null, $customRanges) === true,
            "IP 192.0.2.15 is recognized as trusted when 192.0.2.0/24 is passed as trusted range"
        );

        $requestCustomUntrusted = new Request([], [], ['REMOTE_ADDR' => '192.0.3.15'], [], []);
        $this->assert(
            $requestCustomUntrusted->isTrustedProxyIp(null, $customRanges) === false,
            "IP 192.0.3.15 is rejected when only 192.0.2.0/24 is trusted"
        );

        // Test environment override with TRUSTED_PROXY_IPS
        $origEnv = getenv('TRUSTED_PROXY_IPS');
        putenv('TRUSTED_PROXY_IPS=198.18.0.0/15,2001:db8:cafe::/48');
        $_ENV['TRUSTED_PROXY_IPS'] = '198.18.0.0/15,2001:db8:cafe::/48';

        $reqEnvV4 = new Request([], [], ['REMOTE_ADDR' => '198.18.10.5', 'HTTP_CF_CONNECTING_IP' => '203.0.113.123'], [], []);
        $this->assert(
            $reqEnvV4->isTrustedProxyIp() === true,
            "TRUSTED_PROXY_IPS env variable correctly activates 198.18.10.5"
        );
        $this->assert(
            $reqEnvV4->clientIp() === '203.0.113.123',
            "Client IP resolved via configured TRUSTED_PROXY_IPS"
        );

        $reqEnvV6 = new Request([], [], ['REMOTE_ADDR' => '2001:db8:cafe:1::1', 'HTTP_CF_CONNECTING_IP' => '203.0.113.124'], [], []);
        $this->assert(
            $reqEnvV6->isTrustedProxyIp() === true,
            "TRUSTED_PROXY_IPS env variable correctly activates IPv6 2001:db8:cafe:1::1"
        );
        $this->assert(
            $reqEnvV6->clientIp() === '203.0.113.124',
            "Client IP resolved via configured IPv6 TRUSTED_PROXY_IPS"
        );

        // Restore environment
        if ($origEnv !== false) {
            putenv("TRUSTED_PROXY_IPS={$origEnv}");
            $_ENV['TRUSTED_PROXY_IPS'] = $origEnv;
        } else {
            putenv('TRUSTED_PROXY_IPS=');
            unset($_ENV['TRUSTED_PROXY_IPS']);
        }
    }

    /**
     * Test HomeController contact rate limiting
     */
    private function testHomeControllerContactRateLimiting(): void
    {
        echo "\n--- Test: HomeController Contact Form Rate Limiting ---\n";
        $cfProxyIp = '173.245.48.10';
        $clientIp = '198.51.100.80';

        $this->rateLimiter->clear("contact_form_{$clientIp}");
        $this->rateLimiter->clear("contact_form_{$cfProxyIp}");

        $homeController = new HomeController();

        // Submit contact request via trusted Cloudflare
        $request = new Request(
            [],
            [
                'name' => 'Tester',
                'email' => 'test@example.com',
                'subject' => 'Inquiry',
                'message' => 'Hello team'
            ],
            [
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => $cfProxyIp,
                'HTTP_CF_CONNECTING_IP' => $clientIp
            ],
            [],
            []
        );

        $response = $homeController->contactSubmit($request);

        $stmtClient = $this->pdo->prepare("SELECT attempts FROM rate_limits WHERE rate_key = ?");
        $stmtClient->execute(["contact_form_{$clientIp}"]);
        $clientAttempts = (int)$stmtClient->fetchColumn();

        $stmtProxy = $this->pdo->prepare("SELECT attempts FROM rate_limits WHERE rate_key = ?");
        $stmtProxy->execute(["contact_form_{$cfProxyIp}"]);
        $proxyAttempts = (int)$stmtProxy->fetchColumn();

        $this->assert(
            $clientAttempts >= 1,
            "HomeController contact form rate limited on resolved client IP ({$clientIp})"
        );
        $this->assert(
            $proxyAttempts === 0,
            "HomeController contact form did NOT rate limit on Cloudflare proxy IP ({$cfProxyIp})"
        );

        // Clean up
        $this->rateLimiter->clear("contact_form_{$clientIp}");
        $this->rateLimiter->clear("contact_form_{$cfProxyIp}");
    }

    /**
     * Test clientIp() and ip() parity
     */
    private function testClientIpAndIpMethodParity(): void
    {
        echo "\n--- Test: clientIp() and ip() Parity ---\n";
        $reqDirect = new Request([], [], ['REMOTE_ADDR' => '203.0.113.10'], [], []);
        $this->assert(
            $reqDirect->ip() === $reqDirect->clientIp(),
            "ip() returns identical result to clientIp() on direct client"
        );

        $reqCf = new Request(
            [],
            [],
            [
                'REMOTE_ADDR' => '173.245.48.10',
                'HTTP_CF_CONNECTING_IP' => '198.51.100.20'
            ],
            [],
            []
        );
        $this->assert(
            $reqCf->ip() === $reqCf->clientIp(),
            "ip() returns identical result to clientIp() on Cloudflare proxy"
        );

        // Fallback when REMOTE_ADDR is empty
        $reqEmpty = new Request([], [], [], [], []);
        $this->assert(
            $reqEmpty->clientIp() === '0.0.0.0',
            "clientIp() returns safe 0.0.0.0 when REMOTE_ADDR is missing"
        );
    }
}

$suite = new TrustedClientIpTestSuite();
$suite->run();
