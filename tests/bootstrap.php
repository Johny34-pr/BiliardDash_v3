<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap fájl.
 *
 * - Composer autoload betöltése
 * - Teszt adatbázis környezeti változók beállítása
 * - Session kezelés (fejléc elnyomással)
 */

// Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Teszt adatbázis környezeti változók beállítása
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
$_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'billiard_test';
$_ENV['DB_USERNAME'] = $_ENV['DB_USERNAME'] ?? 'root';
$_ENV['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? '';

// Session indítás fejléc hiba elnyomással (tesztkörnyezetben)
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
