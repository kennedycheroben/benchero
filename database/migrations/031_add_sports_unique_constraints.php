<?php

/**
 * Migration 031 — Add Sports Unique Constraints
 *
 * Adds UNIQUE INDEX on (provider, external_id) across sports_competitions, sports_teams,
 * sports_matches, and sports_news to enforce database-level idempotency for API synchronization.
 */

return new class {
    public function up(\PDO $pdo): void
    {
        $tables = [
            'sports_competitions' => 'idx_uniq_comp_prov_ext',
            'sports_teams' => 'idx_uniq_team_prov_ext',
            'sports_matches' => 'idx_uniq_match_prov_ext',
            'sports_news' => 'idx_uniq_news_prov_ext'
        ];

        foreach ($tables as $table => $indexName) {
            try {
                $pdo->exec("ALTER TABLE {$table} ADD UNIQUE KEY {$indexName} (provider, external_id)");
            } catch (\PDOException $e) {
                // Index might already exist or external_id null handling
            }
        }
    }
};
