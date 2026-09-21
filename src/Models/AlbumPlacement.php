<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Egy album (verseny) helyezettjei.
 *
 * A rendezés a helyezés száma szerint történik, holtversenyben pedig a
 * játékos neve szerint. Erre azért van szükség, mert kieséses rendszerben
 * két játékos is oszthat egy helyet.
 *
 * A név mint másodlagos rendezési szempont szándékos: a created_at csak
 * másodpontosságú, így az egyazon másodpercben felvitt holtversenyzők
 * sorrendje futásonként változott volna. Az azonos helyen állók között
 * nincs valódi rangsor, ezért az ábécé a legkevésbé félrevezető.
 */
class AlbumPlacement
{
    private const COLUMNS = 'id, album_id, position, player_name, note, created_at, updated_at';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Egy album helyezettjei, helyezés szerint növekvő sorrendben.
     *
     * @return array<array{id:string, album_id:string, position:int, player_name:string, note:?string, created_at:string, updated_at:string}>
     */
    public function findByAlbumId(string $albumId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . '
             FROM album_placements
             WHERE album_id = :album_id
             ORDER BY position ASC, player_name ASC'
        );
        $stmt->execute([':album_id' => $albumId]);

        return $stmt->fetchAll();
    }

    /**
     * Egy helyezés lekérdezése azonosító alapján.
     *
     * @return array{id:string, album_id:string, position:int, player_name:string, note:?string, created_at:string, updated_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . ' FROM album_placements WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Új helyezés felvitele.
     */
    public function create(string $id, string $albumId, int $position, string $playerName, ?string $note): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO album_placements (id, album_id, position, player_name, note)
             VALUES (:id, :album_id, :position, :player_name, :note)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':album_id' => $albumId,
            ':position' => $position,
            ':player_name' => $playerName,
            ':note' => $note,
        ]);
    }

    /**
     * Helyezés módosítása. Az albumhoz tartozása nem változtatható.
     */
    public function update(string $id, int $position, string $playerName, ?string $note): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE album_placements
             SET position = :position, player_name = :player_name, note = :note
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':position' => $position,
            ':player_name' => $playerName,
            ':note' => $note,
        ]);
    }

    /**
     * Helyezés törlése.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM album_placements WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Több album helyezettjei egyetlen lekérdezéssel.
     *
     * Az album lista minden albumhoz mutatja a dobogósokat; albumonkénti
     * külön lekérdezés helyett egy IN feltétellel kérjük le mindet, hogy
     * a lista ne szaladjon N+1 lekérdezésbe.
     *
     * @param array<string> $albumIds
     * @return array<string, array<array{position:int, player_name:string, note:?string}>>
     *         Album azonosító => helyezettek
     */
    public function findByAlbumIds(array $albumIds): array
    {
        if ($albumIds === []) {
            return [];
        }

        // Kötési helyőrzők az azonosítókhoz: a lista hossza változó, ezért
        // dinamikusan állítjuk össze, de az értékek továbbra is kötve
        // mennek, nem a lekérdezés szövegébe fűzve.
        $placeholders = implode(', ', array_fill(0, count($albumIds), '?'));

        $stmt = $this->db->prepare(
            'SELECT album_id, position, player_name, note
             FROM album_placements
             WHERE album_id IN (' . $placeholders . ')
             ORDER BY position ASC, player_name ASC'
        );
        $stmt->execute(array_values($albumIds));

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[$row['album_id']][] = [
                'position' => (int) $row['position'],
                'player_name' => $row['player_name'],
                'note' => $row['note'],
            ];
        }

        return $grouped;
    }
}
