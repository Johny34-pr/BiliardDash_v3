<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Fórum topikok (témák).
 *
 * Egy topik címből és nyitó bejegyzésből áll, a hozzászólások hozzá tartoznak.
 * A publikus lekérdezések kihagyják az elrejtett (moderált) topikokat.
 */
class Topic
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Látható topikok, a legutóbbi aktivitás szerint.
     *
     * @return array<array{id:string, user_id:?string, author_name:string, title:string, body:string, comment_count:int, is_locked:int, created_at:string, last_activity_at:string}>
     */
    public function findVisible(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, author_name, title, body, comment_count,
                    is_locked, created_at, last_activity_at
             FROM topics
             WHERE is_hidden = 0
             ORDER BY last_activity_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countVisible(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) AS count FROM topics WHERE is_hidden = 0');
        $result = $stmt->fetch();

        return (int) $result['count'];
    }

    /**
     * Összes topik moderáláshoz, az elrejtettekkel együtt.
     *
     * @return array<array{id:string, user_id:?string, author_name:string, title:string, body:string, comment_count:int, is_locked:int, is_hidden:int, created_at:string}>
     */
    public function findAllForModeration(int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, author_name, title, body, comment_count,
                    is_locked, is_hidden, created_at, last_activity_at
             FROM topics
             ORDER BY last_activity_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Egy topik lekérdezése azonosító alapján.
     *
     * @return array{id:string, user_id:?string, author_name:string, title:string, body:string, comment_count:int, is_locked:int, is_hidden:int, created_at:string, last_activity_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, author_name, title, body, comment_count,
                    is_locked, is_hidden, created_at, last_activity_at
             FROM topics
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Új topik létrehozása.
     */
    public function create(
        string $id,
        ?string $userId,
        string $authorName,
        string $title,
        string $body,
        ?string $ipHash = null
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO topics (id, user_id, author_name, title, body, ip_hash)
             VALUES (:id, :user_id, :author_name, :title, :body, :ip_hash)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':author_name' => $authorName,
            ':title' => $title,
            ':body' => $body,
            ':ip_hash' => $ipHash,
        ]);
    }

    /**
     * Topik elrejtése vagy visszaállítása (moderálás).
     */
    public function setHidden(string $id, bool $hidden): bool
    {
        $stmt = $this->db->prepare('UPDATE topics SET is_hidden = :hidden WHERE id = :id');

        return $stmt->execute([':hidden' => $hidden ? 1 : 0, ':id' => $id]);
    }

    /**
     * Topik lezárása vagy újranyitása (moderálás).
     *
     * A lezárt topik nem fogad új hozzászólást, de olvasható marad.
     */
    public function setLocked(string $id, bool $locked): bool
    {
        $stmt = $this->db->prepare('UPDATE topics SET is_locked = :locked WHERE id = :id');

        return $stmt->execute([':locked' => $locked ? 1 : 0, ':id' => $id]);
    }

    /**
     * Topik végleges törlése. A hozzászólásai kaszkádban törlődnek.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM topics WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * A hozzászólásszámláló és a legutóbbi aktivitás frissítése.
     *
     * A számlálót szándékosan újraszámoljuk a hozzászólásokból, nem
     * növeljük, így nem tud elcsúszni a valós állapottól. Az elrejtett
     * hozzászólások nem számítanak bele.
     */
    public function refreshActivity(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE topics
             SET comment_count = (
                     SELECT COUNT(*) FROM comments c
                     WHERE c.topic_id = topics.id AND c.is_hidden = 0
                 ),
                 last_activity_at = COALESCE(
                     (SELECT MAX(c.created_at) FROM comments c WHERE c.topic_id = topics.id),
                     created_at
                 )
             WHERE id = :id'
        );

        return $stmt->execute([':id' => $id]);
    }
}
