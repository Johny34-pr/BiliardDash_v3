<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Registration
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Egy verseny összes nevezésének lekérdezése.
     *
     * @return array<array{id:string, competition_id:string, created_by_user_id:?string, full_name:string, email:string, phone:string, registered_at:string}>
     */
    public function findByCompetitionId(string $competitionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, competition_id, created_by_user_id, full_name, email, phone, registered_at
             FROM registrations
             WHERE competition_id = :competition_id
             ORDER BY registered_at ASC'
        );
        $stmt->execute([':competition_id' => $competitionId]);

        return $stmt->fetchAll();
    }

    /**
     * Nyilvános nevezői lista: csak a név és a nevezés ideje.
     *
     * Az e-mail címet és a telefonszámot nem kérdezi le, mert ez a lista
     * belépés nélkül is elérhető.
     *
     * @return array<array{full_name:string, registered_at:string}>
     */
    public function findPublicByCompetitionId(string $competitionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT full_name, registered_at
             FROM registrations
             WHERE competition_id = :competition_id
             ORDER BY registered_at ASC'
        );
        $stmt->execute([':competition_id' => $competitionId]);

        return $stmt->fetchAll();
    }

    /**
     * Egy nevezés lekérdezése azonosító alapján.
     *
     * @return array{id:string, competition_id:string, created_by_user_id:?string, full_name:string, email:string, phone:string, registered_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, competition_id, created_by_user_id, full_name, email, phone, registered_at
             FROM registrations
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Egy felhasználó által rögzített nevezések, a verseny adataival együtt.
     *
     * A legközelebbi verseny kerül előre, hogy az aktuális nevezések
     * legyenek a lista élén.
     *
     * @return array<array{id:string, competition_id:string, full_name:string, email:string, phone:string, registered_at:string, competition_name:string, competition_date:string, competition_venue:string, registration_deadline:string}>
     */
    public function findByUserId(string $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT r.id, r.competition_id, r.full_name, r.email, r.phone, r.registered_at,
                    c.name  AS competition_name,
                    c.date  AS competition_date,
                    c.venue AS competition_venue,
                    c.registration_deadline
             FROM registrations r
             INNER JOIN competitions c ON c.id = r.competition_id
             WHERE r.created_by_user_id = :user_id
             ORDER BY c.date ASC, r.registered_at ASC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    }

    /**
     * Új nevezés létrehozása.
     *
     * @param string|null $createdByUserId A rögzítő felhasználó azonosítója,
     *                                     vagy null vendégnevezés esetén.
     */
    public function create(
        string $id,
        string $competitionId,
        string $fullName,
        string $email,
        string $phone,
        ?string $createdByUserId = null
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO registrations (id, competition_id, created_by_user_id, full_name, email, phone)
             VALUES (:id, :competition_id, :created_by_user_id, :full_name, :email, :phone)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':competition_id' => $competitionId,
            ':created_by_user_id' => $createdByUserId,
            ':full_name' => $fullName,
            ':email' => $email,
            ':phone' => $phone,
        ]);
    }

    /**
     * Nevezés keresése verseny ID és email alapján (duplikáció ellenőrzéshez).
     *
     * @return array{id:string, competition_id:string, created_by_user_id:?string, full_name:string, email:string, phone:string, registered_at:string}|null
     */
    public function findByCompetitionAndEmail(string $competitionId, string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, competition_id, created_by_user_id, full_name, email, phone, registered_at
             FROM registrations
             WHERE competition_id = :competition_id AND email = :email'
        );
        $stmt->execute([
            ':competition_id' => $competitionId,
            ':email' => $email,
        ]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Nevezés törlése.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM registrations WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Egy verseny nevezéseinek száma.
     */
    public function countByCompetition(string $competitionId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM registrations WHERE competition_id = :competition_id'
        );
        $stmt->execute([':competition_id' => $competitionId]);

        $result = $stmt->fetch();
        return (int) $result['count'];
    }
}
