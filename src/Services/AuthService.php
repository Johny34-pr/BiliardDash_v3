<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Models\Competition;
use App\Models\Registration;
use App\Models\User;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Publikus felhasználói fiókok kezelése.
 *
 * A jelszavak a PHP beépített password_hash() függvényével készülnek
 * (bcrypt), és soha nem kerülnek naplóba vagy sessionbe.
 */
class AuthService
{
    /** A jelszó minimális hossza karakterben */
    public const MIN_PASSWORD_LENGTH = 8;

    /**
     * A törölt fiók nevezéseihez írt helyőrző e-mail tartománya.
     *
     * Az .invalid az RFC 2606 szerint fenntartott, tehát garantáltan soha
     * nem létező tartomány. Így a helyőrző címre akkor sem megy ki levél, ha
     * valahol mégis címzettként kerülne felhasználásra.
     */
    private const ANONYMIZED_EMAIL_DOMAIN = 'example.invalid';

    private User $userModel;
    private Registration $registrationModel;
    private Competition $competitionModel;

    public function __construct(private PDO $db)
    {
        $this->userModel = new User($db);
        $this->registrationModel = new Registration($db);
        $this->competitionModel = new Competition($db);
    }

    /**
     * Új fiók létrehozása.
     *
     * Az e-mail címet kisbetűsítve tárolja, hogy a bejelentkezés ne legyen
     * kis-nagybetű érzékeny.
     *
     * @return array{id:string, name:string, email:string, phone:string, city:string, created_at:string}
     * @throws AppException Ha az e-mail cím már használatban van.
     */
    public function register(
        string $name,
        string $email,
        string $phone,
        string $city,
        string $password
    ): array {
        $email = $this->normalizeEmail($email);

        if ($this->userModel->emailExists($email)) {
            throw AppException::duplicateEntry('Ezzel az e-mail címmel már létezik fiók');
        }

        $id = Uuid::uuid4()->toString();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $this->userModel->create($id, $name, $email, $phone, $city, $passwordHash);

        $user = $this->userModel->findById($id);

        if ($user === null) {
            throw new AppException('A fiók létrehozása nem sikerült', AppException::SERVER_ERROR);
        }

        return $user;
    }

    /**
     * Bejelentkezési kísérlet.
     *
     * Szándékosan nem árulja el, hogy az e-mail cím vagy a jelszó volt hibás,
     * így nem lehet vele létező fiókokat felderíteni.
     *
     * @return array{id:string, name:string, email:string, phone:string}|null
     *         A felhasználó adatai, vagy null hibás adatok esetén.
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        $user = $this->userModel->findByEmail($this->normalizeEmail($email));

        if ($user === null) {
            // Időzítéses támadás elleni védelem: akkor is végezzünk hash
            // ellenőrzést, ha a fiók nem létezik.
            password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG.');
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'city' => $user['city'] ?? '',
        ];
    }

    /**
     * Felhasználó lekérdezése azonosító alapján.
     *
     * @return array{id:string, name:string, email:string, phone:string, city:string, created_at:string}|null
     */
    public function getUserById(string $id): ?array
    {
        return $this->userModel->findById($id);
    }

    // =====================================================================
    // Szervezői felhasználókezelés
    // =====================================================================

    /**
     * Összes fiók a szervezői listához, a nevezésszámmal együtt.
     *
     * @return array<array{id:string, name:string, email:string, phone:string, city:string, created_at:string, registration_count:int}>
     */
    public function getAllUsers(): array
    {
        return $this->userModel->findAll();
    }

    /**
     * Fiók adatainak módosítása szervezői jogkörben.
     *
     * @return array{id:string, name:string, email:string, phone:string, city:string, created_at:string}
     * @throws AppException Ha a fiók nem létezik, vagy az e-mail cím foglalt.
     */
    public function updateUser(string $id, string $name, string $email, string $phone, string $city): array
    {
        if ($this->userModel->findById($id) === null) {
            throw AppException::notFound('A fiók nem található');
        }

        $email = $this->normalizeEmail($email);

        if ($this->userModel->emailExistsForOther($email, $id)) {
            throw AppException::duplicateEntry('Ezt az e-mail címet már másik fiók használja');
        }

        $this->userModel->update($id, $name, $email, $phone, $city);

        return $this->userModel->findById($id);
    }

    /**
     * Jelszó felülírása szervezői jogkörben.
     *
     * A jelszóváltás minden megjegyzett belépést érvénytelenít, ezért a
     * hívónak a RememberMeService::forgetAllForUser() metódust is meg kell
     * hívnia - különben egy régi süti továbbra is beléptetne.
     *
     * @throws AppException Ha a fiók nem létezik.
     */
    public function updatePassword(string $id, string $password): void
    {
        if ($this->userModel->findById($id) === null) {
            throw AppException::notFound('A fiók nem található');
        }

        $this->userModel->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
    }

    /**
     * Jelszó visszaállítása a szervező által.
     *
     * Akkor kell, ha a tag nem tud belépni, és a szervező segít neki. A
     * beállított jelszót VISSZAADJA, mert a szervezőnek át kell adnia a
     * tagnak - telefonon vagy személyesen. Ez az egyetlen pont, ahol a jelszó
     * nyílt szövegként megjelenik; tárolni csak a lenyomatát tároljuk.
     *
     * A $password elhagyásával a rendszer generál egyet. Megadva a szervező
     * sajátot ír be: erre akkor van szükség, ha a jelszót telefonon kell
     * lediktálni, és a generált, véletlen karaktersor nehezen érthető - vagy
     * ha a klubban megszokott, egyeztetett kezdőjelszót adnak.
     *
     * A megadott jelszóra UGYANAZ a hosszkövetelmény érvényes, mint bárhol
     * máshol: a szervezői jogkör nem ok arra, hogy gyengébb jelszó
     * kerülhessen a rendszerbe.
     *
     * @param string|null $password Saját jelszó, vagy null a generáláshoz
     * @return string Az új, nyílt szövegű jelszó
     * @throws AppException Ha a fiók nem létezik, vagy a megadott jelszó rövid.
     */
    public function resetPassword(string $id, ?string $password = null): string
    {
        if ($this->userModel->findById($id) === null) {
            throw AppException::notFound('A fiók nem található');
        }

        if ($password !== null && $password !== '') {
            if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
                throw AppException::validationError(
                    'A jelszó legalább ' . self::MIN_PASSWORD_LENGTH . ' karakter legyen'
                );
            }
        } else {
            $password = self::generatePassword();
        }

        $this->userModel->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));

        return $password;
    }

    /**
     * Saját jelszó megváltoztatása a fiókban.
     *
     * A régi jelszót is kérjük: enélkül egy eltulajdonított munkamenettel
     * ki lehetne zárni a fiók tulajdonosát azzal, hogy a támadó új jelszót
     * állít be.
     *
     * Erre a szervezői visszaállítás után is szükség van: a generált jelszót
     * a tag jellemzően lecseréli valami megjegyezhetőre.
     *
     * @throws AppException Ha a fiók nem létezik, vagy a régi jelszó hibás.
     */
    public function changeOwnPassword(string $id, string $currentPassword, string $newPassword): void
    {
        $user = $this->userModel->findById($id);

        if ($user === null) {
            throw AppException::notFound('A fiók nem található');
        }

        // A lenyomat csak az e-mail alapú lekérdezésben jön vissza
        $withHash = $this->userModel->findByEmail($user['email']);

        if ($withHash === null || !password_verify($currentPassword, $withHash['password_hash'])) {
            throw new AppException('A jelenlegi jelszó nem megfelelő', AppException::VALIDATION_ERROR);
        }

        $this->userModel->updatePassword($id, password_hash($newPassword, PASSWORD_DEFAULT));
    }

    /**
     * Felolvasható, véletlen jelszó előállítása.
     *
     * Az ábécéből kimaradnak a könnyen összekeverhető karakterek (0 és O,
     * 1 és l és I), mert a jelszót jellemzően telefonon diktálják le. A
     * hossz 12 karakter, ami a kihagyott karakterekkel is jóval a
     * minimum fölött van.
     *
     * A random_int() kriptográfiailag biztonságos, szemben a rand()-dal.
     */
    public static function generatePassword(int $length = 12): string
    {
        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $lastIndex = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $lastIndex)];
        }

        return $password;
    }

    /**
     * Fiók törlése.
     *
     * A felhasználó nevezései megmaradnak, vendégnevezéssé válnak (a
     * registrations idegen kulcsa ON DELETE SET NULL).
     *
     * @throws AppException Ha a fiók nem létezik.
     */
    public function deleteUser(string $id): void
    {
        if ($this->userModel->findById($id) === null) {
            throw AppException::notFound('A fiók nem található');
        }

        $this->userModel->delete($id);
    }

    /**
     * Saját fiók törlése a felhasználó kérésére.
     *
     * A jelenlegi jelszót is kérjük, ugyanazért, amiért a jelszóváltásnál:
     * egy eltulajdonított munkamenettel ne lehessen visszafordíthatatlanul
     * megszüntetni valaki fiókját.
     *
     * Mi történik a nevezésekkel
     * --------------------------
     * Ha csak a users sort törölnénk, a nevezéseken MEGMARADNA a név, az
     * e-mail cím és a telefonszám (az idegen kulcs ON DELETE SET NULL), így
     * a "fiók törlése" félrevezető lenne: a tag azt hinné, az adatai
     * eltűntek. Ezért a nevezéseket is kezeljük, de a két eset szándékosan
     * különbözik:
     *
     *   - MÉG NYITOTT verseny (a határidő nem járt le): a nevezés TÖRLŐDIK.
     *     Aki megszünteti a fiókját, nem fog elindulni, és a szervezői
     *     névsor legyen pontos. A tag ezt a határidőig egyébként is
     *     megtehette volna egyenként.
     *
     *   - LEZÁRULT verseny: a nevezés MEGMARAD, de az elérhetőség
     *     névtelenítve. A névsor a klub versenytörténetének része - a
     *     ranglista is a játékos nevével dolgozik -, az e-mail cím és a
     *     telefonszám viszont elérhetőség, aminek nincs történeti értéke.
     *
     * A ranglista pontszámai, a fórum hozzászólások és a topikok érintetlenek
     * maradnak; ott az idegen kulcs NULL-ra áll, a megjelenített név pedig
     * külön oszlopban él, ezért a szezon állása és a beszélgetések nem
     * csorbulnak.
     *
     * Az egész egyetlen tranzakcióban fut: egy félbeszakadt törlés után
     * maradhatna olyan fiók, amelynek a nevezéseit már névtelenítettük.
     *
     * A hívónak a RememberMeService-t is meg kell hívnia, hogy a megjegyzett
     * belépés sütije eltűnjön az eszközről.
     *
     * @return array{withdrawn:int, anonymized:int} Hány nevezést vontunk
     *         vissza, és hányat tartottunk meg névtelenítve
     * @throws AppException Ha a fiók nem létezik, vagy a jelszó hibás.
     */
    public function deleteOwnAccount(string $id, string $password): array
    {
        $user = $this->userModel->findById($id);

        if ($user === null) {
            throw AppException::notFound('A fiók nem található');
        }

        // A lenyomat csak az e-mail alapú lekérdezésben jön vissza
        $withHash = $this->userModel->findByEmail($user['email']);

        if ($withHash === null || !password_verify($password, $withHash['password_hash'])) {
            throw new AppException('A jelszó nem megfelelő', AppException::VALIDATION_ERROR);
        }

        $registrations = $this->registrationModel->findByUserId($id);
        $now = new \DateTimeImmutable();

        $withdrawn = 0;
        $anonymized = 0;

        $this->db->beginTransaction();

        try {
            foreach ($registrations as $registration) {
                $deadlinePassed = new \DateTimeImmutable($registration['registration_deadline']) <= $now;

                if ($deadlinePassed) {
                    // A nevezés azonosítója adja a helyőrző egyediségét: ez
                    // elsődleges kulcs, tehát ütközés elvileg sem lehet
                    $this->registrationModel->anonymize(
                        $registration['id'],
                        'torolt-' . $registration['id'] . '@' . self::ANONYMIZED_EMAIL_DOMAIN
                    );
                    $anonymized++;

                    continue;
                }

                $this->registrationModel->delete($registration['id']);
                $this->competitionModel->decrementRegistrantCount($registration['competition_id']);
                $withdrawn++;
            }

            $this->userModel->delete($id);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }

        return ['withdrawn' => $withdrawn, 'anonymized' => $anonymized];
    }

    /**
     * E-mail cím normalizálása: körülvágás és kisbetűsítés.
     */
    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
