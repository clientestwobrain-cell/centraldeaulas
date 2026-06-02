<?php

declare(strict_types=1);

namespace CentralDeAulas\Service;

use CentralDeAulas\Database\PdoDatabase;
use CentralDeAulas\Support\PhoneNormalizer;
use InvalidArgumentException;

final class UserApiService
{
    private PdoDatabase $database;
    private PhoneNormalizer $phoneNormalizer;

    public function __construct(PdoDatabase $database, PhoneNormalizer $phoneNormalizer)
    {
        $this->database = $database;
        $this->phoneNormalizer = $phoneNormalizer;
    }

    public function list(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $total = $this->database->fetch('SELECT COUNT(*) AS total FROM users');

        return [
            'items' => $this->database->fetchAll(
                <<<SQL
                SELECT id, name, email, phone, created_at, updated_at, deleted_at, is_active
                FROM users
                ORDER BY id DESC
                LIMIT {$perPage} OFFSET {$offset}
                SQL
            ),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total === false ? 0 : (int) $total['total'],
            ],
        ];
    }

    public function find(int $id): array|false
    {
        return $this->database->fetch(
            'SELECT id, name, email, phone, created_at, updated_at, deleted_at, is_active FROM users WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): array
    {
        $payload = $this->payload($data, true);

        $this->database->query(
            <<<SQL
            INSERT INTO users (name, email, phone, is_active)
            VALUES (:name, :email, :phone, :is_active)
            SQL,
            $payload
        );

        return $this->find((int) $this->database->lastInsertId());
    }

    public function update(int $id, array $data): array|false
    {
        if ($this->find($id) === false) {
            return false;
        }

        $payload = $this->payload($data, false);
        $payload['id'] = $id;

        $this->database->query(
            <<<SQL
            UPDATE users
            SET name = :name,
                email = :email,
                phone = :phone,
                is_active = :is_active
            WHERE id = :id
            SQL,
            $payload
        );

        return $this->find($id);
    }

    public function deactivate(int $id): array|false
    {
        if ($this->find($id) === false) {
            return false;
        }

        $this->database->query(
            <<<SQL
            UPDATE users
            SET is_active = 0,
                deleted_at = COALESCE(deleted_at, UTC_TIMESTAMP())
            WHERE id = :id
            SQL,
            ['id' => $id]
        );

        return $this->find($id);
    }

    private function payload(array $data, bool $creating): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($name === '' || $email === '' || $phone === '') {
            throw new InvalidArgumentException('Nome, e-mail e telefone sao obrigatorios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail invalido.');
        }

        return [
            'name' => $name,
            'email' => $email,
            'phone' => $this->phoneNormalizer->normalize($phone),
            'is_active' => isset($data['is_active']) ? (int) (bool) $data['is_active'] : ($creating ? 1 : 1),
        ];
    }
}
