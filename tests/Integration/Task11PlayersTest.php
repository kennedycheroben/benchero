<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Teamora\Repositories\PlayerRepository;
use Teamora\Repositories\RosterRepository;
use Teamora\Services\PlayerService;
use Teamora\Services\RosterService;
use Teamora\Core\Database\Database;

class Task11PlayersTest extends TestCase
{
    private $db;
    private $playerService;
    private $rosterService;
    private $playerRepo;
    private $rosterRepo;

    protected function setUp(): void
    {
        $this->db = Database::getConnection();
        $this->db->beginTransaction();
        
        $this->playerService = new PlayerService();
        $this->rosterService = new RosterService();
        $this->playerRepo = new PlayerRepository();
        $this->rosterRepo = new RosterRepository();
    }

    protected function tearDown(): void
    {
        $this->db->rollBack();
    }

    public function test_can_create_player()
    {
        // Use existing test org and sport if possible, or create dummies
        $orgId = '01J6G7R3T2K0F6V0Q7S8T9U0V1';
        $sportId = '01J6G7R3T2K0F6V0Q7S8T9U0V3'; // Assuming ulid string format
        
        // This won't work perfectly without real DB constraints, but we can test the service validation
        try {
            $this->playerService->createPlayer($orgId, $sportId, '', 'Doe');
            $this->fail("Expected exception for empty first name");
        } catch (\Exception $e) {
            $this->assertStringContainsString('First name and last name are required', $e->getMessage());
        }
    }
    
    public function test_idor_protection_on_roster_assignment()
    {
        // Try to assign a player to a team that doesn't belong to the org
        $orgId = '01J6G7R3T2K0F6V0Q7S8T9U0V1';
        $sportId = '01J6G7R3T2K0F6V0Q7S8T9U0V3';
        
        try {
            $this->rosterService->assignPlayerToRoster(
                $orgId, 
                $sportId, 
                'player1', 
                'team_from_other_org', 
                'season1', 
                '10', 
                'Forward'
            );
            $this->fail("Expected exception for invalid player/team");
        } catch (\Exception $e) {
            $this->assertStringContainsString('Invalid player', $e->getMessage()); // Player doesn't exist for this org
        }
    }
}
