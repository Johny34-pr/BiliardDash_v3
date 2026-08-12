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
     * @param string|null $createdByUserId A rögzítő felhasználó azonosítója,
     *                                     vagy null vendégnevezés esetén. A nevező
     *                                     adatai ettől függetlenül a $data-ból jönnek,
     *                                     így egy fiók másnak is nevezhet.
     * @return array{id:string, competition_id:string, full_name:string, email:string, phone:string, registered_at:string}
     * @throws AppException
     */
    public function registerForCompetition(string $competitionId, array $data, ?string $createdByUserId = null): array
    {
        $competition = $this->competitionModel->findById($competitionId);

        if ($competition === null) {
            throw AppException::notFound('A verseny nem található');
        }

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
            $data['phone'],
            $createdByUserId
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
     * Teljes adatkörrel tér vissza (e-mail, telefon), ezért kizárólag
     * admin felületen használható. Nyilvános listához a
     * getPublicRegistrants() metódust kell hívni.
     *
     * @return array<array{full_name:string, email:string, phone:string, registered_at:string}>
     */
    public function getRegistrations(string $competitionId): array
    {
        return $this->registrationModel->findByCompetitionId($competitionId);
    }

    /**
     * Nyilvános nevezői lista: csak a nevek és a nevezés ideje.
     *
     * Az e-mail címet és a telefonszámot szándékosan nem adja vissza, mert
     * azok személyes adatok, és a nevezői lista belépés nélkül is látható.
     *
     * @return array<array{full_name:string, registered_at:string}>
     */
    public function getPublicRegistrants(string $competitionId): array
    {
        return $this->registrationModel->findPublicByCompetitionId($competitionId);
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
     * Egy felhasználó által rögzített nevezések, a verseny adataival együtt.
     *
     * Tartalmazza a saját nevezését és azokat is, amelyeket másnak vitt fel.
     *
     * @return array<array{id:string, competition_id:string, full_name:string, email:string, phone:string, registered_at:string, competition_name:string, competition_date:string, competition_venue:string, registration_deadline:string}>
     */
    public function getRegistrationsByUser(string $userId): array
    {
        return $this->registrationModel->findByUserId($userId);
    }

    /**
     * Nevezés törlése szervezői jogkörben.
     *
     * A felhasználói visszavonással szemben itt nincs jogosultsági és
     * határidő-ellenőrzés: a szervező bármelyik nevezést eltávolíthatja,
     * a határidő lejárta után is. Erre azért van szükség, mert lemondás
     * vagy hibás nevezés esetén a névsort utólag is rendezni kell.
     *
     * A nevezőszámot is csökkenti, hogy konzisztens maradjon.
     *
     * @return string A verseny azonosítója, ahová a nevezés tartozott
     * @throws AppException Ha a nevezés nem található.
     */
    public function deleteRegistrationAsAdmin(string $registrationId): string
    {
        $registration = $this->registrationModel->findById($registrationId);

        if ($registration === null) {
            throw AppException::notFound('A nevezés nem található');
        }

        $competitionId = $registration['competition_id'];

        $this->registrationModel->delete($registrationId);
        $this->competitionModel->decrementRegistrantCount($competitionId);

        return $competitionId;
    }

    /**
     * Felhasználó által rögzített nevezés törlése.
     *
     * Két feltételnek kell teljesülnie:
     *   1. A nevezést ez a felhasználó vitte fel (vendégnevezés nem törölhető így)
     *   2. A nevezési határidő még nem járt le - utána a szervező véglegesnek
     *      tekinti a névsort, ezért a törlés csak adminnál marad
     *
     * A nevezőszámot is csökkenti, hogy konzisztens maradjon.
     *
     * @throws AppException Ha a nevezés nem létezik, nem a felhasználóé,
     *                      vagy a határidő már lejárt.
     */
    public function deleteOwnRegistration(string $registrationId, string $userId): void
    {
        $registration = $this->registrationModel->findById($registrationId);

        if ($registration === null) {
            throw AppException::notFound('A nevezés nem található');
        }

        // 1. Jogosultság: csak a rögzítő törölheti
        if ($registration['created_by_user_id'] !== $userId) {
            throw AppException::forbidden('Ez a nevezés nem a te fiókodhoz tartozik');
        }

        $competition = $this->competitionModel->findById($registration['competition_id']);

        if ($competition === null) {
            throw AppException::notFound('A verseny nem található');
        }

        // 2. Határidő: lejárt határidő után nem törölhető
        if (new \DateTime($competition['registration_deadline']) < new \DateTime()) {
            throw AppException::deadlinePassed(
                'A nevezési határidő lejárt, a nevezés már nem vonható vissza'
            );
        }

        $this->registrationModel->delete($registrationId);
        $this->competitionModel->decrementRegistrantCount($registration['competition_id']);
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
