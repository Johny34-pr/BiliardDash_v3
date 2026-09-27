<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * E-mail küldési szolgáltatás PHPMailer-rel.
 *
 * A küldés SMTP-n keresztül, újrapróbálkozással történik. Hiba esetén NEM dob
 * kivételt, hanem naplóz és false-t ad vissza: egy sikertelen levél nem
 * hiúsíthatja meg magát a műveletet (a nevezés akkor is érvényes, ha a
 * visszaigazolás nem ment ki).
 *
 * A levelek egyetlen közös burkolatot használnak (wrapHtml), hogy a klub
 * megjelenése minden értesítésben azonos legyen.
 */
class EmailService
{
    /** Ennyiszer próbálja meg elküldeni a levelet, mielőtt feladja */
    private const MAX_RETRIES = 3;

    /**
     * SMTP időkorlát másodpercben.
     *
     * A PHPMailer alapértelmezése 300 másodperc. Egy elérhetetlen
     * kiszolgálónál ez körlevélnél címzettenként 3x300 másodperc várakozást
     * jelentene, ami a webkérést és az ütemezett futtatást is megfagyasztja.
     */
    private const TIMEOUT_SECONDS = 15;

    private array $config;

    /** A legutóbbi sikertelen küldés oka, vagy null */
    private ?string $lastError = null;

    /**
     * @param array $smtpConfig SMTP konfiguráció (host, port, username, password, encryption, from_address, from_name)
     */
    public function __construct(array $smtpConfig)
    {
        $this->config = $smtpConfig;
    }

    /**
     * Visszaigazoló e-mail küldése a nevezőnek.
     *
     * @param string $to Címzett e-mail cím
     * @param array $data Nevezési adatok: competitionName, competitionDate, competitionVenue, fullName, email, phone
     * @return bool Sikeres küldés esetén true, egyébként false
     */
    public function sendRegistrationConfirmation(string $to, array $data): bool
    {
        return $this->send(
            $to,
            "Nevezés visszaigazolás - {$data['competitionName']}",
            $this->buildHtmlBody($data)
        );
    }

    /**
     * Egy levél elküldése a klub burkolatában.
     *
     * Ez az egyetlen hely, ahol a PHPMailer konfigurálódik - minden értesítés
     * ezen keresztül megy ki, így a feladó, a kódolás és az
     * újrapróbálkozás mindenhol egyforma.
     *
     * @param string $to      Címzett e-mail cím
     * @param string $subject Tárgy
     * @param string $html    Kész HTML törzs
     * @return bool Sikeres küldés esetén true
     */
    public function send(string $to, string $subject, string $html): bool
    {
        $this->lastError = null;

        // A hiányzó vagy hibás beállítást NEM próbáljuk újra: három kísérlet
        // alatt sem lesz meg, körlevélnél viszont minden címzetten
        // elvesztegetnénk a várakozást.
        $configError = $this->configurationError();

        if ($configError !== null) {
            $this->lastError = $configError;
            error_log('EmailService: ' . $configError);

            return false;
        }

        if ($this->config['transport'] === 'log') {
            return $this->writeToLog($to, $subject, $html);
        }

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $mail = $this->createMailer();
                $mail->addAddress($to);

                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body = $html;

                // Szöveges változat a HTML-t nem megjelenítő olvasóknak.
                // Nélküle egyes levelezők spamnek jelölik a csak HTML-es
                // üzenetet.
                $mail->AltBody = $this->htmlToText($html);

                $mail->send();

                $this->lastError = null;

                return true;
            } catch (Exception $e) {
                $this->lastError = $e->getMessage() . $this->failureHint($e->getMessage());

                error_log(
                    'EmailService: ' . $attempt . '/' . self::MAX_RETRIES
                    . ' kísérlet sikertelen (' . $to . ') - ' . $this->lastError
                );

                // A hitelesítési és a címhibát az ismétlés nem oldja meg
                if ($this->isPermanentFailure($e->getMessage())) {
                    break;
                }

                if ($attempt < self::MAX_RETRIES) {
                    sleep(1);
                }
            }
        }

        return false;
    }

    /**
     * A legutóbbi sikertelen küldés oka, ember által olvasható formában.
     *
     * Azért van rá szükség, mert a send() szándékosan nem dob kivételt (egy
     * sikertelen levél nem hiúsíthatja meg magát a műveletet), így a hívó
     * másképp nem tudná megmondani a szervezőnek, MI a baj. A puszta "nem
     * sikerült" üzenetből nem derül ki, hogy hiányzó jelszó, elérhetetlen
     * kiszolgáló vagy hibás cím okozza.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * A PHPMailer beállítása az átvitel fajtája szerint.
     *
     * Ez az egyetlen hely, ahol a levelező konfigurálódik - minden értesítés
     * ezen keresztül megy ki, így a feladó és a kódolás mindenhol egyforma.
     */
    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        if ($this->config['transport'] === 'mail') {
            // A kiszolgáló helyi levelezője a PHP mail() függvényén
            // keresztül: nincs host, port és hitelesítés
            $mail->isMail();
        } else {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->Port = $this->config['port'];
            $mail->Timeout = self::TIMEOUT_SECONDS;

            // Hitelesítés CSAK akkor, ha van mivel. Üres felhasználónévvel az
            // AUTH parancs biztosan elbukik (535 Invalid credentials), és ez
            // a hitelesítést nem kérő kiszolgálókat (MailHog, Mailpit, IP
            // alapján engedélyező továbbító) is használhatatlanná tette.
            $hasCredentials = $this->config['username'] !== '';
            $mail->SMTPAuth = $hasCredentials;

            if ($hasCredentials) {
                $mail->Username = $this->config['username'];
                $mail->Password = $this->config['password'];
            }

            $encryption = $this->config['encryption'];

            if ($encryption === '' || $encryption === 'none') {
                // Titkosítás nélkül az automatikus STARTTLS-t is ki kell
                // kapcsolni, különben a PHPMailer magától megkísérli, és a
                // titkosítást nem támogató helyi kiszolgálónál elhasal
                $mail->SMTPSecure = '';
                $mail->SMTPAutoTLS = false;
            } else {
                $mail->SMTPSecure = $encryption;
            }
        }

        $mail->setFrom($this->config['from_address'], $this->config['from_name']);
        $mail->CharSet = 'UTF-8';

        return $mail;
    }

    /**
     * Olyan beállítási hiba, amivel a küldésnek el sem érdemes indulnia.
     *
     * A cél, hogy a szervező konkrét teendőt kapjon ("nincs megadva
     * MAIL_HOST") egy általános "nem sikerült elküldeni" helyett.
     */
    private function configurationError(): ?string
    {
        if (!in_array($this->config['transport'], ['smtp', 'mail', 'log'], true)) {
            return 'Érvénytelen MAIL_TRANSPORT: "' . $this->config['transport']
                . '". Használható: smtp, mail, log.';
        }

        if ($this->config['from_address'] === '') {
            return 'Nincs megadva feladó cím (MAIL_FROM_ADDRESS).';
        }

        if (filter_var($this->config['from_address'], FILTER_VALIDATE_EMAIL) === false) {
            return 'A feladó cím érvénytelen: "' . $this->config['from_address']
                . '" (MAIL_FROM_ADDRESS). A @localhost végű címet a levelezők elutasítják.';
        }

        if ($this->config['transport'] !== 'smtp') {
            return null;
        }

        if ($this->config['host'] === '') {
            return 'Nincs megadva SMTP kiszolgáló (MAIL_HOST).';
        }

        // Fél pár azonosító: a kiszolgáló ilyenkor mindig elutasítja a
        // hitelesítést, és a hibaüzenetből nem derülne ki, hogy melyik fele
        // hiányzik
        if (($this->config['username'] === '') !== ($this->config['password'] === '')) {
            return 'Az SMTP felhasználónév és jelszó közül csak az egyik van megadva '
                . '(MAIL_USERNAME / MAIL_PASSWORD). Vagy mindkettőt add meg, vagy egyiket se.';
        }

        return null;
    }

    /**
     * Kiegészítő magyarázat a leggyakoribb hibákhoz.
     *
     * A kiszolgáló válasza gyakran önmagában nem elég: a "535 Invalid
     * credentials" üzenetből nem derül ki, hogy a .env fájlból hiányzik a
     * hitelesítés.
     */
    private function failureHint(string $message): string
    {
        $needsAuth = stripos($message, 'authenticat') !== false
            || stripos($message, 'credential') !== false
            || stripos($message, '530') !== false
            || stripos($message, '535') !== false;

        if ($needsAuth && $this->config['transport'] === 'smtp' && $this->config['username'] === '') {
            return ' | A kiszolgáló hitelesítést kér, de a .env fájlban nincs megadva'
                . ' MAIL_USERNAME és MAIL_PASSWORD.';
        }

        if ($needsAuth) {
            return ' | Ellenőrizd a MAIL_USERNAME és MAIL_PASSWORD értékét a .env fájlban.';
        }

        if (stripos($message, 'connect') !== false || stripos($message, 'timed out') !== false) {
            return ' | Nem sikerült elérni a kiszolgálót: ' . $this->config['host']
                . ':' . $this->config['port'] . '. Ellenőrizd a MAIL_HOST, MAIL_PORT és'
                . ' MAIL_ENCRYPTION értékét (587 esetén tls, 465 esetén ssl).';
        }

        return '';
    }

    /**
     * Olyan hiba-e, amit az újrapróbálkozás nem orvosol.
     *
     * A hitelesítési és a címhiba nem múlik el egy másodperc alatt; az
     * ismétlés csak késleltet, körlevélnél címzettenként.
     */
    private function isPermanentFailure(string $message): bool
    {
        foreach ([
            'authenticat',
            'credential',
            'invalid address',
            'must provide at least one recipient',
            '535',
            '530',
        ] as $needle) {
            if (stripos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * A 'log' átvitel: a levél fájlba írása küldés helyett.
     *
     * Fejlesztéshez, hogy az értesítések útja SMTP nélkül is végigjárható és
     * ellenőrizhető legyen. A szöveges változatot írjuk ki, mert a HTML
     * forrás olvashatatlanul hosszú.
     */
    private function writeToLog(string $to, string $subject, string $html): bool
    {
        $path = (string) ($this->config['log_path'] ?? '');

        if ($path === '') {
            $this->lastError = 'A log átvitelhez nincs megadva naplófájl (log_path).';
            error_log('EmailService: ' . $this->lastError);

            return false;
        }

        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $this->lastError = 'Nem sikerült létrehozni a levélnapló könyvtárát: ' . $directory;
            error_log('EmailService: ' . $this->lastError);

            return false;
        }

        $entry = str_repeat('=', 72) . "\n"
            . 'Időpont: ' . date('Y-m-d H:i:s') . "\n"
            . 'Feladó:  ' . $this->config['from_name']
            . ' <' . $this->config['from_address'] . ">\n"
            . 'Címzett: ' . $to . "\n"
            . 'Tárgy:   ' . $subject . "\n"
            . str_repeat('-', 72) . "\n"
            . $this->htmlToText($html) . "\n\n";

        if (file_put_contents($path, $entry, FILE_APPEND | LOCK_EX) === false) {
            $this->lastError = 'Nem sikerült a levélnaplóba írni: ' . $path;
            error_log('EmailService: ' . $this->lastError);

            return false;
        }

        return true;
    }

    /**
     * Ugyanaz a levél több címzettnek, külön-külön.
     *
     * Szándékosan nem egy levél sok címzettel: így egyik tag sem látja a
     * többiek e-mail címét. Ez a körlevél útja a versenykiírásokhoz és a
     * nevezésindításhoz.
     *
     * A sikertelen küldés nem szakítja meg a sort: a többi címzett akkor is
     * megkapja a levelet, ha egy cím hibás.
     *
     * @param array<string> $recipients
     * @return array{sent:int, failed:int, failedAddresses:array<string>}
     */
    public function sendToMany(array $recipients, string $subject, string $html): array
    {
        $sent = 0;
        $failedAddresses = [];

        foreach (array_unique($recipients) as $to) {
            if ($this->send($to, $subject, $html)) {
                $sent++;
            } else {
                $failedAddresses[] = $to;
            }
        }

        return [
            'sent' => $sent,
            'failed' => count($failedAddresses),
            'failedAddresses' => $failedAddresses,
        ];
    }

    /**
     * Értesítés a tagnak arról, hogy a szervező új jelszót állított be.
     *
     * A levél SZÁNDÉKOSAN nem tartalmazza az új jelszót: az e-mail nem
     * biztonságos csatorna, és a jelszót a szervező személyesen vagy
     * telefonon adja át. A levél célja, hogy a tag tudjon a változásról -
     * ha nem ő kérte, jelezhesse.
     */
    public function sendPasswordResetNotice(string $to, string $name): bool
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $body = <<<HTML
<p style="font-size: 16px;">Kedves <strong>{$safeName}</strong>,</p>

<p>A klub szervezője új jelszót állított be a fiókodhoz. Az új jelszót tőle
kapod meg személyesen vagy telefonon &ndash; biztonsági okból ez a levél nem
tartalmazza.</p>

<p>Az összes eszközödön kiléptettünk, ezért a következő belépésnél az új
jelszót kell megadnod.</p>

<div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 15px; margin: 20px 0;">
    <p style="margin: 0;">Ha nem te kérted a jelszó módosítását, jelezd a
    szervezőnek az <a href="mailto:info@okanyibiliard.hu">info@okanyibiliard.hu</a>
    címen.</p>
</div>
HTML;

        return $this->send(
            $to,
            'Új jelszó a fiókodhoz - Okányi Biliárd Klub',
            $this->wrapHtml('Jelszó módosítva', $body)
        );
    }

    /**
     * A klub egységes levélburkolata.
     *
     * Inline stílusokkal, mert a levelezőprogramok a <style> blokkot gyakran
     * eldobják. A szerkezet megegyezik a nevezési visszaigazolásban
     * használttal, hogy minden értesítés felismerhetően a klubtól jöjjön.
     *
     * @param string $heading A fejléc alatti alcím
     * @param string $body    Kész HTML tartalom
     */
    public function wrapHtml(string $heading, string $body): string
    {
        $safeHeading = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>{$safeHeading}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #166534; color: #fff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 24px; color: #facc15;">Okányi Biliárd Klub</h1>
        <p style="margin: 5px 0 0; font-size: 14px;">{$safeHeading}</p>
    </div>

    <div style="background-color: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none;">
        {$body}
    </div>

    <div style="background-color: #f9fafb; padding: 15px; text-align: center; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <p style="margin: 0; font-size: 12px; color: #9ca3af;">&copy; Okányi Biliárd Klub | Ez egy automatikus üzenet, kérjük ne válaszolj rá.</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Egyszerű szöveges változat a HTML törzsből.
     *
     * Nem teljes értékű HTML-feldolgozás, csak annyi, hogy a levél szövege
     * olvasható legyen: a blokkelemek helyére sortörés kerül, a jelölés
     * eltűnik, az entitások feloldódnak.
     */
    private function htmlToText(string $html): string
    {
        $text = preg_replace('#<(br|/p|/div|/h[1-6]|/li|/tr)[^>]*>#i', "\n", $html) ?? $html;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // A sok egymást követő üres sor összevonása
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim((string) preg_replace('/[ \t]+/', ' ', $text));
    }

    /**
     * HTML e-mail sablon összeállítása.
     */
    private function buildHtmlBody(array $data): string
    {
        $competitionName = htmlspecialchars($data['competitionName'], ENT_QUOTES, 'UTF-8');
        $competitionDate = htmlspecialchars($data['competitionDate'], ENT_QUOTES, 'UTF-8');
        $competitionVenue = htmlspecialchars($data['competitionVenue'], ENT_QUOTES, 'UTF-8');
        $fullName = htmlspecialchars($data['fullName'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Nevezés visszaigazolás</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #166534; color: #fff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 24px; color: #facc15;">Okányi Biliárd Klub</h1>
        <p style="margin: 5px 0 0; font-size: 14px;">Nevezés visszaigazolás</p>
    </div>

    <div style="background-color: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none;">
        <p style="font-size: 16px;">Kedves <strong>{$fullName}</strong>,</p>

        <p>Sikeresen neveztél az alábbi versenyre. Köszönjük a jelentkezésedet!</p>

        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h2 style="margin: 0 0 10px; font-size: 18px; color: #166534;">Verseny adatai</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Verseny neve:</td>
                    <td style="padding: 5px 0;">{$competitionName}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Dátum:</td>
                    <td style="padding: 5px 0;">{$competitionDate}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Helyszín:</td>
                    <td style="padding: 5px 0;">{$competitionVenue}</td>
                </tr>
            </table>
        </div>

        <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h2 style="margin: 0 0 10px; font-size: 18px; color: #374151;">Nevezési adataid</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Név:</td>
                    <td style="padding: 5px 0;">{$fullName}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">E-mail:</td>
                    <td style="padding: 5px 0;">{$email}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Telefon:</td>
                    <td style="padding: 5px 0;">{$phone}</td>
                </tr>
            </table>
        </div>

        <p style="color: #6b7280; font-size: 14px;">Ha kérdésed van, kérjük vedd fel velünk a kapcsolatot.</p>
    </div>

    <div style="background-color: #f9fafb; padding: 15px; text-align: center; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <p style="margin: 0; font-size: 12px; color: #9ca3af;">&copy; Okányi Biliárd Klub | Ez egy automatikus üzenet, kérjük ne válaszolj rá.</p>
    </div>
</body>
</html>
HTML;
    }
}
