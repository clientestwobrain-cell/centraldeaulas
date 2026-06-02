<?php

declare(strict_types=1);

namespace CentralDeAulas\Http;

use CentralDeAulas\Core\Env;

final class Cors
{
    public function apply(): void
    {
        $allowedOrigins = $this->csv(Env::get('API_CORS_ALLOWED_ORIGINS', '*'));
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        if (in_array('*', $allowedOrigins, true) || in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . (in_array('*', $allowedOrigins, true) ? '*' : $origin));
        }

        header('Vary: Origin');
        header('Access-Control-Allow-Methods: ' . Env::get('API_CORS_ALLOWED_METHODS', 'GET,POST,OPTIONS'));
        header('Access-Control-Allow-Headers: ' . Env::get('API_CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With'));
        header('Access-Control-Max-Age: 86400');
    }

    public function handlePreflight(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'OPTIONS') {
            return;
        }

        http_response_code(204);
        exit;
    }

    private function csv(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
