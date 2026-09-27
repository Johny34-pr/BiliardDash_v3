<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Album
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Összes album lekérdezése létrehozási dátum szerint csökkenő sorrendben.
     *
     * A szervezői lista használja, ezért az archivált albumokat is adja -
     * azokat is kezelni kell tudni.
     *
     * @return array<array{id:string, name:string, season_id:?string, is_archived:int, cover_image_id:?string, image_count:int, created_at:string, season_name:?string}>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT a.id, a.name, a.season_id, a.is_archived, a.cover_image_id,
                    a.image_count, a.created_at,
                    s.name AS season_name
             FROM albums a
             LEFT JOIN seasons s ON s.id = a.season_id
             ORDER BY a.created_at DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Az albumok száma.
     */
    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM albums')->fetchColumn();
    }

    /**
     * Archivált albumok száma.
     */
    public function countArchived(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM albums WHERE is_archived = 1')->fetchColumn();
    }

    /**
     * Összes album a borítóképe adataival együtt, egyetlen lekérdezésben.
     *
     * A galéria listája minden albumot a borítóképével jelöl. Albumonkénti
     * külön képlekérdezés helyett LEFT JOIN-nal kérjük le a borítót, így a
     * lista N+1 helyett egyetlen lekérdezésből áll össze.
     *
     * A JOIN azért LEFT, mert az album lehet kép nélküli, és a
     * cover_image_id is mutathat már törölt képre.
     *
     * @param bool|null $archived null = mindegyik, false = csak az aktuális,
     *                            true = csak az archivált albumok
     * @return array<array{id:string, name:string, season_id:?string, is_archived:int, cover_image_id:?string, image_count:int, created_at:string, season_name:?string, season_starts_on:?string, cover_thumbnail_path:?string, cover_medium_path:?string, cover_full_path:?string, cover_alt_text:?string}>
     */
    public function findAllWithCover(?bool $archived = null): array
    {
        $where = match ($archived) {
            true => 'WHERE a.is_archived = 1',
            false => 'WHERE a.is_archived = 0',
            null => '',
        };

        // Az archívumban a szezon szerinti csoportosítás a rendezés alapja,
        // ezért a szezon kezdete elé kerül a created_at-nél
        $stmt = $this->db->query(
            'SELECT a.id, a.name, a.season_id, a.is_archived, a.cover_image_id,
                    a.image_count, a.created_at,
                    s.name AS season_name,
                    s.starts_on AS season_starts_on,
                    i.thumbnail_path AS cover_thumbnail_path,
                    i.medium_path AS cover_medium_path,
                    i.full_path AS cover_full_path,
                    i.alt_text AS cover_alt_text
             FROM albums a
             LEFT JOIN images i ON i.id = a.cover_image_id
             LEFT JOIN seasons s ON s.id = a.season_id
             ' . $where . '
             ORDER BY s.starts_on DESC, a.created_at DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Egy album lekérdezése ID alapján.
     *
     * @return array{id:string, name:string, season_id:?string, is_archived:int, cover_image_id:?string, image_count:int, created_at:string, season_name:?string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.name, a.season_id, a.is_archived, a.cover_image_id,
                    a.image_count, a.created_at,
                    s.name AS season_name
             FROM albums a
             LEFT JOIN seasons s ON s.id = a.season_id
             WHERE a.id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Új album létrehozása.
     */
    public function create(string $id, string $name): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO albums (id, name)
             VALUES (:id, :name)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
        ]);
    }

    /**
     * Album nevének módosítása.
     */
    public function updateName(string $id, string $name): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE albums SET name = :name WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
        ]);
    }

    /**
     * Album törlése.
     *
     * A hozzá tartozó képrekordok az images.album_id idegen kulcs
     * ON DELETE CASCADE szabálya miatt automatikusan törlődnek, a
     * feltöltött fájlokat viszont a GalleryService takarítja el.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM albums WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Album képszámának növelése eggyel.
     */
    public function incrementImageCount(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE albums SET image_count = image_count + 1 WHERE id = :id'
        );

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Album képszámának csökkentése eggyel.
     */
    public function decrementImageCount(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE albums SET image_count = image_count - 1 WHERE id = :id AND image_count > 0'
        );

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Album borítókép beállítása.
     */
    public function updateCoverImageId(string $id, ?string $coverImageId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE albums SET cover_image_id = :cover_image_id WHERE id = :id'
        );

        return $stmt->execute([
            ':cover_image_id' => $coverImageId,
            ':id' => $id,
        ]);
    }

    /**
     * Album archiválása vagy visszahelyezése.
     *
     * Az archivált album nem tűnik el: a galéria archívumában marad
     * elérhető, csak az aktuális listából kerül ki.
     */
    public function updateArchived(string $id, bool $archived): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE albums SET is_archived = :is_archived WHERE id = :id'
        );

        return $stmt->execute([
            ':is_archived' => $archived ? 1 : 0,
            ':id' => $id,
        ]);
    }

    /**
     * Album szezonjának beállítása. A null a "nincs megadva" állapot.
     */
    public function updateSeason(string $id, ?string $seasonId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE albums SET season_id = :season_id WHERE id = :id'
        );

        return $stmt->execute([
            ':season_id' => $seasonId,
            ':id' => $id,
        ]);
    }
}
