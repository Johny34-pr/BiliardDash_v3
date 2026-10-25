<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Közvetítések (élő adások és a korábbiak archívuma).
 *
 * Egy közvetítés egy külső adás (YouTube, Facebook) hivatkozása, jellemzően
 * egy versenyhez kötve. A versenyhez kötés a keresés alapja: a látogató a
 * VERSENY NEVÉRE keres, és a találat a hozzá tartozó közvetítés.
 *
 * A kapcsolat LEFT JOIN, mert a közvetítés lehet verseny nélküli (klubest,
 * bemutató), és a verseny törlése sem viszi magával a bejegyzést
 * (ON DELETE SET NULL). Ilyenkor csak a saját címe (title) azonosítja.
 */
class Broadcast
{
    /**
     * A lekérdezések oszloplistája egy helyen.
     *
     * Így a lista, a keresés és az egyedi lekérdezés ugyanazt az adatkört
     * adja vissza, és egy új oszlop felvételekor nem csúszhatnak szét.
     */
    private const COLUMNS = 'b.id, b.competition_id, b.title, b.url,
                    b.broadcast_date, b.is_live, b.created_at, b.updated_at,
                    c.name AS competition_name, c.date AS competition_date,
                    c.venue AS competition_venue';

    private const FROM = 'FROM broadcasts b LEFT JOIN competitions c ON c.id = b.competition_id';

    /**
     * Rendezés: az élő adás elöl, utána a legfrissebb.
     *
     * Az élő adás azért előzi a dátumot, mert a látogatót elsősorban az
     * érdekli, ami MOST látható - a többi visszatekintés. A created_at a
     * harmadik szint, hogy az egy napra eső közvetítések sorrendje stabil
     * legyen (lapozásnál ez nem mindegy: rendezés nélkül ugyanaz az elem
     * két oldalon is megjelenhetne).
     */
    private const ORDER = 'ORDER BY b.is_live DESC, b.broadcast_date DESC, b.created_at DESC';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Közvetítések keresése a verseny neve vagy a közvetítés címe alapján.
     *
     * Üres keresőszóval a teljes lista jön vissza - így a nyilvános oldalnak
     * nem kell kétféle lekérdezést hívnia.
     *
     * A verseny neve ÉS a közvetítés címe is számít: a versenyhez kötött
     * közvetítést a verseny nevén keresik, a verseny nélkülieknek pedig csak
     * a saját címük van.
     *
     * @param string|null $term  Keresőszó, vagy null a teljes listához
     * @param int         $limit Egy oldalon ennyi elem
     * @return array<array<string, mixed>>
     */
    public function search(?string $term, int $limit, int $offset): array
    {
        [$where, $params] = $this->searchCondition($term);

        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . '
             ' . self::FROM . '
             ' . $where . '
             ' . self::ORDER . '
             LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        // A LIMIT és az OFFSET csak számként érvényes, ezért a kötés
        // explicit egész típusú: szövegként idézőjelek közé kerülne
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * A keresésre illeszkedő közvetítések száma (a lapozáshoz).
     */
    public function countSearch(?string $term): int
    {
        [$where, $params] = $this->searchCondition($term);

        $stmt = $this->db->prepare('SELECT COUNT(*) ' . self::FROM . ' ' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Minden közvetítés, élő adás elöl - a szervezői felület listájához.
     *
     * Itt szándékosan nincs lapozás: a szervezőnek a teljes készlet kell,
     * és a műveletek (élő jelzés, törlés) egy kattintásosak.
     *
     * @return array<array<string, mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             ' . self::FROM . '
             ' . self::ORDER
        );

        return $stmt->fetchAll();
    }

    /**
     * Egy közvetítés az azonosítója alapján.
     *
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . '
             ' . self::FROM . '
             WHERE b.id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Összesítés a menühöz: összes közvetítés és ezen belül az élő adások.
     *
     * Egyetlen lekérdezés, mert a fejléc és a lábléc minden oldalbetöltésnél
     * megkérdezi: a menüpont csak akkor jelenik meg, ha van közvetítés, és
     * élő adás esetén jelzést is kap.
     *
     * @return array{total:int, live:int}
     */
    public function summary(): array
    {
        $row = $this->db
            ->query('SELECT COUNT(*) AS total, COALESCE(SUM(is_live), 0) AS live FROM broadcasts')
            ->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'live' => (int) ($row['live'] ?? 0),
        ];
    }

    public function create(
        string $id,
        ?string $competitionId,
        string $title,
        string $url,
        string $broadcastDate,
        bool $isLive
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO broadcasts
                (id, competition_id, title, url, broadcast_date, is_live)
             VALUES
                (:id, :competition_id, :title, :url, :broadcast_date, :is_live)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':competition_id' => $competitionId,
            ':title' => $title,
            ':url' => $url,
            ':broadcast_date' => $broadcastDate,
            ':is_live' => $isLive ? 1 : 0,
        ]);
    }

    /**
     * Közvetítés adatainak módosítása.
     *
     * Az is_live szándékosan nincs benne: azt a setLive() állítja. Így az
     * élő jelzés egy kattintással kapcsolható anélkül, hogy a szerkesztő
     * űrlapot be kellene küldeni - és egy adatmódosítás sem kapcsolja ki
     * véletlenül a futó adást.
     */
    public function update(
        string $id,
        ?string $competitionId,
        string $title,
        string $url,
        string $broadcastDate
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE broadcasts
             SET competition_id = :competition_id,
                 title = :title,
                 url = :url,
                 broadcast_date = :broadcast_date
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':competition_id' => $competitionId,
            ':title' => $title,
            ':url' => $url,
            ':broadcast_date' => $broadcastDate,
        ]);
    }

    /**
     * Az élő jelzés be- vagy kikapcsolása.
     *
     * Nem kizárólagos: egy versenyről több asztal is mehet egyszerre, ezért
     * több közvetítés is lehet élő.
     */
    public function setLive(string $id, bool $isLive): bool
    {
        $stmt = $this->db->prepare('UPDATE broadcasts SET is_live = :is_live WHERE id = :id');

        return $stmt->execute([
            ':is_live' => $isLive ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM broadcasts WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * A keresés WHERE feltétele és a hozzá tartozó paraméterek.
     *
     * Üres keresőszónál nincs feltétel, így ugyanaz a lekérdezés szolgálja a
     * szűrt és a szűretlen listát.
     *
     * @return array{0:string, 1:array<string, string>}
     */
    private function searchCondition(?string $term): array
    {
        $term = trim((string) $term);

        if ($term === '') {
            return ['', []];
        }

        $pattern = '%' . $this->escapeLike($term) . '%';

        /*
         * Két külön elnevezett paraméter ugyanazzal az értékkel, nem egy
         * kétszer felhasznált név: a kapcsolat kikapcsolt
         * ATTR_EMULATE_PREPARES mellett valódi előkészített utasítást küld a
         * MySQL-nek, ahol minden helyőrzőhöz saját érték tartozik - egy
         * kétszer szereplő :term "Invalid parameter number" hibát adna.
         */
        return [
            "WHERE (c.name LIKE :term_competition ESCAPE '!'
                    OR b.title LIKE :term_title ESCAPE '!')",
            [
                ':term_competition' => $pattern,
                ':term_title' => $pattern,
            ],
        ];
    }

    /**
     * A LIKE minta joker karaktereinek hatástalanítása.
     *
     * A % és a _ a LIKE-ban joker, ezért a beírt szövegben escape-elni kell:
     * enélkül egy "100%" keresés mindenre illeszkedne, a "_" pedig bármely
     * karakterre.
     *
     * Az escape karakter szándékosan FELKIÁLTÓJEL és nem backslash: a
     * backslash a MySQL-ben szövegliterál-escape is, ezért az ESCAPE
     * záradékában meg kell kettőzni - az SQLite-ban viszont nem, ott a
     * megkettőzött alak már két karakter lenne, és hibát adna. A felkiáltójel
     * mindkét adatbázisban ugyanazt jelenti, így a tesztek (SQLite) és az
     * éles futás (MySQL) nem válik el egymástól.
     *
     * Az ékezetek és a kis-nagybetű nem igényel külön kezelést: az
     * adatbázis utf8mb4_unicode_ci egybevetést használ, amely mindkettőt
     * egyenértékűnek veszi ("okany kupa" megtalálja az "Okány Kupa"-t).
     */
    private function escapeLike(string $term): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
    }
}
