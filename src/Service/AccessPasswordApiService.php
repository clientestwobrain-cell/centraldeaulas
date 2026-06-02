<?php

declare(strict_types=1);

namespace CentralDeAulas\Service;

use CentralDeAulas\Database\PdoDatabase;
use DateTimeImmutable;
use InvalidArgumentException;

final class AccessPasswordApiService
{
    private PdoDatabase $database;
    private AccessPasswordService $accessPasswordService;

    public function __construct(PdoDatabase $database, AccessPasswordService $accessPasswordService)
    {
        $this->database = $database;
        $this->accessPasswordService = $accessPasswordService;
    }

    public function list(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));
        $offset = ($page - 1) * $perPage;
        $total = $this->database->fetch('SELECT COUNT(*) AS total FROM access_passwords');

        return [
            'items' => $this->database->fetchAll(
                $this->selectSql("ORDER BY ap.id DESC\n                LIMIT {$perPage} OFFSET {$offset}")
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
            $this->selectSql("WHERE ap.id = :id\n            LIMIT 1"),
            ['id' => $id]
        );
    }

    public function create(array $data, array $clientContext): array
    {
        foreach (['name', 'email', 'phone'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                throw new InvalidArgumentException("Campo obrigatorio: {$field}.");
            }
        }

        return $this->accessPasswordService->registerOrReuseAccessPassword(
            (string) $data['name'],
            (string) $data['email'],
            (string) $data['phone'],
            $clientContext
        );
    }

    public function update(int $id, array $data): array|false
    {
        if ($this->find($id) === false) {
            return false;
        }

        $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : null;
        $isExpired = isset($data['is_expired']) ? (int) (bool) $data['is_expired'] : null;
        $expiresAt = isset($data['expires_at']) ? trim((string) $data['expires_at']) : null;

        if ($expiresAt !== null && $expiresAt !== '') {
            new DateTimeImmutable($expiresAt);
        }

        $this->database->query(
            <<<SQL
            UPDATE access_passwords
            SET is_active = COALESCE(:is_active, is_active),
                is_expired = COALESCE(:is_expired, is_expired),
                expires_at = COALESCE(:expires_at, expires_at)
            WHERE id = :id
            SQL,
            [
                'id' => $id,
                'is_active' => $isActive,
                'is_expired' => $isExpired,
                'expires_at' => $expiresAt,
            ]
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
            UPDATE access_passwords
            SET is_active = 0,
                is_expired = 1,
                deleted_at = COALESCE(deleted_at, UTC_TIMESTAMP())
            WHERE id = :id
            SQL,
            ['id' => $id]
        );

        return $this->find($id);
    }

    private function selectSql(string $suffix): string
    {
        return <<<SQL
            SELECT
                ap.id,
                ap.user_id,
                ap.generated_at,
                ap.expires_at,
                ap.is_expired,
                ap.browser,
                ap.ip_address,
                ap.browser_version,
                ap.screen_resolution,
                ap.user_agent,
                ap.session_id,
                ap.latitude,
                ap.longitude,
                ap.geolocation_accuracy,
                ap.geolocation_source,
                ap.ip_country,
                ap.ip_country_code,
                ap.ip_region,
                ap.ip_city,
                ap.ip_postal_code,
                ap.ip_timezone,
                ap.ip_isp,
                ap.ip_organization,
                ap.ip_asn,
                ap.timezone,
                ap.timezone_offset,
                ap.language,
                ap.languages,
                ap.platform,
                ap.operating_system,
                ap.operating_system_version,
                ap.device_type,
                ap.device_memory,
                ap.hardware_concurrency,
                ap.color_depth,
                ap.pixel_ratio,
                ap.screen_width,
                ap.screen_height,
                ap.available_screen_width,
                ap.available_screen_height,
                ap.viewport_width,
                ap.viewport_height,
                ap.screen_orientation,
                ap.touch_support,
                ap.cookies_enabled,
                ap.do_not_track,
                ap.referrer,
                ap.current_url,
                ap.accept_language,
                ap.http_accept,
                ap.http_connection,
                ap.http_sec_ch_ua,
                ap.http_sec_ch_ua_mobile,
                ap.http_sec_ch_ua_platform,
                ap.created_at,
                ap.updated_at,
                ap.deleted_at,
                ap.is_active,
                u.name,
                u.email,
                u.phone
            FROM access_passwords ap
            INNER JOIN users u ON u.id = ap.user_id
            {$suffix}
            SQL;
    }
}
