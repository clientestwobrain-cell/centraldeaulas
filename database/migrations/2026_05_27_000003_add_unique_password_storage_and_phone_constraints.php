<?php

declare(strict_types=1);

use CentralDeAulas\Database\Migration;
use CentralDeAulas\Database\PdoDatabase;

return new class implements Migration {
    public function name(): string
    {
        return '2026_05_27_000003_add_unique_password_storage_and_phone_constraints';
    }

    public function up(PdoDatabase $database): void
    {
        $this->addColumnIfMissing(
            $database,
            'access_passwords',
            'generated_password_encrypted',
            'TEXT NULL AFTER generated_password_hash'
        );
        $this->addColumnIfMissing(
            $database,
            'access_passwords',
            'generated_password_fingerprint',
            'CHAR(64) NULL AFTER generated_password_encrypted'
        );
        $this->addIndexIfMissing(
            $database,
            'access_passwords',
            'uk_access_passwords_generated_password_fingerprint',
            'UNIQUE KEY uk_access_passwords_generated_password_fingerprint (generated_password_fingerprint)'
        );
        $this->addIndexIfMissing(
            $database,
            'users',
            'uk_users_phone',
            'UNIQUE KEY uk_users_phone (phone)'
        );
    }

    private function addColumnIfMissing(PdoDatabase $database, string $table, string $column, string $definition): void
    {
        $existingColumn = $database->fetch(
            <<<SQL
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :table_name
                AND COLUMN_NAME = :column_name
            SQL,
            [
                'table_name' => $table,
                'column_name' => $column,
            ]
        );

        if ($existingColumn !== false) {
            return;
        }

        $database->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private function addIndexIfMissing(PdoDatabase $database, string $table, string $index, string $definition): void
    {
        $existingIndex = $database->fetch(
            <<<SQL
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = :table_name
                AND INDEX_NAME = :index_name
            SQL,
            [
                'table_name' => $table,
                'index_name' => $index,
            ]
        );

        if ($existingIndex !== false) {
            return;
        }

        $database->query("ALTER TABLE {$table} ADD {$definition}");
    }
};
