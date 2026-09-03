<?php

namespace Benchero\Controllers;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Mail\LogMailer;
use Benchero\Core\Mail\SmtpMailer;
use Benchero\Core\RateLimiter;
use Benchero\Services\Auth\AuthService;
use Benchero\Services\Auth\AuthTokenService;

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
                env('MAIL_FROM_ADDRESS', 'noreply@benchero.com'),
                env('MAIL_FROM_NAME', 'Benchero')
            );
        } else {
            $this->mailer = new LogMailer();
        }
    }

    public function registerForm(Request $request): Response
    {
        if (isset($_SESSION['user_id'])) {
            return $this->redirectBasedOnOrgs($_SESSION['user_id']);
        }
        return $this->render('auth/register');
    }

    public function register(Request $request): Response
    {
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

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!$this->rateLimiter->hit("register_{$ip}", 5, 3600)) {
            return $this->render('auth/register', ['error' => 'Too many registration attempts. Please try again later.'], 429);
        }

        $userId = $this->authService->registerUser($name, $email, $password);

        if ($userId) {
            $this->sendVerificationEmail($userId, $email, $name);
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

    public function forgotPasswordForm(Request $request): Response
    {
        return $this->render('auth/forgot_password');
    }

    public function forgotPasswordSubmit(Request $request): Response
    {
        $email = strtolower(trim($request->input('email') ?? ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('auth/forgot_password', ['error' => 'Please enter a valid email address.']);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!$this->rateLimiter->hit("forgot_pass_{$ip}", 3, 900)) {
            return $this->render('auth/forgot_password', ['error' => 'Too many reset attempts. Please try again later.'], 429);
        }

        $user = $this->authService->findUserByEmail($email);
        $genericMessage = 'If your email is registered, we have sent a password reset link to your inbox.';

        if ($user) {
            $token = $this->tokenService->generateToken($user['id'], AuthTokenService::TYPE_PASSWORD_RESET, 3600); // 1 hr
            $appUrl = env('APP_URL', 'http://localhost');
            $resetUrl = rtrim($appUrl, '/') . "/reset-password/{$token}";

            $subject = 'Benchero Password Reset';
            $htmlBody = "<p>Hello {$user['name']},</p><p>Click the link below to reset your Benchero password:</p><p><a href=\"{$resetUrl}\">{$resetUrl}</a></p><p>This link expires in 1 hour.</p>";
            $textBody = "Hello {$user['name']},\n\nClick the link below to reset your Benchero password:\n{$resetUrl}\n\nThis link expires in 1 hour.";

            $this->mailer->send($email, $subject, $htmlBody, $textBody);
        }

        return $this->render('auth/forgot_password', ['success' => $genericMessage]);
    }

    public function resetPasswordForm(Request $request, string $token): Response
    {
        return $this->render('auth/reset_password', ['token' => $token]);
    }

    public function resetPasswordSubmit(Request $request): Response
    {
        $token = $request->input('token') ?? '';
        $password = $request->input('password') ?? '';
        $confirm = $request->input('password_confirmation') ?? '';

        if (empty($token) || empty($password)) {
            return $this->render('auth/reset_password', ['error' => 'Token and password are required.', 'token' => $token], 400);
        }

        if (strlen($password) < 8) {
            return $this->render('auth/reset_password', ['error' => 'Password must be at least 8 characters.', 'token' => $token], 400);
        }

        if ($password !== $confirm) {
            return $this->render('auth/reset_password', ['error' => 'Passwords do not match.', 'token' => $token], 400);
        }

        $userId = $this->tokenService->validateAndUseToken($token, AuthTokenService::TYPE_PASSWORD_RESET);

        if (!$userId) {
            return $this->render('auth/reset_password', ['error' => 'The password reset link is invalid or has expired.', 'token' => $token], 400);
        }

        $this->authService->updatePassword($userId, $password);

        return $this->render('auth/login', ['success' => 'Your password has been successfully reset! You may now log in with your new password.']);
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
        if (!$this->rateLimiter->hit("resend_verify_{$ip}", 3, 900)) {
            return $this->render('auth/resend_verification', ['error' => 'Too many requests. Please try again later.'], 429);
        }

        $user = $this->authService->findUserByEmail($email);
        $successMsg = 'If your account exists and is unverified, a new verification link has been sent.';

        if ($user && $user['email_verified_at'] === null) {
            $this->sendVerificationEmail($user['id'], $user['email'], $user['name']);
        }

        return $this->render('auth/resend_verification', ['success' => $successMsg]);
    }

    private function sendVerificationEmail(string $userId, string $email, string $name): void
    {
        $plaintextToken = $this->tokenService->generateToken($userId, AuthTokenService::TYPE_EMAIL_VERIFICATION, 86400);
        $appUrl = env('APP_URL', 'http://localhost');
        $verifyUrl = rtrim($appUrl, '/') . "/verify-email/{$userId}/{$plaintextToken}";

        $subject = 'Verify your email address';
        $htmlBody = "<p>Hello {$name},</p><p>Please verify your email address by clicking the link below:</p><p><a href=\"{$verifyUrl}\">{$verifyUrl}</a></p><p>This link will expire in 24 hours.</p>";
        $textBody = "Hello {$name},\n\nPlease verify your email address by opening the following link:\n{$verifyUrl}\n\nThis link will expire in 24 hours.";

        $this->mailer->send($email, $subject, $htmlBody, $textBody);
    }
}
