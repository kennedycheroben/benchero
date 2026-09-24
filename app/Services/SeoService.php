<?php

namespace Benchero\Services;

use Benchero\Core\CustomDomainContext;

class SeoService
{
    private string $baseUrl;
    private bool $isCustomDomain = false;

    public function __construct(?string $baseUrl = null)
    {
        if ($baseUrl !== null) {
            $this->baseUrl = rtrim($baseUrl, '/');
        } elseif (class_exists(CustomDomainContext::class) && CustomDomainContext::isActive()) {
            $domain = CustomDomainContext::getDomain();
            if ($domain) {
                $this->baseUrl = 'https://' . $domain;
                $this->isCustomDomain = true;
            }
        }

        if (empty($this->baseUrl)) {
            $appUrl = env('APP_URL', 'https://benchero.co.ke');
            if (!str_starts_with($appUrl, 'http')) {
                $appUrl = 'https://' . $appUrl;
            }
            $this->baseUrl = rtrim($appUrl, '/');
        }
    }

    /**
     * Build standard SEO tags array for any public page.
     */
    public function generateMeta(array $options): array
    {
        $clubName = $options['club_name'] ?? 'Benchero';
        $title = $options['title'] ?? "{$clubName} — Official Digital Identity & Club Website";
        $description = $options['description'] ?? "Official web presence of {$clubName}. View teams, roster, match fixtures, results, standings, and latest news on Benchero.";
        
        $path = '/' . ltrim($options['path'] ?? '', '/');
        if ($this->isCustomDomain && preg_match('#^/club/[^/]+(.*)$#', $path, $matches)) {
            $path = $matches[1] !== '' ? $matches[1] : '/';
        }

        $canonicalUrl = rtrim($this->baseUrl, '/') . ($path === '/' ? '' : $path);

        // Determine Open Graph image
        $ogImage = $options['image_url'] ?? null;
        if (empty($ogImage) && !empty($options['pro_og_image'])) {
            $ogImage = $options['pro_og_image'];
        }
        if (empty($ogImage) && !empty($options['logo_url'])) {
            $ogImage = $options['logo_url'];
        }
        if (empty($ogImage)) {
            $ogImage = '/images/benchero_logo.png';
        }

        if (!str_starts_with($ogImage, 'http')) {
            $ogImage = $this->baseUrl . '/' . ltrim($ogImage, '/');
        }

        return [
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            'canonical_url' => $canonicalUrl,
            'og_title' => htmlspecialchars($options['og_title'] ?? $title, ENT_QUOTES, 'UTF-8'),
            'og_description' => htmlspecialchars($options['og_description'] ?? $description, ENT_QUOTES, 'UTF-8'),
            'og_image' => $ogImage,
            'og_url' => $canonicalUrl,
            'twitter_card' => 'summary_large_image',
            'site_name' => htmlspecialchars($clubName . ' | Benchero', ENT_QUOTES, 'UTF-8')
        ];
    }
}
