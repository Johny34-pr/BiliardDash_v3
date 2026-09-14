<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap fájl.
 *
 * - Composer autoload betöltése
 * - Teszt környezeti változók beállítása
 * - Session kezelés (fejléc elnyomással)
 *
 * A környezeti változókat itt, a .env betöltése *előtt* állítjuk be. Az
 * App\Core\Env soha nem írja felül a már beállított értékeket, így a tesztek
 * mindig a saját beállításaikkal futnak, függetlenül a fejlesztői gép .env
 * fájljától.
 */

// Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Teszt adatbázis: a phpunit.xml env értékei elsőbbséget kapnak, itt csak
// az alapértelmezéseket pótoljuk
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
$_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'billiard_test';
$_ENV['DB_USERNAME'] = $_ENV['DB_USERNAME'] ?? 'root';
$_ENV['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? '';

// Kiszámítható admin jelszó és szerkesztő beállítás a tesztekhez
$_ENV['ADMIN_PASSWORD'] = $_ENV['ADMIN_PASSWORD'] ?? 'test-admin-password';
$_ENV['TINYMCE_API_KEY'] = $_ENV['TINYMCE_API_KEY'] ?? '';

// A .env betöltése: a fentieket nem írja felül
\App\Core\Env::load();

// Session indítás fejléc hiba elnyomással (tesztkörnyezetben)
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
