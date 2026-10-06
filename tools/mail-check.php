<?php

declare(strict_types=1);

/**
 * Levélküldés ellenőrzése
 *
 * Megmondja, hogy a .env beállításaival a rendszer KÉPES-E levelet küldeni,
 * és ha nem, akkor pontosan mi a hiányzó vagy hibás adat. Enélkül a
 * levélküldés hibái csak a kiszolgáló naplójában látszanak, ott pedig egy
 * "535 Invalid credentials" sorból nem derül ki, melyik beállítás rossz.
 *
 * Használat a projekt gyökeréből:
 *
 *   php tools/mail-check.php                       csak ellenőrzés, nem küld
 *   php tools/mail-check.php cim@pelda.hu          próbalevél küldése
 *   php tools/mail-check.php cim@pelda.hu --debug  teljes SMTP beszélgetés
 *
 * A jelszót SOHA nem írja ki, csak a hosszát - így a kimenet biztonsággal
 * továbbküldhető hibakereséshez.
 */

use App\Core\Env;
use App\Services\EmailService;
use PHPMailer\PHPMailer\PHPMailer;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

Env::load($root . '/.env');

$recipient = null;
$debug = false;

foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--debug') {
        $debug = true;
    } elseif (!str_starts_with($argument, '--')) {
        $recipient = $argument;
    }
}

$config = require $root . '/config/mail.php';

echo "Levélküldés ellenőrzése\n\n";

// -----------------------------------------------------------------------------
// 1. A betöltött beállítások
// -----------------------------------------------------------------------------
echo "A BETÖLTÖTT BEÁLLÍTÁSOK (.env)\n";

$show = static function (string $key, string $label) use ($config): void {
    $value = (string) ($config[$key] ?? '');

    // A jelszót szándékosan nem írjuk ki, csak a hosszát: a kimenet így
    // továbbküldhető anélkül, hogy a postafiók jelszava kiszivárogna
    $display = match (true) {
        $key === 'password' => $value === '' ? 'ÜRES' : '*** (' . strlen($value) . ' karakter)',
        $value === '' => 'ÜRES',
        default => $value,
    };

    printf("  %-22s %s\n", $label, $display);
};

$show('transport', 'átvitel');
$show('host', 'kiszolgáló');
$show('port', 'port');
$show('encryption', 'titkosítás');
$show('username', 'felhasználónév');
$show('password', 'jelszó');
$show('from_address', 'feladó cím');
$show('from_name', 'feladó név');

// -----------------------------------------------------------------------------
// 2. Beállítási hibák
// -----------------------------------------------------------------------------
echo "\nBEÁLLÍTÁS ELLENŐRZÉSE\n";

$problems = [];
$warnings = [];

if (!in_array($config['transport'], ['smtp', 'mail', 'log'], true)) {
    $problems[] = 'A MAIL_TRANSPORT értéke érvénytelen: "' . $config['transport']
        . '". Használható: smtp, mail, log.';
}

if ($config['from_address'] === '') {
    $problems[] = 'Nincs megadva MAIL_FROM_ADDRESS.';
} elseif (filter_var($config['from_address'], FILTER_VALIDATE_EMAIL) === false) {
    $problems[] = 'A MAIL_FROM_ADDRESS érvénytelen: ' . $config['from_address'];
}

if ($config['transport'] === 'smtp') {
    if ($config['host'] === '') {
        $problems[] = 'Nincs megadva MAIL_HOST.';
    }

    if ($config['username'] === '' && $config['password'] === '') {
        $warnings[] = 'Nincs megadva MAIL_USERNAME és MAIL_PASSWORD. Ez csak akkor'
            . ' helyes, ha a kiszolgáló nem kér hitelesítést (helyi MailHog/Mailpit).'
            . ' Szolgáltatói postafióknál mindkettő kell.';
    } elseif ($config['username'] === '') {
        $problems[] = 'Van MAIL_PASSWORD, de nincs MAIL_USERNAME.';
    } elseif ($config['password'] === '') {
        $problems[] = 'Van MAIL_USERNAME, de nincs MAIL_PASSWORD. Ez a leggyakoribb ok,'
            . ' amiért a rendszer nem küld levelet.';
    }

    // A port és a titkosítás összetartozik: a gyakori elírás órákat visz el
    $expected = match ($config['port']) {
        465 => 'ssl',
        587, 25 => 'tls',
        default => null,
    };

    if ($expected !== null && $config['encryption'] !== $expected
        && !in_array($config['encryption'], ['', 'none'], true)) {
        $warnings[] = 'A ' . $config['port'] . '-es porthoz általában a "' . $expected
            . '" titkosítás tartozik, a beállítás viszont "' . $config['encryption']
            . '". Rossz párosításnál a kapcsolat időtúllépéssel elhasal.';
    }

    if ($config['username'] !== '' && $config['username'] !== $config['from_address']) {
        $warnings[] = 'A felhasználónév (' . $config['username'] . ') és a feladó cím ('
            . $config['from_address'] . ') nem azonos. cPanel/Exim esetén a kiszolgáló'
            . ' idegen feladóként elutasíthatja a levelet.';
    }

    if ($config['username'] !== '' && !str_contains($config['username'], '@')) {
        $warnings[] = 'cPanelnél a felhasználónév a TELJES e-mail cím, nem csak az'
            . ' @ előtti rész.';
    }
}

// A levelek gombjai és hivatkozásai az APP_URL-ből épülnek. Helyi címmel a
// levél kimegy, de a címzett egyetlen hivatkozást sem tud megnyitni belőle -
// ez a hiba csak a tagoknál derülne ki, ezért itt jelezzük.
$appUrl = trim((string) (Env::get('APP_URL', '') ?? ''));

if ($appUrl === '') {
    $warnings[] = 'Nincs megadva APP_URL. A levelekben szereplő hivatkozások enélkül'
        . ' hiányosak lesznek.';
} elseif (preg_match('#^https?://(localhost|127\.0\.0\.1|\[::1\])#i', $appUrl) === 1) {
    $warnings[] = 'Az APP_URL helyi címre mutat (' . $appUrl . '). A levelek ki fognak'
        . ' menni, de a bennük lévő hivatkozásokat a címzett nem tudja megnyitni.'
        . ' Éles küldés előtt állítsd a valódi domainre.';
}

foreach ($problems as $problem) {
    echo '  HIBA   ' . wordwrap($problem, 72, "\n         ") . "\n";
}

foreach ($warnings as $warning) {
    echo '  FIGYELEM ' . wordwrap($warning, 70, "\n           ") . "\n";
}

if ($problems === [] && $warnings === []) {
    echo "  OK     A beállítások rendben vannak.\n";
}

// -----------------------------------------------------------------------------
// 3. Kapcsolódás és hitelesítés
// -----------------------------------------------------------------------------
if ($config['transport'] === 'smtp' && $problems === []) {
    echo "\nKAPCSOLÓDÁS ÉS HITELESÍTÉS\n";

    $mail = new PHPMailer(true);

    if ($debug) {
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = static function (string $line): void {
            echo '    ' . rtrim($line) . "\n";
        };
    }

    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->Port = $config['port'];
        $mail->Timeout = 15;

        $hasCredentials = $config['username'] !== '';
        $mail->SMTPAuth = $hasCredentials;

        if ($hasCredentials) {
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
        }

        if (in_array($config['encryption'], ['', 'none'], true)) {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        } else {
            $mail->SMTPSecure = $config['encryption'];
        }

        if ($mail->smtpConnect()) {
            echo '  OK     A kapcsolat felállt'
                . ($hasCredentials ? ' és a hitelesítés sikerült.' : ' (hitelesítés nélkül).') . "\n";
            $mail->smtpClose();
        } else {
            echo "  HIBA   A kapcsolat nem állt fel.\n";
            $problems[] = 'SMTP kapcsolódás sikertelen.';
        }
    } catch (Throwable $e) {
        echo '  HIBA   ' . $e->getMessage() . "\n";

        $message = $e->getMessage();

        if (stripos($message, 'authenticat') !== false || stripos($message, 'credential') !== false) {
            echo "         A kiszolgáló elutasította a belépést. Ellenőrizd a postafiók\n";
            echo "         jelszavát a cPanelben (E-mail fiókok -> Jelszó kezelése).\n";
        } elseif (stripos($message, 'connect') !== false || stripos($message, 'timed out') !== false) {
            echo '         Nem sikerült elérni a ' . $config['host'] . ':' . $config['port']
                . " címet.\n";
            echo "         Próbáld a másik párosítást: 465/ssl vagy 587/tls.\n";
        } elseif (stripos($message, 'certificate') !== false || stripos($message, 'ssl') !== false) {
            echo "         Tanúsítvány hiba. A kiszolgáló nevét arra a névre állítsd,\n";
            echo "         amire a tanúsítvány szól (általában mail.sajatdomain.hu).\n";
        }

        $problems[] = $message;
    }
}

// -----------------------------------------------------------------------------
// 4. Próbalevél
// -----------------------------------------------------------------------------
if ($recipient === null) {
    echo "\nPróbalevél küldéséhez adj meg egy címet:\n";
    echo "  php tools/mail-check.php sajat@cimem.hu\n";

    exit($problems === [] ? 0 : 1);
}

if ($problems !== []) {
    echo "\nA próbalevél kimarad, mert a fenti hibákat előbb javítani kell.\n";

    exit(1);
}

if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
    echo "\nHIBA: a megadott címzett nem érvényes e-mail cím: " . $recipient . "\n";

    exit(1);
}

echo "\nPRÓBALEVÉL\n";
echo '  címzett: ' . $recipient . "\n";

$emailService = new EmailService($config);

$body = '<p>Ez egy próbalevél az Okányi Biliárd Klub weboldaláról.</p>'
    . '<p>Ha ezt megkaptad, a levélküldés beállítása rendben van: a nevezési'
    . ' visszaigazolások, a versenykiírásról szóló körlevelek és a'
    . ' jelszó-értesítések is ki tudnak menni.</p>'
    . '<ul>'
    . '<li>Átvitel: ' . htmlspecialchars($config['transport'], ENT_QUOTES, 'UTF-8') . '</li>'
    . '<li>Kiszolgáló: ' . htmlspecialchars($config['host'], ENT_QUOTES, 'UTF-8')
    . ':' . (int) $config['port'] . '</li>'
    . '<li>Időpont: ' . date('Y-m-d H:i:s') . '</li>'
    . '</ul>';

$success = $emailService->send(
    $recipient,
    'Próbalevél - Okányi Biliárd Klub',
    $emailService->wrapHtml('Levélküldés ellenőrzése', $body)
);

if ($success) {
    if ($config['transport'] === 'log') {
        echo '  OK     A levél a naplóba került: ' . $config['log_path'] . "\n";
    } else {
        echo "  OK     A levél elküldve. Ellenőrizd a postafiókot (a spam mappát is).\n";
    }

    exit(0);
}

echo '  HIBA   ' . ($emailService->getLastError() ?? 'ismeretlen hiba') . "\n";

exit(1);
