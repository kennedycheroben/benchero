<?php

return function (PDO $pdo) {
    // Helper function to safely add index if it doesn't already exist
    $addIndexIfNotExists = function (PDO $pdo, string $table, string $indexName, string $columns) {
        // Check if table exists
        $tableCheck = $pdo->prepare("
            SELECT COUNT(1) 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
              AND table_name = ?
        ");
        $tableCheck->execute([$table]);
        if ((int)$tableCheck->fetchColumn() === 0) {
            echo "Table {$table} does not exist, skipping index {$indexName}.\n";
            return;
        }

        $stmt = $pdo->prepare("
            SELECT COUNT(1) 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
              AND table_name = ? 
              AND index_name = ?
        ");
        $stmt->execute([$table, $indexName]);
        $exists = (int)$stmt->fetchColumn() > 0;

        if (!$exists) {
            $pdo->exec("CREATE INDEX `{$indexName}` ON `{$table}` ({$columns})");
            echo "Added index {$indexName} to {$table}.\n";
        } else {
            echo "Index {$indexName} on {$table} already exists, skipping.\n";
        }
    };

    // Performance Indexes for public & tenant query paths
    $addIndexIfNotExists($pdo, 'fixtures', 'idx_fixtures_org_status', 'organization_id, status, scheduled_at');
    $addIndexIfNotExists($pdo, 'fixtures', 'idx_fixtures_org_season', 'organization_id, season_id');
    $addIndexIfNotExists($pdo, 'roster_assignments', 'idx_roster_team_season', 'team_id, season_id');
    $addIndexIfNotExists($pdo, 'roster_assignments', 'idx_roster_org_player', 'organization_id, player_id');
    $addIndexIfNotExists($pdo, 'news_articles', 'idx_news_org_pub', 'organization_id, published_at');
    $addIndexIfNotExists($pdo, 'gallery_images', 'idx_gallery_org_cat', 'organization_id, category');
    $addIndexIfNotExists($pdo, 'players', 'idx_players_org_names', 'organization_id, first_name, last_name');
    $addIndexIfNotExists($pdo, 'teams', 'idx_teams_org_display', 'organization_id, display_order, name');
    $addIndexIfNotExists($pdo, 'staff', 'idx_staff_org_team', 'organization_id, team_id');
};
