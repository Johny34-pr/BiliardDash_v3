<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Models\RememberToken;
use App\Models\User;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Belépési adatok megjegyzése ("emlékezz rám").
 *
 * Osztott token séma
 * ------------------
 * A süti értéke `selector:validator`. A selector nyilvános azonosító, ez
 * alapján keresünk indexelten. A validator titkos, és csak a SHA-256
 * lenyomata kerül az adatbázisba. Az összehasonlítás hash_equals-szal
 * történik, hogy a futásidő ne szivárogtasson információt.
 *
 * A setcookie() URL-kódolja az értéket, ezért a kettőspont %3A-ként utazik;
 * a PHP a beolvasáskor automatikusan visszafejti, így a $_COOKIE már a
 * nyers `selector:validator` alakot tartalmazza. Mindkét fél ugyanezt a
 * beépített kódolást használja, ezért nincs szükség kézi kezelésre.
 *
 * Miért nem elég a jelszó hash-e a sütiben: azt egy ellopott süti
 * korlátlanul újrahasznosíthatóvá tenné. Így viszont a token lejár,
 * eszközönként külön él, és kilépéskor érvénytelenné válik.
 *
 * Rotáció: minden sikeres automatikus belépés új tokent kap, a régi
 * törlődik. Egy ellopott süti így legfeljebb egyszer használható, és a
 * jogos használat után már érvénytelen.
 */
class RememberMeService
{
    /** A süti neve */
    public const COOKIE_NAME = 'mb_remember';

    /** Meddig éljen a megjegyzett belépés */
    private const LIFETIME_DAYS = 30;

    /** A selector hossza hex karakterben (16 bájt) */
    private const SELECTOR_BYTES = 16;

    /** A validator hossza bájtban */
    private const VALIDATOR_BYTES = 32;

    private RememberToken $tokenModel;
    private User $userModel;

    public function __construct(private PDO $db)
    {
        $this->tokenModel = new RememberToken($db);
        $this->userModel = new User($db);
    }

    /**
     * Megjegyzett belépés indítása: token mentése és süti kiadása.
     *
     * A hívónak már be kell léptetnie a felhasználót; ez a metódus csak a
     * későbbi visszatéréshez készíti elő a tokent.
     */
    public function remember(string $userId): void
    {
        $selector = bin2hex(random_bytes(self::SELECTOR_BYTES / 2));
        $validator = bin2hex(random_bytes(self::VALIDATOR_BYTES));
        $expiresAt = new \DateTimeImmutable('+' . self::LIFETIME_DAYS . ' days');

        $this->tokenModel->create(
            Uuid::uuid4()->toString(),
            $userId,
            $selector,
            hash('sha256', $validator),
            $expiresAt->format('Y-m-d H:i:s')
        );

        $this->writeCookie($selector . ':' . $validator, $expiresAt->getTimestamp());
    }

    /**
     * Automatikus belépés a sütiből, ha van érvényes token.
     *
     * A kérés elején fut le. Ha nincs süti vagy már van bejelentkezett
     * felhasználó, azonnal visszatér, így a szokásos kérésekben egyetlen
     * adatbázis-lekérdezést sem okoz.
     *
     * @return bool Sikerült-e visszaléptetni a felhasználót
     */
    public function restoreSession(): bool
    {
        if (Session::isUser()) {
            return false;
        }

        $cookie = $_COOKIE[self::COOKIE_NAME] ?? '';

        if ($cookie === '' || !str_contains($cookie, ':')) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookie, 2);
        $token = $this->tokenModel->findBySelector($selector);

        // Nincs ilyen token: a süti elavult vagy hamis
        if ($token === null) {
            $this->clearCookie();
            return false;
        }

        // Lejárt token: töröljük, hogy ne halmozódjon
        if (new \DateTimeImmutable($token['expires_at']) < new \DateTimeImmutable()) {
            $this->tokenModel->delete($token['id']);
            $this->clearCookie();
            return false;
        }

        // A titkos rész ellenőrzése időzítésre nézve egyenletesen
        if (!hash_equals($token['token_hash'], hash('sha256', $validator))) {
            // Érvénytelen validator létező selectorral: ez lopott vagy
            // manipulált sütire utal, ezért a felhasználó minden tokenjét
            // eldobjuk, és újra be kell lépnie.
            $this->tokenModel->deleteAllForUser($token['user_id']);
            $this->clearCookie();
            return false;
        }

        $user = $this->userModel->findById($token['user_id']);

        // A fiókot időközben törölték
        if ($user === null) {
            $this->tokenModel->delete($token['id']);
            $this->clearCookie();
            return false;
        }

        Session::loginUser($user);

        // Rotáció: a felhasznált token eldobása és új kiadása, hogy egy
        // ellopott süti ne legyen többször felhasználható
        $this->tokenModel->touch($token['id']);
        $this->tokenModel->delete($token['id']);
        $this->remember($user['id']);

        return true;
    }

    /**
     * A megjegyzett belépés visszavonása az adott eszközön (kilépéskor).
     */
    public function forget(): void
    {
        $cookie = $_COOKIE[self::COOKIE_NAME] ?? '';

        if ($cookie !== '' && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);
            $token = $this->tokenModel->findBySelector($selector);

            if ($token !== null) {
                $this->tokenModel->delete($token['id']);
            }
        }

        $this->clearCookie();
    }

    /**
     * Egy felhasználó összes megjegyzett belépésének visszavonása.
     *
     * Jelszóváltásnál és fióktörlésnél hívandó: ilyenkor minden eszközön
     * meg kell szűnnie az automatikus belépésnek.
     */
    public function forgetAllForUser(string $userId): void
    {
        $this->tokenModel->deleteAllForUser($userId);
    }

    /**
     * A lejárt tokenek eltakarítása.
     */
    public function purgeExpired(): int
    {
        return $this->tokenModel->deleteExpired();
    }

    /**
     * A süti kiírása.
     *
     * httponly: JavaScript nem érheti el, így egy XSS nem tudja ellopni.
     * samesite=Lax: más oldalról indított kérésekkel nem megy el.
     * secure: csak HTTPS felett, ha a kérés is azon jött - fejlesztésben
     * (http://localhost) enélkül a süti egyáltalán nem jönne létre.
     */
    private function writeCookie(string $value, int $expiresAt): void
    {
        // Fejlécek kiírása után már nem lehet sütit állítani; ilyenkor a
        // megjegyzés csendben kimarad, de a belépés maga működik.
        if (headers_sent()) {
            error_log('[RememberMeService] A süti nem állítható be: a fejlécek már elmentek.');
            return;
        }

        setcookie(self::COOKIE_NAME, $value, [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * A süti törlése a böngészőből.
     */
    private function clearCookie(): void
    {
        unset($_COOKIE[self::COOKIE_NAME]);

        if (headers_sent()) {
            return;
        }

        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * HTTPS felett érkezett-e a kérés.
     */
    private function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
