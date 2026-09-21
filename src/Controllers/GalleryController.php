<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Database;
use App\Services\GalleryService;
use App\Services\ImageService;

/**
 * Galéria: versenyalbumok borítóképpel és helyezettekkel.
 *
 * Egy album egy verseny eredményhirdetését jelenti. A lista az albumok
 * borítóképét mutatja, az album oldala pedig a nagy képet a helyezettek
 * névsorával egymás mellett.
 */
class GalleryController
{
    private GalleryService $galleryService;

    public function __construct()
    {
        $db = Database::getConnection();
        $imageService = new ImageService();
        $this->galleryService = new GalleryService($db, $imageService);
    }

    /**
     * Albumok listázása - GET /galeria
     */
    public function index(): void
    {
        $albums = $this->galleryService->getAlbumsForListing();

        $pageTitle = 'Galéria - Okányi Biliárd Klub';
        $metaDescription = 'Versenyalbumok a klub versenyeiről: '
            . 'a versenyek fotói és helyezettjei.';

        // Output buffering a layout-hoz
        ob_start();
        require __DIR__ . '/../Views/gallery/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Album oldala - GET /galeria/{albumId}
     *
     * Balra a nagy borítókép (kattintásra teljes méretben nagyítható),
     * jobbra a verseny helyezettjei. A bezárás visszavisz az albumokhoz.
     */
    public function show(string $albumId): void
    {
        $view = $this->galleryService->getAlbumView($albumId);

        if ($view === null) {
            throw AppException::notFound('Az album nem található.');
        }

        $album = $view['album'];
        $cover = $view['cover'];
        $placements = $view['placements'];

        $pageTitle = $album['name'] . ' - Galéria - Okányi Biliárd Klub';
        $metaDescription = $this->buildDescription($album, $placements);

        // Megosztási kép: az album borítója, hogy a link a saját fotójával
        // jelenjen meg a közösségi oldalakon
        $ogImage = $cover !== null ? '/' . $cover['full_path'] : null;

        ob_start();
        require __DIR__ . '/../Views/gallery/show.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Oldalleírás az album adataiból.
     *
     * A helyezettek nevei értékes keresési tartalmat adnak, ezért a dobogó
     * bekerül a leírásba, ha van felvitt eredmény.
     *
     * @param array<array{position:int, player_name:string}> $placements
     */
    private function buildDescription(array $album, array $placements): string
    {
        if ($placements === []) {
            return sprintf('%s - fotók a klub galériájában.', $album['name']);
        }

        $names = [];
        foreach (array_slice($placements, 0, 3) as $placement) {
            $names[] = $placement['position'] . '. ' . $placement['player_name'];
        }

        return sprintf('%s helyezettjei: %s.', $album['name'], implode(', ', $names));
    }
}
