<?php

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                organization_id CHAR(26) NULL DEFAULT NULL,
                user_id CHAR(26) NULL DEFAULT NULL,
                action VARCHAR(255) NOT NULL,
                entity_type VARCHAR(100) NOT NULL,
                entity_id CHAR(26) NOT NULL,
                metadata JSON NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_audit_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE SET NULL,
                CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
