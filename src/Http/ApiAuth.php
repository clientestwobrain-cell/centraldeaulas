<?php

declare(strict_types=1);

namespace CentralDeAulas\Http;

use CentralDeAulas\Core\Env;

final class ApiAuth
{
    public function requireAdminToken(): bool
    {
        $configuredToken = Env::get('API_ADMIN_TOKEN', '');

        if ($configuredToken === '') {
            return false;
        }

        $providedToken = $this->providedToken();

        return $providedToken !== null && hash_equals($configuredToken, $providedToken);
    }

    private function providedToken(): ?string
    {
        $authorization = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $_SERVER['Authorization']
            ?? '';

        if (str_starts_with($authorization, 'Bearer ')) {
            return trim(substr($authorization, 7));
        }

        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

        return $apiKey === '' ? null : $apiKey;
    }
}
