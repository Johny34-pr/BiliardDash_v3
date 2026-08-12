<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session kezelés
 *
 * Két, egymástól független azonosítási szintet kezel:
 *   - Admin: jelszó alapú belépés a tartalomkezeléshez (is_admin kulcs)
 *   - Felhasználó: publikus fiók a nevezések rögzítéséhez (user kulcs)
 *
 * A kettő nem zárja ki egymást, és külön-külön léptethető ki.
 */
class Session
{
    /** Session kulcsok */
    private const KEY_ADMIN = 'is_admin';
    private const KEY_USER = 'user';
    private const KEY_FLASH = '_flash';
    private const KEY_VOTER_TOKEN = 'voter_token';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // =====================================================================
    // Admin
    // =====================================================================

    public static function isAdmin(): bool
    {
        return isset($_SESSION[self::KEY_ADMIN]) && $_SESSION[self::KEY_ADMIN] === true;
    }

    public static function login(string $password): bool
    {
        $config = require __DIR__ . '/../../config/app.php';
        $adminPassword = $config['admin_password'] ?? '';

        if (password_verify($password, $adminPassword)) {
            $_SESSION[self::KEY_ADMIN] = true;
            return true;
        }

        // Fallback: plain text összehasonlítás (egyszerű beállításokhoz)
        if ($password === $adminPassword) {
            $_SESSION[self::KEY_ADMIN] = true;
            return true;
        }

        return false;
    }

    /**
     * Admin kiléptetés.
     *
     * Csak az admin jogosultságot vonja vissza, a felhasználói bejelentkezést
     * érintetlenül hagyja.
     */
    public static function logout(): void
    {
        unset($_SESSION[self::KEY_ADMIN]);
    }

    // =====================================================================
    // Publikus felhasználó
    // =====================================================================

    /**
     * Felhasználó beléptetése.
     *
     * Csak a megjelenítéshez és azonosításhoz szükséges mezőket tárolja,
     * a jelszó hash soha nem kerül a sessionbe.
     *
     * @param array{id:string, name:string, email:string, phone:string} $user
     */
    public static function loginUser(array $user): void
    {
        $_SESSION[self::KEY_USER] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
        ];
    }

    public static function isUser(): bool
    {
        return isset($_SESSION[self::KEY_USER]['id']);
    }

    /**
     * A bejelentkezett felhasználó adatai, vagy null.
     *
     * @return array{id:string, name:string, email:string, phone:string}|null
     */
    public static function user(): ?array
    {
        return $_SESSION[self::KEY_USER] ?? null;
    }

    /**
     * A bejelentkezett felhasználó azonosítója, vagy null vendég esetén.
     */
    public static function userId(): ?string
    {
        return $_SESSION[self::KEY_USER]['id'] ?? null;
    }

    /**
     * Felhasználó kiléptetése (az admin jogosultságot nem érinti).
     */
    public static function logoutUser(): void
    {
        unset($_SESSION[self::KEY_USER]);
    }

    // =====================================================================
    // Szavazó azonosító (fórum értékelések)
    // =====================================================================

    /**
     * A szavazó azonosítója fórum értékelésekhez.
     *
     * Bejelentkezve a fiókhoz kötött, ezért eszközfüggetlen. Vendégként egy
     * sessionben tárolt véletlen tokenhez kötődik: ez megakadályozza az
     * ismételt szavazást ugyanabból a böngészőmenetből, de nem véd a
     * session törlése utáni újraszavazás ellen. Ez a kompromisszum
     * szándékos, mert nem szeretnénk azonosításra alkalmas adatot tárolni.
     */
    public static function voterKey(): string
    {
        $userId = self::userId();

        if ($userId !== null) {
            return 'user:' . $userId;
        }

        if (empty($_SESSION[self::KEY_VOTER_TOKEN])) {
            $_SESSION[self::KEY_VOTER_TOKEN] = bin2hex(random_bytes(16));
        }

        return 'guest:' . $_SESSION[self::KEY_VOTER_TOKEN];
    }

    // =====================================================================
    // Flash üzenetek
    // =====================================================================

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION[self::KEY_FLASH][$key] = $value;
    }

    public static function getFlash(string $key): mixed
    {
        $value = $_SESSION[self::KEY_FLASH][$key] ?? null;
        unset($_SESSION[self::KEY_FLASH][$key]);
        return $value;
    }
}
