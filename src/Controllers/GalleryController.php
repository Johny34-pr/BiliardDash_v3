<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Database;
use App\Services\GalleryService;
use App\Services\ImageService;

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
        $albums = $this->galleryService->getAlbums();

        // Borítókép URL-ek betöltése az albumokhoz
        foreach ($albums as &$album) {
            $album['cover_image_url'] = null;
            if ($album['cover_image_id'] !== null) {
                $images = $this->galleryService->getAlbumImages($album['id']);
                foreach ($images as $image) {
                    if ($image['id'] === $album['cover_image_id']) {
                        $album['cover_image_url'] = '/' . $image['thumbnail_path'];
                        break;
                    }
                }
            }
        }
        unset($album);

        $pageTitle = 'Galéria - Magyar Biliárd';
        $metaDescription = 'Fotógaléria a magyar biliárd versenyeiről és eseményeiről. '
            . 'Böngészd az albumokat versenyenként.';

        // Output buffering a layout-hoz
        ob_start();
        require __DIR__ . '/../Views/gallery/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Album képei - GET /galeria/{albumId}
     */
    public function show(string $albumId): void
    {
        // Album adatok lekérdezése
        $db = Database::getConnection();
        $albumModel = new \App\Models\Album($db);
        $album = $albumModel->findById($albumId);

        if ($album === null) {
            throw AppException::notFound('Az album nem található.');
        }

        $images = $this->galleryService->getAlbumImages($albumId);

        // A címet nyersen adjuk át: az escape-elés a head partial dolga.
        // Kétszeres e() hívásból korábban "&amp;" jelent meg a fülön.
        $pageTitle = $album['name'] . ' - Galéria - Magyar Biliárd';
        $imageCount = count($images);
        $metaDescription = $imageCount > 0
            ? sprintf('%s - %d fotó a magyar biliárd galériájában.', $album['name'], $imageCount)
            : sprintf('%s album a magyar biliárd fotógalériájában.', $album['name']);

        // Megosztási kép: az album borítója, hogy a link az album saját
        // fotójával jelenjen meg. A teljes méretű képet adjuk, mert a
        // 200x200-as bélyegkép a közösségi kártyákon elmosódna.
        $ogImage = $this->resolveCoverImage($album, $images);

        // Output buffering a layout-hoz
        ob_start();
        require __DIR__ . '/../Views/gallery/show.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Az album megosztási képének kiválasztása.
     *
     * Elsőként a beállított borítóképet keressük, ha az nem található
     * (pl. időközben törölték), az album első képére esünk vissza.
     * Kép nélküli albumnál null, ilyenkor a head partial a márkázott
     * alapképet használja.
     *
     * @param array<array{id:string, full_path:string}> $images
     */
    private function resolveCoverImage(array $album, array $images): ?string
    {
        if ($images === []) {
            return null;
        }

        foreach ($images as $image) {
            if ($image['id'] === $album['cover_image_id']) {
                return '/' . $image['full_path'];
            }
        }

        return '/' . $images[0]['full_path'];
    }
}
