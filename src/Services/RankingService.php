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
     * A rendezést a HELYEZÉS vezeti, nem a pontszám. Aki hátrébb végzett, a
     * nagyobb pontszámával sem kerül előre - a helyezés az eredmény, a pont
     * csak a belőle származtatott, a szervező által felülírható érték.
     *
     * A helyezés VERSENYENKÉNT értelmezett, egy játékosnak szezononként több
     * is van, ezért a szezon rendezéséhez az ÁTLAGOS helyezés szolgál. Miért
     * az átlag és nem az összeg: az összeg azt jutalmazná, aki kevesebb
     * versenyen indult, hiszen kevesebb helyezés adódna össze nála.
     *
     * A teljes sorrend: átlagos helyezés növekvően, majd összpontszám
     * csökkenően, majd a pontot szerzett versenyek száma, végül a név
     * betűrendje - így a holtverseny sem ad futásonként változó sorrendet.
     * Akinek egyetlen helyezése sincs, a helyezettek mögé kerül.
     *
     * @return array{competitions:array<array{id:string, name:string, date:string}>, rows:array<array{player_name:string, user_id:?string, points:array<string,int>, places:array<string,?int>, total:int, played:int, best_place:?int, place_average:?float, position:int}>}
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
                    'places' => [],
                    'total' => 0,
                    'played' => 0,
                    'best_place' => null,
                    'place_sum' => 0,
                    'place_count' => 0,
                    'place_average' => null,
                ];
            }

            // A (competition_id, player_name) egyediségi kulcs miatt egy
            // játékos versenyenként egyszer szerepel, tehát nincs összeadás
            $points = (int) $entry['points'];
            $players[$name]['points'][$competitionId] = $points;
            $players[$name]['total'] += $points;
            $players[$name]['played']++;

            $place = $entry['place'] !== null ? (int) $entry['place'] : null;
            $players[$name]['places'][$competitionId] = $place;

            if ($place !== null) {
                $players[$name]['place_sum'] += $place;
                $players[$name]['place_count']++;

                if ($players[$name]['best_place'] === null || $place < $players[$name]['best_place']) {
                    $players[$name]['best_place'] = $place;
                }
            }

            // Ha bármelyik bejegyzésnél van fiók, azt megtartjuk
            if ($entry['user_id'] !== null) {
                $players[$name]['user_id'] = $entry['user_id'];
            }
        }

        // Az átlagos helyezés a rendezés kulcsa, ezért itt áll elő, egyszer
        foreach ($players as $name => $player) {
            $players[$name]['place_average'] = $player['place_count'] > 0
                ? $player['place_sum'] / $player['place_count']
                : null;
        }

        $rows = array_values($players);

        /*
         * A szezon sorrendje. Szándékosan nem a tömb-összehasonlító
         * rövidítéssel, mert a kulcsok iránya váltakozik (a helyezés
         * növekvő, a pont csökkenő), és az egy soros alakban ez könnyen
         * elromlik.
         */
        usort($rows, static function (array $a, array $b): int {
            // 1. A HELYEZÉS az elsődleges: kisebb átlag előrébb. A több pont
            //    ezen NEM változtat - egy hátrébb végzett játékos a nagyobb
            //    pontszámával sem kerül előre.
            //    Akinek egyetlen helyezése sincs, a helyezettek MÖGÉ kerül.
            $placeA = $a['place_average'] ?? INF;
            $placeB = $b['place_average'] ?? INF;

            if ($placeA !== $placeB) {
                return $placeA <=> $placeB;
            }

            // 2. Azonos helyezés-átlagnál több pont előrébb
            if ($a['total'] !== $b['total']) {
                return $b['total'] <=> $a['total'];
            }

            // 3. Több versenyen szerzett pont előrébb
            if ($a['played'] !== $b['played']) {
                return $b['played'] <=> $a['played'];
            }

            // 4. Végül betűrend, hogy a sorrend futásonként ne változzon
            return $a['player_name'] <=> $b['player_name'];
        });

        /*
         * Helyezés a táblázatban. Holtversenyt CSAK akkor jelölünk, ha a
         * rendezés szerint is egyenlők: azonos helyezés-átlag ÉS azonos
         * összpontszám. Ha bármelyik megkülönböztette őket, külön helyet
         * kapnak - különben a táblázat két egymást követő sort ugyanazzal a
         * számmal jelölne, miközben a sorrendjük szándékos.
         *
         * A holtverseny után a következő helyezés annyival ugrik, ahányan
         * előtte állnak.
         */
        $position = 0;
        $previousKey = null;

        foreach ($rows as $index => $row) {
            $key = ($row['place_average'] === null ? 'nincs' : (string) $row['place_average'])
                . ':' . $row['total'];

            if ($previousKey === null || $key !== $previousKey) {
                $position = $index + 1;
                $previousKey = $key;
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
