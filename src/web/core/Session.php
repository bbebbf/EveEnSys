<?php
declare(strict_types=1);

class Session
{
    public static function start(): void
    {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return isset($_SESSION['user_role']) && (int)$_SESSION['user_role'] === 1;
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    public static function requireGuest(): void
    {
        if (self::isLoggedIn()) {
            header('Location: /events');
            exit;
        }
    }

    public static function login(UserDto $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user->userId;
        $_SESSION['user_guid'] = $user->userGuid;
        $_SESSION['user_name'] = $user->userName;
        $_SESSION['user_role'] = $user->userRole;
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function getUserGuid(): ?string
    {
        return $_SESSION['user_guid'] ?? null;
    }

    public static function getUserName(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function setUserName(string $name): void
    {
        $_SESSION['user_name'] = $name;
    }

    public static function getCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function setFlash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}
