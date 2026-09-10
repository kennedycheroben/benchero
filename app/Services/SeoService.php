<?php

namespace Benchero\Services;

class SeoService
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $appUrl = env('APP_URL', 'https://benchero.co.ke');
        if (!str_starts_with($appUrl, 'http')) {
            $appUrl = 'https://' . $appUrl;
        }
        $this->baseUrl = $baseUrl ?? rtrim($appUrl, '/');
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
        $canonicalUrl = $this->baseUrl . $path;

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
