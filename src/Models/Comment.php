<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Fórum hozzászólások.
 *
 * A publikus lekérdezések kihagyják az elrejtett (moderált) hozzászólásokat,
 * az admin lekérdezés viszont mindet visszaadja.
 */
class Comment
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Egy topik látható hozzászólásai, időrendben (legkorábbi elöl).
     *
     * A topikon belül az időrend olvashatóbb, mint a fordított sorrend:
     * a beszélgetés így felülről lefelé követhető.
     *
     * @return array<array{id:string, topic_id:string, user_id:?string, author_name:string, body:string, upvotes:int, downvotes:int, created_at:string}>
     */
    public function findVisibleByTopic(string $topicId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, topic_id, user_id, author_name, body, upvotes, downvotes, created_at
             FROM comments
             WHERE topic_id = :topic_id AND is_hidden = 0
             ORDER BY created_at ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':topic_id', $topicId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Egy topik látható hozzászólásainak száma (lapozáshoz).
     */
    public function countVisibleByTopic(string $topicId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS count FROM comments WHERE topic_id = :topic_id AND is_hidden = 0'
        );
        $stmt->execute([':topic_id' => $topicId]);
        $result = $stmt->fetch();

        return (int) $result['count'];
    }

    /**
     * Összes hozzászólás moderáláshoz, az elrejtettekkel együtt.
     *
     * @return array<array{id:string, user_id:?string, author_name:string, body:string, is_hidden:int, created_at:string}>
     */
    public function findAllForModeration(int $limit = 300): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.topic_id, c.user_id, c.author_name, c.body,
                    c.upvotes, c.downvotes, c.is_hidden, c.created_at,
                    t.title AS topic_title
             FROM comments c
             INNER JOIN topics t ON t.id = c.topic_id
             ORDER BY c.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Egy hozzászólás lekérdezése azonosító alapján.
     *
     * @return array{id:string, user_id:?string, author_name:string, body:string, is_hidden:int, created_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, topic_id, user_id, author_name, body, upvotes, downvotes, is_hidden, created_at
             FROM comments
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * A szavazatszámlálók újraszámolása a comment_votes táblából.
     *
     * Szándékosan nem növelünk/csökkentünk, hanem újraszámolunk, így a
     * gyorsított számlálók nem tudnak elcsúszni a valós szavazatoktól.
     */
    public function refreshVoteCounts(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE comments
             SET upvotes = (SELECT COUNT(*) FROM comment_votes v WHERE v.comment_id = comments.id AND v.value = 1),
                 downvotes = (SELECT COUNT(*) FROM comment_votes v WHERE v.comment_id = comments.id AND v.value = -1)
             WHERE id = :id'
        );

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Új hozzászólás létrehozása egy topikban.
     *
     * @param string      $topicId A topik, amelyhez a hozzászólás tartozik
     * @param string|null $userId  A hozzászóló fiókja, vagy null vendégként
     * @param string|null $ipHash  Az IP cím hash-e visszaélések kezeléséhez
     */
    public function create(
        string $id,
        string $topicId,
        ?string $userId,
        string $authorName,
        string $body,
        ?string $ipHash = null
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO comments (id, topic_id, user_id, author_name, body, ip_hash)
             VALUES (:id, :topic_id, :user_id, :author_name, :body, :ip_hash)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':topic_id' => $topicId,
            ':user_id' => $userId,
            ':author_name' => $authorName,
            ':body' => $body,
            ':ip_hash' => $ipHash,
        ]);
    }

    /**
     * Hozzászólás elrejtése vagy visszaállítása (moderálás).
     */
    public function setHidden(string $id, bool $hidden): bool
    {
        $stmt = $this->db->prepare('UPDATE comments SET is_hidden = :hidden WHERE id = :id');

        return $stmt->execute([
            ':hidden' => $hidden ? 1 : 0,
            ':id' => $id,
        ]);
    }

    /**
     * Hozzászólás végleges törlése.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM comments WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }
}
