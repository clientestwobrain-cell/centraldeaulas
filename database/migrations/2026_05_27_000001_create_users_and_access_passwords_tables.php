<?php

declare(strict_types=1);

use CentralDeAulas\Database\Migration;
use CentralDeAulas\Database\PdoDatabase;

return new class implements Migration {
    public function name(): string
    {
        return '2026_05_27_000001_create_users_and_access_passwords_tables';
    }

    public function up(PdoDatabase $database): void
    {
        $database->query(
            <<<SQL
            CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(190) NOT NULL,
                phone VARCHAR(30) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY uk_users_email (email),
                UNIQUE KEY uk_users_phone (phone),
                KEY idx_users_is_active (is_active),
                KEY idx_users_deleted_at (deleted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        $database->query(
            <<<SQL
            CREATE TABLE IF NOT EXISTS access_passwords (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                generated_password_hash VARCHAR(255) NOT NULL,
                generated_password_encrypted TEXT NULL,
                generated_password_fingerprint CHAR(64) NULL,
                generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                is_expired TINYINT(1) NOT NULL DEFAULT 0,
                browser VARCHAR(120) NULL,
                ip_address VARCHAR(45) NULL,
                browser_version VARCHAR(60) NULL,
                screen_resolution VARCHAR(30) NULL,
                user_agent VARCHAR(512) NULL,
                session_id VARCHAR(128) NULL,
                latitude DECIMAL(10, 8) NULL,
                longitude DECIMAL(11, 8) NULL,
                geolocation_accuracy DECIMAL(10, 2) NULL,
                geolocation_source VARCHAR(30) NULL,
                ip_country VARCHAR(100) NULL,
                ip_country_code CHAR(2) NULL,
                ip_region VARCHAR(100) NULL,
                ip_city VARCHAR(120) NULL,
                ip_postal_code VARCHAR(30) NULL,
                ip_timezone VARCHAR(80) NULL,
                ip_isp VARCHAR(190) NULL,
                ip_organization VARCHAR(190) NULL,
                ip_asn VARCHAR(40) NULL,
                timezone VARCHAR(80) NULL,
                timezone_offset SMALLINT NULL,
                language VARCHAR(20) NULL,
                languages VARCHAR(255) NULL,
                platform VARCHAR(120) NULL,
                operating_system VARCHAR(120) NULL,
                operating_system_version VARCHAR(80) NULL,
                device_type VARCHAR(60) NULL,
                device_memory DECIMAL(5, 2) NULL,
                hardware_concurrency TINYINT UNSIGNED NULL,
                color_depth TINYINT UNSIGNED NULL,
                pixel_ratio DECIMAL(5, 2) NULL,
                screen_width SMALLINT UNSIGNED NULL,
                screen_height SMALLINT UNSIGNED NULL,
                available_screen_width SMALLINT UNSIGNED NULL,
                available_screen_height SMALLINT UNSIGNED NULL,
                viewport_width SMALLINT UNSIGNED NULL,
                viewport_height SMALLINT UNSIGNED NULL,
                screen_orientation VARCHAR(40) NULL,
                touch_support TINYINT(1) NULL,
                cookies_enabled TINYINT(1) NULL,
                do_not_track VARCHAR(20) NULL,
                referrer VARCHAR(512) NULL,
                current_url VARCHAR(512) NULL,
                accept_language VARCHAR(255) NULL,
                http_accept VARCHAR(255) NULL,
                http_connection VARCHAR(80) NULL,
                http_sec_ch_ua VARCHAR(255) NULL,
                http_sec_ch_ua_mobile VARCHAR(20) NULL,
                http_sec_ch_ua_platform VARCHAR(80) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY uk_access_passwords_generated_password_fingerprint (generated_password_fingerprint),
                KEY idx_access_passwords_user_id (user_id),
                KEY idx_access_passwords_expires_at (expires_at),
                KEY idx_access_passwords_is_expired (is_expired),
                KEY idx_access_passwords_is_active (is_active),
                KEY idx_access_passwords_deleted_at (deleted_at),
                KEY idx_access_passwords_ip_country_code (ip_country_code),
                KEY idx_access_passwords_device_type (device_type),
                CONSTRAINT fk_access_passwords_user_id
                    FOREIGN KEY (user_id)
                    REFERENCES users (id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }
};
