<?php

declare(strict_types=1);

namespace CentralDeAulas\Service;

use CentralDeAulas\Core\Env;
use CentralDeAulas\Database\PdoDatabase;
use InvalidArgumentException;
use RuntimeException;

final class AdminUserService
{
    private PdoDatabase $database;

    public function __construct(PdoDatabase $database)
    {
        $this->database = $database;
    }

    public function ensureBootstrapAdmin(): void
    {
        $count = $this->database->fetch('SELECT COUNT(*) AS total FROM admin_users');

        if ($count !== false && (int) $count['total'] > 0) {
            return;
        }

        $name = Env::get('ADMIN_BOOTSTRAP_NAME', 'Administrador');
        $email = Env::get('ADMIN_BOOTSTRAP_EMAIL', 'admin@centraldeaulas.local');
        $password = Env::get('ADMIN_BOOTSTRAP_PASSWORD', '');

        if ($password === '') {
            throw new RuntimeException('ADMIN_BOOTSTRAP_PASSWORD nao configurada.');
        }

        $this->create($name, $email, $password);
    }

    public function authenticate(string $email, string $password): array|false
    {
        $admin = $this->database->fetch(
            'SELECT * FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1',
            ['email' => strtolower(trim($email))]
        );

        if ($admin === false || !password_verify($password, $admin['password_hash'])) {
            return false;
        }

        $this->database->query(
            'UPDATE admin_users SET last_login_at = UTC_TIMESTAMP() WHERE id = :id',
            ['id' => (int) $admin['id']]
        );

        return $admin;
    }

    public function create(string $name, string $email, string $password): int
    {
        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '') {
            throw new InvalidArgumentException('Nome do administrador e obrigatorio.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail do administrador invalido.');
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException('A senha do administrador precisa ter pelo menos 8 caracteres.');
        }

        $this->database->query(
            <<<SQL
            INSERT INTO admin_users (name, email, password_hash)
            VALUES (:name, :email, :password_hash)
            SQL,
            [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]
        );

        return (int) $this->database->lastInsertId();
    }

    public function all(): array
    {
        return $this->database->fetchAll(
            <<<SQL
            SELECT id, name, email, last_login_at, created_at, updated_at, is_active
            FROM admin_users
            ORDER BY id DESC
            SQL
        );
    }
}
