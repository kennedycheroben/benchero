<?php

/**
 * Migration 007 — Phase 3 Integrity Fixes
 *
 * Applies targeted schema corrections to the existing Phase 3 database foundation.
 * This migration must be idempotent: re-running after a partial failure must not
 * produce duplicate columns, duplicate constraints, or data loss.
 *
 * Changes in this migration:
 *
 * 1. payments.fk_payment_org  — ON DELETE CASCADE → ON DELETE RESTRICT
 *    Financial records must survive organization deletion. An organization should
 *    never be hard-deleted if billing history exists; a soft-delete (deleted_at) is
 *    the correct removal path. CASCADE would silently destroy payment audit trails.
 *    RESTRICT is preferable to SET NULL because a payment without an organization
 *    reference has no meaningful owner for financial reconciliation.
 *
 * 2. subscriptions.fk_sub_org — ON DELETE CASCADE → ON DELETE RESTRICT
 *    Subscription records encode the commercial terms purchased by an organization.
 *    Cascade-deleting them loses billing interval, plan, and period data. RESTRICT
 *    forces the application to explicitly cancel/archive subscriptions before
 *    deactivating an organization, preserving the audit trail.
 *
 * 3. payments.mpesa_receipt_number — add UNIQUE constraint (partial: non-null rows only)
 *    mpesa_receipt_number is nullable (VARCHAR(100) DEFAULT NULL). A payment may be
 *    created in 'pending' status before the M-Pesa callback is received, so NULL is
 *    a valid initial state. MariaDB/MySQL UNIQUE constraints permit multiple NULL
 *    values (each NULL is treated as distinct), so a standard UNIQUE index is correct
 *    here: it enforces uniqueness among non-null receipt numbers while allowing
 *    multiple pending payments without a receipt yet.
 *
 * 4. users — add authentication-supporting columns
 *    email_verified_at  TIMESTAMP NULL — records when the user verified their email.
 *    remember_token     CHAR(64)  NULL — stores a hashed persistent-login token.
 *    last_login_at      TIMESTAMP NULL — records the last successful authentication.
 *    None of these columns hold secrets (remember_token stores a hash, not plaintext).
 *
 * 5. teams — add slug column with per-organization uniqueness
 *    slug VARCHAR(100) NOT NULL DEFAULT '' allows a team to have a URL-safe identifier.
 *    The unique constraint is scoped to (organization_id, slug) — two organizations
 *    may have a team named "senior-men" without conflict.
 *    The default '' is intentional for ALTER TABLE compatibility; application code
 *    must populate the slug from the team name before saving.
 *
 * 6. subscriptions — add billing_interval column
 *    Plans will eventually be offered monthly or annually. Capturing billing_interval
 *    on the subscription row preserves the commercial terms as they existed at
 *    subscription time, even if the plan changes later.
 *    This belongs in migration 007 (not a future billing phase) because:
 *      a) The column is structurally required before any subscription can be written.
 *      b) Retrofitting billing_interval after subscription rows exist requires a
 *         data migration, which is riskier than adding the column now while the
 *         table is empty.
 *    Enum values: 'monthly' | 'annual'. Default: 'monthly' (most common trial path).
 *
 * What this migration does NOT do:
 *   - It does not add seasons/competitions/fixtures/results — Phase 5 entities.
 *   - It does not implement team_player/team_staff pivots — Phase 5 entities.
 *   - It does not change the plans table structure beyond what is needed here.
 *   - It does not delete or alter any existing data rows.
 *   - It does not add APP_KEY handling — APP_KEY is not used by any current PHP code.
 */
return new class {
    public function up(\PDO $pdo): void
    {
        // -----------------------------------------------------------------------
        // 1. Fix payments.fk_payment_org: CASCADE → RESTRICT
        //    Drop the existing FK and re-add it with RESTRICT.
        //    IF NOT EXISTS is not available for CONSTRAINT in MariaDB 10.4, so we
        //    use a conditional check via information_schema.
        // -----------------------------------------------------------------------
        $hasFkPaymentOrg = $this->constraintExists($pdo, 'teamora_dev', 'payments', 'fk_payment_org');
        if ($hasFkPaymentOrg) {
            $pdo->exec('ALTER TABLE payments DROP FOREIGN KEY fk_payment_org');
        }

        // Re-add with RESTRICT (always, to ensure correct state even if it was absent)
        $this->addFkIfAbsent(
            $pdo,
            'teamora_dev',
            'payments',
            'fk_payment_org',
            'ALTER TABLE payments ADD CONSTRAINT fk_payment_org
             FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT'
        );

        // -----------------------------------------------------------------------
        // 2. Fix subscriptions.fk_sub_org: CASCADE → RESTRICT
        // -----------------------------------------------------------------------
        $hasFkSubOrg = $this->constraintExists($pdo, 'teamora_dev', 'subscriptions', 'fk_sub_org');
        if ($hasFkSubOrg) {
            $pdo->exec('ALTER TABLE subscriptions DROP FOREIGN KEY fk_sub_org');
        }

        $this->addFkIfAbsent(
            $pdo,
            'teamora_dev',
            'subscriptions',
            'fk_sub_org',
            'ALTER TABLE subscriptions ADD CONSTRAINT fk_sub_org
             FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT'
        );

        // -----------------------------------------------------------------------
        // 3. payments.mpesa_receipt_number — add UNIQUE constraint (non-null rows)
        //    mpesa_receipt_number is nullable (DEFAULT NULL). MariaDB UNIQUE indexes
        //    correctly permit multiple NULL values while enforcing uniqueness on
        //    non-null values. No special partial-index syntax is needed.
        // -----------------------------------------------------------------------
        if (!$this->indexExists($pdo, 'teamora_dev', 'payments', 'uq_mpesa_receipt')) {
            $pdo->exec(
                'ALTER TABLE payments ADD UNIQUE KEY uq_mpesa_receipt (mpesa_receipt_number)'
            );
        }

        // -----------------------------------------------------------------------
        // 4. users — add authentication columns (conditional per column)
        // -----------------------------------------------------------------------
        if (!$this->columnExists($pdo, 'teamora_dev', 'users', 'email_verified_at')) {
            // Place after email column for logical grouping
            $pdo->exec(
                "ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL DEFAULT NULL
                 AFTER email"
            );
        }

        if (!$this->columnExists($pdo, 'teamora_dev', 'users', 'remember_token')) {
            // CHAR(64): stores a SHA-256 hash of the raw token (not the token itself)
            $pdo->exec(
                "ALTER TABLE users ADD COLUMN remember_token CHAR(64) NULL DEFAULT NULL
                 AFTER password_hash"
            );
        }

        if (!$this->columnExists($pdo, 'teamora_dev', 'users', 'last_login_at')) {
            $pdo->exec(
                "ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL
                 AFTER remember_token"
            );
        }

        // -----------------------------------------------------------------------
        // 5. teams — add slug + per-organization unique constraint
        // -----------------------------------------------------------------------
        if (!$this->columnExists($pdo, 'teamora_dev', 'teams', 'slug')) {
            // DEFAULT '' allows the ALTER to run on existing rows without error.
            // Application layer must always set a non-empty slug before INSERT/UPDATE.
            $pdo->exec(
                "ALTER TABLE teams ADD COLUMN slug VARCHAR(100) NOT NULL DEFAULT ''
                 AFTER name"
            );
        }

        if (!$this->indexExists($pdo, 'teamora_dev', 'teams', 'uq_team_slug_per_org')) {
            $pdo->exec(
                'ALTER TABLE teams ADD UNIQUE KEY uq_team_slug_per_org (organization_id, slug)'
            );
        }

        // -----------------------------------------------------------------------
        // 6. subscriptions — add billing_interval
        //    Captures monthly/annual terms at subscription time so the record
        //    remains accurate even if the referenced plan's terms change later.
        // -----------------------------------------------------------------------
        if (!$this->columnExists($pdo, 'teamora_dev', 'subscriptions', 'billing_interval')) {
            $pdo->exec(
                "ALTER TABLE subscriptions ADD COLUMN
                 billing_interval ENUM('monthly', 'annual') NOT NULL DEFAULT 'monthly'
                 AFTER plan_id"
            );
        }
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    /**
     * Check whether a named FOREIGN KEY constraint exists on a table.
     */
    private function constraintExists(\PDO $pdo, string $schema, string $table, string $constraint): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ?
               AND TABLE_NAME       = ?
               AND CONSTRAINT_NAME  = ?
               AND CONSTRAINT_TYPE  = ?'
        );
        $stmt->execute([$schema, $table, $constraint, 'FOREIGN KEY']);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Add a FOREIGN KEY constraint only if it does not already exist.
     */
    private function addFkIfAbsent(\PDO $pdo, string $schema, string $table, string $name, string $sql): void
    {
        if (!$this->constraintExists($pdo, $schema, $table, $name)) {
            $pdo->exec($sql);
        }
    }

    /**
     * Check whether a named index (or unique key) exists on a table.
     */
    private function indexExists(\PDO $pdo, string $schema, string $table, string $indexName): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME   = ?
               AND INDEX_NAME   = ?'
        );
        $stmt->execute([$schema, $table, $indexName]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Check whether a column exists on a table.
     */
    private function columnExists(\PDO $pdo, string $schema, string $table, string $column): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME   = ?
               AND COLUMN_NAME  = ?'
        );
        $stmt->execute([$schema, $table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }
};
