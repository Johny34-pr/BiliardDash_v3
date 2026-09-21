<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Szerkeszthető tartalmi oldalak (Rólunk, Emlékoldal, Adatkezelési tájékoztató).
 *
 * Az oldalak fix slug alatt élnek, mert a menü és a lábléc közvetlenül ezekre
 * hivatkozik. Ezért nincs create() és delete(): új oldal felvétele migrációval
 * történik, a szerkesztő felület csak a címet, a tartalmat és a leírást írja.
 * Így egy elgépelt slug nem tud törött menüpontot csinálni.
 */
class Page
{
    /**
     * Az oldalak lekérdezésében használt oszloplista.
     */
    private const COLUMNS = 'id, slug, title, content, meta_description, created_at, updated_at';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Összes oldal, cím szerint rendezve.
     *
     * @return array<array{id:string, slug:string, title:string, content:string, meta_description:?string, created_at:string, updated_at:string}>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . ' FROM pages ORDER BY title ASC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Egy oldal a publikus útvonal alapján.
     *
     * @return array{id:string, slug:string, title:string, content:string, meta_description:?string, created_at:string, updated_at:string}|null
     */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . ' FROM pages WHERE slug = :slug'
        );
        $stmt->execute([':slug' => $slug]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Egy oldal azonosító alapján.
     *
     * @return array{id:string, slug:string, title:string, content:string, meta_description:?string, created_at:string, updated_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . ' FROM pages WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Oldal tartalmának frissítése. A slug szándékosan nem módosítható.
     */
    public function update(string $id, string $title, string $content, ?string $metaDescription): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE pages
             SET title = :title, content = :content, meta_description = :meta_description
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':content' => $content,
            ':meta_description' => $metaDescription,
        ]);
    }
}
