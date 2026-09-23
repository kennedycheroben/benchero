<?php

namespace Benchero\Services\Sports\Providers;

use Benchero\Contracts\SportsProviderInterface;

class ApiFootballSportsProvider implements SportsProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://v3.football.api-sports.io/';

    private const COMPETITION_SLUG_MAP = [
        'fkf-premier-league' => 382,
        'kenya-premier-league' => 382,
        'kenya-super-league' => 691,
        'caf-champions-league' => 12,
        'caf-confederation-cup' => 20,
        'afcon' => 6,
        'africa-cup-of-nations' => 6,
        'chan' => 17,
        'premier-league' => 39,
        'championship' => 40,
        'la-liga' => 140,
        'serie-a' => 135,
        'bundesliga' => 78,
        'ligue-1' => 61,
        'eredivisie' => 88,
        'primeira-liga' => 94,
        'copa-libertadores' => 13,
        'brasileirao' => 71
    ];

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string)env('API_FOOTBALL_API_KEY', '');
    }

    private function makeRequest(string $endpoint, array $params = []): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("API-Football API key missing. Configure API_FOOTBALL_API_KEY in .env.", 401);
        }

        $url = $this->baseUrl . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'x-apisports-key: ' . $this->apiKey,
                'Accept: application/json'
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Benchero-Sports-Platform/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("API-Football connection failed: {$curlError}", 504);
        }

        if ($httpCode === 401 || $httpCode === 403) {
            throw new \RuntimeException("API-Football authentication failed (HTTP {$httpCode}). Verify API_FOOTBALL_API_KEY.", $httpCode);
        }

        if ($httpCode === 429) {
            throw new \RuntimeException("API-Football rate limit exceeded (HTTP 429).", 429);
        }

        if ($httpCode >= 500) {
            throw new \RuntimeException("API-Football server error (HTTP {$httpCode}).", 500);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \RuntimeException("API-Football returned malformed JSON response.", 502);
        }

        // Validate API-Football error metadata
        if (!empty($data['errors'])) {
            $errMessage = is_array($data['errors']) ? implode(', ', array_values($data['errors'])) : (string)$data['errors'];
            error_log("API-Football returned API error metadata: " . $errMessage);
        }

        return $data;
    }

    public function getLiveScores(): array
    {
        try {
            $data = $this->makeRequest('fixtures', ['live' => 'all']);
            $matches = $data['response'] ?? [];
            return array_map([$this, 'normalizeMatch'], $matches);
        } catch (\Throwable $e) {
            error_log("ApiFootballSportsProvider getLiveScores error: " . $e->getMessage());
            return [];
        }
    }

    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        $dates = $date ? [$date] : [date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))];
        $allMatches = [];

        try {
            foreach ($dates as $d) {
                if (count($allMatches) >= $limit) {
                    break;
                }
                $data = $this->makeRequest('fixtures', ['date' => $d, 'status' => 'FT-AET-PEN']);
                $matches = $data['response'] ?? [];
                $allMatches = array_merge($allMatches, $matches);
            }
            $normalized = array_map([$this, 'normalizeMatch'], $allMatches);
            usort($normalized, function ($a, $b) {
                return strcmp($b['start_time'] ?? '', $a['start_time'] ?? '');
            });
            return array_slice($normalized, 0, $limit);
        } catch (\Throwable $e) {
            error_log("ApiFootballSportsProvider getResults error: " . $e->getMessage());
            return [];
        }
    }

    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        $dates = $date ? [$date] : [
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 day')),
            date('Y-m-d', strtotime('+2 days'))
        ];
        $allMatches = [];

        try {
            foreach ($dates as $d) {
                if (count($allMatches) >= $limit) {
                    break;
                }
                $data = $this->makeRequest('fixtures', ['date' => $d, 'status' => 'NS']);
                $matches = $data['response'] ?? [];
                $allMatches = array_merge($allMatches, $matches);
            }
            $normalized = array_map([$this, 'normalizeMatch'], $allMatches);
            usort($normalized, function ($a, $b) {
                return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
            });
            return array_slice($normalized, 0, $limit);
        } catch (\Throwable $e) {
            error_log("ApiFootballSportsProvider getFixtures error: " . $e->getMessage());
            return [];
        }
    }

    public function getCompetitions(?string $sport = null): array
    {
        $featuredSlugs = [
            'fkf-premier-league' => ['name' => 'FKF Premier League', 'country' => 'Kenya', 'id' => 382],
            'caf-champions-league' => ['name' => 'CAF Champions League', 'country' => 'Africa', 'id' => 12],
            'caf-confederation-cup' => ['name' => 'CAF Confederation Cup', 'country' => 'Africa', 'id' => 20],
            'premier-league' => ['name' => 'Premier League', 'country' => 'England', 'id' => 39],
            'la-liga' => ['name' => 'La Liga', 'country' => 'Spain', 'id' => 140],
            'serie-a' => ['name' => 'Serie A', 'country' => 'Italy', 'id' => 135],
            'bundesliga' => ['name' => 'Bundesliga', 'country' => 'Germany', 'id' => 78]
        ];

        $result = [];
        foreach ($featuredSlugs as $slug => $info) {
            $result[] = [
                'id' => 'af_comp_' . $info['id'],
                'sport' => 'football',
                'name' => $info['name'],
                'slug' => $slug,
                'country' => $info['country'],
                'logo' => null,
                'is_featured' => true,
                'external_id' => (string)$info['id'],
                'provider' => 'api-football'
            ];
        }

        return $result;
    }

    public function getStandings(string $competitionSlug): array
    {
        if (!isset(self::COMPETITION_SLUG_MAP[$competitionSlug])) {
            error_log("ApiFootballSportsProvider: Competition slug '{$competitionSlug}' not mapped.");
            return [];
        }

        $leagueId = self::COMPETITION_SLUG_MAP[$competitionSlug];
        $currentYear = (int)date('Y');
        $season = ((int)date('m') >= 7) ? $currentYear : ($currentYear - 1);

        try {
            $data = $this->makeRequest('standings', ['league' => $leagueId, 'season' => $season]);
            if (empty($data['response']) && $season !== $currentYear) {
                // Fallback attempt with current year if split-year season yielded empty response
                $data = $this->makeRequest('standings', ['league' => $leagueId, 'season' => $currentYear]);
            }
        } catch (\Throwable $e) {
            error_log("ApiFootballSportsProvider getStandings error for '{$competitionSlug}': " . $e->getMessage());
            return [];
        }

        $standingsGroup = $data['response'][0]['league']['standings'][0] ?? [];
        $result = [];

        foreach ($standingsGroup as $row) {
            $result[] = [
                'position' => (int)($row['rank'] ?? 0),
                'team' => $row['team']['name'] ?? 'Team',
                'team_slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $row['team']['name'] ?? 'team'), '-')),
                'team_id' => (string)($row['team']['id'] ?? ''),
                'team_logo' => $row['team']['logo'] ?? null,
                'played' => (int)($row['all']['played'] ?? 0),
                'won' => (int)($row['all']['win'] ?? 0),
                'drawn' => (int)($row['all']['draw'] ?? 0),
                'lost' => (int)($row['all']['lose'] ?? 0),
                'points' => (int)($row['points'] ?? 0),
                'gf' => (int)($row['all']['goals']['for'] ?? 0),
                'ga' => (int)($row['all']['goals']['against'] ?? 0),
                'gd' => (int)($row['goalsDiff'] ?? 0)
            ];
        }

        return $result;
    }

    public function getMatchDetail(string $matchId): ?array
    {
        try {
            $data = $this->makeRequest('fixtures', ['id' => $matchId]);
            $matches = $data['response'] ?? [];
            return isset($matches[0]) ? $this->normalizeMatch($matches[0]) : null;
        } catch (\Throwable $e) {
            error_log("ApiFootballSportsProvider getMatchDetail error: " . $e->getMessage());
            return null;
        }
    }

    public function normalizeMatch(array $m): array
    {
        $rawStatus = strtoupper($m['fixture']['status']['short'] ?? 'NS');
        $status = match ($rawStatus) {
            '1H', '2H', 'ET', 'BT', 'P', 'LIVE' => 'LIVE',
            'HT' => 'HT',
            'FT', 'AET', 'PEN' => 'FT',
            'PST', 'POSTPONED' => 'POSTPONED',
            'CANC', 'CANCELLED' => 'CANCELLED',
            'SUSP', 'INT', 'ABD' => 'SUSPENDED',
            default => 'NS'
        };

        $elapsed = $m['fixture']['status']['elapsed'] ?? null;
        $minute = match ($status) {
            'LIVE' => $elapsed ? "{$elapsed}'" : 'LIVE',
            'HT' => 'HT',
            'FT' => 'FT',
            default => date('H:i', strtotime($m['fixture']['date'] ?? 'now'))
        };

        $compName = $m['league']['name'] ?? 'Football Competition';

        return [
            'id' => 'af_m_' . ($m['fixture']['id'] ?? ''),
            'external_id' => (string)($m['fixture']['id'] ?? ''),
            'provider' => 'api-football',
            'sport' => 'football',
            'competition' => $compName,
            'competition_slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $compName), '-')),
            'competition_country' => $m['league']['country'] ?? null,
            'home_team' => $m['teams']['home']['name'] ?? 'Home Team',
            'home_slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $m['teams']['home']['name'] ?? 'home'), '-')),
            'home_logo' => $m['teams']['home']['logo'] ?? null,
            'away_team' => $m['teams']['away']['name'] ?? 'Away Team',
            'away_slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $m['teams']['away']['name'] ?? 'away'), '-')),
            'away_logo' => $m['teams']['away']['logo'] ?? null,
            'home_score' => (int)($m['goals']['home'] ?? 0),
            'away_score' => (int)($m['goals']['away'] ?? 0),
            'status' => $status,
            'minute' => $minute,
            'start_time' => date('Y-m-d H:i:s', strtotime($m['fixture']['date'] ?? 'now')),
            'venue' => $m['fixture']['venue']['name'] ?? null,
            'round' => $m['league']['round'] ?? null
        ];
    }
}
