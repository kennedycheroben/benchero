<?php

/**
 * Migration 007 — Phase 3 Integrity Fixes
 */
return new class {
    public function up(\PDO $pdo): void
    {
        $dbName = env('DB_DATABASE', 'benchero_dev');

        $hasFkPaymentOrg = $this->constraintExists($pdo, $dbName, 'payments', 'fk_payment_org');
        if ($hasFkPaymentOrg) {
            $pdo->exec('ALTER TABLE payments DROP FOREIGN KEY fk_payment_org');
        }

        $this->addFkIfAbsent(
            $pdo,
            $dbName,
            'payments',
            'fk_payment_org',
            'ALTER TABLE payments ADD CONSTRAINT fk_payment_org
             FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT'
        );

        $hasFkSubOrg = $this->constraintExists($pdo, $dbName, 'subscriptions', 'fk_sub_org');
        if ($hasFkSubOrg) {
            $pdo->exec('ALTER TABLE subscriptions DROP FOREIGN KEY fk_sub_org');
        }

        $this->addFkIfAbsent(
            $pdo,
            $dbName,
            'subscriptions',
            'fk_sub_org',
            'ALTER TABLE subscriptions ADD CONSTRAINT fk_sub_org
             FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT'
        );

        if (!$this->indexExists($pdo, $dbName, 'payments', 'uq_mpesa_receipt')) {
            $pdo->exec(
                'ALTER TABLE payments ADD UNIQUE KEY uq_mpesa_receipt (mpesa_receipt_number)'
            );
        }

        if (!$this->columnExists($pdo, $dbName, 'users', 'email_verified_at')) {
            $pdo->exec(
                "ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL DEFAULT NULL
                 AFTER email"
            );
        }

        if (!$this->columnExists($pdo, $dbName, 'users', 'remember_token')) {
            $pdo->exec(
                "ALTER TABLE users ADD COLUMN remember_token CHAR(64) NULL DEFAULT NULL
                 AFTER password_hash"
            );
        }

        if (!$this->columnExists($pdo, $dbName, 'users', 'last_login_at')) {
            $pdo->exec(
                "ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL
                 AFTER remember_token"
            );
        }

        if (!$this->columnExists($pdo, $dbName, 'teams', 'slug')) {
            $pdo->exec(
                "ALTER TABLE teams ADD COLUMN slug VARCHAR(100) NOT NULL DEFAULT ''
                 AFTER name"
            );
        }

        if (!$this->indexExists($pdo, $dbName, 'teams', 'uq_team_slug_per_org')) {
            $pdo->exec(
                'ALTER TABLE teams ADD UNIQUE KEY uq_team_slug_per_org (organization_id, slug)'
            );
        }

        if (!$this->columnExists($pdo, $dbName, 'subscriptions', 'billing_interval')) {
            $pdo->exec(
                "ALTER TABLE subscriptions ADD COLUMN
                 billing_interval ENUM('monthly', 'annual') NOT NULL DEFAULT 'monthly'
                 AFTER plan_id"
            );
        }
    }

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

    private function addFkIfAbsent(\PDO $pdo, string $schema, string $table, string $name, string $sql): void
    {
        if (!$this->constraintExists($pdo, $schema, $table, $name)) {
            $pdo->exec($sql);
        }
    }

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
