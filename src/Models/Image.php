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
     * Albumonként a legfrissebb kép, több albumra egyszerre.
     *
     * Akkor kell, ha egy albumnak van képe, de nincs beállított borítója:
     * ilyenkor a legutóbb feltöltött kép szolgál borítóként. Egyetlen
     * lekérdezés, hogy a galéria listája ne szaladjon N+1-be.
     *
     * @param array<string> $albumIds
     * @return array<string, array{id:string, thumbnail_path:string, full_path:string, alt_text:?string}>
     *         Album azonosító => a hozzá tartozó kép
     */
    public function findLatestByAlbumIds(array $albumIds): array
    {
        if ($albumIds === []) {
            return [];
        }

        // A helyőrzők száma a lista hosszától függ, de az értékek kötve
        // mennek, nem a lekérdezés szövegébe fűzve.
        $placeholders = implode(', ', array_fill(0, count($albumIds), '?'));

        $stmt = $this->db->prepare(
            'SELECT album_id, id, thumbnail_path, full_path, alt_text
             FROM images
             WHERE album_id IN (' . $placeholders . ')
             ORDER BY uploaded_at DESC'
        );
        $stmt->execute(array_values($albumIds));

        $latest = [];
        foreach ($stmt->fetchAll() as $row) {
            // A rendezés miatt az első találat a legfrissebb; a későbbieket
            // ugyanahhoz az albumhoz már nem írjuk felül
            if (!isset($latest[$row['album_id']])) {
                $latest[$row['album_id']] = [
                    'id' => $row['id'],
                    'thumbnail_path' => $row['thumbnail_path'],
                    'full_path' => $row['full_path'],
                    'alt_text' => $row['alt_text'],
                ];
            }
        }

        return $latest;
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
