<?php

declare(strict_types=1);

namespace CentralDeAulas\Service;

use CentralDeAulas\Database\PdoDatabase;

final class AdminDashboardService
{
    private PdoDatabase $database;

    public function __construct(PdoDatabase $database)
    {
        $this->database = $database;
    }

    public function stats(): array
    {
        return [
            'users' => $this->scalar('SELECT COUNT(*) AS total FROM users'),
            'passwords' => $this->scalar('SELECT COUNT(*) AS total FROM access_passwords'),
            'active_passwords' => $this->scalar(
                'SELECT COUNT(*) AS total FROM access_passwords WHERE is_active = 1 AND is_expired = 0 AND expires_at > UTC_TIMESTAMP()'
            ),
            'expired_passwords' => $this->scalar('SELECT COUNT(*) AS total FROM access_passwords WHERE is_expired = 1'),
        ];
    }

    public function latestLeadAccessRecords(int $limit = 50): array
    {
        return $this->database->fetchAll(
            <<<SQL
            SELECT
                u.id AS user_id,
                u.name,
                u.email,
                u.phone,
                u.created_at AS user_created_at,
                u.updated_at AS user_updated_at,
                u.is_active AS user_is_active,
                ap.id AS access_password_id,
                ap.generated_at,
                ap.expires_at,
                ap.is_expired,
                ap.is_active AS password_is_active,
                ap.browser,
                ap.ip_address,
                ap.ip_city,
                ap.ip_region,
                ap.ip_country_code,
                ap.timezone,
                ap.language,
                ap.device_type,
                ap.operating_system,
                ap.browser_version,
                ap.screen_resolution,
                ap.screen_width,
                ap.screen_height,
                ap.viewport_width,
                ap.viewport_height
            FROM users u
            LEFT JOIN access_passwords ap ON ap.user_id = u.id
            ORDER BY COALESCE(ap.id, u.id) DESC
            LIMIT {$limit}
            SQL
        );
    }

    private function scalar(string $sql): int
    {
        $row = $this->database->fetch($sql);

        return $row === false ? 0 : (int) $row['total'];
    }
}
