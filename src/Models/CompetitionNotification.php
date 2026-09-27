<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

/**
 * Kiküldött versenyértesítések nyilvántartása.
 *
 * A tábla célja egyetlen dolog: megakadályozni, hogy ugyanaz az értesítés
 * kétszer menjen ki. Egy klubnál a kétszer megkapott körlevél zavaró, és a
 * kiküldés több helyről indulhat (szervezői gomb, ütemezett futtatás,
 * párhuzamos kérések).
 *
 * Ezért a (competition_id, kind) páron UNIQUE kulcs áll, és a foglalás
 * ELŐBB történik, mint a küldés: aki be tudja szúrni a sort, az küld. Aki
 * ütközik, az tudja, hogy valaki más már elvégezte.
 */
class CompetitionNotification
{
    /** Új versenykiírás */
    public const KIND_ANNOUNCED = 'announced';

    /** Megnyílt a nevezés */
    public const KIND_REGISTRATION_OPEN = 'registration_open';

    public function __construct(private PDO $db)
    {
    }

    /**
     * A kiküldés jogának lefoglalása.
     *
     * A visszatérési érték dönti el, hogy szabad-e küldeni: false esetén
     * ugyanez az értesítés már kiment (vagy épp most megy egy másik
     * folyamatban).
     *
     * A PDOException elkapása itt szándékos: az ütközés NEM hiba, hanem a
     * várt működés. Más adatbázishibát viszont nem nyelünk el csendben,
     * ezért csak az egyediségi ütközést kezeljük false-ként.
     */
    public function claim(string $id, string $competitionId, string $kind): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO competition_notifications (id, competition_id, kind)
                 VALUES (:id, :competition_id, :kind)'
            );

            return $stmt->execute([
                ':id' => $id,
                ':competition_id' => $competitionId,
                ':kind' => $kind,
            ]);
        } catch (PDOException $e) {
            // 23000 = integrity constraint violation (MySQL és SQLite is ezt adja)
            if ($e->getCode() === '23000') {
                return false;
            }

            throw $e;
        }
    }

    /**
     * A kiküldés eredményének rögzítése a lefoglalt soron.
     */
    public function recordResult(string $id, int $recipientCount, int $failedCount): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE competition_notifications
             SET recipient_count = :recipient_count, failed_count = :failed_count
             WHERE id = :id'
        );

        return $stmt->execute([
            ':recipient_count' => $recipientCount,
            ':failed_count' => $failedCount,
            ':id' => $id,
        ]);
    }

    /**
     * Egy foglalás visszavonása.
     *
     * Akkor kell, ha a foglalás megtörtént, de a küldés érdemben el sem
     * indult (például nincs egyetlen címzett sem). Enélkül az értesítés
     * örökre kiküldöttnek látszana, holott senki nem kapta meg.
     */
    public function release(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM competition_notifications WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Egy verseny kiküldött értesítései: fajta => a kiküldés adatai.
     *
     * @return array<string, array{sent_at:string, recipient_count:int, failed_count:int}>
     */
    public function findByCompetition(string $competitionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT kind, sent_at, recipient_count, failed_count
             FROM competition_notifications
             WHERE competition_id = :competition_id'
        );
        $stmt->execute([':competition_id' => $competitionId]);

        $result = [];

        foreach ($stmt->fetchAll() as $row) {
            $result[$row['kind']] = [
                'sent_at' => $row['sent_at'],
                'recipient_count' => (int) $row['recipient_count'],
                'failed_count' => (int) $row['failed_count'],
            ];
        }

        return $result;
    }

    /**
     * Több verseny értesítési állapota egyszerre.
     *
     * A szervezői versenylista ebből tudja soronként kiírni, mi ment már ki -
     * versenyenkénti külön lekérdezés nélkül.
     *
     * @param array<string> $competitionIds
     * @return array<string, array<string, true>> verseny => [fajta => true]
     */
    public function findKindsByCompetitions(array $competitionIds): array
    {
        if ($competitionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($competitionIds), '?'));

        $stmt = $this->db->prepare(
            'SELECT competition_id, kind
             FROM competition_notifications
             WHERE competition_id IN (' . $placeholders . ')'
        );
        $stmt->execute(array_values($competitionIds));

        $result = [];

        foreach ($stmt->fetchAll() as $row) {
            $result[$row['competition_id']][$row['kind']] = true;
        }

        return $result;
    }

    /**
     * Versenyek, amelyeknél a nevezés MÁR megnyílt, de az értesítés még nem
     * ment ki.
     *
     * Ez az ütemezett (cron) és a kézi kiküldés listája. Feltételek:
     *
     *   - a nyitódátum már elmúlt (vagy nincs is megadva, tehát azonnal nyitott),
     *   - a nevezési határidő még nem járt le - lejárt versenyre értesíteni
     *     félrevezető lenne,
     *   - és még nincs 'registration_open' bejegyzés.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_deadline:string, registration_opens_at:?string}>
     */
    public function findDueForRegistrationOpen(): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.name, c.date, c.venue, c.registration_deadline, c.registration_opens_at
             FROM competitions c
             LEFT JOIN competition_notifications n
                    ON n.competition_id = c.id AND n.kind = :kind
             WHERE n.id IS NULL
               AND c.registration_deadline > NOW()
               AND (c.registration_opens_at IS NULL OR c.registration_opens_at <= NOW())
             ORDER BY c.date ASC"
        );
        $stmt->execute([':kind' => self::KIND_REGISTRATION_OPEN]);

        return $stmt->fetchAll();
    }
}
