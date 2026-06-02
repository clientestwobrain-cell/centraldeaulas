<?php

declare(strict_types=1);

namespace CentralDeAulas\Config;

use CentralDeAulas\Core\Env;

final class DatabaseConfig
{
    private string $connection;
    private string $host;
    private int $port;
    private string $socket;
    private string $database;
    private string $username;
    private string $password;
    private string $charset;
    private string $collation;
    private bool $persistent;
    private int $timeout;

    public function __construct(
        string $connection,
        string $host,
        int $port,
        string $socket,
        string $database,
        string $username,
        string $password,
        string $charset,
        string $collation,
        bool $persistent,
        int $timeout
    ) {
        $this->connection = $connection;
        $this->host = $host;
        $this->port = $port;
        $this->socket = $socket;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->charset = $charset;
        $this->collation = $collation;
        $this->persistent = $persistent;
        $this->timeout = $timeout;
    }

    public static function fromEnvironment(): self
    {
        return new self(
            Env::get('DB_CONNECTION', 'mysql'),
            Env::get('DB_HOST', '127.0.0.1'),
            (int) Env::get('DB_PORT', '8889'),
            Env::get('DB_SOCKET', ''),
            Env::get('DB_DATABASE', 'centraldeaulas'),
            Env::get('DB_USERNAME', 'root'),
            Env::get('DB_PASSWORD', ''),
            Env::get('DB_CHARSET', 'utf8mb4'),
            Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            self::booleanFromEnvironment('DB_PERSISTENT', false),
            (int) Env::get('DB_TIMEOUT', '5')
        );
    }

    public function dsn(): string
    {
        $dsn = $this->serverDsn();

        if ($this->database !== '') {
            $dsn .= ';dbname=' . $this->database;
        }

        return $dsn;
    }

    public function serverDsn(): string
    {
        if ($this->socket !== '' && file_exists($this->socket)) {
            return sprintf(
                '%s:unix_socket=%s;charset=%s',
                $this->connection,
                $this->socket,
                $this->charset
            );
        }

        return sprintf(
            '%s:host=%s;port=%d;charset=%s',
            $this->connection,
            $this->host,
            $this->port,
            $this->charset
        );
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function database(): string
    {
        return $this->database;
    }

    public function charset(): string
    {
        return $this->charset;
    }

    public function collation(): string
    {
        return $this->collation;
    }

    public function options(): array
    {
        return [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_PERSISTENT => $this->persistent,
            \PDO::ATTR_TIMEOUT => $this->timeout,
        ];
    }

    private static function booleanFromEnvironment(string $key, bool $default): bool
    {
        $value = Env::get($key, $default ? 'true' : 'false');

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}
