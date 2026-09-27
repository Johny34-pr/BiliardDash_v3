<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Models\Season;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Szezonok kezelése.
 *
 * A szezon két funkciót fog össze: a galéria archívumát (melyik évad albumai)
 * és a ranglistát (melyik évad pontjai). Ezért egyetlen szolgáltatás kezeli
 * mindkettőt - így nem lehet a két helyen eltérő szezonlista.
 *
 * Az "aktuális" jelző kizárólagos: egyszerre legfeljebb egy szezon lehet az.
 * A kizárólagosságot itt érvényesítjük, mert adatbázis-megkötéssel nem
 * fejezhető ki egyszerűen.
 */
class SeasonService
{
    private Season $seasonModel;

    public function __construct(private PDO $db)
    {
        $this->seasonModel = new Season($db);
    }

    /**
     * @return array<array{id:string, name:string, starts_on:?string, is_current:int, is_archived:int, created_at:string, updated_at:string}>
     */
    public function getAll(): array
    {
        return $this->seasonModel->findAll();
    }

    /**
     * Az archivált szezonok - ezek adják a galéria és a ranglista archívumát.
     *
     * @return array<array{id:string, name:string, starts_on:?string, is_current:int, is_archived:int, created_at:string, updated_at:string}>
     */
    public function getArchived(): array
    {
        return $this->seasonModel->findArchived();
    }

    /**
     * @return array{id:string, name:string, starts_on:?string, is_current:int, is_archived:int, created_at:string, updated_at:string}|null
     */
    public function getCurrent(): ?array
    {
        return $this->seasonModel->findCurrent();
    }

    /**
     * @return array{id:string, name:string, starts_on:?string, is_current:int, is_archived:int, created_at:string, updated_at:string}|null
     */
    public function getById(string $id): ?array
    {
        return $this->seasonModel->findById($id);
    }

    /**
     * Egyszerű választólista az űrlapokhoz: azonosító => megjelenő név.
     *
     * Az aktuális szezon nevéhez jelzést fűz, hogy a szervező a legördülő
     * listában is lássa, melyik a futó évad.
     *
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        $options = [];

        foreach ($this->seasonModel->findAll() as $season) {
            $label = $season['name'];

            if ((int) $season['is_current'] === 1) {
                $label .= ' (aktuális)';
            } elseif ((int) $season['is_archived'] === 1) {
                $label .= ' (archív)';
            }

            $options[$season['id']] = $label;
        }

        return $options;
    }

    /**
     * Új szezon.
     *
     * @throws AppException Ha a név már használatban van.
     */
    public function create(string $name, ?string $startsOn, bool $isCurrent = false): string
    {
        $name = trim($name);

        if ($this->seasonModel->nameExists($name)) {
            throw AppException::duplicateEntry('Ezzel a névvel már létezik szezon');
        }

        $id = Uuid::uuid4()->toString();

        $this->seasonModel->create($id, $name, $this->normalizeDate($startsOn), false);

        // A jelölést külön lépésben tesszük fel, hogy a kizárólagosságot
        // ugyanaz a metódus biztosítsa, mint a későbbi váltásoknál
        if ($isCurrent) {
            $this->makeCurrent($id);
        }

        return $id;
    }

    /**
     * Szezon adatainak módosítása.
     *
     * @throws AppException Ha a szezon nem létezik, vagy a név foglalt.
     */
    public function update(string $id, string $name, ?string $startsOn): void
    {
        if ($this->seasonModel->findById($id) === null) {
            throw AppException::notFound('A szezon nem található');
        }

        $name = trim($name);

        if ($this->seasonModel->nameExists($name, $id)) {
            throw AppException::duplicateEntry('Ezzel a névvel már létezik szezon');
        }

        $this->seasonModel->update($id, $name, $this->normalizeDate($startsOn));
    }

    /**
     * Az aktuális szezon kijelölése.
     *
     * Előbb minden szezonról leveszi a jelzőt, majd erre az egyre felteszi.
     * Az aktuálisra jelölt szezon nem lehet archív is: a kettő egymást
     * kizárja, ezért az archiválást is visszavonjuk.
     *
     * @throws AppException Ha a szezon nem létezik.
     */
    public function makeCurrent(string $id): void
    {
        if ($this->seasonModel->findById($id) === null) {
            throw AppException::notFound('A szezon nem található');
        }

        $this->seasonModel->clearCurrent();
        $this->seasonModel->setCurrent($id);
        $this->seasonModel->setArchived($id, false);
    }

    /**
     * Szezon archiválása vagy visszahelyezése.
     *
     * Az aktuális szezon nem archiválható: amíg fut, az eredményei
     * változhatnak, tehát nem tekinthető véglegesnek. Először másik szezont
     * kell aktuálisra állítani.
     *
     * @throws AppException Ha a szezon nem létezik, vagy az aktuálisat
     *                      próbálnánk archiválni.
     */
    public function setArchived(string $id, bool $archived): void
    {
        $season = $this->seasonModel->findById($id);

        if ($season === null) {
            throw AppException::notFound('A szezon nem található');
        }

        if ($archived && (int) $season['is_current'] === 1) {
            throw new AppException(
                'Az aktuális szezon nem archiválható. Előbb jelölj ki másik szezont aktuálisként.',
                AppException::VALIDATION_ERROR
            );
        }

        $this->seasonModel->setArchived($id, $archived);
    }

    /**
     * Szezon törlése.
     *
     * Az aktuális szezon nem törölhető, mert az új tartalom hozzá kerülne.
     * A hozzá tartozó versenyek és albumok megmaradnak, csak szezon
     * nélkülivé válnak (ON DELETE SET NULL).
     *
     * @throws AppException Ha a szezon nem létezik, vagy az aktuális.
     */
    public function delete(string $id): void
    {
        $season = $this->seasonModel->findById($id);

        if ($season === null) {
            throw AppException::notFound('A szezon nem található');
        }

        if ((int) $season['is_current'] === 1) {
            throw new AppException(
                'Az aktuális szezon nem törölhető. Előbb jelölj ki másik szezont aktuálisként.',
                AppException::VALIDATION_ERROR
            );
        }

        $this->seasonModel->delete($id);
    }

    /**
     * Üres dátum null-ra alakítása.
     *
     * Az űrlap üres mezője '' értéket küld, az adatbázisban viszont a
     * "nincs megadva" állapot null - különben érvénytelen dátum kerülne be.
     */
    private function normalizeDate(?string $date): ?string
    {
        $date = trim((string) $date);

        return $date === '' ? null : $date;
    }
}
