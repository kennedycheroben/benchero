<?php

namespace Benchero\Services\Sports\Providers;

use Benchero\Contracts\NewsProviderInterface;

class MockNewsProvider implements NewsProviderInterface
{
    private array $newsArticles;

    public function __construct()
    {
        $this->seedMockNews();
    }

    private function seedMockNews(): void
    {
        $now = date('Y-m-d H:i:s');
        $twoHoursAgo = date('Y-m-d H:i:s', strtotime('-2 hours'));
        $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));
        $twoDaysAgo = date('Y-m-d H:i:s', strtotime('-2 days'));

        $this->newsArticles = [
            [
                'id' => 'news_1',
                'title' => 'Gor Mahia Extend KPL Lead After Thrilling Derby Victory',
                'slug' => 'gor-mahia-extend-kpl-lead-derby-victory',
                'summary' => 'Gor Mahia secured a crucial 1-0 win against rivals AFC Leopards at Nyayo Stadium to maintain their top position in the FKF Premier League standings.',
                'content' => 'Gor Mahia produced a disciplined performance to claim bragging rights in the Mashemeji Derby. A second-half goal proved to be the decisive moment in a high-tempo encounter attended by thousands of roaring fans.',
                'category' => 'Football',
                'sport' => 'football',
                'source' => 'Benchero Sports Wire',
                'source_url' => 'https://www.goal.com',
                'image_url' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=800&auto=format&fit=crop&q=80',
                'published_at' => $twoHoursAgo
            ],
            [
                'id' => 'news_2',
                'title' => 'Premier League Title Race Heats Up Ahead of Crucial Matchday',
                'slug' => 'premier-league-title-race-heats-up',
                'summary' => 'Arsenal and Liverpool remain locked in a tight battle for the top spot as Manchester City closely pursue with games in hand.',
                'content' => 'With only ten rounds of fixtures remaining, the 2025/26 English Premier League title race is shaping up to be one of the closest battles in recent football history.',
                'category' => 'Football',
                'sport' => 'football',
                'source' => 'Sky Sports News',
                'source_url' => 'https://www.skysports.com',
                'image_url' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=800&auto=format&fit=crop&q=80',
                'published_at' => $now
            ],
            [
                'id' => 'news_3',
                'title' => 'Kenya Harlequins Overpower KCB in Kenya Cup Rugby Showdown',
                'slug' => 'kenya-harlequins-overpower-kcb-kenya-cup',
                'summary' => 'Kenya Harlequins delivered a tactical masterpiece at the RFUEA Grounds to defeat defending champions KCB Rugby 24-18.',
                'content' => 'Hard-hitting tackles and dominant scrummaging propelled Quins to an impressive victory over rival side KCB, solidifying their playoff ambitions.',
                'category' => 'Rugby',
                'sport' => 'rugby',
                'source' => 'Standard Sports Kenya',
                'source_url' => 'https://www.standardmedia.co.ke',
                'image_url' => 'https://images.unsplash.com/photo-1519766304817-4f37bda74a29?w=800&auto=format&fit=crop&q=80',
                'published_at' => $yesterday
            ],
            [
                'id' => 'news_4',
                'title' => 'Kenya Basketball Federation Launches Grassroots 3x3 Championship',
                'slug' => 'kbf-launches-grassroots-3x3-championship',
                'summary' => 'The KBF has unveiled a nationwide 3x3 basketball circuit aimed at discovering young talent in schools and community academies.',
                'content' => 'Starting next month, community teams across Nairobi, Mombasa, Kisumu, and Nakuru will compete in a fast-paced tournament format designed to boost youth participation.',
                'category' => 'Basketball',
                'sport' => 'basketball',
                'source' => 'Nation Africa Sports',
                'source_url' => 'https://nation.africa',
                'image_url' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800&auto=format&fit=crop&q=80',
                'published_at' => $twoDaysAgo
            ]
        ];
    }

    public function getLatestNews(int $limit = 10, ?string $sport = null): array
    {
        $news = $this->newsArticles;
        if ($sport) {
            $news = array_filter($news, fn($n) => strtolower($n['sport']) === strtolower($sport));
        }
        usort($news, fn($a, $b) => strcmp($b['published_at'], $a['published_at']));
        return array_slice(array_values($news), 0, $limit);
    }

    public function getNewsBySlug(string $slug): ?array
    {
        foreach ($this->newsArticles as $article) {
            if ($article['slug'] === $slug) {
                return $article;
            }
        }
        return null;
    }
}
