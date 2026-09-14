<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A .env fájl betöltése környezeti változókba.
 *
 * Korábban ez a logika a config/database.php fájlban volt beágyazva, ezért a
 * config/app.php értékei csak akkor működtek helyesen, ha előtte véletlenül
 * betöltődött az adatbázis konfiguráció. Külön osztályban a betöltés
 * kiszámítható: a belépési pont (public/index.php) hívja meg legelőször,
 * így minden konfigurációs fájl számíthat rá.
 *
 * A már beállított környezeti változókat soha nem írja felül, így a
 * kiszolgáló szintjén (Apache SetEnv, phpunit.xml) megadott értékek
 * elsőbbséget kapnak a .env fájllal szemben.
 */
class Env
{
    /** Csak egyszer töltjük be, akárhányszor is hívják */
    private static bool $loaded = false;

    /**
     * A .env betöltése, ha létezik.
     *
     * @param string|null $path A .env útvonala; alapértelmezésben a projekt gyökere
     */
    public static function load(?string $path = null): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;

        $file = $path ?? dirname(__DIR__, 2) . '/.env';

        if (!is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Megjegyzés és üres sor kihagyása
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);

            if ($key === '') {
                continue;
            }

            // Körülvágás, majd az érték körüli idézőjelek eltávolítása
            $value = trim($value);
            if (strlen($value) >= 2
                && ($value[0] === '"' || $value[0] === "'")
                && $value[strlen($value) - 1] === $value[0]
            ) {
                $value = substr($value, 1, -1);
            }

            // Meglévő értéket nem írunk felül
            if (array_key_exists($key, $_ENV) || getenv($key) !== false) {
                continue;
            }

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    /**
     * Környezeti változó kiolvasása.
     *
     * @param string      $key     A változó neve
     * @param string|null $default Visszatérési érték, ha nincs beállítva vagy üres
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    /**
     * Logikai környezeti változó kiolvasása.
     *
     * A "false", "0", "off" és "no" értékeket hamisként értelmezi, mert a
     * .env fájlból minden szövegként érkezik - a "false" szöveg egyébként
     * igaz értékké alakulna.
     *
     * Az alapértelmezést csak akkor adja vissza, ha a változó egyáltalán nincs
     * beállítva. A szándékosan üresen hagyott érték (pl. `APP_DEBUG=`)
     * kikapcsolást jelent, nem az alapértelmezéshez való visszatérést -
     * különben egy igaz alapértelmezésű jelzőt nem lehetne üresítéssel
     * kikapcsolni.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $raw = $_ENV[$key] ?? getenv($key);

        // A változó nincs beállítva
        if ($raw === false || $raw === null) {
            return $default;
        }

        return !in_array(strtolower(trim((string) $raw)), ['false', '0', 'off', 'no', ''], true);
    }

    /**
     * Csak teszteléshez: a betöltési állapot visszaállítása.
     */
    public static function reset(): void
    {
        self::$loaded = false;
    }
}
