<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Competition
{
    /**
     * A versenyek lekérdezéseiben használt oszloplista.
     *
     * Egy helyen definiálva, hogy a findOpen/findAll/findById ugyanazt az
     * adatkört adja vissza, és új oszlop hozzáadásakor ne csúszhassanak szét.
     */
    private const COLUMNS = 'id, name, date, venue, registration_opens_at, registration_deadline,
                    registrant_count, created_at, updated_at';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Nevezésre meghirdetett versenyek: a határidő még nem járt le.
     *
     * A még nem megnyílt nevezésű versenyek is szerepelnek a listában,
     * hogy a látogató előre lássa őket - a nevezés gomb helyett a nyitás
     * időpontja jelenik meg. Így a kiírás és a nevezés szétválik.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string, registrant_count:int, created_at:string, updated_at:string}>
     */
    public function findOpen(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             FROM competitions
             WHERE registration_deadline > NOW()
             ORDER BY date ASC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Összes verseny lekérdezése dátum szerint csökkenő sorrendben.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string, registrant_count:int, created_at:string, updated_at:string}>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             FROM competitions
             ORDER BY date DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Egy verseny lekérdezése ID alapján.
     *
     * @return array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string, registrant_count:int, created_at:string, updated_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . '
             FROM competitions
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Új verseny létrehozása.
     *
     * @param string|null $registrationOpensAt A nevezés nyitásának időpontja,
     *                                         vagy null, ha azonnal nyitott.
     */
    public function create(
        string $id,
        string $name,
        string $date,
        string $venue,
        string $registrationDeadline,
        ?string $registrationOpensAt = null
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO competitions (id, name, date, venue, registration_opens_at, registration_deadline)
             VALUES (:id, :name, :date, :venue, :registration_opens_at, :registration_deadline)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':date' => $date,
            ':venue' => $venue,
            ':registration_opens_at' => $registrationOpensAt,
            ':registration_deadline' => $registrationDeadline,
        ]);
    }

    /**
     * Verseny frissítése.
     *
     * @param string|null $registrationOpensAt A nevezés nyitásának időpontja,
     *                                         vagy null, ha azonnal nyitott.
     */
    public function update(
        string $id,
        string $name,
        string $date,
        string $venue,
        string $registrationDeadline,
        ?string $registrationOpensAt = null
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE competitions
             SET name = :name, date = :date, venue = :venue,
                 registration_opens_at = :registration_opens_at,
                 registration_deadline = :registration_deadline
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':date' => $date,
            ':venue' => $venue,
            ':registration_opens_at' => $registrationOpensAt,
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
