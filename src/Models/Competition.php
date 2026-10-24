<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Competition
{
    /**
     * A versenyek lekérdezéseiben használt oszloplista.
     *
     * Egy helyen definiálva, hogy a findOpen/findAll/findById ugyanazt az
     * adatkört adja vissza, és új oszlop hozzáadásakor ne csúszhassanak szét.
     */
    private const COLUMNS = 'c.id, c.season_id, c.name, c.date, c.venue,
                    c.registration_opens_at, c.registration_deadline,
                    c.registrant_count, c.created_at, c.updated_at,
                    c.protocol_path, c.protocol_name, c.protocol_size,
                    c.protocol_uploaded_at,
                    s.name AS season_name';

    /**
     * A szezon nevét minden lekérdezés mellé hozzuk.
     *
     * LEFT JOIN, mert a verseny lehet szezon nélküli: a régi versenyeknél nem
     * tudjuk biztosan, melyik évadhoz tartoztak, és a szervező utólag
     * állíthatja be.
     */
    private const FROM = 'FROM competitions c LEFT JOIN seasons s ON s.id = c.season_id';

    public function __construct(private PDO $db)
    {
    }

    /**
     * Nevezésre meghirdetett versenyek: a határidő még nem járt le.
     *
     * A még nem megnyílt nevezésű versenyek is szerepelnek a listában,
     * hogy a látogató előre lássa őket - a nevezés gomb helyett a nyitás
     * időpontja jelenik meg. Így a kiírás és a nevezés szétválik.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string, registrant_count:int, created_at:string, updated_at:string}>
     */
    public function findOpen(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             ' . self::FROM . '
             WHERE c.registration_deadline > NOW()
             ORDER BY c.date ASC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Közelgő versenyek: a verseny napja még nem volt.
     *
     * A szűrés a verseny DÁTUMÁRA megy, nem a nevezési határidőre. Így a
     * verseny a határidő lejárta után is látható marad - egészen a verseny
     * napjának végéig -, tehát a nevezői lista és a versenykiírás a helyszínen
     * is elérhető. A findOpen() ettől eltérően azt mondja meg, hol lehet MÉG
     * nevezni; a kettőt nem szabad összekeverni.
     *
     * A CURDATE() napra kerekít, ezért a mai nap még beleszámít.
     *
     * @return array<array<string, mixed>>
     */
    public function findUpcoming(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             ' . self::FROM . '
             WHERE c.date >= CURDATE()
             ORDER BY c.date ASC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Lezárult versenyek: a verseny napja elmúlt.
     *
     * A legfrissebb elöl, mert a visszatekintés jellemzően a legutóbbi
     * versennyel kezdődik.
     *
     * @return array<array<string, mixed>>
     */
    public function findPast(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             ' . self::FROM . '
             WHERE c.date < CURDATE()
             ORDER BY c.date DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * A meghirdetett versenyek száma (a határidő még nem járt le).
     */
    public function countOpen(): int
    {
        return (int) $this->db
            ->query('SELECT COUNT(*) FROM competitions WHERE registration_deadline > NOW()')
            ->fetchColumn();
    }

    /**
     * Összes verseny lekérdezése dátum szerint csökkenő sorrendben.
     *
     * @return array<array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string, registrant_count:int, created_at:string, updated_at:string}>
     */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT ' . self::COLUMNS . '
             ' . self::FROM . '
             ORDER BY c.date DESC'
        );

        return $stmt->fetchAll();
    }

    /**
     * Egy verseny lekérdezése ID alapján.
     *
     * @return array{id:string, name:string, date:string, venue:string, registration_opens_at:?string, registration_deadline:string, registrant_count:int, created_at:string, updated_at:string}|null
     */
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ' . self::COLUMNS . '
             ' . self::FROM . '
             WHERE c.id = :id'
        );
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Új verseny létrehozása.
     *
     * @param string|null $registrationOpensAt A nevezés nyitásának időpontja,
     *                                         vagy null, ha azonnal nyitott.
     */
    public function create(
        string $id,
        string $name,
        string $date,
        string $venue,
        string $registrationDeadline,
        ?string $registrationOpensAt = null,
        ?string $seasonId = null
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO competitions
                (id, season_id, name, date, venue, registration_opens_at, registration_deadline)
             VALUES
                (:id, :season_id, :name, :date, :venue, :registration_opens_at, :registration_deadline)'
        );

        return $stmt->execute([
            ':id' => $id,
            ':season_id' => $seasonId,
            ':name' => $name,
            ':date' => $date,
            ':venue' => $venue,
            ':registration_opens_at' => $registrationOpensAt,
            ':registration_deadline' => $registrationDeadline,
        ]);
    }

    /**
     * Verseny frissítése.
     *
     * @param string|null $registrationOpensAt A nevezés nyitásának időpontja,
     *                                         vagy null, ha azonnal nyitott.
     */
    public function update(
        string $id,
        string $name,
        string $date,
        string $venue,
        string $registrationDeadline,
        ?string $registrationOpensAt = null,
        ?string $seasonId = null
    ): bool {
        $stmt = $this->db->prepare(
            'UPDATE competitions
             SET season_id = :season_id,
                 name = :name, date = :date, venue = :venue,
                 registration_opens_at = :registration_opens_at,
                 registration_deadline = :registration_deadline
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':season_id' => $seasonId,
            ':name' => $name,
            ':date' => $date,
            ':venue' => $venue,
            ':registration_opens_at' => $registrationOpensAt,
            ':registration_deadline' => $registrationDeadline,
        ]);
    }

    /**
     * Verseny törlése.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM competitions WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    /**
     * A verseny jegyzőkönyvének rögzítése.
     *
     * Versenyenként EGY jegyzőkönyv van: az újabb feltöltés felülírja a
     * korábbit. Az eredeti fájlnevet is eltároljuk, mert a tárolt név
     * generált UUID - a látogató viszont beszédes nevet kap letöltéskor.
     */
    public function setProtocol(string $id, string $path, string $originalName, int $size): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE competitions
             SET protocol_path = :path,
                 protocol_name = :name,
                 protocol_size = :size,
                 protocol_uploaded_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            ':id' => $id,
            ':path' => $path,
            ':name' => $originalName,
            ':size' => $size,
        ]);
    }

    /**
     * A jegyzőkönyv hivatkozásának törlése a versenyről.
     *
     * Csak az adatbázis-mezőket üríti; a fájl törléséről a szolgáltatás
     * gondoskodik, mert az a fájlrendszer dolga.
     */
    public function clearProtocol(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE competitions
             SET protocol_path = NULL,
                 protocol_name = NULL,
                 protocol_size = NULL,
                 protocol_uploaded_at = NULL
             WHERE id = :id'
        );

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Verseny nevezőszámának növelése eggyel.
     */
    public function incrementRegistrantCount(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE competitions SET registrant_count = registrant_count + 1 WHERE id = :id'
        );

        return $stmt->execute([':id' => $id]);
    }

    /**
     * Verseny nevezőszámának csökkentése eggyel.
     *
     * A registrant_count UNSIGNED, ezért a `> 0` feltétel véd az alulcsordulástól.
     */
    public function decrementRegistrantCount(string $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE competitions
             SET registrant_count = registrant_count - 1
             WHERE id = :id AND registrant_count > 0'
        );

        return $stmt->execute([':id' => $id]);
    }
}
