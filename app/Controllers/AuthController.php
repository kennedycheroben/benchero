<?php

namespace Teamora\Controllers;

use Teamora\Core\Controller;
use Teamora\Core\Database\Database;
use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Core\Mail\LogMailer;
use Teamora\Core\Mail\SmtpMailer;
use Teamora\Core\RateLimiter;
use Teamora\Services\Auth\AuthService;
use Teamora\Services\Auth\AuthTokenService;

class AuthController extends Controller
{
    private AuthService $authService;
    private AuthTokenService $tokenService;
    private RateLimiter $rateLimiter;
    private $mailer;

    public function __construct()
    {
        parent::__construct();
        $pdo = Database::getConnection();
        $this->authService = new AuthService($pdo);
        $this->tokenService = new AuthTokenService($pdo);
        $this->rateLimiter = new RateLimiter($pdo);

        if (env('MAIL_MAILER', 'log') === 'smtp') {
            $this->mailer = new SmtpMailer(
                env('MAIL_HOST', ''),
                (int)env('MAIL_PORT', 587),
                env('MAIL_ENCRYPTION', 'tls'),
                env('MAIL_USERNAME', ''),
                env('MAIL_PASSWORD', ''),
                env('MAIL_FROM_ADDRESS', 'noreply@teamora.local'),
                env('MAIL_FROM_NAME', 'Teamora')
            );
        } else {
            $this->mailer = new LogMailer();
        }
    }

    public function registerForm(Request $request): Response
    {
        // Don't show register form if already logged in
        if (isset($_SESSION['user_id'])) {
            return $this->redirectBasedOnOrgs($_SESSION['user_id']);
        }
        return $this->render('auth/register');
    }

    public function register(Request $request): Response
    {
        // CSRF handled by middleware.
        $name = trim($request->input('name') ?? '');
        $email = strtolower(trim($request->input('email') ?? ''));
        $password = $request->input('password') ?? '';
        $confirm = $request->input('password_confirmation') ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            return $this->render('auth/register', ['error' => 'All fields are required.', 'name' => $name, 'email' => $email], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('auth/register', ['error' => 'Invalid email address.', 'name' => $name, 'email' => $email], 400);
        }

        if (strlen($password) < 8) {
            return $this->render('auth/register', ['error' => 'Password must be at least 8 characters.', 'name' => $name, 'email' => $email], 400);
        }

        if ($password !== $confirm) {
            return $this->render('auth/register', ['error' => 'Passwords do not match.', 'name' => $name, 'email' => $email], 400);
        }

        // Apply rate limit on registration to prevent spam/enumeration
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!$this->rateLimiter->hit("register_{$ip}", 5, 3600)) {
            return $this->render('auth/register', ['error' => 'Too many registration attempts. Please try again later.'], 429);
        }

        $userId = $this->authService->registerUser($name, $email, $password);

        if ($userId) {
            // New user created. Generate token and send email.
            $this->sendVerificationEmail($userId, $email, $name);
        } else {
            // Email already exists. To prevent enumeration, we silently act as if successful, 
            // but we could send an email saying "an account already exists". For this MVP, we just show success.
            // (Alternatively, many SaaS show "Email taken" - but user requested no unnecessary enumeration).
        }

        return $this->render('auth/register', ['success' => 'Registration successful! Please check your email to verify your account.']);
    }

    public function loginForm(Request $request): Response
    {
        if (isset($_SESSION['user_id'])) {
            return $this->redirectBasedOnOrgs($_SESSION['user_id']);
        }
        return $this->render('auth/login');
    }

    public function login(Request $request): Response
    {
        $email = strtolower(trim($request->input('email') ?? ''));
        $password = $request->input('password') ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('auth/login', ['error' => 'Email and password are required.', 'email' => $email], 400);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!$this->rateLimiter->hit("login_{$ip}", 10, 900) || !$this->rateLimiter->hit("login_email_{$email}", 10, 900)) {
            return $this->render('auth/login', ['error' => 'Too many login attempts. Please try again later.'], 429);
        }

        $user = $this->authService->findUserByEmail($email);
        
        if (!$user || !$this->authService->verifyPassword($password, $user['password_hash'])) {
            return $this->render('auth/login', ['error' => 'Invalid credentials.', 'email' => $email], 401);
        }

        if ($user['email_verified_at'] === null) {
            return $this->render('auth/login', [
                'error' => 'Please verify your email address before logging in.',
                'email' => $email
            ], 403);
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->authService->updatePassword($user['id'], $password);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['_user_id'] = $user['id'];

        $this->authService->updateLastLogin($user['id']);

        return $this->redirectBasedOnOrgs($user['id']);
    }

    public function logout(Request $request): Response
    {
        $_SESSION = [];
        session_destroy();
        
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );

        return Response::redirect('/login');
    }

    public function organizationSelection(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return Response::redirect('/login');
        }
        
        $orgs = $this->authService->getUserOrganizations($_SESSION['user_id']);
        if (count($orgs) === 0) {
            return Response::redirect('/onboarding');
        } elseif (count($orgs) === 1) {
            return Response::redirect('/o/' . $orgs[0]['slug'] . '/dashboard');
        }
        
        return $this->render('auth/organizations', ['organizations' => $orgs]);
    }

    private function redirectBasedOnOrgs(string $userId): Response
    {
        $orgs = $this->authService->getUserOrganizations($userId);
        if (count($orgs) === 0) {
            return Response::redirect('/onboarding');
        } elseif (count($orgs) === 1) {
            return Response::redirect('/o/' . $orgs[0]['slug'] . '/dashboard');
        } else {
            return Response::redirect('/organizations');
        }
    }

    public function verifyEmail(Request $request, string $id, string $token): Response
    {
        // $id is the user ID, $token is the plaintext token
        // First check if user exists
        $user = $this->authService->findUserById($id);
        if (!$user) {
            return $this->render('auth/verify_result', ['error' => 'Invalid verification link.'], 400);
        }

        if ($user['email_verified_at'] !== null) {
            return $this->render('auth/verify_result', ['success' => 'Your email is already verified. You may now log in.']);
        }

        $validUserId = $this->tokenService->validateAndUseToken($token, AuthTokenService::TYPE_EMAIL_VERIFICATION);

        if ($validUserId === $id) {
            $this->authService->markEmailVerified($id);
            return $this->render('auth/verify_result', ['success' => 'Email verified successfully! You may now log in.']);
        }

        return $this->render('auth/verify_result', ['error' => 'The verification link is invalid or has expired.'], 400);
    }

    public function resendVerificationForm(Request $request): Response
    {
        return $this->render('auth/resend_verification');
    }

    public function resendVerification(Request $request): Response
    {
        $email = strtolower(trim($request->input('email') ?? ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('auth/resend_verification', ['error' => 'Please provide a valid email address.']);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!$this->rateLimiter->hit("resend_verify_{$ip}", 3, 900)) { // 3 per 15 minutes
            return $this->render('auth/resend_verification', ['error' => 'Too many requests. Please try again later.'], 429);
        }

        $user = $this->authService->findUserByEmail($email);
        
        // Generic success message to prevent enumeration
        $successMsg = 'If your account exists and is unverified, a new verification link has been sent.';

        if ($user && $user['email_verified_at'] === null) {
            $this->sendVerificationEmail($user['id'], $user['email'], $user['name']);
        }

        return $this->render('auth/resend_verification', ['success' => $successMsg]);
    }

    private function sendVerificationEmail(string $userId, string $email, string $name): void
    {
        $plaintextToken = $this->tokenService->generateToken($userId, AuthTokenService::TYPE_EMAIL_VERIFICATION, 86400); // 24 hours
        
        $appUrl = env('APP_URL', 'http://localhost');
        $verifyUrl = rtrim($appUrl, '/') . "/verify-email/{$userId}/{$plaintextToken}";

        $subject = 'Verify your email address';
        $htmlBody = "<p>Hello {$name},</p><p>Please verify your email address by clicking the link below:</p><p><a href=\"{$verifyUrl}\">{$verifyUrl}</a></p><p>This link will expire in 24 hours.</p>";
        $textBody = "Hello {$name},\n\nPlease verify your email address by opening the following link:\n{$verifyUrl}\n\nThis link will expire in 24 hours.";

        $this->mailer->send($email, $subject, $htmlBody, $textBody);
    }
}

