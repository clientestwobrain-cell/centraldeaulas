<?php

declare(strict_types=1);

namespace CentralDeAulas\Http;

use CentralDeAulas\Core\Env;

final class AdminSession
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(Env::get('ADMIN_SESSION_NAME', 'centraldeaulas_admin'));
        session_start();
    }

    public function login(array $admin): void
    {
        session_regenerate_id(true);

        $_SESSION['admin_user'] = [
            'id' => (int) $admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
        ];
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public function user(): ?array
    {
        return isset($_SESSION['admin_user']) && is_array($_SESSION['admin_user'])
            ? $_SESSION['admin_user']
            : null;
    }

    public function requireLogin(): void
    {
        if ($this->user() !== null) {
            return;
        }

        header('Location: login.php');
        exit;
    }

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public function validateCsrf(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
