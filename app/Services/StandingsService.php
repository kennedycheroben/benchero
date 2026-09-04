<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use PDO;

class StandingsService
{
    /**
     * Compute live league standings for an organization, sport, and optional season.
     */
    public function getStandings(string $organizationId, string $sportId, ?string $seasonId = null): array
    {
        $db = Database::getConnection();

        // 1. Get all active teams for this org & sport
        $teamsStmt = $db->prepare("
            SELECT id, name, slug, team_type
            FROM teams
            WHERE organization_id = :org_id AND sport_id = :sport_id AND deleted_at IS NULL
            ORDER BY display_order ASC, name ASC
        ");
        $teamsStmt->execute(['org_id' => $organizationId, 'sport_id' => $sportId]);
        $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($teams)) {
            return [];
        }

        // Initialize table map indexed by team_id
        $standings = [];
        foreach ($teams as $t) {
            $standings[$t['id']] = [
                'team_id' => $t['id'],
                'team_name' => $t['name'],
                'team_slug' => $t['slug'],
                'team_type' => $t['team_type'],
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0
            ];
        }

        // 2. Fetch completed league fixtures
        $sql = "
            SELECT home_team_id, away_team_id, home_score, away_score
            FROM fixtures
            WHERE organization_id = :org_id
              AND sport_id = :sport_id
              AND competition_type = 'league'
              AND status = 'completed'
              AND home_score IS NOT NULL
              AND away_score IS NOT NULL
              AND deleted_at IS NULL
        ";
        $params = ['org_id' => $organizationId, 'sport_id' => $sportId];

        if (!empty($seasonId)) {
            $sql .= " AND season_id = :season_id";
            $params['season_id'] = $seasonId;
        }

        $fixturesStmt = $db->prepare($sql);
        $fixturesStmt->execute($params);
        $fixtures = $fixturesStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fixtures as $f) {
            $homeId = $f['home_team_id'];
            $awayId = $f['away_team_id'];
            $homeScore = (int)$f['home_score'];
            $awayScore = (int)$f['away_score'];

            if (isset($standings[$homeId])) {
                $standings[$homeId]['played']++;
                $standings[$homeId]['goals_for'] += $homeScore;
                $standings[$homeId]['goals_against'] += $awayScore;
            }

            if (isset($standings[$awayId])) {
                $standings[$awayId]['played']++;
                $standings[$awayId]['goals_for'] += $awayScore;
                $standings[$awayId]['goals_against'] += $homeScore;
            }

            if ($homeScore > $awayScore) {
                if (isset($standings[$homeId])) {
                    $standings[$homeId]['won']++;
                    $standings[$homeId]['points'] += 3;
                }
                if (isset($standings[$awayId])) {
                    $standings[$awayId]['lost']++;
                }
            } elseif ($awayScore > $homeScore) {
                if (isset($standings[$awayId])) {
                    $standings[$awayId]['won']++;
                    $standings[$awayId]['points'] += 3;
                }
                if (isset($standings[$homeId])) {
                    $standings[$homeId]['lost']++;
                }
            } else {
                if (isset($standings[$homeId])) {
                    $standings[$homeId]['drawn']++;
                    $standings[$homeId]['points'] += 1;
                }
                if (isset($standings[$awayId])) {
                    $standings[$awayId]['drawn']++;
                    $standings[$awayId]['points'] += 1;
                }
            }
        }

        // Calculate Goal Difference
        foreach ($standings as $id => $row) {
            $standings[$id]['goal_difference'] = $row['goals_for'] - $row['goals_against'];
        }

        // Sort by Points DESC, Goal Difference DESC, Goals For DESC, Team Name ASC
        usort($standings, function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }
            if ($a['goal_difference'] !== $b['goal_difference']) {
                return $b['goal_difference'] <=> $a['goal_difference'];
            }
            if ($a['goals_for'] !== $b['goals_for']) {
                return $b['goals_for'] <=> $a['goals_for'];
            }
            return strcmp($a['team_name'], $b['team_name']);
        });

        // Add rank / position
        $pos = 1;
        foreach ($standings as $key => $row) {
            $standings[$key]['position'] = $pos++;
        }

        return $standings;
    }
}
