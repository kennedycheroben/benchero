<?php

namespace Benchero\Controllers;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Database\Database;
use Benchero\Core\RateLimiter;
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

        return $this->render('public/pricing', [
            'title' => 'Benchero Pricing — Simple & Transparent Plans',
            'plans' => $plans
        ]);
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
