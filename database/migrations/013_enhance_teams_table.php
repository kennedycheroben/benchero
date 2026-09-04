<?php

/**
 * Migration 013 — Enhance Teams Table
 *
 * Adds fields for full Teams module (Model A: persistent squads).
 */

return new class {
    public function up(\PDO $pdo): void
    {
        $cols = $pdo->query("SHOW COLUMNS FROM teams LIKE 'description'")->fetchAll();
        if (empty($cols)) {
            try {
                $pdo->exec("
                    ALTER TABLE teams
                    ADD COLUMN description TEXT NULL AFTER slug,
                    ADD COLUMN team_type VARCHAR(100) NULL AFTER description,
                    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER team_type,
                    ADD COLUMN display_order INT NOT NULL DEFAULT 0 AFTER is_active
                ");
            } catch (\Exception $e) {}
        }

        try {
            $pdo->exec("ALTER TABLE teams DROP INDEX uq_team_slug_per_org");
        } catch (\Exception $e) {}

        try {
            $pdo->exec("ALTER TABLE teams ADD CONSTRAINT uq_team_slug_per_org_sport UNIQUE (organization_id, sport_id, slug)");
        } catch (\Exception $e) {}
    }
};
