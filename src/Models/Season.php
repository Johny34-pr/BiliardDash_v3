<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Szezonok.
 *
 * A szezon két helyen fogja össze a tartalmat: a galéria archívumában
 * (melyik évad albumai) és a ranglistán (melyik évad pontjai). Ezért önálló
 * tábla, nem pedig szöveges címke a versenyen - így egyetlen helyen kell
 * átnevezni, és a hozzárendelés nem tud elírás miatt szétesni.
 *
 * A rendezés a starts_on szerint történik, nem a név szerint: a "2026/2027"
 * típusú nevek szöveges rendezése csak véletlenül adna helyes sorrendet.
 */
class Season
{
    /** Az adatkör egy helyen, hogy a lekérdezések ne csússzanak el egymástól */
    private const COLUMNS = 'id, name, starts_on, is_current, is_archived, created_at, updated_at';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Összes szezon, a legfrissebb elöl.
     *
     * @return array<array{id:string, name:string, starts_on:?string, is_current:int, is_archived:int, created_at:string, updated_at:string}>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             FROM seasons
             ORDER BY starts_on DESC, name DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Csak az archivált szezonok, a legfrissebb elöl.
     *
     * @return array<array{id:string, name:string, starts_on:?string, is_current:int, is_archived:int, created_at:string, updated_at:string}>
     */
    public function findArchived(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             FROM seasons
             WHERE is_archived = 1
             ORDER BY starts_on DESC, name DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Az aktuális szezon.
     *
     * Ha több sor is aktuálisra lenne jelölve (kézi adatbázis-módosítás
     * után előfordulhat), a legfrissebbet adja vissza - nem hibázik el egy
     * inkonzisztens állapot miatt.
     *
     * @return array{id:string, name:string, starts_on:?string, is_current:int, is_archived:int, created_at:string, updated_at:string}|null
     */
    public function findCurrent(): ?array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             FROM seasons
             WHERE is_current = 1
             ORDER BY starts_on DESC
             LIMIT 1'
        );

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * @return array{id:string, name:string, starts_on:?string, is_current:int, is_archived:int, created_at:string, updated_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . '
             FROM seasons
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Létezik-e már ilyen nevű szezon (a megadotton kívül).
     *
     * A név egyedi, de az ütközést a mentés előtt szeretnénk jelezni, nem
     * adatbázis-hibaként.
     */
    public function nameExists(string $name, ?string $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM seasons WHERE name = :name';
        $params = [':name' => $name];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params[':except_id'] = $exceptId;
        }

        $stmt = $this->db->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }

    public function create(string $id, string $name, ?string $startsOn, bool $isCurrent): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO seasons (id, name, starts_on, is_current)
             VALUES (:id, :name, :starts_on, :is_current)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':starts_on' => $startsOn,
            ':is_current' => $isCurrent ? 1 : 0,
        ]);
    }

    public function update(string $id, string $name, ?string $startsOn): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE seasons SET name = :name, starts_on = :starts_on WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':starts_on' => $startsOn,
        ]);
    }

    /**
     * Minden szezonról leveszi az "aktuális" jelzőt.
     *
     * A kizárólagosságot két lépésben érjük el (először mindenkiről le,
     * aztán egyre fel), mert így nem kell tranzakciót nyitni, és egy
     * félbeszakadt művelet legrosszabb esetben is csak azt jelenti, hogy
     * egyetlen szezon sem aktuális - ez javítható, míg a "két aktuális"
     * állapot kétértelmű lenne.
     */
    public function clearCurrent(): bool
    {
        return $this->db->exec('UPDATE seasons SET is_current = 0 WHERE is_current = 1') !== false;
    }

    public function setCurrent(string $id): bool
    {
        $stmt = $this->db->prepare('UPDATE seasons SET is_current = 1 WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    public function setArchived(string $id, bool $archived): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE seasons SET is_archived = :is_archived WHERE id = :id'
        );

        return $stmt->execute([
            ':is_archived' => $archived ? 1 : 0,
            ':id' => $id,
        ]);
    }

    /**
     * Szezon törlése.
     *
     * A versenyek és albumok idegen kulcsa ON DELETE SET NULL, tehát a
     * tartalom megmarad, csak szezon nélkülivé válik.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM seasons WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }
}
