<?php

declare(strict_types=1);

/**
 * Okányi Biliárd Klub - esedékes értesítések kiküldése
 * =============================================================================
 *
 * A nevezés megnyílása IDŐPONT, nem felhasználói művelet: nincs olyan kérés,
 * amihez a körlevél kiküldését hozzá lehetne kötni. Ez az eszköz végigmegy
 * azokon a versenyeken, amelyeknél a nevezés már megnyílt, de az értesítés
 * még nem ment ki, és elküldi a levelet minden regisztrált tagnak.
 *
 * Futtatás a projekt gyökeréből:
 *
 *     php tools/send-notifications.php            kiküldés
 *     php tools/send-notifications.php --check    csak jelentés, küldés nélkül
 *
 * Ütemezés (ajánlott naponta egyszer-kétszer):
 *
 *     Linux cron:
 *         0 8 * * * cd /var/www/okanyibiliard && php tools/send-notifications.php
 *
 *     Windows Feladatütemező:
 *         C:\xampp\php\php.exe C:\xampp\htdocs\tools\send-notifications.php
 *
 * Cron NÉLKÜL is működik: a szervezői áttekintő oldal betöltésekor ugyanez a
 * logika lefut, tehát a levél legkésőbb akkor kimegy, amikor a szervező
 * belép. Az ütemezés csak annyit ad, hogy ne kelljen ehhez belépnie.
 *
 * Kétszeres kiküldés nem fordulhat elő: a competition_notifications tábla
 * (competition_id, kind) párja egyedi, és a foglalás megelőzi a küldést.
 * Ezért az eszköz bármikor, akárhányszor futtatható.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Models\CompetitionNotification;
use App\Services\EmailService;
use App\Services\NotificationService;

$root = dirname(__DIR__);
$checkOnly = in_array('--check', $argv, true);

Env::load($root . '/.env');

try {
    $db = Database::getConnection();
} catch (\Throwable $e) {
    fwrite(STDERR, 'Nem sikerült kapcsolódni az adatbázishoz: ' . $e->getMessage() . "\n");
    fwrite(STDERR, "Fut a MySQL kiszolgáló?\n");
    exit(1);
}

$mailConfig = require $root . '/config/mail.php';
$emailService = new EmailService($mailConfig);
$notificationService = new NotificationService($db, $emailService);
$notificationModel = new CompetitionNotification($db);

$due = $notificationModel->findDueForRegistrationOpen();
$recipientCount = $notificationService->countRecipients();

echo "Esedékes értesítések\n";
echo '  Regisztrált tag: ' . $recipientCount . "\n";
echo '  Kiküldésre váró verseny: ' . count($due) . "\n\n";

if ($due === []) {
    echo "Nincs kiküldésre váró értesítés.\n";
    exit(0);
}

foreach ($due as $competition) {
    echo '  - ' . $competition['name']
        . ' (nevezési határidő: ' . date('Y. m. d. H:i', strtotime($competition['registration_deadline'])) . ")\n";
}

echo "\n";

if ($checkOnly) {
    echo "--check mód: levél nem került kiküldésre.\n";
    exit(0);
}

if ($recipientCount === 0) {
    echo "Nincs regisztrált tag, akinek ki lehetne küldeni. A versenyek\n";
    echo "kiküldésre várónak maradnak, amíg lesz címzett.\n";
    exit(0);
}

$results = $notificationService->sendDueRegistrationOpenNotices();

$totalSent = 0;
$totalFailed = 0;

foreach ($results as $result) {
    $totalSent += $result['sent'];
    $totalFailed += $result['failed'];

    echo '  kiküldve: ' . $result['competition']
        . ' - ' . $result['sent'] . ' címre'
        . ($result['failed'] > 0 ? ', ' . $result['failed'] . ' sikertelen' : '')
        . "\n";
}

echo "\nÖsszesen: " . $totalSent . ' elküldött levél';
echo $totalFailed > 0 ? ', ' . $totalFailed . " sikertelen.\n" : ".\n";

if ($totalFailed > 0) {
    echo "\nA sikertelen címek a szerver naplójában szerepelnek. Ellenőrizd az\n";
    echo "SMTP beállításokat a .env fájlban (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD).\n";
}

exit($totalFailed > 0 ? 1 : 0);
