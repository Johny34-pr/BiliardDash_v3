<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Services\BroadcastService;

/**
 * Közvetítések: az élő adás és a korábbiak archívuma.
 *
 * Egyetlen oldal, mert a két dolog ugyanaz a lista: ami most fut, az az élő
 * adás, a többi a visszatekintés. A lista élén mindig az élő közvetítés áll,
 * így a főmenüből ide érkező látogató egy kattintással a futó adásnál van.
 *
 * A keresés a VERSENY NEVÉRE megy (és a közvetítés saját nevére), mert a
 * régebbi adást a látogató így keresi: "melyik volt az Okány Kupa adása".
 */
class BroadcastController
{
    /** Közvetítések száma egy oldalon */
    private const PER_PAGE = 12;

    private BroadcastService $broadcastService;

    public function __construct()
    {
        $this->broadcastService = new BroadcastService(Database::getConnection());
    }

    /**
     * Közvetítések listája kereséssel - GET /kozvetitesek
     *
     * Paraméterek: ?kereses=... a keresőszó, ?oldal=N a lapozás.
     */
    public function index(): void
    {
        $term = trim((string) ($_GET['kereses'] ?? ''));

        $total = $this->broadcastService->countSearch($term);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, (int) ($_GET['oldal'] ?? 1)), $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;

        $broadcasts = $this->broadcastService->search($term, self::PER_PAGE, $offset);

        // A teljes készlet akkor is kell, ha a keresés nem adott találatot:
        // ebből tudja a nézet, hogy "nincs ilyen közvetítés" vagy "még
        // egyetlen közvetítés sincs" - a kettő más üres állapot.
        $libraryState = $this->broadcastService->getMenuState();
        $libraryCount = $libraryState['total'];

        // A lapozás és a keresés megőrzéséhez a nézet ezt fűzi a linkekre
        $searchQuery = $term !== '' ? '?kereses=' . urlencode($term) : '';

        $pageTitle = $term !== ''
            ? 'Közvetítések - keresés: ' . $term . ' - Okányi Biliárd Klub'
            : ($page > 1
                ? "Közvetítések ({$page}. oldal) - Okányi Biliárd Klub"
                : 'Közvetítések - Okányi Biliárd Klub');

        $metaDescription = 'Az Okányi Biliárd Klub élő közvetítései és a korábbi '
            . 'adások archívuma. A régebbi közvetítések a verseny neve alapján '
            . 'is visszakereshetők.';

        /*
         * A találati lista nem önálló tartalom, ezért kimarad az indexből:
         * ugyanazok a közvetítések jelennek meg benne, mint a teljes
         * listában, csak megszűrve. A keresés nélküli lapok indexelhetők.
         */
        if ($term !== '') {
            $noIndex = true;
        } elseif ($page > 1) {
            // Lapozásnál a lapszám valódi tartalmi különbség, ezért az adott
            // lap önmagára kanonizál
            $canonical = siteUrl('/kozvetitesek') . '?oldal=' . $page;
        }

        ob_start();
        require __DIR__ . '/../Views/broadcasts/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
