<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Hozzászólásokra adott szavazatok.
 *
 * Egy szavazót a voter_key azonosít ("user:{id}" vagy "guest:{token}"),
 * és a UNIQUE (comment_id, voter_key) megkötés biztosítja, hogy egy
 * hozzászólásra csak egy szavazata legyen.
 */
class CommentVote
{
    public const UP = 1;
    public const DOWN = -1;

    public function __construct(private PDO $db)
    {
    }

    /**
     * Egy szavazó adott hozzászólásra vonatkozó szavazata.
     *
     * @return array{id:string, comment_id:string, voter_key:string, value:int}|null
     */
    public function findByCommentAndVoter(string $commentId, string $voterKey): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, comment_id, voter_key, value
             FROM comment_votes
             WHERE comment_id = :comment_id AND voter_key = :voter_key'
        );
        $stmt->execute([
            ':comment_id' => $commentId,
            ':voter_key' => $voterKey,
        ]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Egy szavazó szavazatai a megadott hozzászólásokra.
     *
     * A megjelenítéshez kell, hogy kiemelhető legyen, mire szavazott már.
     *
     * @param array<string> $commentIds
     * @return array<string,int> hozzászólás azonosító => szavazat értéke
     */
    public function findVotesByVoter(array $commentIds, string $voterKey): array
    {
        if ($commentIds === []) {
            return [];
        }

        // Paraméterezett IN lista: a helyőrzők száma a bemenettől függ
        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));

        $stmt = $this->db->prepare(
            "SELECT comment_id, value
             FROM comment_votes
             WHERE voter_key = ? AND comment_id IN ({$placeholders})"
        );
        $stmt->execute([$voterKey, ...$commentIds]);

        $votes = [];
        foreach ($stmt->fetchAll() as $row) {
            $votes[$row['comment_id']] = (int) $row['value'];
        }

        return $votes;
    }

    public function create(string $id, string $commentId, string $voterKey, ?string $userId, int $value): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comment_votes (id, comment_id, voter_key, user_id, value)
             VALUES (:id, :comment_id, :voter_key, :user_id, :value)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':comment_id' => $commentId,
            ':voter_key' => $voterKey,
            ':user_id' => $userId,
            ':value' => $value,
        ]);
    }

    public function updateValue(string $id, int $value): bool
    {
        $stmt = $this->db->prepare('UPDATE comment_votes SET value = :value WHERE id = :id');

        return $stmt->execute([':value' => $value, ':id' => $id]);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM comment_votes WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }
}
