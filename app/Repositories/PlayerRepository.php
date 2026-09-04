<?php

namespace Benchero\Repositories;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;

class PlayerRepository
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findActiveByOrgAndSport(string $orgId, string $sportId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM players 
            WHERE organization_id = :org_id 
            AND sport_id = :sport_id
            AND deleted_at IS NULL 
            ORDER BY first_name ASC, last_name ASC
        ");
        $stmt->execute(['org_id' => $orgId, 'sport_id' => $sportId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findPublicSquadByOrg(string $orgId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   ra.jersey_number, ra.position, ra.is_captain, ra.is_vice_captain,
                   t.name as team_name, t.slug as team_slug,
                   s.name as sport_name, s.slug as sport_slug
            FROM players p
            LEFT JOIN roster_assignments ra ON ra.player_id = p.id AND ra.deleted_at IS NULL AND ra.status = 'active'
            LEFT JOIN teams t ON ra.team_id = t.id AND t.deleted_at IS NULL
            LEFT JOIN sports s ON p.sport_id = s.id
            WHERE p.organization_id = :org_id
              AND p.is_active = 1
              AND p.deleted_at IS NULL
            ORDER BY ra.position ASC, ra.jersey_number ASC, p.first_name ASC
        ");
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(string $id, string $orgId, string $sportId = ''): ?array
    {
        $sql = "SELECT * FROM players WHERE id = :id AND organization_id = :org_id AND deleted_at IS NULL";
        $params = ['id' => $id, 'org_id' => $orgId];
        if (!empty($sportId)) {
            $sql .= " AND sport_id = :sport_id";
            $params['sport_id'] = $sportId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): string
    {
        $id = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO players (id, organization_id, sport_id, first_name, last_name, display_name, photo_url, bio, is_active, date_of_birth, nationality, preferred_foot, emergency_contact) 
            VALUES (:id, :org_id, :sport_id, :first_name, :last_name, :display_name, :photo_url, :bio, :is_active, :date_of_birth, :nationality, :preferred_foot, :emergency_contact)
        ");
        $stmt->execute([
            'id' => $id,
            'org_id' => $data['organization_id'],
            'sport_id' => $data['sport_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['display_name'] ?? null,
            'photo_url' => $data['photo_url'] ?? null,
            'bio' => $data['bio'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'date_of_birth' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
            'nationality' => $data['nationality'] ?? null,
            'preferred_foot' => $data['preferred_foot'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null
        ]);
        return $id;
    }

    public function update(string $id, array $data, string $orgId, string $sportId = ''): bool
    {
        $stmt = $this->db->prepare("
            UPDATE players 
            SET first_name = :first_name, 
                last_name = :last_name, 
                display_name = :display_name, 
                photo_url = :photo_url,
                bio = :bio, 
                is_active = :is_active, 
                date_of_birth = :date_of_birth,
                nationality = :nationality,
                preferred_foot = :preferred_foot,
                emergency_contact = :emergency_contact
            WHERE id = :id AND organization_id = :org_id
        ");
        return $stmt->execute([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['display_name'] ?? null,
            'photo_url' => $data['photo_url'] ?? null,
            'bio' => $data['bio'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'date_of_birth' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
            'nationality' => $data['nationality'] ?? null,
            'preferred_foot' => $data['preferred_foot'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'id' => $id,
            'org_id' => $orgId
        ]);
    }

    public function archive(string $id, string $orgId, string $sportId = ''): bool
    {
        $stmt = $this->db->prepare("
            UPDATE players 
            SET deleted_at = NOW() 
            WHERE id = :id AND organization_id = :org_id
        ");
        return $stmt->execute(['id' => $id, 'org_id' => $orgId]);
    }
}
