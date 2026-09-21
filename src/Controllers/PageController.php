<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Database;
use App\Services\PageService;

/**
 * Statikus és szerkeszthető tartalmi oldalak megjelenítése.
 *
 * Kétféle oldal van itt:
 *
 *   1. Adatbázisból szerkeszthető: Rólunk, Emlékoldal, Adatkezelési
 *      tájékoztató. A szövegüket a szervező a felületen írja át.
 *
 *   2. Statikus: Társhonlapok. A tartalma a config/contact.php-ból és egy
 *      nézetből áll össze, mert ritkán változik, és a szerkezete
 *      (elérhetőségek, hivatkozások) kötöttebb annál, hogy szabad szöveges
 *      szerkesztőt érdemes lenne rá adni.
 */
class PageController
{
    private PageService $pageService;

    public function __construct()
    {
        $this->pageService = new PageService(Database::getConnection());
    }

    /**
     * Rólunk - GET /rolunk
     */
    public function about(): void
    {
        $this->renderPage(PageService::SLUG_ABOUT);
    }

    /**
     * Emlékoldal - GET /emlekoldal
     */
    public function memorial(): void
    {
        $this->renderPage(PageService::SLUG_MEMORIAL);
    }

    /**
     * Adatkezelési tájékoztató - GET /adatkezeles
     */
    public function privacy(): void
    {
        $this->renderPage(PageService::SLUG_PRIVACY);
    }

    /**
     * Társhonlapok - GET /tarshonlapok
     *
     * Statikus oldal: a kapcsolódó szervezetek hivatkozásai, a biliárdterem
     * és a kapcsolattartó elérhetőségei, valamint a közösségi oldalak.
     */
    public function partners(): void
    {
        $contact = contactConfig();

        $pageTitle = 'Társhonlapok és kapcsolat - Okányi Biliárd Klub';
        $metaDescription = 'Kapcsolódó biliárd szervezetek honlapjai, az Okányi Biliárdterem '
            . 'elérhetőségei és a közösségi oldalaink.';

        ob_start();
        require __DIR__ . '/../Views/pages/partners.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Adatbázisból szerkeszthető oldal renderelése.
     *
     * A meta leírás az oldal saját mezőjéből jön; ha nincs kitöltve, a
     * head partial az általános alapleírásra esik vissza.
     */
    private function renderPage(string $slug): void
    {
        $page = $this->pageService->getPageBySlug($slug);

        if ($page === null) {
            throw AppException::notFound('A keresett oldal nem található.');
        }

        $pageTitle = $page['title'] . ' - Okányi Biliárd Klub';
        $metaDescription = $page['meta_description'] ?? null;

        ob_start();
        require __DIR__ . '/../Views/pages/show.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
