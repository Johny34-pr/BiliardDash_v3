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
     * @return array<array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string, registrant_count:int}>
     */
    public function getAllCompetitions(): array
    {
        return $this->competitionModel->findAll();
    }

    /**
     * Meghirdetett versenyek: a nevezési határidő még nem járt le.
     *
     * A még meg nem nyílt nevezésű versenyek is benne vannak, a nevezés
     * állapotát a hasRegistrationOpened() dönti el.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string, registrant_count:int}>
     */
    public function getOpenCompetitions(): array
    {
        return $this->competitionModel->findOpen();
    }

    /**
     * Közelgő versenyek: a verseny napja még nem volt.
     *
     * A "Versenyeink > Közelgő versenyek" oldal és a nevezési lista is ezt
     * használja. A szűrés a verseny DÁTUMÁRA megy, nem a nevezési határidőre,
     * ezért a verseny a határidő lejárta után is látható marad a verseny
     * napjáig - a nevezői névsor így a helyszínen is elérhető.
     *
     * @return array<array<string, mixed>>
     */
    public function getUpcomingCompetitions(): array
    {
        return $this->competitionModel->findUpcoming();
    }

    /**
     * Lezárult versenyek, a legfrissebb elöl.
     *
     * @return array<array<string, mixed>>
     */
    public function getPastCompetitions(): array
    {
        return $this->competitionModel->findPast();
    }

    /**
     * Jegyzőkönyv feltöltése egy versenyhez.
     *
     * A fájl ellenőrzését és mentését a MediaService végzi: a típust a
     * tartalomból állapítja meg, a nevet újragenerálja, és a feltöltési
     * könyvtárban a szkriptfuttatás tiltva van. Itt csak az eredeti nevet
     * őrizzük meg, hogy a letöltés beszédes fájlnevet adjon.
     *
     * A korábbi jegyzőkönyv fájlja törlődik, különben minden újratöltés
     * árván hagyna egy fájlt a lemezen.
     *
     * @param array $file A $_FILES egy eleme
     * @throws AppException Ha a verseny nem létezik, vagy a fájl érvénytelen.
     */
    public function uploadProtocol(string $competitionId, array $file, MediaService $mediaService): void
    {
        $competition = $this->competitionModel->findById($competitionId);

        if ($competition === null) {
            throw AppException::notFound('A verseny nem található');
        }

        $stored = $mediaService->storeDocument($file);

        // Az eredeti név megtisztítva: a letöltési fejlécbe kerül, ezért nem
        // tartalmazhat útvonalat vagy vezérlőkaraktert
        $originalName = $this->sanitizeFilename((string) ($file['name'] ?? 'jegyzokonyv'));

        $previousPath = $competition['protocol_path'] ?? null;

        $this->competitionModel->setProtocol(
            $competitionId,
            $stored['url'],
            $originalName,
            $stored['size']
        );

        if ($previousPath !== null && $previousPath !== $stored['url']) {
            $this->removeProtocolFile($previousPath);
        }
    }

    /**
     * Jegyzőkönyv törlése: a fájl és a hivatkozás is.
     *
     * @throws AppException Ha a verseny nem létezik.
     */
    public function deleteProtocol(string $competitionId): void
    {
        $competition = $this->competitionModel->findById($competitionId);

        if ($competition === null) {
            throw AppException::notFound('A verseny nem található');
        }

        $path = $competition['protocol_path'] ?? null;

        $this->competitionModel->clearProtocol($competitionId);

        if ($path !== null) {
            $this->removeProtocolFile($path);
        }
    }

    /**
     * A nyilvános nevezői lista CSV formában.
     *
     * Szándékosan CSAK a nyilvános adatkört tartalmazza (sorszám, név,
     * nevezés ideje) - ugyanazt, ami az oldalon is látszik. Az e-mail címes,
     * telefonszámos export az admin felületen marad.
     *
     * UTF-8 BOM-mal, hogy az Excel az ékezeteket helyesen nyissa meg.
     */
    public function exportPublicRegistrantsCsv(string $competitionId): string
    {
        $registrants = $this->getPublicRegistrants($competitionId);

        $csv = "\xEF\xBB\xBF";
        $csv .= "Sorszám,Név,Nevezés ideje\n";

        foreach ($registrants as $index => $registrant) {
            $csv .= ($index + 1) . ','
                . $this->escapeCsvField($registrant['full_name']) . ','
                . $this->escapeCsvField($registrant['registered_at']) . "\n";
        }

        return $csv;
    }

    /**
     * Fájlnév megtisztítása a letöltési fejléchez.
     *
     * Az útvonal-részeket és a vezérlőkaraktereket eltávolítjuk: a név a
     * Content-Disposition fejlécbe kerül, ahol egy sortörés fejléc-injektálást
     * tenne lehetővé.
     */
    private function sanitizeFilename(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = (string) preg_replace('/[\x00-\x1F\x7F"]+/u', '', $name);
        $name = trim($name);

        if ($name === '') {
            return 'jegyzokonyv';
        }

        return mb_substr($name, 0, 200);
    }

    /**
     * A jegyzőkönyv fájljának törlése a lemezről.
     *
     * Védelem a könyvtárból kilépés ellen: csak a feltöltési könyvtáron belüli
     * útvonalat fogadjuk el. Az adatbázisból jövő érték elvileg mindig onnan
     * származik, de egy elírt vagy manipulált bejegyzés különben bármely
     * fájlt törölhetné.
     */
    private function removeProtocolFile(string $relativePath): void
    {
        $publicRoot = dirname(__DIR__, 2) . '/public';
        $uploadsRoot = realpath($publicRoot . '/uploads');
        $target = realpath($publicRoot . '/' . ltrim($relativePath, '/'));

        if ($uploadsRoot === false || $target === false) {
            return;
        }

        if (!str_starts_with($target, $uploadsRoot . DIRECTORY_SEPARATOR)) {
            error_log('[CompetitionService] Jegyzőkönyv törlés megtagadva (útvonal): ' . $relativePath);
            return;
        }

        if (is_file($target)) {
            @unlink($target);
        }
    }

    /**
     * Egy verseny lekérdezése ID alapján.
     *
     * @return array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string, registrant_count:int}|null
     */
    /**
     * A meghirdetett versenyek száma (az áttekintő statisztikához).
     */
    public function countOpenCompetitions(): int
    {
        return $this->competitionModel->countOpen();
    }

    public function getCompetitionById(string $id): ?array
    {
        return $this->competitionModel->findById($id);
    }

    /**
     * Új verseny létrehozása.
     *
     * @return array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string}
     */
    public function createCompetition(array $data): array
    {
        $id = Uuid::uuid4()->toString();
        $name = $data['name'];
        $date = $data['date'];
        $venue = $data['venue'];
        $registrationDeadline = $data['registrationDeadline'];
        $registrationOpensAt = $this->normalizeOpensAt($data['registrationOpensAt'] ?? null);

        $this->competitionModel->create($id, $name, $date, $venue, $registrationDeadline, $registrationOpensAt);

        return $this->competitionModel->findById($id);
    }

    /**
     * Verseny frissítése.
     *
     * @return array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string}
     */
    public function updateCompetition(string $id, array $data): array
    {
        $name = $data['name'];
        $date = $data['date'];
        $venue = $data['venue'];
        $registrationDeadline = $data['registrationDeadline'];
        $registrationOpensAt = $this->normalizeOpensAt($data['registrationOpensAt'] ?? null);

        $this->competitionModel->update($id, $name, $date, $venue, $registrationDeadline, $registrationOpensAt);

        return $this->competitionModel->findById($id);
    }

    /**
     * Az űrlapról érkező nyitódátum előkészítése tárolásra.
     *
     * Az üresen hagyott mező azt jelenti, hogy a nevezés azonnal nyitott,
     * ezt az adatbázisban NULL jelöli - nem üres string, mert abból a
     * MySQL '0000-00-00' dátumot csinálna.
     */
    private function normalizeOpensAt(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Megnyílt-e már a nevezés.
     *
     * Nyitódátum nélkül (NULL) a nevezés a verseny kiírásától nyitott,
     * ezért ilyenkor igazat ad vissza.
     */
    public function hasRegistrationOpened(array $competition): bool
    {
        $opensAt = $competition['registration_opens_at'] ?? null;

        if ($opensAt === null || $opensAt === '') {
            return true;
        }

        return new \DateTime($opensAt) <= new \DateTime();
    }

    /**
     * Lejárt-e a nevezési határidő.
     */
    public function isDeadlinePassed(array $competition): bool
    {
        return new \DateTime($competition['registration_deadline']) < new \DateTime();
    }

    /**
     * Fogad-e a verseny nevezést ebben a pillanatban.
     *
     * Két feltétel együtt: a nyitódátum már elmúlt, és a határidő még nem.
     */
    public function isRegistrationOpen(array $competition): bool
    {
        return $this->hasRegistrationOpened($competition) && !$this->isDeadlinePassed($competition);
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
     * 1. Nevezési időablak ellenőrzése (nyitódátum és határidő)
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

        // 1a. Nyitódátum: a megnyílás előtt nem fogadunk nevezést
        if (!$this->hasRegistrationOpened($competition)) {
            throw AppException::registrationNotOpen('A nevezés még nem nyílt meg erre a versenyre');
        }

        // 1b. Határidő ellenőrzés
        if ($this->isDeadlinePassed($competition)) {
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
        /*$this->emailService->sendRegistrationConfirmation($data['email'], [
            'competitionName' => $competition['name'],
            'competitionDate' => $competition['date'],
            'competitionVenue' => $competition['venue'],
            'fullName' => $data['fullName'],
            'email' => $data['email'],
            'phone' => $data['phone'],
        ]);*/

        // Visszatérés a rögzített nevezéssel
        return $this->registrationModel->findByCompetitionAndEmail($competitionId, $data['email']);
    }

    /**
     * Saját nevezés rögzítése belépett felhasználóként.
     *
     * A nevező NEVE és E-MAIL CÍME a fiókból származik, nem az űrlapról:
     * mindenki csak a saját nevében nevezhet. Az űrlapon egyedül a
     * telefonszám szerkeszthető, mert az eltérhet a fiókban tárolttól (más
     * készülék, elérhető szám a verseny napján), és a szervezőnek a
     * használható számra van szüksége.
     *
     * A duplikációt a fiók szerint vizsgáljuk, nem e-mail szerint: így egy
     * másik címmel sem lehet kétszer nevezni ugyanarra a versenyre.
     *
     * @param array{id:string, name:string, email:string} $user A belépett felhasználó
     * @return array{id:string, competition_id:string, full_name:string, email:string, phone:string, registered_at:string}
     * @throws AppException
     */
    public function registerSelf(string $competitionId, array $user, string $phone): array
    {
        if ($this->hasUserRegistered($competitionId, $user['id'])) {
            throw AppException::duplicateEntry('Erre a versenyre már neveztél');
        }

        return $this->registerForCompetition(
            $competitionId,
            [
                'fullName' => $user['name'],
                'email' => $user['email'],
                'phone' => $phone,
            ],
            $user['id']
        );
    }

    /**
     * Nevezés rögzítése szervezői jogkörben, bárki nevében.
     *
     * Két eset van, és a különbség az utólagos kezelésben számít:
     *
     *   - Ha a szervező a tagok közül választ, a nevezés az adott FIÓKHOZ
     *     kötődik: a tag a saját fiókjában is látja, és a határidő lejártáig
     *     maga visszavonhatja.
     *
     *   - Ha kézzel írja be az adatokat (fiók nélküli játékos), a nevezés
     *     vendégnevezés marad. A névsorban ugyanúgy szerepel, csak nem
     *     tartozik fiókhoz.
     *
     * A nyitódátumot és a határidőt itt SEM lépjük át: ha a szervezőnek a
     * határidő után kell nevezőt felvinnie, előbb a határidőt módosítja. Így
     * a nevezői lista és a meghirdetett határidő nem mond ellent egymásnak.
     *
     * @param array{fullName:string, email:string, phone:string} $data
     * @param string|null $userId A nevező fiókja, vagy null fiók nélküli játékosnál
     * @return array{id:string, competition_id:string, full_name:string, email:string, phone:string, registered_at:string}
     * @throws AppException
     */
    public function registerAsAdmin(string $competitionId, array $data, ?string $userId = null): array
    {
        if ($userId !== null && $this->hasUserRegistered($competitionId, $userId)) {
            throw AppException::duplicateEntry('Ez a fiók már nevezett erre a versenyre');
        }

        return $this->registerForCompetition($competitionId, $data, $userId);
    }

    /**
     * Nevezett-e már ez a fiók erre a versenyre.
     */
    public function hasUserRegistered(string $competitionId, string $userId): bool
    {
        return $this->registrationModel->findByCompetitionAndUser($competitionId, $userId) !== null;
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
        if ($this->isDeadlinePassed($competition)) {
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
