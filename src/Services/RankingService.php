<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Models\Competition;
use App\Models\RankingEntry;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Szezon pontverseny (ranglista).
 *
 * A ranglista SZÁMOLT eredmény, nem tárolt összeg: minden megjelenítés a
 * ranking_entries soraiból áll össze. Ezért nincs "újraszámolás" művelet -
 * nem is lehet elmaradni vele. Ha a szervező módosít egy pontszámot, a
 * következő oldalbetöltés már a helyes állást mutatja.
 *
 * A táblázat alakja: minden SOR egy játékos, minden OSZLOP egy verseny, a
 * végén az összesítés. Ez az a forma, amit a szervező kért: mindenkinél
 * versenyenként lebontva látszanak a pontok.
 */
class RankingService
{
    private RankingEntry $entryModel;
    private Competition $competitionModel;

    public function __construct(private PDO $db)
    {
        $this->entryModel = new RankingEntry($db);
        $this->competitionModel = new Competition($db);
    }

    /**
     * Egy szezon teljes ranglistája, megjelenítésre kész alakban.
     *
     * A visszaadott szerkezet:
     *   competitions: a szezon versenyei, amelyeken van pontszám (oszlopok)
     *   rows:         játékosonként egy sor, a versenyenkénti pontokkal
     *                 (verseny azonosító => pont) és az összeggel
     *
     * A rendezés: több pont előrébb; pontegyenlőségnél a több megszerzett
     * versenypont-bejegyzés, végül a név betűrendje dönt. Így a holtverseny
     * sem ad futásonként változó sorrendet.
     *
     * @return array{competitions:array<array{id:string, name:string, date:string}>, rows:array<array{player_name:string, user_id:?string, points:array<string,int>, total:int, played:int, position:int}>}
     */
    public function getSeasonTable(string $seasonId): array
    {
        $entries = $this->entryModel->findBySeason($seasonId);

        if ($entries === []) {
            return ['competitions' => [], 'rows' => []];
        }

        // Oszlopok: a szezon azon versenyei, amelyeken van pontszám. A
        // pontszám nélküli verseny üres oszlopot adna, ami csak zaj.
        $competitions = [];
        $players = [];

        foreach ($entries as $entry) {
            $competitionId = $entry['competition_id'];

            if (!isset($competitions[$competitionId])) {
                $competitions[$competitionId] = [
                    'id' => $competitionId,
                    'name' => $entry['competition_name'],
                    'date' => $entry['competition_date'],
                ];
            }

            $name = $entry['player_name'];

            if (!isset($players[$name])) {
                $players[$name] = [
                    'player_name' => $name,
                    'user_id' => $entry['user_id'],
                    'points' => [],
                    'total' => 0,
                    'played' => 0,
                ];
            }

            // A (competition_id, player_name) egyediségi kulcs miatt egy
            // játékos versenyenként egyszer szerepel, tehát nincs összeadás
            $points = (int) $entry['points'];
            $players[$name]['points'][$competitionId] = $points;
            $players[$name]['total'] += $points;
            $players[$name]['played']++;

            // Ha bármelyik bejegyzésnél van fiók, azt megtartjuk
            if ($entry['user_id'] !== null) {
                $players[$name]['user_id'] = $entry['user_id'];
            }
        }

        $rows = array_values($players);

        usort($rows, static function (array $a, array $b): int {
            return [$b['total'], $b['played'], $a['player_name']]
                <=> [$a['total'], $a['played'], $b['player_name']];
        });

        // Helyezés: a holtverseny ugyanazt a helyet kapja, az utána
        // következő pedig annyival lejjebb kerül, ahányan előtte állnak
        $position = 0;
        $previousTotal = null;

        foreach ($rows as $index => $row) {
            if ($previousTotal === null || $row['total'] !== $previousTotal) {
                $position = $index + 1;
                $previousTotal = $row['total'];
            }

            $rows[$index]['position'] = $position;
        }

        return [
            'competitions' => array_values($competitions),
            'rows' => $rows,
        ];
    }

    /**
     * Hány játékos szerzett pontot a szezonban.
     */
    public function countPlayers(string $seasonId): int
    {
        return $this->entryModel->countPlayersInSeason($seasonId);
    }

    /**
     * Egy verseny pontszámai a szervezői szerkesztéshez.
     *
     * @return array<array{id:string, competition_id:string, user_id:?string, player_name:string, points:int, place:?int}>
     */
    public function getCompetitionEntries(string $competitionId): array
    {
        return $this->entryModel->findByCompetition($competitionId);
    }

    /**
     * @return array{id:string, competition_id:string, user_id:?string, player_name:string, points:int, place:?int}|null
     */
    public function getEntry(string $id): ?array
    {
        return $this->entryModel->findById($id);
    }

    /**
     * Pontszám felvitele egy versenyhez.
     *
     * @throws AppException Ha a verseny nem létezik, vagy a játékos már
     *                      szerepel ezen a versenyen.
     */
    public function addEntry(
        string $competitionId,
        string $playerName,
        int $points,
        ?int $place = null,
        ?string $userId = null
    ): string {
        if ($this->competitionModel->findById($competitionId) === null) {
            throw AppException::notFound('A verseny nem található');
        }

        $playerName = trim($playerName);

        if ($this->entryModel->playerExists($competitionId, $playerName)) {
            throw AppException::duplicateEntry(
                'Ez a játékos már szerepel ezen a versenyen. A meglévő pontszámát szerkeszd.'
            );
        }

        $id = Uuid::uuid4()->toString();

        $this->entryModel->create($id, $competitionId, $playerName, $points, $place, $userId);

        return $id;
    }

    /**
     * Pontszám módosítása.
     *
     * A ranglista számolt eredmény, ezért a mentés után nincs további
     * teendő: az összesítés a következő betöltéskor már ezt tartalmazza.
     *
     * @return string A verseny azonosítója, ahová a bejegyzés tartozik
     * @throws AppException Ha a bejegyzés nem létezik, vagy a név ütközik.
     */
    public function updateEntry(string $id, string $playerName, int $points, ?int $place = null): string
    {
        $entry = $this->entryModel->findById($id);

        if ($entry === null) {
            throw AppException::notFound('A pontszám nem található');
        }

        $playerName = trim($playerName);

        if ($this->entryModel->playerExists($entry['competition_id'], $playerName, $id)) {
            throw AppException::duplicateEntry('Ezen a versenyen már szerepel ez a játékos.');
        }

        $this->entryModel->update($id, $playerName, $points, $place);

        return $entry['competition_id'];
    }

    /**
     * Pontszám törlése.
     *
     * @return string A verseny azonosítója, ahová a bejegyzés tartozott
     * @throws AppException Ha a bejegyzés nem létezik.
     */
    public function deleteEntry(string $id): string
    {
        $entry = $this->entryModel->findById($id);

        if ($entry === null) {
            throw AppException::notFound('A pontszám nem található');
        }

        $this->entryModel->delete($id);

        return $entry['competition_id'];
    }

    /**
     * Pontszámok átvétele az album helyezettjeiből.
     *
     * A szervező a galériában amúgy is felviszi a dobogót, ezért a nevek
     * kétszeri beírása felesleges munka lenne. A pontokat a HELYEZÉSBŐL
     * származtatjuk egy egyszerű, kiszámítható skálával.
     *
     * A már meglévő bejegyzéseket NEM írja felül: ha a szervező kézzel
     * módosított egy pontszámot, azt a másolás nem teheti tönkre.
     *
     * @param array<array{position:int, player_name:string}> $placements
     * @return array{added:int, skipped:int}
     */
    public function importFromPlacements(string $competitionId, array $placements): array
    {
        $added = 0;
        $skipped = 0;

        foreach ($placements as $placement) {
            $playerName = trim($placement['player_name']);
            $position = (int) $placement['position'];

            if ($playerName === '') {
                continue;
            }

            if ($this->entryModel->playerExists($competitionId, $playerName)) {
                $skipped++;
                continue;
            }

            $this->entryModel->create(
                Uuid::uuid4()->toString(),
                $competitionId,
                $playerName,
                self::pointsForPlace($position),
                $position
            );

            $added++;
        }

        return ['added' => $added, 'skipped' => $skipped];
    }

    /**
     * Helyezésből származtatott pontszám.
     *
     * Egyszerű, kiszámítható skála: az első hat helyezés kiemelt pontot kap,
     * a további helyezettek egyet. A szervező bármelyik értéket átírhatja -
     * ez csak kényelmes kezdőérték, nem szabályzat.
     */
    public static function pointsForPlace(int $place): int
    {
        return match ($place) {
            1 => 12,
            2 => 9,
            3 => 7,
            4 => 5,
            5 => 3,
            6 => 2,
            default => 1,
        };
    }
}
