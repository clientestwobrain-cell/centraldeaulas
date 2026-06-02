<?php

declare(strict_types=1);

namespace CentralDeAulas\Database;

use CentralDeAulas\Config\DatabaseConfig;
use PDO;
use PDOStatement;

final class PdoDatabase
{
    private DatabaseConfig $config;
    private ?PDO $pdo = null;
    private ?PDOStatement $statement = null;
    private ?bool $connectedWithDatabase = null;

    public function __construct(DatabaseConfig $config)
    {
        $this->config = $config;
    }

    public function connect(bool $withDatabase = true): PDO
    {
        if ($this->pdo instanceof PDO && $this->connectedWithDatabase === $withDatabase) {
            return $this->pdo;
        }

        $this->pdo = new PDO(
            $withDatabase ? $this->config->dsn() : $this->config->serverDsn(),
            $this->config->username(),
            $this->config->password(),
            $this->config->options()
        );
        $this->connectedWithDatabase = $withDatabase;

        return $this->pdo;
    }

    public function disconnect(): void
    {
        $this->statement = null;
        $this->pdo = null;
        $this->connectedWithDatabase = null;
    }

    public function prepare(string $sql, bool $withDatabase = true): self
    {
        $this->statement = $this->connect($withDatabase)->prepare($sql);

        return $this;
    }

    public function bind(string|int $parameter, mixed $value, ?int $type = null): self
    {
        $this->requireStatement()->bindValue($parameter, $value, $type ?? $this->detectType($value));

        return $this;
    }

    public function bindMany(array $parameters): self
    {
        foreach ($parameters as $parameter => $value) {
            $placeholder = is_int($parameter) ? $parameter + 1 : $parameter;
            $this->bind($placeholder, $value);
        }

        return $this;
    }

    public function execute(array $parameters = []): bool
    {
        if ($parameters !== []) {
            $this->bindMany($parameters);
        }

        return $this->requireStatement()->execute();
    }

    public function query(string $sql, array $parameters = [], bool $withDatabase = true): self
    {
        $this->prepare($sql, $withDatabase);
        $this->execute($parameters);

        return $this;
    }

    public function fetch(string $sql, array $parameters = [], bool $withDatabase = true): array|false
    {
        return $this->query($sql, $parameters, $withDatabase)->requireStatement()->fetch();
    }

    public function fetchAll(string $sql, array $parameters = [], bool $withDatabase = true): array
    {
        return $this->query($sql, $parameters, $withDatabase)->requireStatement()->fetchAll();
    }

    public function rowCount(): int
    {
        return $this->requireStatement()->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->connect()->lastInsertId();
    }

    public function beginTransaction(): bool
    {
        return $this->connect()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connect()->commit();
    }

    public function rollBack(): bool
    {
        return $this->connect()->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->connect()->inTransaction();
    }

    private function requireStatement(): PDOStatement
    {
        if (!$this->statement instanceof PDOStatement) {
            throw new \RuntimeException('Nenhuma query foi preparada para execucao.');
        }

        return $this->statement;
    }

    private function detectType(mixed $value): int
    {
        return match (true) {
            is_int($value) => PDO::PARAM_INT,
            is_bool($value) => PDO::PARAM_BOOL,
            $value === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }
}
