<?php

declare(strict_types=1);

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        if (empty($_SESSION['auth_user'])) {
            $_SESSION['auth_user'] = (new User())->findWithRole((int) $_SESSION['user_id']);
        }

        return $_SESSION['auth_user'];
    }

    public static function permissions(): array
    {
        if (!self::check()) {
            return [];
        }

        if (!isset($_SESSION['auth_permissions'])) {
            $_SESSION['auth_permissions'] = (new Permission())->forUser((int) $_SESSION['user_id']);
        }

        return $_SESSION['auth_permissions'];
    }

    public static function can(string $permission): bool
    {
        $user = self::user();

        if ($user !== null && ($user['role_name'] ?? '') === 'Super Admin') {
            return true;
        }

        return in_array($permission, self::permissions(), true);
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['auth_user'] = (new User())->findWithRole((int) $user['id']);
        $_SESSION['auth_permissions'] = (new Permission())->forUser((int) $user['id']);
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}

