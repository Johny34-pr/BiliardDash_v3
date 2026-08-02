<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Models\Competition;
use App\Models\Registration;
use PDO;
use Ramsey\Uuid\Uuid;

class CompetitionService
{
    private Competition $competitionModel;
    private Registration $registrationModel;

    public function __construct(private PDO $db, private EmailService $emailService)
    {
        $this->competitionModel = new Competition($db);
        $this->registrationModel = new Registration($db);
    }

    /**
     * Összes verseny lekérdezése dátum szerint csökkenő sorrendben.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_deadline:string, registrant_count:int}>
     */
    public function getAllCompetitions(): array
    {
        return $this->competitionModel->findAll();
    }

    /**
     * Nyitott versenyek lekérdezése (jövőbeli határidejű), dátum szerinti növekvő sorrendben.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_deadline:string, registrant_count:int}>
     */
    public function getOpenCompetitions(): array
    {
        return $this->competitionModel->findOpen();
    }

    /**
     * Egy verseny lekérdezése ID alapján.
     *
     * @return array{id:string, name:string, date:string, venue:string, registration_deadline:string, registrant_count:int}|null
     */
    public function getCompetitionById(string $id): ?array
    {
        return $this->competitionModel->findById($id);
    }

    /**
     * Új verseny létrehozása.
     *
     * @return array{id:string, name:string, date:string, venue:string, registration_deadline:string}
     */
    public function createCompetition(array $data): array
    {
        $id = Uuid::uuid4()->toString();
        $name = $data['name'];
        $date = $data['date'];
        $venue = $data['venue'];
        $registrationDeadline = $data['registrationDeadline'];

        $this->competitionModel->create($id, $name, $date, $venue, $registrationDeadline);

        return $this->competitionModel->findById($id);
    }

    /**
     * Verseny frissítése.
     *
     * @return array{id:string, name:string, date:string, venue:string, registration_deadline:string}
     */
    public function updateCompetition(string $id, array $data): array
    {
        $name = $data['name'];
        $date = $data['date'];
        $venue = $data['venue'];
        $registrationDeadline = $data['registrationDeadline'];

        $this->competitionModel->update($id, $name, $date, $venue, $registrationDeadline);

        return $this->competitionModel->findById($id);
    }

    /**
     * Verseny törlése. A hozzá tartozó nevezések FK cascade révén törlődnek.
     */
    public function deleteCompetition(string $id): void
    {
        $this->competitionModel->delete($id);
    }

    /**
     * Nevezés rögzítése egy versenyre.
     *
     * 1. Határidő ellenőrzés
     * 2. Duplikáció ellenőrzés
     * 3. Nevezés mentése
     * 4. Nevezőszám növelése
     * 5. Visszaigazoló email küldés triggerelése
     *
     * @return array{id:string, competition_id:string, full_name:string, email:string, phone:string, registered_at:string}
     * @throws AppException
     */
    public function registerForCompetition(string $competitionId, array $data): array
    {
        $competition = $this->competitionModel->findById($competitionId);

        // 1. Határidő ellenőrzés
        if (new \DateTime($competition['registration_deadline']) < new \DateTime()) {
            throw AppException::deadlinePassed('A nevezési határidő lejárt');
        }

        // 2. Duplikáció ellenőrzés
        if ($this->checkDuplicateRegistration($competitionId, $data['email'])) {
            throw AppException::duplicateEntry('Ezzel az e-mail címmel már történt nevezés erre a versenyre');
        }

        // 3. Nevezés mentése
        $registrationId = Uuid::uuid4()->toString();
        $this->registrationModel->create(
            $registrationId,
            $competitionId,
            $data['fullName'],
            $data['email'],
            $data['phone']
        );

        // 4. Nevezőszám növelése
        $this->competitionModel->incrementRegistrantCount($competitionId);

        // 5. Email küldés triggerelése
        $this->emailService->sendRegistrationConfirmation($data['email'], [
            'competitionName' => $competition['name'],
            'competitionDate' => $competition['date'],
            'competitionVenue' => $competition['venue'],
            'fullName' => $data['fullName'],
            'email' => $data['email'],
            'phone' => $data['phone'],
        ]);

        // Visszatérés a rögzített nevezéssel
        return $this->registrationModel->findByCompetitionAndEmail($competitionId, $data['email']);
    }

    /**
     * Egy verseny összes nevezésének lekérdezése.
     *
     * @return array<array{full_name:string, email:string, phone:string, registered_at:string}>
     */
    public function getRegistrations(string $competitionId): array
    {
        return $this->registrationModel->findByCompetitionId($competitionId);
    }

    /**
     * CSV export generálása egy verseny nevezéseiből.
     * UTF-8 BOM + fejléc (Név,Email,Telefon) + sorok.
     */
    public function exportRegistrationsCsv(string $competitionId): string
    {
        $registrations = $this->registrationModel->findByCompetitionId($competitionId);

        // UTF-8 BOM
        $csv = "\xEF\xBB\xBF";

        // Fejléc sor
        $csv .= "Név,Email,Telefon\n";

        // Adatsorok
        foreach ($registrations as $reg) {
            $csv .= $this->escapeCsvField($reg['full_name']) . ','
                  . $this->escapeCsvField($reg['email']) . ','
                  . $this->escapeCsvField($reg['phone']) . "\n";
        }

        return $csv;
    }

    /**
     * Duplikáció ellenőrzés: van-e már azonos e-mail címmel nevezés adott versenyre.
     */
    public function checkDuplicateRegistration(string $competitionId, string $email): bool
    {
        return $this->registrationModel->findByCompetitionAndEmail($competitionId, $email) !== null;
    }

    /**
     * CSV mező megfelelő escape-elése: ha tartalmaz vesszőt, idézőjelet vagy sortörést,
     * idézőjelek közé teszi, és a belső idézőjeleket megduplázza.
     */
    private function escapeCsvField(string $field): string
    {
        if (str_contains($field, ',') || str_contains($field, '"') || str_contains($field, "\n") || str_contains($field, "\r")) {
            return '"' . str_replace('"', '""', $field) . '"';
        }

        return $field;
    }
}
