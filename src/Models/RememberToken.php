<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * A "belépési adatok megjegyzése" tokenjei.
 *
 * Osztott token séma: a sütiben egy nyilvános selector és egy titkos
 * validator utazik. Az adatbázis csak a validator lenyomatát tárolja, így
 * egy kiszivárgott adatbázis önmagában nem elég a bejelentkezéshez.
 *
 * A keresés a selector szerint indexelt: enélkül minden sort végig kellene
 * próbálni, ami időzítéses támadásra adna felületet.
 */
class RememberToken
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Token keresése a nyilvános selector alapján.
     *
     * @return array{id:string, user_id:string, selector:string, token_hash:string, expires_at:string}|null
     */
    public function findBySelector(string $selector): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, user_id, selector, token_hash, expires_at
             FROM remember_tokens
             WHERE selector = :selector'
        );
        $stmt->execute([':selector' => $selector]);

        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Új token mentése.
     *
     * @param string $tokenHash A validator SHA-256 lenyomata
     * @param string $expiresAt 'Y-m-d H:i:s' formátumú lejárat
     */
    public function create(
        string $id,
        string $userId,
        string $selector,
        string $tokenHash,
        string $expiresAt
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO remember_tokens (id, user_id, selector, token_hash, expires_at)
             VALUES (:id, :user_id, :selector, :token_hash, :expires_at)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':selector' => $selector,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);
    }

    /**
     * A használat idejének feljegyzése.
     *
     * Nem biztonsági eszköz, hanem üzemeltetési: ebből látszik, mely
     * tokenek élnek még valójában.
     */
    public function touch(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE remember_tokens SET last_used_at = NOW() WHERE id = :id'
        );

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Egy token törlése (kilépés az adott eszközön).
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM remember_tokens WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Egy felhasználó összes tokenjének törlése.
     *
     * Jelszóváltás és fióktörlés esetén kell: ilyenkor minden eszközön
     * meg kell szűnnie az automatikus belépésnek.
     */
    public function deleteAllForUser(string $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id');

        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * A lejárt tokenek eltakarítása.
     *
     * @return int A törölt sorok száma
     */
    public function deleteExpired(): int
    {
        $stmt = $this->db->prepare('DELETE FROM remember_tokens WHERE expires_at < NOW()');
        $stmt->execute();

        return $stmt->rowCount();
    }
}
