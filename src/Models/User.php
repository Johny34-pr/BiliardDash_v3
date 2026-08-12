<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Publikus felhasználói fiókok.
 *
 * Minden művelet prepared statementet használ (SQL injection védelem).
 * A jelszó kizárólag hash formában kerül tárolásra.
 */
class User
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Felhasználó keresése e-mail cím alapján (bejelentkezéshez).
     *
     * A password_hash mezőt is visszaadja, ezért csak a bejelentkezési
     * folyamatban használható.
     *
     * @return array{id:string, name:string, email:string, phone:string, password_hash:string, created_at:string}|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, phone, password_hash, created_at
             FROM users
             WHERE email = :email'
        );
        $stmt->execute([':email' => $email]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Felhasználó keresése azonosító alapján.
     *
     * A password_hash mezőt szándékosan nem adja vissza.
     *
     * @return array{id:string, name:string, email:string, phone:string, created_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, phone, created_at
             FROM users
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Új felhasználó létrehozása.
     *
     * @param string $passwordHash Már hashelt jelszó (password_hash())
     */
    public function create(string $id, string $name, string $email, string $phone, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (id, name, email, phone, password_hash)
             VALUES (:id, :name, :email, :phone, :password_hash)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':password_hash' => $passwordHash,
        ]);
    }

    /**
     * Létezik-e már fiók ezzel az e-mail címmel.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);

        return $stmt->fetch() !== false;
    }
}
