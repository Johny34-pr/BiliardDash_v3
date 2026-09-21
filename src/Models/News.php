<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class News
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Legfrissebb hírek lekérdezése publikálási dátum szerint csökkenő sorrendben.
     *
     * @return array<array{id:string, title:string, summary:?string, published_at:string, created_at:string, updated_at:string}>
     */
    /**
     * A hírek száma.
     *
     * Az áttekintő korábban a teljes híranyagot betöltötte, csak hogy
     * megszámolja - ez a lekérdezés csak a számot kéri le.
     */
    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM news')->fetchColumn();
    }

    public function findLatest(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, summary, published_at, created_at, updated_at
             FROM news
             ORDER BY published_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Egy hír lekérdezése ID alapján.
     *
     * @return array{id:string, title:string, content:string, summary:?string, published_at:string, created_at:string, updated_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, content, summary, published_at, created_at, updated_at
             FROM news
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Új hír létrehozása.
     */
    public function create(string $id, string $title, string $content, string $summary, string $publishedAt): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO news (id, title, content, summary, published_at)
             VALUES (:id, :title, :content, :summary, :published_at)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':content' => $content,
            ':summary' => $summary,
            ':published_at' => $publishedAt,
        ]);
    }

    /**
     * Hír frissítése.
     */
    public function update(string $id, string $title, string $content, string $summary): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE news
             SET title = :title, content = :content, summary = :summary
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':content' => $content,
            ':summary' => $summary,
        ]);
    }

    /**
     * Hír törlése.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM news WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }
}
