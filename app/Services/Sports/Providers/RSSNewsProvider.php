<?php

namespace Benchero\Services\Sports\Providers;

use Benchero\Contracts\NewsProviderInterface;
use SimpleXMLElement;

class RSSNewsProvider implements NewsProviderInterface
{
    private array $rssFeeds;

    public function __construct()
    {
        $this->rssFeeds = [
            [
                'source' => 'BBC Sport',
                'url' => 'http://feeds.bbci.co.uk/sport/rss.xml',
                'category' => 'Football',
                'sport' => 'football'
            ],
            [
                'source' => 'Sky Sports',
                'url' => 'https://www.skysports.com/rss/12040',
                'category' => 'Football',
                'sport' => 'football'
            ],
            [
                'source' => 'Standard Sports Kenya',
                'url' => 'https://www.standardmedia.co.ke/rss/sports.php',
                'category' => 'Kenyan Sports',
                'sport' => 'football'
            ]
        ];
    }

    public function getLatestNews(int $limit = 10, ?string $sport = null): array
    {
        $articles = [];
        $seenUrls = [];

        foreach ($this->rssFeeds as $feedInfo) {
            if ($sport && strtolower($feedInfo['sport']) !== strtolower($sport)) {
                continue;
            }

            $feedItems = $this->fetchFeed($feedInfo);
            foreach ($feedItems as $item) {
                if (isset($seenUrls[$item['source_url']])) {
                    continue;
                }
                $seenUrls[$item['source_url']] = true;
                $articles[] = $item;
            }
        }

        // Sort by publication timestamp descending
        usort($articles, fn($a, $b) => strcmp($b['published_at'], $a['published_at']));

        return array_slice($articles, 0, $limit);
    }

    public function getNewsBySlug(string $slug): ?array
    {
        $all = $this->getLatestNews(30);
        foreach ($all as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
        }
        return null;
    }

    private function fetchFeed(array $feedInfo): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $feedInfo['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Benchero-Sports-Platform/1.0'
        ]);

        $xmlString = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($xmlString === false || $httpCode !== 200 || empty($xmlString)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);
        if ($xml === false) {
            libxml_clear_errors();
            return [];
        }

        $items = [];
        $channelItems = $xml->channel->item ?? $xml->item ?? [];

        foreach ($channelItems as $item) {
            $title = trim(strip_tags((string)$item->title));
            $link = trim((string)$item->link);
            $description = trim(strip_tags((string)$item->description));
            $pubDateRaw = (string)($item->pubDate ?? $item->children('dc', true)->date ?? 'now');
            $pubDate = date('Y-m-d H:i:s', strtotime($pubDateRaw));

            if (empty($title) || empty($link)) {
                continue;
            }

            // Clean title and generate slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
            if (strlen($slug) > 180) {
                $slug = substr($slug, 0, 180);
            }

            // Limit summary length to 280 characters to comply with short summary policy
            $summary = mb_substr($description, 0, 280);
            if (mb_strlen($description) > 280) {
                $summary .= '...';
            }

            $items[] = [
                'id' => 'rss_' . md5($link),
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'slug' => $slug,
                'summary' => htmlspecialchars($summary, ENT_QUOTES, 'UTF-8'),
                'content' => htmlspecialchars($summary, ENT_QUOTES, 'UTF-8'),
                'category' => $feedInfo['category'],
                'sport' => $feedInfo['sport'],
                'source' => $feedInfo['source'],
                'source_url' => filter_var($link, FILTER_VALIDATE_URL) ? $link : null,
                'image_url' => null, // Safe policy: No unauthorized image rehosting
                'published_at' => $pubDate,
                'provider' => 'rss',
                'external_id' => md5($link)
            ];
        }

        return $items;
    }
}
