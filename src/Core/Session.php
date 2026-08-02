<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isAdmin(): bool
    {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    public static function login(string $password): bool
    {
        $config = require __DIR__ . '/../../config/app.php';
        $adminPassword = $config['admin_password'] ?? '';

        if (password_verify($password, $adminPassword)) {
            $_SESSION['is_admin'] = true;
            return true;
        }

        // Fallback: plain text comparison (for simple setups)
        if ($password === $adminPassword) {
            $_SESSION['is_admin'] = true;
            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        unset($_SESSION['is_admin']);
        session_destroy();
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}
