<?php

declare(strict_types=1);

namespace CentralDeAulas\Http;

final class Request
{
    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function input(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $rawBody = file_get_contents('php://input') ?: '';
            $decoded = json_decode($rawBody, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function clientContext(): array
    {
        $input = $this->input();
        $context = isset($input['client_context']) && is_array($input['client_context'])
            ? $input['client_context']
            : [];

        return array_merge([
            'ip_address' => $this->ipAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null,
            'http_accept' => $_SERVER['HTTP_ACCEPT'] ?? null,
            'http_connection' => $_SERVER['HTTP_CONNECTION'] ?? null,
            'http_sec_ch_ua' => $_SERVER['HTTP_SEC_CH_UA'] ?? null,
            'http_sec_ch_ua_mobile' => $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? null,
            'http_sec_ch_ua_platform' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? null,
            'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
        ], $context);
    }

    private function ipAddress(): ?string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            $ip = trim(explode(',', $candidate)[0]);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return null;
    }
}
