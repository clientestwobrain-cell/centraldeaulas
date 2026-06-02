<?php

declare(strict_types=1);

use CentralDeAulas\Database\Migration;
use CentralDeAulas\Database\PdoDatabase;

return new class implements Migration {
    public function name(): string
    {
        return '2026_05_27_000002_add_client_context_to_access_passwords_table';
    }

    public function up(PdoDatabase $database): void
    {
        $columns = [
            'latitude' => 'DECIMAL(10, 8) NULL AFTER session_id',
            'longitude' => 'DECIMAL(11, 8) NULL AFTER latitude',
            'geolocation_accuracy' => 'DECIMAL(10, 2) NULL AFTER longitude',
            'geolocation_source' => "VARCHAR(30) NULL AFTER geolocation_accuracy",
            'ip_country' => 'VARCHAR(100) NULL AFTER geolocation_source',
            'ip_country_code' => 'CHAR(2) NULL AFTER ip_country',
            'ip_region' => 'VARCHAR(100) NULL AFTER ip_country_code',
            'ip_city' => 'VARCHAR(120) NULL AFTER ip_region',
            'ip_postal_code' => 'VARCHAR(30) NULL AFTER ip_city',
            'ip_timezone' => 'VARCHAR(80) NULL AFTER ip_postal_code',
            'ip_isp' => 'VARCHAR(190) NULL AFTER ip_timezone',
            'ip_organization' => 'VARCHAR(190) NULL AFTER ip_isp',
            'ip_asn' => 'VARCHAR(40) NULL AFTER ip_organization',
            'timezone' => 'VARCHAR(80) NULL AFTER ip_asn',
            'timezone_offset' => 'SMALLINT NULL AFTER timezone',
            'language' => 'VARCHAR(20) NULL AFTER timezone_offset',
            'languages' => 'VARCHAR(255) NULL AFTER language',
            'platform' => 'VARCHAR(120) NULL AFTER languages',
            'operating_system' => 'VARCHAR(120) NULL AFTER platform',
            'operating_system_version' => 'VARCHAR(80) NULL AFTER operating_system',
            'device_type' => 'VARCHAR(60) NULL AFTER operating_system_version',
            'device_memory' => 'DECIMAL(5, 2) NULL AFTER device_type',
            'hardware_concurrency' => 'TINYINT UNSIGNED NULL AFTER device_memory',
            'color_depth' => 'TINYINT UNSIGNED NULL AFTER hardware_concurrency',
            'pixel_ratio' => 'DECIMAL(5, 2) NULL AFTER color_depth',
            'screen_width' => 'SMALLINT UNSIGNED NULL AFTER pixel_ratio',
            'screen_height' => 'SMALLINT UNSIGNED NULL AFTER screen_width',
            'available_screen_width' => 'SMALLINT UNSIGNED NULL AFTER screen_height',
            'available_screen_height' => 'SMALLINT UNSIGNED NULL AFTER available_screen_width',
            'viewport_width' => 'SMALLINT UNSIGNED NULL AFTER available_screen_height',
            'viewport_height' => 'SMALLINT UNSIGNED NULL AFTER viewport_width',
            'screen_orientation' => 'VARCHAR(40) NULL AFTER viewport_height',
            'touch_support' => 'TINYINT(1) NULL AFTER screen_orientation',
            'cookies_enabled' => 'TINYINT(1) NULL AFTER touch_support',
            'do_not_track' => 'VARCHAR(20) NULL AFTER cookies_enabled',
            'referrer' => 'VARCHAR(512) NULL AFTER do_not_track',
            'current_url' => 'VARCHAR(512) NULL AFTER referrer',
            'accept_language' => 'VARCHAR(255) NULL AFTER current_url',
            'http_accept' => 'VARCHAR(255) NULL AFTER accept_language',
            'http_connection' => 'VARCHAR(80) NULL AFTER http_accept',
            'http_sec_ch_ua' => 'VARCHAR(255) NULL AFTER http_connection',
            'http_sec_ch_ua_mobile' => 'VARCHAR(20) NULL AFTER http_sec_ch_ua',
            'http_sec_ch_ua_platform' => 'VARCHAR(80) NULL AFTER http_sec_ch_ua_mobile',
        ];

        foreach ($columns as $column => $definition) {
            $this->addColumnIfMissing($database, $column, $definition);
        }

        $this->addIndexIfMissing($database, 'idx_access_passwords_ip_country_code', 'ip_country_code');
        $this->addIndexIfMissing($database, 'idx_access_passwords_device_type', 'device_type');
    }

    private function addColumnIfMissing(PdoDatabase $database, string $column, string $definition): void
    {
        $existingColumn = $database->fetch(
            <<<SQL
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'access_passwords'
                AND COLUMN_NAME = :column
            SQL,
            ['column' => $column]
        );

        if ($existingColumn !== false) {
            return;
        }

        $database->query("ALTER TABLE access_passwords ADD COLUMN {$column} {$definition}");
    }

    private function addIndexIfMissing(PdoDatabase $database, string $index, string $column): void
    {
        $existingIndex = $database->fetch(
            <<<SQL
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'access_passwords'
                AND INDEX_NAME = :index_name
            SQL,
            ['index_name' => $index]
        );

        if ($existingIndex !== false) {
            return;
        }

        $database->query("ALTER TABLE access_passwords ADD KEY {$index} ({$column})");
    }
};
