<?php

declare(strict_types=1);

namespace CentralDeAulas\Database;

use CentralDeAulas\Config\DatabaseConfig;
use InvalidArgumentException;
use RuntimeException;

final class MigrationRunner
{
    private DatabaseConfig $config;
    private PdoDatabase $database;
    private string $migrationsPath;

    public function __construct(DatabaseConfig $config, PdoDatabase $database, string $migrationsPath)
    {
        $this->config = $config;
        $this->database = $database;
        $this->migrationsPath = rtrim($migrationsPath, DIRECTORY_SEPARATOR);
    }

    public function run(): void
    {
        $this->createDatabaseIfNeeded();
        $this->createMigrationsTable();

        $executedMigrations = $this->executedMigrations();
        $batch = $this->nextBatchNumber();

        foreach ($this->loadMigrations() as $migration) {
            if (in_array($migration->name(), $executedMigrations, true)) {
                continue;
            }

            $migration->up($this->database);
            $this->recordMigration($migration->name(), $batch);
        }
    }

    private function createDatabaseIfNeeded(): void
    {
        $databaseName = $this->quoteIdentifier($this->config->database());
        $charset = $this->sanitizeSqlName($this->config->charset(), 'charset');
        $collation = $this->sanitizeSqlName($this->config->collation(), 'collation');

        $this->database->query(
            "CREATE DATABASE IF NOT EXISTS {$databaseName} CHARACTER SET {$charset} COLLATE {$collation}",
            [],
            false
        );
        $this->database->disconnect();
    }

    private function createMigrationsTable(): void
    {
        $this->database->query(
            <<<SQL
            CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                migration VARCHAR(190) NOT NULL,
                batch INT UNSIGNED NOT NULL,
                executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_migrations_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    /**
     * @return array<int, Migration>
     */
    private function loadMigrations(): array
    {
        $files = glob($this->migrationsPath . '/*.php') ?: [];
        sort($files);

        $migrations = [];

        foreach ($files as $file) {
            $migration = require $file;

            if (!$migration instanceof Migration) {
                throw new RuntimeException("Migration invalida: {$file}");
            }

            $migrations[] = $migration;
        }

        return $migrations;
    }

    /**
     * @return array<int, string>
     */
    private function executedMigrations(): array
    {
        $rows = $this->database->fetchAll('SELECT migration FROM migrations ORDER BY id ASC');

        return array_map(static fn (array $row): string => $row['migration'], $rows);
    }

    private function nextBatchNumber(): int
    {
        $row = $this->database->fetch('SELECT MAX(batch) AS last_batch FROM migrations');

        if ($row === false || $row['last_batch'] === null) {
            return 1;
        }

        return ((int) $row['last_batch']) + 1;
    }

    private function recordMigration(string $migration, int $batch): void
    {
        $this->database->query(
            'INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)',
            [
                'migration' => $migration,
                'batch' => $batch,
            ]
        );
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new InvalidArgumentException('Nome de banco de dados invalido.');
        }

        return '`' . $identifier . '`';
    }

    private function sanitizeSqlName(string $value, string $label): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $value)) {
            throw new InvalidArgumentException("Valor de {$label} invalido.");
        }

        return $value;
    }
}
