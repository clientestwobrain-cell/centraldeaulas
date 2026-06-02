<?php

declare(strict_types=1);

use CentralDeAulas\Database\Migration;
use CentralDeAulas\Database\PdoDatabase;

return new class implements Migration {
    public function name(): string
    {
        return '2026_05_28_000005_create_admin_users_table';
    }

    public function up(PdoDatabase $database): void
    {
        $database->query(
            <<<SQL
            CREATE TABLE IF NOT EXISTS admin_users (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(190) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                last_login_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY uk_admin_users_email (email),
                KEY idx_admin_users_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }
};
