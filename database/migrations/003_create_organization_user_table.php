<?php

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS organization_user (
                organization_id CHAR(26) NOT NULL,
                user_id CHAR(26) NOT NULL,
                role ENUM('owner', 'admin', 'member') NOT NULL DEFAULT 'member',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (organization_id, user_id),
                CONSTRAINT fk_ou_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
                CONSTRAINT fk_ou_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
