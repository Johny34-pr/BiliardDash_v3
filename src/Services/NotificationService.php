<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompetitionNotification;
use App\Models\User;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Körlevelek a klub tagjainak.
 *
 * Két értesítés van, és a kettő más pillanatban érdekes:
 *
 *   - Versenykiírás: amikor a szervező meghirdet egy versenyt. A tag ebből
 *     tudja, hogy lesz verseny, és mikor nyílik a nevezés.
 *   - Nevezés indulása: amikor a nevezési időablak megnyílik. Ez a tényleges
 *     cselekvésre hívás, ezért külön levél - a kiírás jellemzően hetekkel
 *     korábban megy ki.
 *
 * A KÉTSZERES KIKÜLDÉS ELLENI VÉDELEM az adatbázisban van, nem itt: a
 * competition_notifications tábla (competition_id, kind) párja egyedi, és a
 * foglalás ELŐBB történik, mint a küldés. Így akkor sem megy ki kétszer, ha
 * a szervező rákattint a gombra, miközben az ütemezett futtatás is elindul.
 *
 * A küldés a kérés szálán, egymás után történik. Egy klubméretű
 * címzettlistánál ez elfogadható; ha a taglista jelentősen megnő, a
 * tools/send-notifications.php ütemezett futtatása a helyes út, mert az nem
 * egy HTTP kérés időkorlátja alatt fut.
 */
class NotificationService
{
    private CompetitionNotification $notificationModel;
    private User $userModel;

    public function __construct(
        private PDO $db,
        private EmailService $emailService
    ) {
        $this->notificationModel = new CompetitionNotification($db);
        $this->userModel = new User($db);
    }

    /**
     * Értesítés az új versenykiírásról.
     *
     * @param array<string, mixed> $competition
     * @return array{sent:int, failed:int, skipped:bool, reason:?string}
     */
    public function notifyAnnounced(array $competition): array
    {
        return $this->dispatch(
            $competition,
            CompetitionNotification::KIND_ANNOUNCED,
            'Új verseny: ' . $competition['name'],
            $this->buildAnnouncedBody($competition)
        );
    }

    /**
     * Értesítés arról, hogy megnyílt a nevezés.
     *
     * @param array<string, mixed> $competition
     * @return array{sent:int, failed:int, skipped:bool, reason:?string}
     */
    public function notifyRegistrationOpen(array $competition): array
    {
        return $this->dispatch(
            $competition,
            CompetitionNotification::KIND_REGISTRATION_OPEN,
            'Megnyílt a nevezés: ' . $competition['name'],
            $this->buildRegistrationOpenBody($competition)
        );
    }

    /**
     * Minden olyan versenyről kiküldi a nevezésindítási értesítést, amelynél
     * a nevezés már megnyílt, de a levél még nem ment ki.
     *
     * Erre azért van szükség, mert a nevezés megnyílása IDŐPONT, nem
     * felhasználói művelet: nincs kérés, amihez hozzá lehetne kötni a
     * küldést. Két helyről hívjuk - az ütemezett futtatásból
     * (tools/send-notifications.php) és a szervezői áttekintő oldalról -,
     * hogy cron nélkül se maradjon el.
     *
     * @return array<array{competition:string, sent:int, failed:int}>
     */
    public function sendDueRegistrationOpenNotices(): array
    {
        $results = [];

        foreach ($this->notificationModel->findDueForRegistrationOpen() as $competition) {
            $result = $this->notifyRegistrationOpen($competition);

            if ($result['skipped']) {
                continue;
            }

            $results[] = [
                'competition' => $competition['name'],
                'sent' => $result['sent'],
                'failed' => $result['failed'],
            ];
        }

        return $results;
    }

    /**
     * Egy verseny kiküldött értesítései.
     *
     * @return array<string, array{sent_at:string, recipient_count:int, failed_count:int}>
     */
    public function getStatus(string $competitionId): array
    {
        return $this->notificationModel->findByCompetition($competitionId);
    }

    /**
     * Több verseny értesítési állapota, a szervezői listához.
     *
     * @param array<string> $competitionIds
     * @return array<string, array<string, true>>
     */
    public function getSentKinds(array $competitionIds): array
    {
        return $this->notificationModel->findKindsByCompetitions($competitionIds);
    }

    /**
     * A címzettek száma - a szervezői felület ebből írja ki, hány tagnak
     * megy majd ki a levél.
     */
    public function countRecipients(): int
    {
        return count($this->userModel->findAllForMailing());
    }

    /**
     * A kiküldés közös menete: foglalás, küldés, eredmény rögzítése.
     *
     * @param array<string, mixed> $competition
     * @return array{sent:int, failed:int, skipped:bool, reason:?string}
     */
    private function dispatch(array $competition, string $kind, string $subject, string $body): array
    {
        $recipients = array_column($this->userModel->findAllForMailing(), 'email');

        if ($recipients === []) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => true,
                'reason' => 'Nincs regisztrált tag, akinek ki lehetne küldeni.',
            ];
        }

        // Foglalás: aki be tudja szúrni a sort, az küld. Ütközésnél az
        // értesítés már kiment, tehát nincs mit tenni.
        $notificationId = Uuid::uuid4()->toString();

        if (!$this->notificationModel->claim($notificationId, $competition['id'], $kind)) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => true,
                'reason' => 'Ez az értesítés már kiment erről a versenyről.',
            ];
        }

        $html = $this->emailService->wrapHtml($subject, $body);
        $result = $this->emailService->sendToMany($recipients, $subject, $html);

        if ($result['failedAddresses'] !== []) {
            error_log(
                '[NotificationService] ' . $kind . ' - nem sikerült ' . $result['failed']
                . ' címre kiküldeni: ' . implode(', ', array_slice($result['failedAddresses'], 0, 10))
                . ' - ' . ($this->emailService->getLastError() ?? 'ismeretlen hiba')
            );
        }

        // Ha EGYETLEN levél sem ment ki, a foglalást visszavonjuk.
        //
        // Enélkül a napló sora megmaradna, és a (competition_id, kind) egyedi
        // kulcs MINDEN további kísérletet elutasítana: az értesítés örökre
        // kiküldöttnek látszana, holott senki nem kapta meg. Rossz SMTP
        // beállítás mellett pont ez történt - a javítás után sem lehetett
        // volna újraküldeni, csak az adatbázissor kézi törlésével.
        //
        // Részleges sikernél NEM szabadítunk fel: akik megkapták, azoknak a
        // második futás kétszer küldené el.
        if ($result['sent'] === 0) {
            $this->notificationModel->release($notificationId);

            return [
                'sent' => 0,
                'failed' => $result['failed'],
                'skipped' => false,
                'reason' => 'Egyetlen levél sem ment ki, ezért az értesítés újra megkísérelhető. '
                    . 'Ok: ' . ($this->emailService->getLastError() ?? 'ismeretlen hiba'),
            ];
        }

        $this->notificationModel->recordResult(
            $notificationId,
            $result['sent'],
            $result['failed']
        );

        return [
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'skipped' => false,
            'reason' => $result['failed'] > 0
                ? $result['failed'] . ' címre nem sikerült kiküldeni. Ok: '
                    . ($this->emailService->getLastError() ?? 'ismeretlen hiba')
                : null,
        ];
    }

    /**
     * A versenykiírás levelének törzse.
     *
     * @param array<string, mixed> $competition
     */
    private function buildAnnouncedBody(array $competition): string
    {
        $name = $this->escape($competition['name']);
        $date = $this->formatDate($competition['date']);
        $venue = $this->escape($competition['venue']);
        $deadline = $this->formatDateTime($competition['registration_deadline']);
        $url = siteUrl('/nevezes/' . $competition['id']);

        $opensAt = $competition['registration_opens_at'] ?? null;

        // A nevezés vagy azonnal nyitott, vagy egy megadott időpontban nyílik.
        // A kettő más cselekvést kíván a tagtól, ezért más a záró bekezdés.
        $openingBlock = $opensAt !== null && $opensAt !== ''
            ? '<p>A nevezés <strong>' . $this->formatDateTime($opensAt)
                . '</strong> időpontban nyílik meg. Akkor újra írunk, hogy ne maradj le róla.</p>'
            : '<p>A nevezés már nyitva van, a lenti hivatkozáson leadhatod.</p>';

        return <<<HTML
<p style="font-size: 16px;">Kedves Sporttárs!</p>

<p>Új versenyt hirdettünk meg:</p>

<div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px; margin: 20px 0;">
    <h2 style="margin: 0 0 10px; font-size: 18px; color: #166534;">{$name}</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Időpont:</td>
            <td style="padding: 5px 0;">{$date}</td>
        </tr>
        <tr>
            <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Helyszín:</td>
            <td style="padding: 5px 0;">{$venue}</td>
        </tr>
        <tr>
            <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Nevezési határidő:</td>
            <td style="padding: 5px 0;">{$deadline}</td>
        </tr>
    </table>
</div>

{$openingBlock}

<p style="margin: 25px 0;">
    <a href="{$url}" style="display: inline-block; background-color: #166534; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">
        Verseny megnyitása
    </a>
</p>

<p style="color: #6b7280; font-size: 14px;">A nevezéshez a fiókodba kell belépned. Mindenki a saját nevében nevez; ha valakinek nincs fiókja, a versenyszervező tudja felvenni a nevezők közé.</p>
HTML;
    }

    /**
     * A nevezésindítási levél törzse.
     *
     * @param array<string, mixed> $competition
     */
    private function buildRegistrationOpenBody(array $competition): string
    {
        $name = $this->escape($competition['name']);
        $date = $this->formatDate($competition['date']);
        $venue = $this->escape($competition['venue']);
        $deadline = $this->formatDateTime($competition['registration_deadline']);
        $url = siteUrl('/nevezes/' . $competition['id']);

        return <<<HTML
<p style="font-size: 16px;">Kedves Sporttárs!</p>

<p>Megnyílt a nevezés a következő versenyre:</p>

<div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px; margin: 20px 0;">
    <h2 style="margin: 0 0 10px; font-size: 18px; color: #166534;">{$name}</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Időpont:</td>
            <td style="padding: 5px 0;">{$date}</td>
        </tr>
        <tr>
            <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Helyszín:</td>
            <td style="padding: 5px 0;">{$venue}</td>
        </tr>
    </table>
</div>

<div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 15px; margin: 20px 0;">
    <p style="margin: 0;">A nevezés <strong>{$deadline}</strong> időpontig tart.</p>
</div>

<p style="margin: 25px 0;">
    <a href="{$url}" style="display: inline-block; background-color: #166534; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">
        Nevezés leadása
    </a>
</p>

<p style="color: #6b7280; font-size: 14px;">A nevezésedet a fiókodban a határidő lejártáig visszavonhatod.</p>
HTML;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function formatDate(string $value): string
    {
        return date('Y. m. d.', strtotime($value));
    }

    private function formatDateTime(string $value): string
    {
        return date('Y. m. d. H:i', strtotime($value));
    }
}
