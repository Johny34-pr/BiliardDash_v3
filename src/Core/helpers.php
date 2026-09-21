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
 * Asset URL generálás (publikus fájlokhoz), gyorsítótör verzióval.
 *
 * Hívás: asset('css/app.css') → '/assets/css/app.css?v=1712345678'
 *
 * A verziószám a fájl utolsó módosításának időpontja. Erre azért van
 * szükség, mert a stíluslapokat és a szkripteket hosszú lejárattal
 * gyorsítótárazzuk: enélkül a látogató böngészője egy módosítás után is a
 * régi fájlt használná. A fájl módosításakor a cím megváltozik, ezért a
 * böngésző újra letölti - kézi verziózás nélkül.
 *
 * Nem létező fájlnál a verzió elmarad, így a hivatkozás nem törik el.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $absolute = dirname(__DIR__, 2) . '/public/assets/' . $path;

    if (is_file($absolute)) {
        return '/assets/' . $path . '?v=' . filemtime($absolute);
    }

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

/**
 * Az oldalbeállítások kulcs-érték térképe.
 *
 * A nézetekbe és az útvonaldefinícióba nem lehet szolgáltatást átadni, ezért
 * a kapcsolható funkciókat ezen a helperen keresztül kérdezzük le. A
 * SettingsService statikus gyorsítótára miatt ez kérésenként egyetlen
 * adatbázis-lekérdezést jelent.
 *
 * Adatbázishiba esetén üres térképet ad vissza, így a kapcsolók az
 * alapértelmezésükre esnek, és egy beállítási hiba nem viszi magával az
 * egész oldalt.
 *
 * @return array<string, string|null>
 */
function siteSettings(): array
{
    try {
        return (new \App\Services\SettingsService(\App\Core\Database::getConnection()))->all();
    } catch (\Throwable $e) {
        error_log('[helpers] Az oldalbeállítások betöltése nem sikerült: ' . $e->getMessage());
        return [];
    }
}

/**
 * Aktív-e a fórum modul.
 *
 * Alapértelmezetten nem: a fórum csak akkor jelenik meg a menüben és csak
 * akkor érhetők el az útvonalai, ha a szervező bekapcsolta.
 */
function forumEnabled(): bool
{
    return (siteSettings()[\App\Services\SettingsService::FORUM_ENABLED] ?? '0') === '1';
}

/**
 * Kapcsolati adatok és társhonlapok a config/contact.php fájlból.
 *
 * A lábléc és a Társhonlapok oldal is ezt olvassa, ezért egyszer töltjük be
 * és megjegyezzük. Az üresen hagyott értékeket a nézetek kihagyják, így egy
 * kitöltetlen mező nem okoz csonka megjelenést.
 *
 * @return array<string, mixed>
 */
function contactConfig(): array
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__, 2) . '/config/contact.php';
    }

    return $config;
}

/**
 * A megadott címmel rendelkező közösségi hivatkozások.
 *
 * A config/contact.php minden bejegyzést felsorol, de a még kitöltetlen
 * címűeket nem szabad linkként kiírni, mert üres hivatkozás lenne belőlük.
 *
 * @return array<string, array{label:string, description:string, url:string}>
 */
function socialLinks(): array
{
    $links = contactConfig()['social'] ?? [];

    return array_filter($links, static fn(array $link): bool => trim($link['url'] ?? '') !== '');
}
