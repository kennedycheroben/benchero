<?php

namespace Benchero\Controllers\Public;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\SubscriptionService;
use PDO;

class SitemapController extends Controller
{
    public function sitemap(Request $request): Response
    {
        $appUrl = rtrim(env('APP_URL', 'https://benchero.co.ke'), '/');
        $db = Database::getConnection();
        $subService = new SubscriptionService();

        // 1. Core Public Benchero URLs
        $urls = [
            $appUrl . '/',
            $appUrl . '/about',
            $appUrl . '/pricing',
            $appUrl . '/contact',
            $appUrl . '/terms',
            $appUrl . '/privacy',
            $appUrl . '/cookies'
        ];

        // 2. Active Public Club Websites with Active Subscriptions
        $stmt = $db->query("
            SELECT id, slug, updated_at 
            FROM organizations 
            WHERE deleted_at IS NULL AND slug IS NOT NULL AND slug != ''
            ORDER BY updated_at DESC
        ");
        $orgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $clubSections = ['teams', 'players', 'staff', 'fixtures', 'results', 'standings', 'news', 'gallery', 'about', 'contact'];

        foreach ($orgs as $org) {
            if ($subService->isPublicProfileVisible($org['id'])) {
                $baseClubUrl = $appUrl . '/club/' . urlencode($org['slug']);
                $urls[] = $baseClubUrl;
                foreach ($clubSections as $section) {
                    $urls[] = $baseClubUrl . '/' . $section;
                }
            }
        }

        // Build XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        $response = new Response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
        return $response;
    }

    public function robots(Request $request): Response
    {
        $appUrl = rtrim(env('APP_URL', 'https://benchero.co.ke'), '/');
        
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Allow: /about\n";
        $robots .= "Allow: /pricing\n";
        $robots .= "Allow: /contact\n";
        $robots .= "Allow: /terms\n";
        $robots .= "Allow: /privacy\n";
        $robots .= "Allow: /cookies\n";
        $robots .= "Allow: /club/\n";
        $robots .= "Disallow: /o/\n";
        $robots .= "Disallow: /dashboard\n";
        $robots .= "Disallow: /billing\n";
        $robots .= "Disallow: /account\n";
        $robots .= "Disallow: /admin\n";
        $robots .= "Disallow: /login\n";
        $robots .= "Disallow: /register\n";
        $robots .= "Disallow: /reset-password\n";
        $robots .= "Disallow: /verify-email\n\n";
        $robots .= "Sitemap: " . $appUrl . "/sitemap.xml\n";

        return new Response($robots, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
