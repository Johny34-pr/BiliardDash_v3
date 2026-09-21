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
     * @return array<array{id:string, name:string, cover_image_id:?string, image_count:int, created_at:string}>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, cover_image_id, image_count, created_at
             FROM albums
             ORDER BY created_at DESC'
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
     * Összes album a borítóképe adataival együtt, egyetlen lekérdezésben.
     *
     * A galéria listája minden albumot a borítóképével jelöl. Albumonkénti
     * külön képlekérdezés helyett LEFT JOIN-nal kérjük le a borítót, így a
     * lista N+1 helyett egyetlen lekérdezésből áll össze.
     *
     * A JOIN azért LEFT, mert az album lehet kép nélküli, és a
     * cover_image_id is mutathat már törölt képre.
     *
     * @return array<array{id:string, name:string, cover_image_id:?string, image_count:int, created_at:string, cover_thumbnail_path:?string, cover_full_path:?string, cover_alt_text:?string}>
     */
    public function findAllWithCover(): array
    {
        $stmt = $this->db->query(
            'SELECT a.id, a.name, a.cover_image_id, a.image_count, a.created_at,
                    i.thumbnail_path AS cover_thumbnail_path,
                    i.full_path AS cover_full_path,
                    i.alt_text AS cover_alt_text
             FROM albums a
             LEFT JOIN images i ON i.id = a.cover_image_id
             ORDER BY a.created_at DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Egy album lekérdezése ID alapján.
     *
     * @return array{id:string, name:string, cover_image_id:?string, image_count:int, created_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, cover_image_id, image_count, created_at
             FROM albums
             WHERE id = :id'
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
}
