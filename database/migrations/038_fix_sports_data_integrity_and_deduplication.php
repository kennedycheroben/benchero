<?php

use Benchero\Core\Ulid;

/**
 * Migration 038: Fix Sports Data Integrity, Stale Matches, and Deduplication Index
 *
 * 1. Resolves competition slug collision: creates dedicated competitions for Jamaica
 *    and Bhutan Premier Leagues and reassigns contaminated matches from English Premier League.
 * 2. Cleans up stale matches stuck in LIVE status older than 150 minutes to terminal FINISHED status.
 * 3. Adds index (home_team_id, away_team_id, start_time) to sports_matches for cross-provider deduplication.
 */
return new class {
    public function up(PDO $pdo): void
    {
        echo "Running Migration 038...\n";

        // 1. Resolve sport_id for football
        $sportId = $pdo->query("SELECT id FROM sports WHERE slug = 'football' LIMIT 1")->fetchColumn();
        if (!$sportId) {
            $sportId = $pdo->query("SELECT id FROM sports LIMIT 1")->fetchColumn();
        }

        // 2. Ensure Jamaica Premier League competition exists
        $stmtCheckJam = $pdo->prepare("SELECT id FROM sports_competitions WHERE slug = 'jamaica-premier-league'");
        $stmtCheckJam->execute();
        $jamCompId = $stmtCheckJam->fetchColumn();

        if (!$jamCompId) {
            $jamCompId = Ulid::generate();
            $stmtInsertJam = $pdo->prepare("
                INSERT INTO sports_competitions (id, sport_id, name, slug, country, is_featured, provider)
                VALUES (?, ?, 'Jamaica Premier League', 'jamaica-premier-league', 'Jamaica', 0, 'api-football')
            ");
            $stmtInsertJam->execute([$jamCompId, $sportId]);
            echo "  [OK] Created Jamaica Premier League competition ({$jamCompId})\n";
        }

        // 3. Ensure Bhutan Premier League competition exists
        $stmtCheckBhu = $pdo->prepare("SELECT id FROM sports_competitions WHERE slug = 'bhutan-premier-league'");
        $stmtCheckBhu->execute();
        $bhuCompId = $stmtCheckBhu->fetchColumn();

        if (!$bhuCompId) {
            $bhuCompId = Ulid::generate();
            $stmtInsertBhu = $pdo->prepare("
                INSERT INTO sports_competitions (id, sport_id, name, slug, country, is_featured, provider)
                VALUES (?, ?, 'Bhutan Premier League', 'bhutan-premier-league', 'Bhutan', 0, 'api-football')
            ");
            $stmtInsertBhu->execute([$bhuCompId, $sportId]);
            echo "  [OK] Created Bhutan Premier League competition ({$bhuCompId})\n";
        }

        // 4. Reassign contaminated matches from English Premier League (slug: 'premier-league')
        // Match 1: Arnett Gardens vs Dunbeholden (external_id: 1639892) -> Jamaica
        $stmtReassignJam = $pdo->prepare("
            UPDATE sports_matches
            SET competition_id = ?, updated_at = NOW()
            WHERE external_id = '1639892' AND provider = 'api-football'
        ");
        $stmtReassignJam->execute([$jamCompId]);

        // Match 2: RTC vs Tsirang (external_id: 1636771) -> Bhutan
        $stmtReassignBhu1 = $pdo->prepare("
            UPDATE sports_matches
            SET competition_id = ?, updated_at = NOW()
            WHERE external_id = '1636771' AND provider = 'api-football'
        ");
        $stmtReassignBhu1->execute([$bhuCompId]);

        // Match 3: Thimphu City vs Transport United (external_id: 1636772) -> Bhutan & transition from stale LIVE to FINISHED
        $stmtReassignBhu2 = $pdo->prepare("
            UPDATE sports_matches
            SET competition_id = ?, status = 'FINISHED', updated_at = NOW()
            WHERE external_id = '1636772' AND provider = 'api-football'
        ");
        $stmtReassignBhu2->execute([$bhuCompId]);

        echo "  [OK] Reassigned contaminated matches to Jamaica and Bhutan Premier Leagues\n";

        // 5. Update team countries for reassigned matches
        $pdo->exec("UPDATE sports_teams SET country = 'Jamaica' WHERE slug IN ('arnett-gardens', 'dunbeholden')");
        $pdo->exec("UPDATE sports_teams SET country = 'Bhutan' WHERE slug IN ('rtc', 'tsirang', 'thimphu-city', 'transport-united')");

        // 6. Transition all remaining stale LIVE matches older than 150 minutes to terminal FINISHED status
        $stmtCleanLive = $pdo->prepare("
            UPDATE sports_matches
            SET status = 'FINISHED', updated_at = NOW()
            WHERE status IN ('LIVE', 'IN_PLAY', 'HT', 'PAUSED')
              AND start_time < DATE_SUB(NOW(), INTERVAL 150 MINUTE)
        ");
        $stmtCleanLive->execute();
        $cleanedCount = $stmtCleanLive->rowCount();
        echo "  [OK] Transitioned {$cleanedCount} stale LIVE matches older than 150m to FINISHED\n";

        // 7. Add index for fast cross-provider match deduplication
        try {
            $pdo->exec("ALTER TABLE sports_matches ADD INDEX idx_smatch_cross_dedup (home_team_id, away_team_id, start_time)");
            echo "  [OK] Added idx_smatch_cross_dedup index on sports_matches\n";
        } catch (\PDOException $e) {
            // Index might already exist
            echo "  [INFO] Index idx_smatch_cross_dedup already exists or skipped: " . $e->getMessage() . "\n";
        }
    }
};
