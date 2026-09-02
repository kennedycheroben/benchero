<?php

namespace Teamora\Repositories;

use Teamora\Core\Database\Database;
use Teamora\Core\Ulid;

class RosterRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByTeamAndSeason(string $teamId, string $seasonId, string $orgId, string $sportId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, p.first_name, p.last_name, p.display_name
            FROM roster_assignments r
            JOIN players p ON r.player_id = p.id
            WHERE r.team_id = :team_id
            AND r.season_id = :season_id
            AND r.organization_id = :org_id 
            AND r.sport_id = :sport_id 
            AND r.deleted_at IS NULL 
            ORDER BY r.jersey_number ASC, p.first_name ASC
        ");
        $stmt->execute([
            'team_id' => $teamId, 
            'season_id' => $seasonId, 
            'org_id' => $orgId, 
            'sport_id' => $sportId
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findAssignment(string $playerId, string $teamId, string $seasonId, string $orgId, string $sportId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM roster_assignments 
            WHERE player_id = :player_id
            AND team_id = :team_id
            AND season_id = :season_id
            AND organization_id = :org_id 
            AND sport_id = :sport_id 
            AND deleted_at IS NULL
        ");
        $stmt->execute([
            'player_id' => $playerId,
            'team_id' => $teamId,
            'season_id' => $seasonId,
            'org_id' => $orgId, 
            'sport_id' => $sportId
        ]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    public function findAssignmentsByPlayer(string $playerId, string $orgId, string $sportId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, t.name as team_name, s.name as season_name
            FROM roster_assignments r
            JOIN teams t ON r.team_id = t.id
            JOIN seasons s ON r.season_id = s.id
            WHERE r.player_id = :player_id
            AND r.organization_id = :org_id
            AND r.sport_id = :sport_id
            ORDER BY s.starts_on DESC, r.created_at DESC
        ");
        $stmt->execute([
            'player_id' => $playerId,
            'org_id' => $orgId,
            'sport_id' => $sportId
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO roster_assignments (id, organization_id, sport_id, player_id, team_id, season_id, jersey_number, position, status, joined_at) 
            VALUES (:id, :org_id, :sport_id, :player_id, :team_id, :season_id, :jersey_number, :position, :status, :joined_at)
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $data['organization_id'],
            'sport_id' => $data['sport_id'],
            'player_id' => $data['player_id'],
            'team_id' => $data['team_id'],
            'season_id' => $data['season_id'],
            'jersey_number' => $data['jersey_number'] ?? null,
            'position' => $data['position'] ?? null,
            'status' => $data['status'] ?? 'active',
            'joined_at' => $data['joined_at'] ?? null
        ]);
        return $id;
    }

    public function update(string $id, array $data, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE roster_assignments 
            SET jersey_number = :jersey_number, position = :position, status = :status 
            WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
        ");
        return $stmt->execute([
            'jersey_number' => $data['jersey_number'] ?? null,
            'position' => $data['position'] ?? null,
            'status' => $data['status'] ?? 'active',
            'id' => $id,
            'org_id' => $orgId,
            'sport_id' => $sportId
        ]);
    }

    public function archive(string $id, string $orgId, string $sportId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE roster_assignments 
            SET deleted_at = NOW(), status = 'archived' 
            WHERE id = :id AND organization_id = :org_id AND sport_id = :sport_id
        ");
        return $stmt->execute(['id' => $id, 'org_id' => $orgId, 'sport_id' => $sportId]);
    }
}
