<?php

declare(strict_types=1);

use CentralDeAulas\Database\Migration;
use CentralDeAulas\Database\PdoDatabase;

return new class implements Migration {
    public function name(): string
    {
        return '2026_05_27_000004_add_expiration_flag_to_access_passwords_table';
    }

    public function up(PdoDatabase $database): void
    {
        $existingColumn = $database->fetch(
            <<<SQL
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'access_passwords'
                AND COLUMN_NAME = 'is_expired'
            SQL
        );

        if ($existingColumn === false) {
            $database->query(
                'ALTER TABLE access_passwords ADD COLUMN is_expired TINYINT(1) NOT NULL DEFAULT 0 AFTER expires_at'
            );
        }

        $existingIndex = $database->fetch(
            <<<SQL
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'access_passwords'
                AND INDEX_NAME = 'idx_access_passwords_is_expired'
            SQL
        );

        if ($existingIndex === false) {
            $database->query('ALTER TABLE access_passwords ADD KEY idx_access_passwords_is_expired (is_expired)');
        }

        $database->query(
            <<<SQL
            UPDATE access_passwords
            SET is_expired = 1
            WHERE expires_at <= UTC_TIMESTAMP()
                AND is_expired = 0
            SQL
        );
    }
};
