<?php

declare(strict_types=1);

/**
 * Adatbázis konfiguráció
 * .env fájlból olvas ha elérhető, egyébként alapértelmezett értékek
 */

// .env fájl betöltése ha létezik
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Idézőjelek eltávolítása
            $value = trim($value, '"\'');
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

return [
    'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost',
    'dbname' => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'billiard',
    'username' => $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',
];
