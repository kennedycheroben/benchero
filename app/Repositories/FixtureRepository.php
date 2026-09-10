<?php

namespace Benchero\Repositories;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;

class FixtureRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findBySeason(string $seasonId, string $orgId, string $sportId): array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, 
                   ht.name as home_team_name, 
                   at.name as away_team_name,
                   s.name as season_name
            FROM fixtures f
            JOIN teams ht ON f.home_team_id = ht.id
            JOIN teams at ON f.away_team_id = at.id
            JOIN seasons s ON f.season_id = s.id
            WHERE f.season_id = :season_id
            AND f.organization_id = :org_id 
            AND f.sport_id = :sport_id
            AND f.deleted_at IS NULL
            ORDER BY f.scheduled_at ASC
        ");
        $stmt->execute([
            'season_id' => $seasonId, 
            'org_id' => $orgId, 
            'sport_id' => $sportId
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(string $id, string $orgId, string $sportId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, 
                   ht.name as home_team_name, 
                   at.name as away_team_name,
                   s.name as season_name
            FROM fixtures f
            JOIN teams ht ON f.home_team_id = ht.id
            JOIN teams at ON f.away_team_id = at.id
            JOIN seasons s ON f.season_id = s.id
            WHERE f.id = :id 
            AND f.organization_id = :org_id 
            AND f.sport_id = :sport_id
            AND f.deleted_at IS NULL
        ");
        $stmt->execute(['id' => $id, 'org_id' => $orgId, 'sport_id' => $sportId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO fixtures (id, organization_id, sport_id, season_id, home_team_id, away_team_id, scheduled_at, venue_name, competition_type, competition_name, status, notes) 
            VALUES (:id, :org_id, :sport_id, :season_id, :home_team_id, :away_team_id, :scheduled_at, :venue_name, :competition_type, :competition_name, :status, :notes)
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $data['organization_id'],
            'sport_id' => $data['sport_id'],
            'season_id' => $data['season_id'],
            'home_team_id' => $data['home_team_id'],
            'away_team_id' => $data['away_team_id'],
            'scheduled_at' => $data['scheduled_at'],
            'venue_name' => $data['venue_name'] ?? null,
            'competition_type' => $data['competition_type'] ?? 'league',
            'competition_name' => $data['competition_name'] ?? null,
            'status' => $data['status'] ?? 'scheduled',
            'notes' => $data['notes'] ?? null
        ]);
        return $id;
    }

    public function update(string $id, array $data, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE fixtures 
            SET home_team_id = :home_team_id, 
                away_team_id = :away_team_id, 
                scheduled_at = :scheduled_at, 
                venue_name = :venue_name, 
                competition_type = :competition_type, 
                competition_name = :competition_name, 
                notes = :notes 
            WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
        ");
        return $stmt->execute([
            'home_team_id' => $data['home_team_id'],
            'away_team_id' => $data['away_team_id'],
            'scheduled_at' => $data['scheduled_at'],
            'venue_name' => $data['venue_name'] ?? null,
            'competition_type' => $data['competition_type'] ?? 'league',
            'competition_name' => $data['competition_name'] ?? null,
            'notes' => $data['notes'] ?? null,
            'id' => $id,
            'org_id' => $orgId,
            'sport_id' => $sportId
        ]);
    }

    public function updateStatus(string $id, string $status, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE fixtures 
            SET status = :status 
            WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
        ");
        return $stmt->execute([
            'status' => $status,
            'id' => $id,
            'org_id' => $orgId,
            'sport_id' => $sportId
        ]);
    }

    public function delete(string $id, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE fixtures 
            SET deleted_at = NOW()
            WHERE id = :id 
            AND organization_id = :org_id 
            AND sport_id = :sport_id
            AND deleted_at IS NULL
        ");
        return $stmt->execute(['id' => $id, 'org_id' => $orgId, 'sport_id' => $sportId]);
    }

    public function updateResult(string $id, int $homeScore, int $awayScore, ?string $resultNotes, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE fixtures 
            SET home_score = :home_score,
                away_score = :away_score,
                result_notes = :result_notes,
                status = 'completed',
                completed_at = NOW()
            WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
        ");
        return $stmt->execute([
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'result_notes' => $resultNotes,
            'id' => $id,
            'org_id' => $orgId,
            'sport_id' => $sportId
        ]);
    }

    public function findPublicBySeason(string $seasonId, string $orgId, string $sportId): array
    {
        $stmt = $this->db->prepare("
            SELECT f.id, f.home_team_id, f.away_team_id, f.scheduled_at, f.venue_name, 
                   f.competition_type, f.competition_name, f.status, f.home_score, f.away_score,
                   ht.name as home_team_name, 
                   at.name as away_team_name,
                   s.name as season_name
            FROM fixtures f
            JOIN teams ht ON f.home_team_id = ht.id
            JOIN teams at ON f.away_team_id = at.id
            JOIN seasons s ON f.season_id = s.id
            WHERE f.season_id = :season_id
            AND f.organization_id = :org_id 
            AND f.sport_id = :sport_id
            AND f.deleted_at IS NULL
            ORDER BY f.scheduled_at ASC
        ");
        $stmt->execute([
            'season_id' => $seasonId, 
            'org_id' => $orgId, 
            'sport_id' => $sportId
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
