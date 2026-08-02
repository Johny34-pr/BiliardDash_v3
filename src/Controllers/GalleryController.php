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

        $pageTitle = e($album['name']) . ' - Galéria - Magyar Biliárd';

        // Output buffering a layout-hoz
        ob_start();
        require __DIR__ . '/../Views/gallery/show.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
