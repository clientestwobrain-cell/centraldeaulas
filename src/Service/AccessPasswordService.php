<?php

declare(strict_types=1);

namespace CentralDeAulas\Service;

use CentralDeAulas\Database\PdoDatabase;
use CentralDeAulas\Security\Crypto;
use CentralDeAulas\Support\PhoneNormalizer;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

final class AccessPasswordService
{
    private const PASSWORD_LENGTH = 8;
    private const VALIDITY_DAYS = 7;
    private const SESSION_COLUMNS = [
        'browser',
        'ip_address',
        'browser_version',
        'screen_resolution',
        'user_agent',
        'session_id',
        'latitude',
        'longitude',
        'geolocation_accuracy',
        'geolocation_source',
        'ip_country',
        'ip_country_code',
        'ip_region',
        'ip_city',
        'ip_postal_code',
        'ip_timezone',
        'ip_isp',
        'ip_organization',
        'ip_asn',
        'timezone',
        'timezone_offset',
        'language',
        'languages',
        'platform',
        'operating_system',
        'operating_system_version',
        'device_type',
        'device_memory',
        'hardware_concurrency',
        'color_depth',
        'pixel_ratio',
        'screen_width',
        'screen_height',
        'available_screen_width',
        'available_screen_height',
        'viewport_width',
        'viewport_height',
        'screen_orientation',
        'touch_support',
        'cookies_enabled',
        'do_not_track',
        'referrer',
        'current_url',
        'accept_language',
        'http_accept',
        'http_connection',
        'http_sec_ch_ua',
        'http_sec_ch_ua_mobile',
        'http_sec_ch_ua_platform',
    ];
    private const SESSION_MAX_LENGTHS = [
        'browser' => 120,
        'ip_address' => 45,
        'browser_version' => 60,
        'screen_resolution' => 30,
        'user_agent' => 512,
        'session_id' => 128,
        'geolocation_source' => 30,
        'ip_country' => 100,
        'ip_country_code' => 2,
        'ip_region' => 100,
        'ip_city' => 120,
        'ip_postal_code' => 30,
        'ip_timezone' => 80,
        'ip_isp' => 190,
        'ip_organization' => 190,
        'ip_asn' => 40,
        'timezone' => 80,
        'language' => 20,
        'languages' => 255,
        'platform' => 120,
        'operating_system' => 120,
        'operating_system_version' => 80,
        'device_type' => 60,
        'screen_orientation' => 40,
        'do_not_track' => 20,
        'referrer' => 512,
        'current_url' => 512,
        'accept_language' => 255,
        'http_accept' => 255,
        'http_connection' => 80,
        'http_sec_ch_ua' => 255,
        'http_sec_ch_ua_mobile' => 20,
        'http_sec_ch_ua_platform' => 80,
    ];

    private PdoDatabase $database;
    private Crypto $crypto;
    private PhoneNormalizer $phoneNormalizer;

    public function __construct(PdoDatabase $database, Crypto $crypto, PhoneNormalizer $phoneNormalizer)
    {
        $this->database = $database;
        $this->crypto = $crypto;
        $this->phoneNormalizer = $phoneNormalizer;
    }

    public function registerOrReuseAccessPassword(
        string $name,
        string $email,
        string $phone,
        array $sessionData = []
    ): array {
        $normalizedPhone = $this->phoneNormalizer->normalize($phone);

        try {
            $this->database->beginTransaction();
            $this->markExpiredPasswords();

            $user = $this->findUserByPhone($normalizedPhone);

            if ($user === false) {
                $user = $this->createUser($name, $email, $normalizedPhone);
            }

            $activePassword = $this->findActivePasswordByUserId((int) $user['id']);

            if ($activePassword !== false && $activePassword['generated_password_encrypted'] !== null) {
                $this->database->commit();

                return [
                    'user_id' => (int) $user['id'],
                    'access_password_id' => (int) $activePassword['id'],
                    'password' => $this->crypto->decrypt($activePassword['generated_password_encrypted']),
                    'expires_at' => $activePassword['expires_at'],
                    'reused' => true,
                ];
            }

            $password = $this->generateUniquePassword();
            $generatedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $expiresAt = $generatedAt->modify('+' . self::VALIDITY_DAYS . ' days');

            $this->insertAccessPassword(
                (int) $user['id'],
                $password,
                $generatedAt->format('Y-m-d H:i:s'),
                $expiresAt->format('Y-m-d H:i:s'),
                $sessionData
            );

            $accessPasswordId = (int) $this->database->lastInsertId();
            $this->database->commit();

            return [
                'user_id' => (int) $user['id'],
                'access_password_id' => $accessPasswordId,
                'password' => $password,
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'reused' => false,
            ];
        } catch (\Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    public function verifyPassword(string $phone, string $password): bool
    {
        $normalizedPhone = $this->phoneNormalizer->normalize($phone);
        $user = $this->findUserByPhone($normalizedPhone);

        if ($user === false) {
            return false;
        }

        $activePassword = $this->findActivePasswordByUserId((int) $user['id']);

        if ($activePassword === false) {
            return false;
        }

        return password_verify($password, $activePassword['generated_password_hash']);
    }

    private function findUserByPhone(string $phone): array|false
    {
        return $this->database->fetch(
            'SELECT * FROM users WHERE phone = :phone LIMIT 1',
            ['phone' => $phone]
        );
    }

    private function createUser(string $name, string $email, string $phone): array
    {
        $this->database->query(
            <<<SQL
            INSERT INTO users (name, email, phone)
            VALUES (:name, :email, :phone)
            SQL,
            [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ]
        );

        $user = $this->findUserByPhone($phone);

        if ($user === false) {
            throw new RuntimeException('Usuario nao encontrado apos cadastro.');
        }

        return $user;
    }

    private function findActivePasswordByUserId(int $userId): array|false
    {
        return $this->database->fetch(
            <<<SQL
            SELECT *
            FROM access_passwords
            WHERE user_id = :user_id
                AND is_active = 1
                AND is_expired = 0
                AND expires_at > UTC_TIMESTAMP()
            ORDER BY generated_at ASC
            LIMIT 1
            SQL,
            ['user_id' => $userId]
        );
    }

    private function insertAccessPassword(
        int $userId,
        string $password,
        string $generatedAt,
        string $expiresAt,
        array $sessionData
    ): void {
        $payload = $this->normalizeSessionData($sessionData);
        $sessionColumns = implode(",\n                ", self::SESSION_COLUMNS);
        $sessionPlaceholders = ':' . implode(",\n                :", self::SESSION_COLUMNS);

        $this->database->query(
            <<<SQL
            INSERT INTO access_passwords (
                user_id,
                generated_password_hash,
                generated_password_encrypted,
                generated_password_fingerprint,
                generated_at,
                expires_at,
                is_expired,
                {$sessionColumns}
            ) VALUES (
                :user_id,
                :generated_password_hash,
                :generated_password_encrypted,
                :generated_password_fingerprint,
                :generated_at,
                :expires_at,
                0,
                {$sessionPlaceholders}
            )
            SQL,
            array_merge([
                'user_id' => $userId,
                'generated_password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'generated_password_encrypted' => $this->crypto->encrypt($password),
                'generated_password_fingerprint' => $this->crypto->fingerprint($password),
                'generated_at' => $generatedAt,
                'expires_at' => $expiresAt,
            ], $payload)
        );
    }

    private function generateUniquePassword(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $password = str_pad((string) random_int(0, 99999999), self::PASSWORD_LENGTH, '0', STR_PAD_LEFT);

            if (!$this->passwordFingerprintExists($this->crypto->fingerprint($password))) {
                return $password;
            }
        }

        throw new RuntimeException('Nao foi possivel gerar uma senha unica.');
    }

    private function passwordFingerprintExists(string $fingerprint): bool
    {
        return $this->database->fetch(
            'SELECT id FROM access_passwords WHERE generated_password_fingerprint = :fingerprint LIMIT 1',
            ['fingerprint' => $fingerprint]
        ) !== false;
    }

    private function markExpiredPasswords(): void
    {
        $this->database->query(
            <<<SQL
            UPDATE access_passwords
            SET is_expired = 1
            WHERE expires_at <= UTC_TIMESTAMP()
                AND is_expired = 0
            SQL
        );
    }

    private function normalizeSessionData(array $sessionData): array
    {
        $payload = [];

        foreach (self::SESSION_COLUMNS as $column) {
            $value = $sessionData[$column] ?? null;

            if (is_bool($value)) {
                $payload[$column] = $value ? 1 : 0;
                continue;
            }

            if (is_array($value)) {
                $payload[$column] = implode(',', array_map('strval', $value));
                continue;
            }

            $payload[$column] = $this->normalizeSessionValue($column, $value);
        }

        return $payload;
    }

    private function normalizeSessionValue(string $column, mixed $value): mixed
    {
        if ($value === null || is_int($value) || is_float($value)) {
            return $value;
        }

        $normalizedValue = (string) $value;
        $maxLength = self::SESSION_MAX_LENGTHS[$column] ?? null;

        if ($maxLength !== null && strlen($normalizedValue) > $maxLength) {
            return substr($normalizedValue, 0, $maxLength);
        }

        return $normalizedValue;
    }
}
