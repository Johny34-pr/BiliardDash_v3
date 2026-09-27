<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Database;
use App\Services\RankingService;
use App\Services\SeasonService;

/**
 * Ranglista: a szezon pontversenye.
 *
 * Három nézet:
 *   /ranglista              az aktuális szezon állása
 *   /ranglista/archiv       a korábbi szezonok listája
 *   /ranglista/{seasonId}   egy megadott szezon állása
 *
 * Az állás minden esetben SZÁMOLT eredmény a versenyenkénti pontszámokból,
 * nem tárolt összeg - így a szervezői szerkesztés azonnal érvényesül, és a
 * két adat nem tud elcsúszni egymástól.
 */
class RankingController
{
    private RankingService $rankingService;
    private SeasonService $seasonService;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->rankingService = new RankingService($db);
        $this->seasonService = new SeasonService($db);
    }

    /**
     * Az aktuális szezon ranglistája - GET /ranglista
     */
    public function index(): void
    {
        $season = $this->seasonService->getCurrent();

        // Aktuális szezon nélkül nincs mit mutatni, de ez nem hiba: a
        // szervező még nem jelölt ki futó évadot
        if ($season === null) {
            $this->render(null, ['competitions' => [], 'rows' => []], false);
            return;
        }

        $this->render($season, $this->rankingService->getSeasonTable($season['id']), false);
    }

    /**
     * Egy megadott szezon ranglistája - GET /ranglista/{seasonId}
     */
    public function show(string $seasonId): void
    {
        $season = $this->seasonService->getById($seasonId);

        if ($season === null) {
            throw AppException::notFound('A szezon nem található.');
        }

        $this->render(
            $season,
            $this->rankingService->getSeasonTable($season['id']),
            (int) $season['is_archived'] === 1
        );
    }

    /**
     * Archív ranglisták - GET /ranglista/archiv
     *
     * A korábbi szezonok végleges állása. Az archivált szezon eredménye már
     * nem változik, ezért kerül külön oldalra: az élen a futó évad
     * pontversenye a lényeges.
     */
    public function archive(): void
    {
        $seasons = [];

        foreach ($this->seasonService->getArchived() as $season) {
            $seasons[] = [
                'season' => $season,
                'playerCount' => $this->rankingService->countPlayers($season['id']),
            ];
        }

        $pageTitle = 'Archív ranglista - Okányi Biliárd Klub';
        $metaDescription = 'A korábbi szezonok végleges pontverseny-állása.';

        ob_start();
        require __DIR__ . '/../Views/ranking/archive.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * A ranglista táblázat megjelenítése.
     *
     * @param array<string, mixed>|null $season
     * @param array{competitions:array, rows:array} $table
     */
    private function render(?array $season, array $table, bool $isArchived): void
    {
        $competitions = $table['competitions'];
        $rows = $table['rows'];

        // Az archívum hivatkozása csak akkor jelenik meg, ha van benne szezon
        $archivedCount = count($this->seasonService->getArchived());

        $seasonName = $season['name'] ?? null;

        $pageTitle = $seasonName !== null
            ? 'Ranglista ' . $seasonName . ' - Okányi Biliárd Klub'
            : 'Ranglista - Okányi Biliárd Klub';

        $metaDescription = $seasonName !== null
            ? sprintf(
                'A %s szezon pontversenyének állása: %d játékos, %d verseny.',
                $seasonName,
                count($rows),
                count($competitions)
            )
            : 'A klub szezonális pontversenyének állása versenyenkénti bontásban.';

        ob_start();
        require __DIR__ . '/../Views/ranking/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
