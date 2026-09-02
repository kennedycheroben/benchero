<?php

/**
 * Migration 008 — Auth Tokens Table
 *
 * Creates the auth_tokens table to handle generalized authentication tokens 
 * (e.g., email verification, password resets).
 * 
 * Requirements:
 * - Secure hash storage (token_hash) instead of plain tokens
 * - Expiration (expires_at)
 * - Single-use (used_at)
 */
return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS auth_tokens (
                id CHAR(26) PRIMARY KEY,
                user_id CHAR(26) NOT NULL,
                type ENUM('email_verification', 'password_reset') NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                used_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_auth_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY uq_auth_token_hash (token_hash)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
};
