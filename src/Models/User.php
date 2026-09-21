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
    /**
     * A megjelenítéshez használt oszloplista.
     *
     * A password_hash szándékosan nincs benne: csak a bejelentkezési
     * folyamat kérdezi le, külön metódussal.
     */
    private const PUBLIC_COLUMNS = 'id, name, email, phone, city, created_at';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Felhasználó keresése e-mail cím alapján (bejelentkezéshez).
     *
     * A password_hash mezőt is visszaadja, ezért csak a bejelentkezési
     * folyamatban használható.
     *
     * @return array{id:string, name:string, email:string, phone:string, city:string, password_hash:string, created_at:string}|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, phone, city, password_hash, created_at
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
     * @return array{id:string, name:string, email:string, phone:string, city:string, created_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . '
             FROM users
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Összes fiók a szervezői felület listájához, legújabb elöl.
     *
     * A nevezésszám is kiszámolódik, hogy a listában látszódjon, kihez
     * tartozik nevezés - a törlés következményét ez teszi átláthatóvá.
     *
     * @return array<array{id:string, name:string, email:string, phone:string, city:string, created_at:string, registration_count:int}>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT u.id, u.name, u.email, u.phone, u.city, u.created_at,
                    COUNT(r.id) AS registration_count
             FROM users u
             LEFT JOIN registrations r ON r.created_by_user_id = u.id
             GROUP BY u.id, u.name, u.email, u.phone, u.city, u.created_at
             ORDER BY u.created_at DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Új felhasználó létrehozása.
     *
     * @param string $passwordHash Már hashelt jelszó (password_hash())
     */
    public function create(
        string $id,
        string $name,
        string $email,
        string $phone,
        string $city,
        string $passwordHash
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO users (id, name, email, phone, city, password_hash)
             VALUES (:id, :name, :email, :phone, :city, :password_hash)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':city' => $city,
            ':password_hash' => $passwordHash,
        ]);
    }

    /**
     * Fiók adatainak módosítása (jelszó nélkül).
     */
    public function update(string $id, string $name, string $email, string $phone, string $city): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET name = :name, email = :email, phone = :phone, city = :city
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':city' => $city,
        ]);
    }

    /**
     * Jelszó felülírása.
     *
     * @param string $passwordHash Már hashelt jelszó
     */
    public function updatePassword(string $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':password_hash' => $passwordHash,
        ]);
    }

    /**
     * Fiók törlése.
     *
     * A felhasználó nevezései megmaradnak: a registrations.created_by_user_id
     * idegen kulcs ON DELETE SET NULL szabálya miatt vendégnevezéssé válnak,
     * így a szervező névsora nem csorbul.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');

        return $stmt->execute([':id' => $id]);
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

    /**
     * Használja-e más fiók ezt az e-mail címet.
     *
     * Szerkesztéskor kell: a saját, változatlanul hagyott cím nem lehet
     * ütközés, egy másik fiók címe viszont igen.
     */
    public function emailExistsForOther(string $email, string $exceptId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM users WHERE email = :email AND id <> :id LIMIT 1'
        );
        $stmt->execute([':email' => $email, ':id' => $exceptId]);

        return $stmt->fetch() !== false;
    }
}
