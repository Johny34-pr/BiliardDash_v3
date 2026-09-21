<?php

declare(strict_types=1);

/**
 * Front Controller - Az alkalmazás belépési pontja
 * 
 * Minden HTTP kérés ide érkezik az Apache mod_rewrite szabályokon keresztül.
 * Felelős: autoload, session, routing, központi hibakezelés.
 */

// Composer autoloader betöltése
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\AppException;
use App\Core\Database;
use App\Core\Env;
use App\Core\Router;
use App\Core\Session;
use App\Services\RememberMeService;

// Környezeti változók betöltése a .env fájlból.
// Ez a legelső lépés, hogy minden konfigurációs fájl számíthasson rá,
// függetlenül attól, milyen sorrendben töltődnek be.
Env::load();

// Session indítása
Session::start();

/*
 * Megjegyzett belépés visszaállítása.
 *
 * Ha a látogató korábban kérte a belépési adatok megjegyzését, egy süti
 * alapján itt léptetjük vissza - még a routing előtt, hogy a nézetek már
 * bejelentkezett állapotot lássanak.
 *
 * Süti nélkül vagy meglévő bejelentkezéssel ez azonnal visszatér, ezért a
 * szokásos kérésekben nem jelent adatbázis-terhelést. A hibát elnyeljük:
 * egy hibás token nem akadályozhatja meg az oldal betöltését.
 */
try {
    (new RememberMeService(Database::getConnection()))->restoreSession();
} catch (\Throwable $e) {
    error_log('[index] Megjegyzett belépés visszaállítása nem sikerült: ' . $e->getMessage());
}

try {
    // Router példányosítás
    $router = new Router();

    // Útvonal definíciók betöltése
    require __DIR__ . '/../config/routes.php';

    // HTTP metódus és URI meghatározása
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    // Query string eltávolítása
    $pos = strpos($uri, '?');
    if ($pos !== false) {
        $uri = substr($uri, 0, $pos);
    }

    // URL decode
    $uri = rawurldecode($uri);

    // Route dispatch
    $router->dispatch($method, $uri);

} catch (AppException $e) {
    // Alkalmazás-szintű hibák kezelése
    $code = $e->getCode();
    http_response_code($code);

    match ($code) {
        404 => require __DIR__ . '/../src/Views/errors/404.php',
        401 => redirect('/admin/login'),
        default => require __DIR__ . '/../src/Views/errors/500.php',
    };

} catch (\PDOException $e) {
    // Adatbázis hibák kezelése
    error_log('[PDOException] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    require __DIR__ . '/../src/Views/errors/500.php';

} catch (\Throwable $e) {
    // Minden egyéb nem kezelt hiba
    error_log('[Throwable] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    require __DIR__ . '/../src/Views/errors/500.php';
}
