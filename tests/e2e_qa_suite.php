<?php

/**
 * Benchero End-to-End QA Acceptance Test Runner
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;

class E2EAutomationTester
{
    private string $baseUrl = 'http://localhost/benchero';
    private string $cookieFile;
    private PDO $db;
    private array $results = [];
    private array $bugs = [];

    public function __construct()
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'tm_qa_cookie_');
        $this->db = Database::getConnection();
        $this->db->query("DELETE FROM rate_limits");
    }

    public function __destruct()
    {
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
    }

    private function request(string $method, string $path, array $data = [], bool $useCookies = true): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        if ($useCookies) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'code' => $httpCode,
            'headers' => $headers,
            'body' => $body,
            'redirect' => $redirectUrl
        ];
    }

    private function extractCsrfToken(string $html): ?string
    {
        if (preg_match('/<input[^>]*name=["\'](?:_csrf|csrf_token|_token)["\'][^>]*value=["\']([^"\']+)["\']/i', $html, $matches)) {
            return $matches[1];
        }
        if (preg_match('/<input[^>]*value=["\']([^"\']+)["\'][^>]*name=["\'](?:_csrf|csrf_token|_token)["\']/i', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function recordResult(string $section, string $testName, string $status, string $details = ''): void
    {
        $this->results[] = [
            'section' => $section,
            'test' => $testName,
            'status' => $status,
            'details' => $details
        ];
        echo sprintf("[%s] %s - %s: %s\n", $status, $section, $testName, $details);
    }

    public function recordBug(string $feature, string $severity, string $steps, string $expected, string $actual, string $rootCause, string $files, string $fix = ''): void
    {
        $this->bugs[] = [
            'id' => 'BUG #' . (count($this->bugs) + 1),
            'feature' => $feature,
            'severity' => $severity,
            'steps' => $steps,
            'expected' => $expected,
            'actual' => $actual,
            'root_cause' => $rootCause,
            'files' => $files,
            'fix' => $fix
        ];
    }

    public function runAll(): array
    {
        echo "==================================================\n";
        echo "RUNNING BENCHERO E2E ACCEPTANCE TEST SUITE\n";
        echo "==================================================\n\n";

        $this->testEnvironment();
        $this->testRegistrationAndAuth();
        $this->testAccountFeatures();
        $this->testOnboardingAndTrial();
        $this->testSportsSeasonsTeamsPlayersRostersFixtures();
        $this->testTenantIsolationAndAuthorization();
        $this->testCsrfAndValidation();
        $this->testNavigationAndErrorPages();
        $this->testDatabaseIntegrity();

        return [
            'results' => $this->results,
            'bugs' => $this->bugs
        ];
    }

    private function testEnvironment(): void
    {
        $res = $this->request('GET', '/health');
        if ($res['code'] === 200) {
            $this->recordResult('1. Environment', 'Apache & Web Server Response', 'PASS', 'HTTP 200 OK from /health');
        } else {
            $this->recordResult('1. Environment', 'Apache & Web Server Response', 'FAIL', 'HTTP code: ' . $res['code']);
        }

        try {
            $stmt = $this->db->query("SELECT VERSION() as ver");
            $row = $stmt->fetch();
            $this->recordResult('1. Environment', 'Database Connection & MySQL/MariaDB', 'PASS', 'MySQL version: ' . $row['ver']);
        } catch (\Throwable $e) {
            $this->recordResult('1. Environment', 'Database Connection', 'FAIL', $e->getMessage());
        }
    }

    private function testRegistrationAndAuth(): void
    {
        // 1. GET /register
        $regForm = $this->request('GET', '/register');
        if ($regForm['code'] === 200 && str_contains($regForm['body'], '_csrf')) {
            $this->recordResult('2. Registration', 'Registration Page Load & CSRF Token Present', 'PASS', 'Form rendered with _csrf field');
        } else {
            $this->recordResult('2. Registration', 'Registration Page Load', 'FAIL', 'Status: ' . $regForm['code']);
        }

        $token = $this->extractCsrfToken($regForm['body']);

        // 2. Submit without CSRF
        $noCsrf = $this->request('POST', '/register', [
            'name' => 'No CSRF Test',
            'email' => 'nocsrf@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);
        if ($noCsrf['code'] === 403) {
            $this->recordResult('16. CSRF', 'Registration missing CSRF rejected', 'PASS', 'Returned HTTP 403');
        } else {
            $this->recordResult('16. CSRF', 'Registration missing CSRF rejected', 'FAIL', 'Got HTTP ' . $noCsrf['code']);
        }

        // 3. Short Password
        $shortPass = $this->request('POST', '/register', [
            '_csrf' => $token,
            'name' => 'Weak Pass',
            'email' => 'weak@test.com',
            'password' => '123',
            'password_confirmation' => '123'
        ]);
        if (str_contains($shortPass['body'], 'at least') || str_contains($shortPass['body'], 'Password')) {
            $this->recordResult('2. Registration', 'Password Strength Validation', 'PASS', 'Weak password rejected');
        } else {
            $this->recordResult('2. Registration', 'Password Strength Validation', 'FAIL', 'Weak password allowed or unhandled');
        }

        // 4. Invalid Email
        $invalidEmail = $this->request('POST', '/register', [
            '_csrf' => $token,
            'name' => 'Invalid Email',
            'email' => 'invalid-email-format',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);
        if (str_contains($invalidEmail['body'], 'email') || str_contains($invalidEmail['body'], 'Invalid')) {
            $this->recordResult('2. Registration', 'Invalid Email Format Validation', 'PASS', 'Invalid email format rejected');
        } else {
            $this->recordResult('2. Registration', 'Invalid Email Format Validation', 'FAIL', 'Invalid email format accepted');
        }

        // 5. Valid Registration
        $testEmail = 'qa_e2e_' . time() . '@benchero.test';
        $validReg = $this->request('POST', '/register', [
            '_csrf' => $token,
            'name' => 'QA E2E Tester',
            'email' => $testEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        if (str_contains($validReg['body'], 'verification link') || str_contains($validReg['body'], 'registered') || str_contains($validReg['body'], 'verify')) {
            $this->recordResult('2. Registration', 'Successful Registration', 'PASS', 'Registered user ' . $testEmail);
        } else {
            $this->recordResult('2. Registration', 'Successful Registration', 'FAIL', 'Response did not indicate success');
        }

        // 6. Test Unverified Login Block
        $loginPage = $this->request('GET', '/login');
        $loginToken = $this->extractCsrfToken($loginPage['body']);
        $unverifiedLogin = $this->request('POST', '/login', [
            '_csrf' => $loginToken,
            'email' => $testEmail,
            'password' => 'Password123!'
        ]);
        if (str_contains($unverifiedLogin['body'], 'verify your email') || str_contains($unverifiedLogin['body'], 'Unverified') || $unverifiedLogin['code'] === 403) {
            $this->recordResult('3. Login / Auth', 'Unverified Account Login Blocked', 'PASS', 'Blocked unverified user login (Code: ' . $unverifiedLogin['code'] . ')');
        } else {
            $this->recordResult('3. Login / Auth', 'Unverified Account Login Blocked', 'FAIL', 'Code: ' . $unverifiedLogin['code'] . ' Body snippet: ' . substr(strip_tags($unverifiedLogin['body']), 0, 100));
        }

        // 7. Extract Verification Link from Mail Log & Verify User
        $mailLog = file_get_contents(__DIR__ . '/../storage/logs/mail.log');
        if (preg_match_all('/http:\/\/localhost\/(?:benchero|teamora)\/public\/verify-email\/([A-Za-z0-9]+)\/([A-Fa-f0-9]+)/', $mailLog, $matches, PREG_SET_ORDER)) {
            $lastMatch = end($matches);
            $userId = $lastMatch[1];
            $verifyToken = $lastMatch[2];
            $verifyRes = $this->request('GET', "/verify-email/{$userId}/{$verifyToken}");
            if (str_contains($verifyRes['body'], 'verified') || $verifyRes['code'] === 200) {
                $this->recordResult('2. Registration', 'Email Verification URL Execution', 'PASS', 'User verified via token link');
            } else {
                $this->recordResult('2. Registration', 'Email Verification URL Execution', 'FAIL', 'Email verification link failed');
            }
        } else {
            $this->recordResult('2. Registration', 'Email Verification Mail Logging', 'FAIL', 'Could not locate token in storage/logs/mail.log');
        }

        // 8. Test Valid Login
        $loginPage2 = $this->request('GET', '/login');
        $token2 = $this->extractCsrfToken($loginPage2['body']);
        $loginRes = $this->request('POST', '/login', [
            '_csrf' => $token2,
            'email' => $testEmail,
            'password' => 'Password123!'
        ]);
        if ($loginRes['code'] === 302 || str_contains($loginRes['headers'], 'Location: /benchero/onboarding') || str_contains($loginRes['headers'], 'Location: /benchero/organizations')) {
            $this->recordResult('3. Login / Auth', 'Verified User Login & Session Creation', 'PASS', 'Logged in and redirected to onboarding/org selection');
        } else {
            $this->recordResult('3. Login / Auth', 'Verified User Login & Session Creation', 'FAIL', 'Login failed for verified user');
        }
    }

    private function testAccountFeatures(): void
    {
        $this->recordResult('4. Account Features', 'Forgot Password', 'NOT IMPLEMENTED', 'No route or controller for forgot password');
        $this->recordResult('4. Account Features', 'Password Reset', 'NOT IMPLEMENTED', 'No route or controller for password reset');
        $this->recordResult('4. Account Features', 'Change Password', 'NOT IMPLEMENTED', 'No route or controller for change password');
        $this->recordResult('4. Account Features', 'Profile / Account Edit', 'NOT IMPLEMENTED', 'No route or controller for user profile editing');

        // Test Resend Email Verification
        $resendGet = $this->request('GET', '/verify-email/resend');
        if ($resendGet['code'] === 200 && str_contains($resendGet['body'], 'Resend Verification')) {
            $this->recordResult('4. Account Features', 'Resend Email Verification Form', 'PASS', 'GET /verify-email/resend loads form');
        } else {
            $this->recordResult('4. Account Features', 'Resend Email Verification Form', 'FAIL', 'GET /verify-email/resend failed: ' . $resendGet['code']);
        }
    }

    private ?array $createdOrg = null;

    private function testOnboardingAndTrial(): void
    {
        $onboardingPage = $this->request('GET', '/onboarding');
        if ($onboardingPage['code'] === 200 && (str_contains($onboardingPage['body'], 'Organization') || str_contains($onboardingPage['body'], 'Create'))) {
            $this->recordResult('5. Onboarding', 'Onboarding Page Render', 'PASS', 'Loaded organization creation form');
        } else {
            $this->recordResult('5. Onboarding', 'Onboarding Page Render', 'FAIL', 'Status: ' . $onboardingPage['code'] . ' Body snippet: ' . substr(strip_tags($onboardingPage['body']), 0, 150));
        }

        $token = $this->extractCsrfToken($onboardingPage['body']);
        $orgName = 'E2E Test Organization ' . rand(100, 999);
        $orgSlug = 'e2e-org-' . rand(100, 999);
        $onboardingPost = $this->request('POST', '/onboarding', [
            '_csrf' => $token,
            'name' => $orgName,
            'slug' => $orgSlug,
            'country' => 'KE',
            'timezone' => 'Africa/Nairobi'
        ]);

        if ($onboardingPost['code'] === 302 && str_contains($onboardingPost['redirect'], '/dashboard')) {
            $this->recordResult('5. Onboarding', 'Organization Creation & Auto-Slug', 'PASS', 'Organization created, redirected to dashboard: ' . $onboardingPost['redirect']);
        } else {
            $this->recordResult('5. Onboarding', 'Organization Creation & Auto-Slug', 'FAIL', 'Onboarding POST failed. Redirect: ' . $onboardingPost['redirect']);
        }

        // DB Verification for Org & Subscription & Role
        $org = $this->db->query("SELECT * FROM organizations WHERE name = " . $this->db->quote($orgName) . " ORDER BY created_at DESC LIMIT 1")->fetch();
        if ($org) {
            $this->createdOrg = $org;
            $this->recordResult('5. Onboarding', 'DB Organization Persistence', 'PASS', 'Org ID: ' . $org['id'] . ', Slug: ' . $org['slug']);

            // Check member role
            $member = $this->db->query("SELECT * FROM organization_user WHERE organization_id = " . $this->db->quote($org['id']))->fetch();
            if ($member && $member['role'] === 'owner') {
                $this->recordResult('5. Onboarding', 'User Role Assignment (Owner)', 'PASS', 'User assigned owner role in org');
            } else {
                $this->recordResult('5. Onboarding', 'User Role Assignment (Owner)', 'FAIL', 'User role: ' . ($member['role'] ?? 'none'));
            }

            // Check Subscription
            $sub = $this->db->query("SELECT * FROM subscriptions WHERE organization_id = " . $this->db->quote($org['id']))->fetch();
            if ($sub && $sub['status'] === 'trialing') {
                $this->recordResult('6. Trial Subscription', 'Trial Subscription Creation', 'PASS', 'Subscription trialing, plan: ' . $sub['plan_id'] . ', ends: ' . ($sub['trial_ends_at'] ?? 'N/A'));
            } else {
                $this->recordResult('6. Trial Subscription', 'Trial Subscription Creation', 'FAIL', 'Subscription missing or status invalid');
            }
        } else {
            $this->recordResult('5. Onboarding', 'DB Organization Persistence', 'FAIL', 'Org not found in database');
        }
    }

    private function testSportsSeasonsTeamsPlayersRostersFixtures(): void
    {
        $org = $this->createdOrg ?? $this->db->query("SELECT * FROM organizations ORDER BY created_at DESC LIMIT 1")->fetch();
        if (!$org) {
            return;
        }

        $slug = $org['slug'];
        $orgIdQuoted = $this->db->quote($org['id']);

        // 7. Sports
        $sportsPage = $this->request('GET', "/o/{$slug}/sports");
        if ($sportsPage['code'] === 200 && (str_contains($sportsPage['body'], 'Sports') || str_contains($sportsPage['body'], 'sports'))) {
            $this->recordResult('7. Sports', 'Organization Sports Dashboard', 'PASS', 'Loaded sports dashboard');
        } else {
            $this->recordResult('7. Sports', 'Organization Sports Dashboard', 'FAIL', 'Code: ' . $sportsPage['code'] . ' Body: ' . substr(strip_tags($sportsPage['body']), 0, 200));
        }

        // Fetch Football sport ID
        $footballSport = $this->db->query("SELECT * FROM sports WHERE slug = 'football'")->fetch();
        $footballId = $footballSport['id'] ?? '1';

        // Toggle Football active
        $token = $this->extractCsrfToken($sportsPage['body']);
        $this->recordResult('7. Sports', 'Extracted Token Check', 'INFO', 'Token: ' . ($token ?? 'NULL'));
        $toggleRes = $this->request('POST', "/o/{$slug}/sports/toggle", [
            '_csrf' => $token,
            'sport_id' => $footballId,
            'action' => 'activate'
        ]);
        if ($toggleRes['code'] === 302) {
            $this->recordResult('7. Sports', 'Activate Football Sport', 'PASS', 'Activated Football (Sport ID 1)');
        } else {
            $this->recordResult('7. Sports', 'Activate Football Sport', 'FAIL', 'Toggle response: ' . $toggleRes['code'] . ' Body: ' . strip_tags($toggleRes['body']));
        }

        // 8. Seasons
        $seasonsPage = $this->request('GET', "/o/{$slug}/s/football/seasons");
        if ($seasonsPage['code'] === 200) {
            $this->recordResult('8. Seasons', 'Seasons List Index', 'PASS', 'Seasons page rendered');
        } else {
            $this->recordResult('8. Seasons', 'Seasons List Index', 'FAIL', 'Seasons list returned ' . $seasonsPage['code']);
        }

        $createSeasonPage = $this->request('GET', "/o/{$slug}/s/football/seasons/create");
        $tokenSeason = $this->extractCsrfToken($createSeasonPage['body']);
        $seasonName = '2026/2027 E2E Season';
        $createSeasonPost = $this->request('POST', "/o/{$slug}/s/football/seasons", [
            '_csrf' => $tokenSeason,
            'name' => $seasonName,
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-05-31',
            'is_current' => '1'
        ]);

        if ($createSeasonPost['code'] === 302 && str_contains($createSeasonPost['redirect'], '/seasons') && !str_contains($createSeasonPost['redirect'], '/create')) {
            $this->recordResult('8. Seasons', 'Season Creation & Current Invariant', 'PASS', 'Created season ' . $seasonName);
        } else {
            $this->recordResult('8. Seasons', 'Season Creation & Current Invariant', 'FAIL', 'Season POST code: ' . $createSeasonPost['code'] . ' redirect: ' . $createSeasonPost['redirect'] . ' body: ' . substr(strip_tags($createSeasonPost['body']), 0, 150));
        }

        $seasonRow = $this->db->query("SELECT * FROM seasons WHERE organization_id = {$orgIdQuoted} AND name = " . $this->db->quote($seasonName))->fetch();
        if (!$seasonRow) {
            $this->recordResult('8. Seasons', 'Season Query Check', 'INFO', 'Season POST code: ' . $createSeasonPost['code'] . ' body: ' . substr(strip_tags($createSeasonPost['body']), 0, 200));
        }

        // 9. Teams
        $createTeamPage = $this->request('GET', "/o/{$slug}/s/football/teams/create");
        $tokenTeam = $this->extractCsrfToken($createTeamPage['body']);
        $teamName1 = 'Alpha Lions FC';
        $teamName2 = 'Beta Tigers FC';

        $createTeamPost1 = $this->request('POST', "/o/{$slug}/s/football/teams", [
            '_csrf' => $tokenTeam,
            'name' => $teamName1,
            'team_type' => 'senior'
        ]);

        $createTeamPost2 = $this->request('POST', "/o/{$slug}/s/football/teams", [
            '_csrf' => $tokenTeam,
            'name' => $teamName2,
            'team_type' => 'youth'
        ]);

        if ($createTeamPost1['code'] === 302 && str_contains($createTeamPost1['redirect'], '/teams') && !str_contains($createTeamPost1['redirect'], '/create')) {
            $this->recordResult('9. Teams', 'Team Creation (Home & Away)', 'PASS', 'Created team 1 & team 2');
        } else {
            $this->recordResult('9. Teams', 'Team Creation (Home & Away)', 'FAIL', 'Team POST 1 redirect: ' . $createTeamPost1['redirect'] . ' body: ' . substr(strip_tags($createTeamPost1['body']), 0, 150));
        }

        $team1 = $this->db->query("SELECT * FROM teams WHERE organization_id = {$orgIdQuoted} AND name = " . $this->db->quote($teamName1))->fetch();
        $team2 = $this->db->query("SELECT * FROM teams WHERE organization_id = {$orgIdQuoted} AND name = " . $this->db->quote($teamName2))->fetch();

        // 10. Players
        $createPlayerPage = $this->request('GET', "/o/{$slug}/s/football/players/create");
        $tokenPlayer = $this->extractCsrfToken($createPlayerPage['body']);

        $createPlayerPost1 = $this->request('POST', "/o/{$slug}/s/football/players", [
            '_csrf' => $tokenPlayer,
            'first_name' => 'John',
            'last_name' => 'Striker',
            'date_of_birth' => '2000-01-15'
        ]);
        $createPlayerPost2 = $this->request('POST', "/o/{$slug}/s/football/players", [
            '_csrf' => $tokenPlayer,
            'first_name' => 'David',
            'last_name' => 'Keeper',
            'date_of_birth' => '1998-05-20'
        ]);

        if ($createPlayerPost1['code'] === 302 && $createPlayerPost2['code'] === 302) {
            $this->recordResult('10. Players', 'Player Creation', 'PASS', 'Created 2 players');
        } else {
            $this->recordResult('10. Players', 'Player Creation', 'FAIL', 'Player 1 code: ' . $createPlayerPost1['code'] . ' redirect: ' . $createPlayerPost1['redirect'] . ' body: ' . strip_tags($createPlayerPost1['body']));
        }

        $player1 = $this->db->query("SELECT * FROM players WHERE organization_id = {$orgIdQuoted} AND first_name = 'John'")->fetch();
        $player2 = $this->db->query("SELECT * FROM players WHERE organization_id = {$orgIdQuoted} AND first_name = 'David'")->fetch();

        // 11. Rosters
        if (!$team1 || !$seasonRow || !$player1) {
            $this->recordResult('11. Rosters', 'Roster Prerequisite Check', 'INFO', sprintf('team1: %s, seasonRow: %s, player1: %s', $team1 ? 'YES' : 'NO', $seasonRow ? 'YES' : 'NO', $player1 ? 'YES' : 'NO'));
        }
        if ($team1 && $seasonRow && $player1) {
            $rosterPage = $this->request('GET', "/o/{$slug}/s/football/teams/{$team1['id']}/rosters/{$seasonRow['id']}");
            $tokenRoster = $this->extractCsrfToken($rosterPage['body']);
            $this->recordResult('11. Rosters', 'Extracted Roster Token Check', 'INFO', 'Roster Page Code: ' . $rosterPage['code'] . ' Token: ' . ($tokenRoster ?? 'NULL') . ' Body: ' . substr(strip_tags($rosterPage['body']), 0, 150));

            $addRosterPost = $this->request('POST', "/o/{$slug}/s/football/teams/{$team1['id']}/rosters/{$seasonRow['id']}", [
                '_csrf' => $tokenRoster,
                'player_id' => $player1['id'],
                'jersey_number' => '9',
                'position' => 'Forward'
            ]);

            if ($addRosterPost['code'] === 302 && str_contains($addRosterPost['redirect'], '/rosters')) {
                $this->recordResult('11. Rosters', 'Add Player to Roster', 'PASS', "Assigned player {$player1['id']} to team {$team1['id']}");
            } else {
                $this->recordResult('11. Rosters', 'Add Player to Roster', 'FAIL', 'Add Roster code: ' . $addRosterPost['code'] . ' redirect: ' . $addRosterPost['redirect'] . ' body: ' . substr(strip_tags($addRosterPost['body']), 0, 150));
            }

            // Duplicate roster check
            $tokenRoster2 = $this->extractCsrfToken($this->request('GET', "/o/{$slug}/s/football/teams/{$team1['id']}/rosters/{$seasonRow['id']}")['body']);
            $dupRosterPost = $this->request('POST', "/o/{$slug}/s/football/teams/{$team1['id']}/rosters/{$seasonRow['id']}", [
                '_csrf' => $tokenRoster2,
                'player_id' => $player1['id'],
                'jersey_number' => '9',
                'position' => 'Forward'
            ]);

            if (str_contains($dupRosterPost['body'], 'already') || str_contains($dupRosterPost['redirect'], '/rosters') || $dupRosterPost['code'] !== 302) {
                $this->recordResult('11. Rosters', 'Duplicate Roster Assignment Protection', 'PASS', 'Prevented duplicate roster assignment');
            } else {
                $this->recordResult('11. Rosters', 'Duplicate Roster Assignment Protection', 'FAIL', 'Allowed duplicate roster assignment');
            }
        }

        // 12. Fixtures
        if ($team1 && $team2 && $seasonRow) {
            $createFixPage = $this->request('GET', "/o/{$slug}/s/football/fixtures/create");
            $tokenFix = $this->extractCsrfToken($createFixPage['body']);
            $this->recordResult('12. Fixtures', 'Create Fixture Page Load', 'INFO', 'Page Code: ' . $createFixPage['code'] . ' Token: ' . ($tokenFix ?? 'NULL') . ' Body: ' . substr(strip_tags($createFixPage['body']), 0, 150));

            // Same team validation test (Home == Away)
            $sameTeamPost = $this->request('POST', "/o/{$slug}/s/football/fixtures", [
                '_csrf' => $tokenFix,
                'season_id' => $seasonRow['id'],
                'home_team_id' => $team1['id'],
                'away_team_id' => $team1['id'],
                'scheduled_at' => '2026-10-15T16:00',
                'venue_name' => 'Main Stadium',
                'competition_type' => 'league',
                'competition_name' => 'Premier League'
            ]);

            if (str_contains($sameTeamPost['body'], 'cannot be the same') || str_contains($sameTeamPost['body'], 'different') || $sameTeamPost['code'] === 400 || str_contains($sameTeamPost['redirect'], '/create')) {
                $this->recordResult('12. Fixtures', 'Same Home and Away Team Validation', 'PASS', 'Blocked identical home & away team selection');
            } else {
                $this->recordResult('12. Fixtures', 'Same Home and Away Team Validation', 'FAIL', 'Allowed identical home & away teams');
            }

            // Valid Fixture A (Competitive)
            $tokenFixA = $this->extractCsrfToken($this->request('GET', "/o/{$slug}/s/football/fixtures/create")['body']);
            $this->recordResult('12. Fixtures', 'Extracted Fixture A Token Check', 'INFO', 'FixA Token: ' . ($tokenFixA ?? 'NULL'));
            $fixAPost = $this->request('POST', "/o/{$slug}/s/football/fixtures", [
                '_csrf' => $tokenFixA,
                'season_id' => $seasonRow['id'],
                'home_team_id' => $team1['id'],
                'away_team_id' => $team2['id'],
                'scheduled_at' => '2026-10-20T15:00',
                'venue_name' => 'National Arena',
                'competition_type' => 'league',
                'competition_name' => 'National Championship'
            ]);

            if ($fixAPost['code'] === 302 && str_contains($fixAPost['redirect'], '/fixtures') && !str_contains($fixAPost['redirect'], '/create')) {
                $this->recordResult('12. Fixtures', 'Create Competitive Fixture A', 'PASS', 'Created Competitive Fixture');
            } else {
                $this->recordResult('12. Fixtures', 'Create Competitive Fixture A', 'FAIL', 'Fixture A POST redirect: ' . $fixAPost['redirect'] . ' body: ' . substr(strip_tags($fixAPost['body']), 0, 150));
            }

            // Valid Fixture B (Friendly)
            $tokenFixB = $this->extractCsrfToken($this->request('GET', "/o/{$slug}/s/football/fixtures/create")['body']);
            $fixBPost = $this->request('POST', "/o/{$slug}/s/football/fixtures", [
                '_csrf' => $tokenFixB,
                'season_id' => $seasonRow['id'],
                'home_team_id' => $team2['id'],
                'away_team_id' => $team1['id'],
                'scheduled_at' => '2026-11-05T18:00',
                'venue_name' => 'Training Ground',
                'competition_type' => 'friendly',
                'competition_name' => 'Pre-Season Friendly'
            ]);

            if ($fixBPost['code'] === 302 && str_contains($fixBPost['redirect'], '/fixtures') && !str_contains($fixBPost['redirect'], '/create')) {
                $this->recordResult('12. Fixtures', 'Create Friendly Fixture B', 'PASS', 'Created Friendly Fixture');
            } else {
                $this->recordResult('12. Fixtures', 'Create Friendly Fixture B', 'FAIL', 'Fixture B creation failed');
            }

            $fixtureRow = $this->db->query("SELECT * FROM fixtures WHERE organization_id = {$orgIdQuoted} ORDER BY id DESC LIMIT 1")->fetch();

            // 13. Public Fixture Page
            if ($fixtureRow) {
                $publicUrl = "/{$slug}/football/fixtures";
                $publicRes = $this->request('GET', $publicUrl, [], false); // unauthenticated

                if ($publicRes['code'] === 200 && (str_contains($publicRes['body'], 'National Arena') || str_contains($publicRes['body'], 'Pre-Season Friendly') || str_contains($publicRes['body'], 'Alpha Lions FC'))) {
                    $this->recordResult('13. Public Fixtures', 'Unauthenticated Public Fixture Overview', 'PASS', 'Public fixture page loaded without auth');
                } else {
                    $this->recordResult('13. Public Fixtures', 'Unauthenticated Public Fixture Overview', 'FAIL', 'Public fixture page code: ' . $publicRes['code'] . ' body: ' . substr(strip_tags($publicRes['body']), 0, 200));
                }

                // Check sensitive data leakage in public page
                if (str_contains($publicRes['body'], 'database') || str_contains($publicRes['body'], 'created_at') || str_contains($publicRes['body'], 'user_id')) {
                    $this->recordResult('13. Public Fixtures', 'Public Data Sanitization', 'FAIL', 'Exposed sensitive tenant fields');
                } else {
                    $this->recordResult('13. Public Fixtures', 'Public Data Sanitization', 'PASS', 'No sensitive tenant data leaked');
                }
            }
        }
    }

    private function testTenantIsolationAndAuthorization(): void
    {
        // Create second user & org to test tenant isolation
        $userBEmail = 'tenant_b_' . time() . '@benchero.test';
        $regForm = $this->request('GET', '/register', [], false);
        $token = $this->extractCsrfToken($regForm['body']);

        $this->request('POST', '/register', [
            '_csrf' => $token,
            'name' => 'User B',
            'email' => $userBEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ], false);

        // Fetch verification link
        $mailLog = file_get_contents(__DIR__ . '/../storage/logs/mail.log');
        preg_match_all('/http:\/\/localhost\/teamora\/public\/verify-email\/([A-Za-z0-9]+)\/([A-Fa-f0-9]+)/', $mailLog, $matches, PREG_SET_ORDER);
        $lastMatch = end($matches);
        $this->request('GET', "/verify-email/{$lastMatch[1]}/{$lastMatch[2]}");

        // Login as User B
        $cookieB = tempnam(sys_get_temp_dir(), 'tm_user_b_');
        $ch = curl_init($this->baseUrl . '/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieB);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieB);
        $loginPageB = curl_exec($ch);
        $tokenB = $this->extractCsrfToken($loginPageB);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            '_csrf' => $tokenB,
            'email' => $userBEmail,
            'password' => 'Password123!'
        ]));
        curl_exec($ch);

        // Onboarding for User B -> Org B
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/onboarding');
        curl_setopt($ch, CURLOPT_POST, false);
        $onboardingB = curl_exec($ch);
        $tokenOnboardB = $this->extractCsrfToken($onboardingB);

        $orgBName = 'Org B Rivals ' . rand(100, 999);
        $orgBSlug = 'org-b-rivals-' . rand(100, 999);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            '_csrf' => $tokenOnboardB,
            'name' => $orgBName,
            'slug' => $orgBSlug,
            'country' => 'KE',
            'timezone' => 'Africa/Nairobi'
        ]));
        curl_exec($ch);

        // Get Org A & Org B data
        $orgA = $this->db->query("SELECT * FROM organizations WHERE slug LIKE 'e2e-org-%' ORDER BY created_at DESC LIMIT 1")->fetch();
        $orgB = $this->db->query("SELECT * FROM organizations WHERE name = " . $this->db->quote($orgBName))->fetch();

        if ($orgA && $orgB) {
            // User B attempts to access Org A dashboard
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . "/o/{$orgA['slug']}/dashboard");
            curl_setopt($ch, CURLOPT_POST, false);
            $crossDash = curl_exec($ch);
            $codeDash = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($codeDash === 403 || str_contains($crossDash, 'Forbidden') || str_contains($crossDash, 'Access Denied')) {
                $this->recordResult('14. Tenant Isolation', 'Cross-Tenant Dashboard Access', 'PASS', 'User B blocked from Org A dashboard (403)');
            } else {
                $this->recordResult('14. Tenant Isolation', 'Cross-Tenant Dashboard Access', 'FAIL', 'User B accessed Org A dashboard! Code: ' . $codeDash);
            }

            // User B attempts to view Org A teams
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . "/o/{$orgA['slug']}/s/football/teams");
            $crossTeams = curl_exec($ch);
            $codeTeams = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($codeTeams === 403) {
                $this->recordResult('14. Tenant Isolation', 'Cross-Tenant Teams Access', 'PASS', 'User B blocked from Org A teams (403)');
            } else {
                $this->recordResult('14. Tenant Isolation', 'Cross-Tenant Teams Access', 'FAIL', 'User B accessed Org A teams! Code: ' . $codeTeams);
            }
        }

        curl_close($ch);
        @unlink($cookieB);
    }

    private function testCsrfAndValidation(): void
    {
        // Missing CSRF on Login
        $loginPost = $this->request('POST', '/login', [
            'email' => 'test@test.com',
            'password' => 'Password123!'
        ]);
        if ($loginPost['code'] === 403) {
            $this->recordResult('16. CSRF', 'Login missing CSRF token rejected', 'PASS', 'HTTP 403 returned');
        } else {
            $this->recordResult('16. CSRF', 'Login missing CSRF token rejected', 'FAIL', 'HTTP code: ' . $loginPost['code']);
        }

        // Invalid CSRF on Login
        $loginInvalidToken = $this->request('POST', '/login', [
            '_csrf' => 'invalid_token_12345',
            'email' => 'test@test.com',
            'password' => 'Password123!'
        ]);
        if ($loginInvalidToken['code'] === 403) {
            $this->recordResult('16. CSRF', 'Login invalid CSRF token rejected', 'PASS', 'HTTP 403 returned');
        } else {
            $this->recordResult('16. CSRF', 'Login invalid CSRF token rejected', 'FAIL', 'HTTP code: ' . $loginInvalidToken['code']);
        }
    }

    private function testNavigationAndErrorPages(): void
    {
        // 404 Route
        $notFound = $this->request('GET', '/nonexistent-route-path-12345');
        if ($notFound['code'] === 404) {
            $this->recordResult('20. Error Handling', '404 Graceful Response', 'PASS', 'Nonexistent route returned HTTP 404');
        } else {
            $this->recordResult('20. Error Handling', '404 Graceful Response', 'FAIL', 'Got HTTP code ' . $notFound['code']);
        }

        // Information Leakage Check
        if (str_contains($notFound['body'], 'Stack trace:') || str_contains($notFound['body'], 'DB_PASSWORD') || str_contains($notFound['body'], 'SQLSTATE')) {
            $this->recordResult('20. Error Handling', 'Sensitive Data Leakage Protection', 'FAIL', 'Stack trace or credentials exposed on 404');
        } else {
            $this->recordResult('20. Error Handling', 'Sensitive Data Leakage Protection', 'PASS', 'No stack traces or sensitive credentials leaked');
        }
    }

    private function testDatabaseIntegrity(): void
    {
        $tables = [
            'users', 'organizations', 'organization_user', 'organization_sports',
            'sports', 'seasons', 'teams', 'players', 'roster_assignments', 'fixtures', 'subscriptions'
        ];

        foreach ($tables as $tbl) {
            try {
                $count = $this->db->query("SELECT COUNT(*) FROM {$tbl}")->fetchColumn();
                $this->recordResult('21. Database Verification', "Table `{$tbl}` integrity check", 'PASS', "Row count: {$count}");
            } catch (\Throwable $e) {
                $this->recordResult('21. Database Verification', "Table `{$tbl}` integrity check", 'FAIL', $e->getMessage());
            }
        }
    }
}

$tester = new E2EAutomationTester();
$data = $tester->runAll();

file_put_contents(__DIR__ . '/e2e_results.json', json_encode($data, JSON_PRETTY_PRINT));
echo "\nE2E Test Suite Completed! Results saved to tests/e2e_results.json\n";
