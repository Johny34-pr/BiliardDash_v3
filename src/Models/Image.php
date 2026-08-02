<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Image
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Egy album összes képének lekérdezése feltöltési dátum szerint.
     *
     * @return array<array{id:string, album_id:string, filename:string, thumbnail_path:string, full_path:string, alt_text:?string, uploaded_at:string}>
     */
    public function findByAlbumId(string $albumId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, album_id, filename, thumbnail_path, full_path, alt_text, uploaded_at
             FROM images
             WHERE album_id = :album_id
             ORDER BY uploaded_at DESC'
        );
        $stmt->execute([':album_id' => $albumId]);

        return $stmt->fetchAll();
    }

    /**
     * Egy kép lekérdezése ID alapján.
     *
     * @return array{id:string, album_id:string, filename:string, thumbnail_path:string, full_path:string, alt_text:?string, uploaded_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, album_id, filename, thumbnail_path, full_path, alt_text, uploaded_at
             FROM images
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Új kép létrehozása.
     */
    public function create(string $id, string $albumId, string $filename, string $thumbnailPath, string $fullPath, ?string $altText = null): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO images (id, album_id, filename, thumbnail_path, full_path, alt_text)
             VALUES (:id, :album_id, :filename, :thumbnail_path, :full_path, :alt_text)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':album_id' => $albumId,
            ':filename' => $filename,
            ':thumbnail_path' => $thumbnailPath,
            ':full_path' => $fullPath,
            ':alt_text' => $altText,
        ]);
    }

    /**
     * Kép törlése.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM images WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }
}
