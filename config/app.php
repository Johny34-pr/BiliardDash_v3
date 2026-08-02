<?php

declare(strict_types=1);

/**
 * Alkalmazás konfiguráció
 */

return [
    'name' => $_ENV['APP_NAME'] ?? getenv('APP_NAME') ?: 'Magyar Biliárd',
    'base_url' => $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost',
    'upload_max_size' => 10 * 1024 * 1024, // 10 MB
    'upload_allowed_types' => ['image/jpeg', 'image/png'],
    'admin_password' => $_ENV['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?: 'admin123',
    'debug' => (bool)($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false),
];
