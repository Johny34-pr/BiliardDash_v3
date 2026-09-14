<?php

declare(strict_types=1);

/**
 * HTML special characters escape - XSS védelem
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * HTTP redirect (Header Location)
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Asset URL generálás (publikus fájlokhoz)
 * Hívás: asset('css/app.css') → '/assets/css/app.css'
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    return '/assets/' . $path;
}

/**
 * Aktuális URL lekérése (path rész, query string nélkül)
 */
function currentUrl(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return parse_url($uri, PHP_URL_PATH) ?: '/';
}

/**
 * Aktív menüpont CSS class meghatározása
 *
 * Visszaadja az aktív menüpont osztályát, ha az aktuális URL megegyezik a
 * megadott útvonallal, vagy annak alútvonala. A `nav-link-active` osztály
 * arany szöveget és jelzővonalat ad (Requirement 8.3), a stílus definíciója
 * a public/assets/css/app.css fájlban található.
 *
 * @param string $route A vizsgált útvonal (pl. '/galeria')
 * @return string 'nav-link-active' ha aktív, egyébként üres string
 */
function isActive(string $route): string
{
    $current = currentUrl();

    if ($route === '/') {
        return $current === '/' ? 'nav-link-active' : '';
    }

    if ($current === $route || str_starts_with($current, $route . '/')) {
        return 'nav-link-active';
    }

    return '';
}

/**
 * Alkalmazás konfiguráció elérése kulcs alapján.
 *
 * A config/app.php-t egyszer olvassa be és megjegyzi, így a helperek
 * kérésenként többször is hívhatók extra fájlműveletek nélkül.
 *
 * @param string $key     Kulcs a config/app.php tömbjéből (pl. 'base_url')
 * @param mixed  $default Visszatérési érték, ha a kulcs nincs beállítva
 */
function appConfig(string $key, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
    }

    return $config[$key] ?? $default;
}

/**
 * A webhely nyilvános origója (séma + host), záró perjel nélkül.
 *
 * Elsősorban a .env APP_URL értékéből származik. Ez szándékosan
 * konfigurációs adat és nem a kérés Host fejléce: a canonical és a
 * megosztási URL-eket nem befolyásolhatja egy hamisított fejléc.
 *
 * Ha az APP_URL nincs beállítva, végszükségből a kérésből építjük fel,
 * hogy a linkek ne mutassanak rossz helyre.
 */
function siteOrigin(): string
{
    $configured = trim((string) appConfig('base_url', ''));

    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    // Csak a hosztnévben megengedett karakterek maradhatnak
    $host = preg_replace('/[^A-Za-z0-9\.\-:]/', '', $host) ?: 'localhost';

    return $scheme . '://' . $host;
}

/**
 * Abszolút URL képzése egy alkalmazáson belüli útvonalból.
 *
 * Hívás: siteUrl('/galeria') → 'https://pelda.hu/galeria'
 * A már abszolút (http/https) URL-eket változatlanul adja vissza, így
 * külső képek és hivatkozások is átadhatók neki.
 */
function siteUrl(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }

    $origin = siteOrigin();
    $path = ltrim($path, '/');

    return $path === '' ? $origin . '/' : $origin . '/' . $path;
}

/**
 * Az aktuális oldal kanonikus URL-je.
 *
 * A query stringet szándékosan elhagyja, mert a szűrő- és követő
 * paraméterek nem külön oldalak. Ahol a paraméter valódi tartalmi
 * különbséget jelent (pl. a fórum lapozása), ott a kontroller a
 * $canonical változóval írja felül.
 */
function canonicalUrl(): string
{
    return siteUrl(currentUrl());
}
