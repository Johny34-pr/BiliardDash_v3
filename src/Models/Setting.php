<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Oldalbeállítások kulcs-érték tárolása.
 *
 * A beállítások futásidőben módosíthatók a szervezői felületről, ezért
 * adatbázisban élnek és nem konfigurációs fájlban. Az értékek szövegként
 * tárolódnak; az értelmezés a hívó dolga (a logikai kapcsolók '0' vagy '1').
 */
class Setting
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Az összes beállítás kulcs => érték formában.
     *
     * Egyetlen lekérdezés, mert a beállítások száma kicsi, és a kérés
     * több pontján is kellenek (menü, útvonalak, nézetek).
     *
     * @return array<string, string|null>
     */
    public function findAllAsMap(): array
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value FROM site_settings');

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }

        return $map;
    }

    /**
     * Egy beállítás értéke, vagy null ha nincs ilyen kulcs.
     */
    public function find(string $key): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT setting_value FROM site_settings WHERE setting_key = :key'
        );
        $stmt->execute([':key' => $key]);

        $result = $stmt->fetch();

        return $result === false ? null : $result['setting_value'];
    }

    /**
     * Beállítás mentése: létrehozás vagy felülírás.
     *
     * A hívónak nem kell tudnia, létezik-e már a kulcs, ezért előbb
     * frissítünk, és csak akkor szúrunk be, ha nem volt érintett sor. Így
     * egy új kapcsoló bevezetése migráció nélkül is működik, az
     * alapértelmezéssel a kódban.
     *
     * Szándékosan nem INSERT ... ON DUPLICATE KEY UPDATE: az MySQL-specifikus,
     * és a tesztek SQLite-on futnak. Ez a két utasítás mindkettőn működik.
     */
    public function save(string $key, ?string $value): bool
    {
        $update = $this->db->prepare(
            'UPDATE site_settings SET setting_value = :value WHERE setting_key = :key'
        );
        $update->execute([':key' => $key, ':value' => $value]);

        if ($update->rowCount() > 0) {
            return true;
        }

        // Nem volt ilyen kulcs - vagy volt, de az érték nem változott.
        // A második esetben a beszúrás kulcsütközést adna, ezért előbb
        // megnézzük, létezik-e a sor.
        if ($this->find($key) !== null) {
            return true;
        }

        $insert = $this->db->prepare(
            'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)'
        );

        return $insert->execute([':key' => $key, ':value' => $value]);
    }
}
