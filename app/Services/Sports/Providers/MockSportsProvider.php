<?php

namespace Benchero\Services\Sports\Providers;

use Benchero\Contracts\SportsProviderInterface;

class MockSportsProvider implements SportsProviderInterface
{
    private array $competitions;
    private array $teams;
    private array $matches;
    private array $standings;

    public function __construct()
    {
        $this->seedMockData();
    }

    private function seedMockData(): void
    {
        $this->competitions = [
            [
                'id' => 'comp_epl',
                'sport' => 'football',
                'name' => 'English Premier League',
                'slug' => 'premier-league',
                'country' => 'England',
                'logo' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=100&auto=format&fit=crop&q=80',
                'is_featured' => true
            ],
            [
                'id' => 'comp_ucl',
                'sport' => 'football',
                'name' => 'UEFA Champions League',
                'slug' => 'champions-league',
                'country' => 'Europe',
                'logo' => 'https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=100&auto=format&fit=crop&q=80',
                'is_featured' => true
            ],
            [
                'id' => 'comp_fkf',
                'sport' => 'football',
                'name' => 'FKF Kenyan Premier League',
                'slug' => 'kenyan-premier-league',
                'country' => 'Kenya',
                'logo' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=100&auto=format&fit=crop&q=80',
                'is_featured' => true
            ],
            [
                'id' => 'comp_laliga',
                'sport' => 'football',
                'name' => 'La Liga',
                'slug' => 'la-liga',
                'country' => 'Spain',
                'logo' => 'https://images.unsplash.com/photo-1518091043644-c1d4457512c6?w=100&auto=format&fit=crop&q=80',
                'is_featured' => false
            ],
            [
                'id' => 'comp_nba',
                'sport' => 'basketball',
                'name' => 'NBA',
                'slug' => 'nba',
                'country' => 'USA',
                'logo' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=100&auto=format&fit=crop&q=80',
                'is_featured' => true
            ],
            [
                'id' => 'comp_rugby',
                'sport' => 'rugby',
                'name' => 'Kenya Cup Rugby',
                'slug' => 'kenya-cup-rugby',
                'country' => 'Kenya',
                'logo' => 'https://images.unsplash.com/photo-1519766304817-4f37bda74a29?w=100&auto=format&fit=crop&q=80',
                'is_featured' => true
            ]
        ];

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $this->matches = [
            // Live Football
            [
                'id' => 'match_live_1',
                'sport' => 'football',
                'competition' => 'English Premier League',
                'competition_slug' => 'premier-league',
                'home_team' => 'Arsenal',
                'home_slug' => 'arsenal',
                'home_logo' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=80&auto=format&fit=crop&q=80',
                'away_team' => 'Chelsea',
                'away_slug' => 'chelsea',
                'away_logo' => 'https://images.unsplash.com/photo-1518091043644-c1d4457512c6?w=80&auto=format&fit=crop&q=80',
                'home_score' => 2,
                'away_score' => 1,
                'status' => 'LIVE',
                'minute' => "78'",
                'start_time' => "{$today} 19:30:00",
                'venue' => 'Emirates Stadium, London',
                'round' => 'Matchday 28'
            ],
            [
                'id' => 'match_live_2',
                'sport' => 'football',
                'competition' => 'FKF Kenyan Premier League',
                'competition_slug' => 'kenyan-premier-league',
                'home_team' => 'Gor Mahia',
                'home_slug' => 'gor-mahia',
                'home_logo' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=80&auto=format&fit=crop&q=80',
                'away_team' => 'AFC Leopards',
                'away_slug' => 'afc-leopards',
                'away_logo' => 'https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=80&auto=format&fit=crop&q=80',
                'home_score' => 1,
                'away_score' => 0,
                'status' => 'LIVE',
                'minute' => "62'",
                'start_time' => "{$today} 16:00:00",
                'venue' => 'Nayo National Stadium, Nairobi',
                'round' => 'Round 18'
            ],
            [
                'id' => 'match_live_3',
                'sport' => 'football',
                'competition' => 'La Liga',
                'competition_slug' => 'la-liga',
                'home_team' => 'Real Madrid',
                'home_slug' => 'real-madrid',
                'home_logo' => 'https://images.unsplash.com/photo-1518091043644-c1d4457512c6?w=80&auto=format&fit=crop&q=80',
                'away_team' => 'Barcelona',
                'away_slug' => 'barcelona',
                'away_logo' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=80&auto=format&fit=crop&q=80',
                'home_score' => 0,
                'away_score' => 1,
                'status' => 'LIVE',
                'minute' => "34'",
                'start_time' => "{$today} 21:00:00",
                'venue' => 'Santiago Bernabéu, Madrid',
                'round' => 'El Clásico'
            ],

            // Finished Results
            [
                'id' => 'match_res_1',
                'sport' => 'football',
                'competition' => 'English Premier League',
                'competition_slug' => 'premier-league',
                'home_team' => 'Liverpool',
                'home_slug' => 'liverpool',
                'away_team' => 'Manchester City',
                'away_slug' => 'man-city',
                'home_score' => 3,
                'away_score' => 1,
                'status' => 'FT',
                'minute' => 'FT',
                'start_time' => "{$yesterday} 18:30:00",
                'venue' => 'Anfield, Liverpool',
                'round' => 'Matchday 27'
            ],
            [
                'id' => 'match_res_2',
                'sport' => 'football',
                'competition' => 'FKF Kenyan Premier League',
                'competition_slug' => 'kenyan-premier-league',
                'home_team' => 'Tusker FC',
                'home_slug' => 'tusker-fc',
                'away_team' => 'Shabana FC',
                'away_slug' => 'shabana-fc',
                'home_score' => 2,
                'away_score' => 0,
                'status' => 'FT',
                'minute' => 'FT',
                'start_time' => "{$yesterday} 15:00:00",
                'venue' => 'Ruaraka Grounds, Nairobi',
                'round' => 'Round 17'
            ],
            [
                'id' => 'match_res_3',
                'sport' => 'rugby',
                'competition' => 'Kenya Cup Rugby',
                'competition_slug' => 'kenya-cup-rugby',
                'home_team' => 'Kenya Harlequins',
                'home_slug' => 'kenya-harlequins',
                'away_team' => 'KCB Rugby',
                'away_slug' => 'kcb-rugby',
                'home_score' => 24,
                'away_score' => 18,
                'status' => 'FT',
                'minute' => 'FT',
                'start_time' => "{$yesterday} 16:00:00",
                'venue' => 'RFUEA Grounds, Nairobi',
                'round' => 'Round 6'
            ],

            // Upcoming Fixtures
            [
                'id' => 'match_fix_1',
                'sport' => 'football',
                'competition' => 'English Premier League',
                'competition_slug' => 'premier-league',
                'home_team' => 'Manchester City',
                'home_slug' => 'man-city',
                'away_team' => 'Arsenal',
                'away_slug' => 'arsenal',
                'home_score' => null,
                'away_score' => null,
                'status' => 'NS',
                'minute' => '20:00',
                'start_time' => "{$tomorrow} 20:00:00",
                'venue' => 'Etihad Stadium, Manchester',
                'round' => 'Matchday 29'
            ],
            [
                'id' => 'match_fix_2',
                'sport' => 'football',
                'competition' => 'UEFA Champions League',
                'competition_slug' => 'champions-league',
                'home_team' => 'Bayern Munich',
                'home_slug' => 'bayern-munich',
                'away_team' => 'Real Madrid',
                'away_slug' => 'real-madrid',
                'home_score' => null,
                'away_score' => null,
                'status' => 'NS',
                'minute' => '22:00',
                'start_time' => "{$tomorrow} 22:00:00",
                'venue' => 'Allianz Arena, Munich',
                'round' => 'Quarter-Final Leg 1'
            ],
            [
                'id' => 'match_fix_3',
                'sport' => 'basketball',
                'competition' => 'NBA',
                'competition_slug' => 'nba',
                'home_team' => 'LA Lakers',
                'home_slug' => 'la-lakers',
                'away_team' => 'Boston Celtics',
                'away_slug' => 'boston-celtics',
                'home_score' => null,
                'away_score' => null,
                'status' => 'NS',
                'minute' => '04:00',
                'start_time' => "{$tomorrow} 04:00:00",
                'venue' => 'Crypto.com Arena, Los Angeles',
                'round' => 'Regular Season'
            ]
        ];

        $this->standings = [
            'premier-league' => [
                ['position' => 1, 'team' => 'Arsenal', 'played' => 28, 'won' => 20, 'drawn' => 5, 'lost' => 3, 'points' => 65, 'gf' => 62, 'ga' => 24, 'gd' => 38],
                ['position' => 2, 'team' => 'Liverpool', 'played' => 28, 'won' => 19, 'drawn' => 7, 'lost' => 2, 'points' => 64, 'gf' => 65, 'ga' => 26, 'gd' => 39],
                ['position' => 3, 'team' => 'Manchester City', 'played' => 28, 'won' => 19, 'drawn' => 5, 'lost' => 4, 'points' => 62, 'gf' => 63, 'ga' => 28, 'gd' => 35],
                ['position' => 4, 'team' => 'Aston Villa', 'played' => 28, 'won' => 17, 'drawn' => 4, 'lost' => 7, 'points' => 55, 'gf' => 59, 'ga' => 41, 'gd' => 18],
                ['position' => 5, 'team' => 'Tottenham Hotspur', 'played' => 28, 'won' => 16, 'drawn' => 5, 'lost' => 7, 'points' => 53, 'gf' => 59, 'ga' => 42, 'gd' => 17],
                ['position' => 6, 'team' => 'Chelsea', 'played' => 28, 'won' => 13, 'drawn' => 6, 'lost' => 9, 'points' => 45, 'gf' => 47, 'ga' => 43, 'gd' => 4]
            ],
            'kenyan-premier-league' => [
                ['position' => 1, 'team' => 'Gor Mahia', 'played' => 18, 'won' => 12, 'drawn' => 5, 'lost' => 1, 'points' => 41, 'gf' => 28, 'ga' => 8, 'gd' => 20],
                ['position' => 2, 'team' => 'Tusker FC', 'played' => 18, 'won' => 11, 'drawn' => 4, 'lost' => 3, 'points' => 37, 'gf' => 27, 'ga' => 12, 'gd' => 15],
                ['position' => 3, 'team' => 'Bandari FC', 'played' => 18, 'won' => 10, 'drawn' => 5, 'lost' => 3, 'points' => 35, 'gf' => 22, 'ga' => 11, 'gd' => 11],
                ['position' => 4, 'team' => 'AFC Leopards', 'played' => 18, 'won' => 9, 'drawn' => 6, 'lost' => 3, 'points' => 33, 'gf' => 21, 'ga' => 12, 'gd' => 9],
                ['position' => 5, 'team' => 'Nairobi City Stars', 'played' => 18, 'won' => 8, 'drawn' => 5, 'lost' => 5, 'points' => 29, 'gf' => 23, 'ga' => 19, 'gd' => 4]
            ]
        ];
    }

    public function getLiveScores(): array
    {
        return array_values(array_filter($this->matches, fn($m) => $m['status'] === 'LIVE'));
    }

    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        $filtered = array_filter($this->matches, fn($m) => $m['status'] === 'FT');
        if ($sport) {
            $filtered = array_filter($filtered, fn($m) => strtolower($m['sport']) === strtolower($sport));
        }
        return array_slice(array_values($filtered), 0, $limit);
    }

    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        $filtered = array_filter($this->matches, fn($m) => $m['status'] === 'NS');
        if ($sport) {
            $filtered = array_filter($filtered, fn($m) => strtolower($m['sport']) === strtolower($sport));
        }
        return array_slice(array_values($filtered), 0, $limit);
    }

    public function getCompetitions(?string $sport = null): array
    {
        if ($sport) {
            return array_values(array_filter($this->competitions, fn($c) => strtolower($c['sport']) === strtolower($sport)));
        }
        return $this->competitions;
    }

    public function getStandings(string $competitionSlug): array
    {
        return $this->standings[$competitionSlug] ?? [];
    }

    public function getMatchDetail(string $matchId): ?array
    {
        foreach ($this->matches as $match) {
            if ($match['id'] === $matchId) {
                return array_merge($match, [
                    'stats' => [
                        'possession' => ['home' => 58, 'away' => 42],
                        'shots' => ['home' => 14, 'away' => 8],
                        'shots_on_target' => ['home' => 6, 'away' => 3],
                        'corners' => ['home' => 7, 'away' => 2],
                        'fouls' => ['home' => 9, 'away' => 12]
                    ]
                ]);
            }
        }
        return null;
    }
}
