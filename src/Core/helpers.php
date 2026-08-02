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
