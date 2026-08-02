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
     * @return array<array{id:string, competition_id:string, full_name:string, email:string, phone:string, registered_at:string}>
     */
    public function findByCompetitionId(string $competitionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, competition_id, full_name, email, phone, registered_at
             FROM registrations
             WHERE competition_id = :competition_id
             ORDER BY registered_at ASC'
        );
        $stmt->execute([':competition_id' => $competitionId]);

        return $stmt->fetchAll();
    }

    /**
     * Új nevezés létrehozása.
     */
    public function create(string $id, string $competitionId, string $fullName, string $email, string $phone): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO registrations (id, competition_id, full_name, email, phone)
             VALUES (:id, :competition_id, :full_name, :email, :phone)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':competition_id' => $competitionId,
            ':full_name' => $fullName,
            ':email' => $email,
            ':phone' => $phone,
        ]);
    }

    /**
     * Nevezés keresése verseny ID és email alapján (duplikáció ellenőrzéshez).
     *
     * @return array{id:string, competition_id:string, full_name:string, email:string, phone:string, registered_at:string}|null
     */
    public function findByCompetitionAndEmail(string $competitionId, string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, competition_id, full_name, email, phone, registered_at
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
