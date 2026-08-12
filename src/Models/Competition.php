<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Competition
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Nyitott versenyek lekérdezése (határidő még nem járt le), dátum szerint növekvő sorrendben.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_deadline:string, registrant_count:int, created_at:string, updated_at:string}>
     */
    public function findOpen(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, date, venue, registration_deadline, registrant_count, created_at, updated_at
             FROM competitions
             WHERE registration_deadline > NOW()
             ORDER BY date ASC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Összes verseny lekérdezése dátum szerint csökkenő sorrendben.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_deadline:string, registrant_count:int, created_at:string, updated_at:string}>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, date, venue, registration_deadline, registrant_count, created_at, updated_at
             FROM competitions
             ORDER BY date DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Egy verseny lekérdezése ID alapján.
     *
     * @return array{id:string, name:string, date:string, venue:string, registration_deadline:string, registrant_count:int, created_at:string, updated_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, date, venue, registration_deadline, registrant_count, created_at, updated_at
             FROM competitions
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Új verseny létrehozása.
     */
    public function create(string $id, string $name, string $date, string $venue, string $registrationDeadline): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO competitions (id, name, date, venue, registration_deadline)
             VALUES (:id, :name, :date, :venue, :registration_deadline)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':date' => $date,
            ':venue' => $venue,
            ':registration_deadline' => $registrationDeadline,
        ]);
    }

    /**
     * Verseny frissítése.
     */
    public function update(string $id, string $name, string $date, string $venue, string $registrationDeadline): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE competitions
             SET name = :name, date = :date, venue = :venue, registration_deadline = :registration_deadline
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':date' => $date,
            ':venue' => $venue,
            ':registration_deadline' => $registrationDeadline,
        ]);
    }

    /**
     * Verseny törlése.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM competitions WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Verseny nevezőszámának növelése eggyel.
     */
    public function incrementRegistrantCount(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE competitions SET registrant_count = registrant_count + 1 WHERE id = :id'
        );

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Verseny nevezőszámának csökkentése eggyel.
     *
     * A registrant_count UNSIGNED, ezért a `> 0` feltétel véd az alulcsordulástól.
     */
    public function decrementRegistrantCount(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE competitions
             SET registrant_count = registrant_count - 1
             WHERE id = :id AND registrant_count > 0'
        );

        return $stmt->execute([':id' => $id]);
    }
}
