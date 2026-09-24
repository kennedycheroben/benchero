<?php

namespace Benchero\Services\Sports\Providers;

use Benchero\Contracts\SportsProviderInterface;

class FootballDataSportsProvider implements SportsProviderInterface
{
    private string $apiKey;
    private string $baseUrl = 'https://api.football-data.org/v4/';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? (string)env('FOOTBALL_DATA_API_KEY', '');
    }

    private function makeRequest(string $endpoint): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException("Football-Data.org API key missing. Configure FOOTBALL_DATA_API_KEY in .env.", 401);
        }

        $url = $this->baseUrl . ltrim($endpoint, '/');
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-Auth-Token: ' . $this->apiKey,
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
            throw new \RuntimeException("Football-Data.org connection timeout or network failure: {$curlError}", 504);
        }

        if ($httpCode === 401 || $httpCode === 403) {
            throw new \RuntimeException("Football-Data.org authentication failed (HTTP {$httpCode}). Verify FOOTBALL_DATA_API_KEY.", $httpCode);
        }

        if ($httpCode === 429) {
            // Short backoff and single retry for momentary rate limits
            sleep(2);
            $chRetry = curl_init();
            curl_setopt_array($chRetry, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'X-Auth-Token: ' . $this->apiKey,
                    'Accept: application/json'
                ],
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT => 'Benchero-Sports-Platform/1.0'
            ]);
            $response = curl_exec($chRetry);
            $httpCode = curl_getinfo($chRetry, CURLINFO_HTTP_CODE);
            curl_close($chRetry);

            if ($httpCode === 429) {
                throw new \RuntimeException("Football-Data.org rate limit exceeded (HTTP 429).", 429);
            }
        }

        if ($httpCode >= 500) {
            throw new \RuntimeException("Football-Data.org service error (HTTP {$httpCode}).", 500);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \RuntimeException("Football-Data.org returned malformed JSON response.", 502);
        }

        return $data;
    }

    public function getLiveScores(): array
    {
        $data = $this->makeRequest('matches?status=IN_PLAY,PAUSED');
        $matches = $data['matches'] ?? [];
        return array_map([$this, 'normalizeMatch'], $matches);
    }

    public function getResults(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        if ($date) {
            $endpoint = "matches?status=FINISHED&dateFrom={$date}&dateTo={$date}";
        } else {
            // Rolling 10-day window into the past to capture recent finished results
            $dateFrom = date('Y-m-d', strtotime('-10 days'));
            $dateTo = date('Y-m-d');
            $endpoint = "matches?status=FINISHED&dateFrom={$dateFrom}&dateTo={$dateTo}";
        }

        // Genuine provider errors must bubble to SportsSyncService
        $data = $this->makeRequest($endpoint);
        $matches = $data['matches'] ?? [];

        $normalized = array_map([$this, 'normalizeMatch'], $matches);

        // Deduplicate by external/provider match ID
        $deduped = [];
        foreach ($normalized as $m) {
            $key = (string)($m['external_id'] ?? $m['id']);
            $deduped[$key] = $m;
        }

        $results = array_values($deduped);
        usort($results, function ($a, $b) {
            return strcmp($b['start_time'] ?? '', $a['start_time'] ?? '');
        });

        return array_slice($results, 0, $limit);
    }

    public function getFixtures(?string $sport = null, ?string $date = null, int $limit = 20): array
    {
        if ($date) {
            $endpoint = "matches?status=SCHEDULED,TIMED&dateFrom={$date}&dateTo={$date}";
            $data = $this->makeRequest($endpoint);
            $matches = $data['matches'] ?? [];
            $normalized = array_map([$this, 'normalizeMatch'], $matches);
            $deduped = [];
            foreach ($normalized as $m) {
                $key = (string)($m['external_id'] ?? $m['id']);
                $deduped[$key] = $m;
            }
            return array_slice(array_values($deduped), 0, $limit);
        }

        // Rolling 10-day window into the future (max allowed by football-data /v4/matches)
        $dateFrom = date('Y-m-d');
        $dateTo = date('Y-m-d', strtotime('+10 days'));
        $endpoint = "matches?status=SCHEDULED,TIMED&dateFrom={$dateFrom}&dateTo={$dateTo}";

        // Genuine API errors bubble up
        $data = $this->makeRequest($endpoint);
        $allMatches = $data['matches'] ?? [];

        // If the rolling window yielded 0 fixtures (e.g. between gameweeks or during international breaks),
        // fallback to querying featured tier-one competitions directly for their next scheduled fixtures.
        if (empty($allMatches)) {
            $featuredCodes = ['PL', 'PD', 'BL1', 'SA', 'FL1'];
            foreach ($featuredCodes as $code) {
                if (count($allMatches) >= $limit) {
                    break;
                }
                try {
                    $compData = $this->makeRequest("competitions/{$code}/matches?status=SCHEDULED");
                    $compMatches = $compData['matches'] ?? [];
                    if (!empty($compMatches)) {
                        $batch = array_slice($compMatches, 0, 10);
                        $allMatches = array_merge($allMatches, $batch);
                    }
                } catch (\Throwable $e) {
                    error_log("FootballDataSportsProvider getFixtures fallback error for {$code}: " . $e->getMessage());
                }
            }
        }

        $normalized = array_map([$this, 'normalizeMatch'], $allMatches);

        // Deduplicate by external/provider match ID
        $deduped = [];
        foreach ($normalized as $m) {
            $key = (string)($m['external_id'] ?? $m['id']);
            $deduped[$key] = $m;
        }

        $fixtures = array_values($deduped);
        usort($fixtures, function ($a, $b) {
            return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
        });

        return array_slice($fixtures, 0, $limit);
    }

    public function getCompetitions(?string $sport = null): array
    {
        $data = $this->makeRequest('competitions?plan=TIER_ONE');
        $competitions = $data['competitions'] ?? [];
        $result = [];

        foreach ($competitions as $c) {
            $result[] = [
                'id' => 'fd_comp_' . ($c['id'] ?? ''),
                'sport' => 'football',
                'name' => $c['name'] ?? 'Competition',
                'slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $c['name'] ?? 'comp'), '-')),
                'country' => $c['area']['name'] ?? 'International',
                'logo' => $c['emblem'] ?? null,
                'is_featured' => true,
                'external_id' => (string)($c['id'] ?? ''),
                'provider' => 'football-data'
            ];
        }

        return $result;
    }

    public const COMPETITION_CODE_MAP = [
        'premier-league' => 'PL',
        'champions-league' => 'CL',
        'uefa-champions-league' => 'CL',
        'la-liga' => 'PD',
        'primera-division' => 'PD',
        'serie-a' => 'SA',
        'bundesliga' => 'BL1',
        'ligue-1' => 'FL1',
        'championship' => 'ELC',
        'eredivisie' => 'DED',
        'primeira-liga' => 'PPL',
        'copa-libertadores' => 'CLI',
        'brasileirao' => 'BSA',
        'campeonato-brasileiro-s-rie-a' => 'BSA',
        'european-championship' => 'EC',
        'world-cup' => 'WC'
    ];

    public function getStandings(string $competitionSlug): array
    {
        if (!isset(self::COMPETITION_CODE_MAP[$competitionSlug])) {
            error_log("FootballDataSportsProvider: Competition slug '{$competitionSlug}' is not supported by football-data.org. Skipping standings request.");
            return [];
        }

        $code = self::COMPETITION_CODE_MAP[$competitionSlug];

        // Bubble exceptions if API fails
        $data = $this->makeRequest("competitions/{$code}/standings");

        $tables = $data['standings'][0]['table'] ?? [];
        $result = [];

        foreach ($tables as $row) {
            $result[] = [
                'position' => (int)($row['position'] ?? 0),
                'team' => $row['team']['name'] ?? 'Team',
                'team_slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $row['team']['name'] ?? 'team'), '-')),
                'team_id' => (string)($row['team']['id'] ?? ''),
                'team_logo' => $row['team']['crest'] ?? null,
                'played' => (int)($row['playedGames'] ?? 0),
                'won' => (int)($row['won'] ?? 0),
                'drawn' => (int)($row['draw'] ?? 0),
                'lost' => (int)($row['lost'] ?? 0),
                'points' => (int)($row['points'] ?? 0),
                'gf' => (int)($row['goalsFor'] ?? 0),
                'ga' => (int)($row['goalsAgainst'] ?? 0),
                'gd' => (int)($row['goalDifference'] ?? 0)
            ];
        }

        return $result;
    }


    public function getMatchDetail(string $matchId): ?array
    {
        $data = $this->makeRequest("matches/{$matchId}");
        return isset($data['id']) ? $this->normalizeMatch($data) : null;
    }

    public function normalizeMatch(array $m): array
    {
        $rawStatus = strtoupper($m['status'] ?? 'SCHEDULED');
        $status = match ($rawStatus) {
            'IN_PLAY' => 'LIVE',
            'PAUSED' => 'HT',
            'FINISHED' => 'FT',
            'POSTPONED' => 'POSTPONED',
            'CANCELLED' => 'CANCELLED',
            'SUSPENDED' => 'SUSPENDED',
            default => 'NS'
        };

        $minute = match ($status) {
            'LIVE' => 'LIVE',
            'HT' => 'HT',
            'FT' => 'FT',
            default => date('H:i', strtotime($m['utcDate'] ?? 'now'))
        };

        return [
            'id' => 'fd_m_' . ($m['id'] ?? ''),
            'external_id' => (string)($m['id'] ?? ''),
            'provider' => 'football-data',
            'sport' => 'football',
            'competition' => $m['competition']['name'] ?? 'Football Competition',
            'competition_slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $m['competition']['name'] ?? 'comp'), '-')),
            'competition_country' => $m['competition']['area']['name'] ?? null,
            'home_team' => $m['homeTeam']['name'] ?? 'Home Team',
            'home_slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $m['homeTeam']['name'] ?? 'home'), '-')),
            'home_logo' => $m['homeTeam']['crest'] ?? null,
            'away_team' => $m['awayTeam']['name'] ?? 'Away Team',
            'away_slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $m['awayTeam']['name'] ?? 'away'), '-')),
            'away_logo' => $m['awayTeam']['crest'] ?? null,
            'home_score' => $m['score']['fullTime']['home'] ?? $m['score']['halfTime']['home'] ?? 0,
            'away_score' => $m['score']['fullTime']['away'] ?? $m['score']['halfTime']['away'] ?? 0,
            'status' => $status,
            'minute' => $minute,
            'start_time' => date('Y-m-d H:i:s', strtotime($m['utcDate'] ?? 'now')),
            'venue' => $m['venue'] ?? null,
            'round' => $m['stage'] ?? null
        ];
    }
}
