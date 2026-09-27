<?php

declare(strict_types=1);

/**
 * E-mail konfiguráció (PHPMailer)
 *
 * A környezeti változókat az App\Core\Env osztály olvassa be a .env fájlból.
 *
 * Háromféle átvitel közül lehet választani a MAIL_TRANSPORT értékével:
 *
 *   smtp  - külső SMTP kiszolgáló (alapértelmezés). Éles üzemhez ez kell.
 *   mail  - a kiszolgáló helyi levelezője a PHP mail() függvényén keresztül.
 *           Sok szolgáltatónál ez SMTP azonosítók nélkül is működik.
 *   log   - nem küld levelet, hanem fájlba írja. Fejlesztéshez, hogy az
 *           alkalmazás SMTP nélkül is végigfuttatható és ellenőrizhető
 *           legyen.
 *
 * Az értékeket trimmeljük, mert a .env fájlba könnyen kerül záró szóköz, és
 * egy szóközzel kezdődő jelszó vagy gépnév néma hitelesítési hibát ad.
 */

use App\Core\Env;

Env::load();

/** A .env értéke körüli szóközök levágása, null-biztosan */
$value = static function (string $key, string $default = ''): string {
    return trim((string) (Env::get($key, $default) ?? $default));
};

return [
    'transport' => strtolower($value('MAIL_TRANSPORT', 'smtp')),
    'host' => $value('MAIL_HOST'),
    'port' => (int) ($value('MAIL_PORT', '587') ?: '587'),
    // A felhasználó és a jelszó lehet szándékosan üres: a helyi fejlesztői
    // SMTP kiszolgálók (MailHog, Mailpit) és az IP alapján engedélyező
    // továbbítók nem kérnek hitelesítést. Ilyenkor az EmailService NEM
    // kapcsol be SMTP hitelesítést - üres azonosítókkal az AUTH mindig
    // 535-es hibát adna.
    'username' => $value('MAIL_USERNAME'),
    'password' => (string) (Env::get('MAIL_PASSWORD', '') ?? ''),
    // Üresen hagyva (vagy 'none') titkosítás nélkül kapcsolódik
    'encryption' => strtolower($value('MAIL_ENCRYPTION', 'tls')),
    'from_address' => $value('MAIL_FROM_ADDRESS', 'info@okanyibiliard.hu'),
    'from_name' => $value('MAIL_FROM_NAME', 'Okányi Biliárd Klub'),
    // A 'log' átvitel ide írja a leveleket
    'log_path' => dirname(__DIR__) . '/storage/logs/mail.log',
];
