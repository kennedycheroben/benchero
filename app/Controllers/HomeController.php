<?php

namespace Benchero\Controllers;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Database\Database;
use Benchero\Core\RateLimiter;
use Benchero\Core\Ulid;
use PDO;

class HomeController extends Controller
{
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        parent::__construct();
        $this->rateLimiter = new RateLimiter(Database::getConnection());
    }

    public function index(Request $request): Response
    {
        return $this->render('home', [
            'title' => 'Benchero — Your Club. Your Teams. Your Players. Your Game. Your Platform.'
        ]);
    }

    public function features(Request $request): Response
    {
        return $this->render('public/features', [
            'title' => 'Features & Capabilities — Benchero Sports Operating System'
        ]);
    }

    public function about(Request $request): Response
    {
        return $this->render('public/about', [
            'title' => 'About Benchero — Complete Sports Club Management Platform'
        ]);
    }

    public function pricing(Request $request): Response
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY price_kes ASC");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $currencyParam = $request->input('currency');
        $rawCurrency = $currencyParam ?? $_SESSION['currency'] ?? $_COOKIE['benchero_currency'] ?? 'KES';
        $activeCurrency = strtoupper(trim((string)$rawCurrency));
        if (!in_array($activeCurrency, ['KES', 'USD'], true)) {
            $activeCurrency = 'KES';
        }
        $_SESSION['currency'] = $activeCurrency;
        if (!headers_sent()) {
            setcookie('benchero_currency', $activeCurrency, time() + (86400 * 30), '/');
        }

        return $this->render('public/pricing', [
            'title' => 'Benchero Pricing — Simple & Transparent Plans',
            'plans' => $plans,
            'activeCurrency' => $activeCurrency
        ]);
    }

    public function setCurrency(Request $request): Response
    {
        $rawCurrency = $request->input('currency') ?? $request->post('currency') ?? 'KES';
        $currency = strtoupper(trim((string)$rawCurrency));

        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? $request->header('Accept') ?? '';
        $xReq = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? $request->header('X-Requested-With') ?? '';
        $isAjax = (!empty($xReq) && strtolower($xReq) === 'xmlhttprequest') 
            || str_contains($acceptHeader, 'application/json')
            || $request->input('format') === 'json';

        if (!in_array($currency, ['KES', 'USD'], true)) {
            if ($isAjax) {
                return Response::json([
                    'success' => false,
                    'error' => 'Unsupported currency. Only KES and USD are currently supported.'
                ], 400);
            }
            $currency = 'KES';
        }

        $_SESSION['currency'] = $currency;
        if (!headers_sent()) {
            setcookie('benchero_currency', $currency, time() + (86400 * 30), '/');
        }

        if ($isAjax) {
            return Response::json([
                'success' => true,
                'currency' => $currency
            ]);
        }

        $redirect = $request->input('redirect') ?? '/pricing?currency=' . $currency;
        return Response::redirect($redirect);
    }

    public function contactForm(Request $request): Response
    {
        return $this->render('public/contact', [
            'title' => 'Contact Benchero Team',
            'success' => $request->query()['success'] ?? null,
            'error' => null
        ]);
    }

    public function contactSubmit(Request $request): Response
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!$this->rateLimiter->hit("contact_form_{$ip}", 5, 3600)) {
            return $this->render('public/contact', [
                'title' => 'Contact Benchero Team',
                'error' => 'Too many contact form submissions. Please try again later.'
            ], 429);
        }

        $name = trim((string)$request->input('name'));
        $email = trim((string)$request->input('email'));
        $subject = trim((string)$request->input('subject'));
        $message = trim((string)$request->input('message'));

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            return $this->render('public/contact', [
                'title' => 'Contact Benchero Team',
                'error' => 'Please fill in all required fields.',
                'input' => ['name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message]
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('public/contact', [
                'title' => 'Contact Benchero Team',
                'error' => 'Please provide a valid email address.',
                'input' => ['name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message]
            ], 400);
        }

        $recipient = env('BRAND_CONTACT_EMAIL', 'contact@benchero.co.ke');
        error_log("Contact message received from {$name} ({$email}) to {$recipient}: {$subject}");

        // Insert into contact_messages
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO contact_messages (id, organization_id, name, email, subject, message, status, created_at)
            VALUES (?, NULL, ?, ?, ?, ?, 'unread', NOW())
        ");
        $stmt->execute([
            Ulid::generate(),
            $name,
            $email,
            $subject,
            $message
        ]);

        return new Response('', 302, ['Location' => url('/contact?success=1')]);
    }

    public function terms(Request $request): Response
    {
        return $this->render('public/terms', [
            'title' => 'Benchero — Terms of Service'
        ]);
    }

    public function privacy(Request $request): Response
    {
        return $this->render('public/privacy', [
            'title' => 'Benchero — Privacy Policy'
        ]);
    }

    public function cookies(Request $request): Response
    {
        return $this->render('public/cookies', [
            'title' => 'Benchero — Cookie Policy'
        ]);
    }
}
