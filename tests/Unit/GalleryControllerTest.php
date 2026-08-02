<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\GalleryController;
use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;

class GalleryControllerTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('
            CREATE TABLE albums (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                cover_image_id TEXT,
                image_count INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->pdo->exec('
            CREATE TABLE images (
                id TEXT PRIMARY KEY,
                album_id TEXT NOT NULL,
                filename TEXT NOT NULL,
                thumbnail_path TEXT NOT NULL,
                full_path TEXT NOT NULL,
                alt_text TEXT,
                uploaded_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');

        Database::setConnection($this->pdo);

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    protected function tearDown(): void
    {
        Database::resetConnection();
    }

    public function testIndexRendersAlbumCards(): void
    {
        $this->pdo->exec("INSERT INTO albums (id, name, image_count) VALUES ('album-1', 'Nyári verseny', 5)");
        $this->pdo->exec("INSERT INTO albums (id, name, image_count) VALUES ('album-2', 'Téli kupa', 3)");

        ob_start();
        $controller = new GalleryController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('Nyári verseny', $output);
        $this->assertStringContainsString('Téli kupa', $output);
        $this->assertStringContainsString('5 kép', $output);
        $this->assertStringContainsString('3 kép', $output);
        $this->assertStringContainsString('/galeria/album-1', $output);
        $this->assertStringContainsString('/galeria/album-2', $output);
    }

    public function testIndexShowsEmptyMessageWhenNoAlbums(): void
    {
        ob_start();
        $controller = new GalleryController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('Jelenleg nincsenek albumok', $output);
    }

    public function testIndexSetsCorrectPageTitle(): void
    {
        ob_start();
        $controller = new GalleryController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('<title>Galéria - Magyar Biliárd</title>', $output);
    }

    public function testShowRendersAlbumImages(): void
    {
        $this->pdo->exec("INSERT INTO albums (id, name, image_count) VALUES ('album-1', 'Nyári verseny', 2)");
        $this->pdo->exec("INSERT INTO images (id, album_id, filename, thumbnail_path, full_path, alt_text) VALUES ('img-1', 'album-1', 'photo1.jpg', 'uploads/albums/album-1/thumb/photo1.jpg', 'uploads/albums/album-1/full/photo1.jpg', 'Első kép')");
        $this->pdo->exec("INSERT INTO images (id, album_id, filename, thumbnail_path, full_path, alt_text) VALUES ('img-2', 'album-1', 'photo2.jpg', 'uploads/albums/album-1/thumb/photo2.jpg', 'uploads/albums/album-1/full/photo2.jpg', 'Második kép')");

        ob_start();
        $controller = new GalleryController();
        $controller->show('album-1');
        $output = ob_get_clean();

        $this->assertStringContainsString('Nyári verseny', $output);
        $this->assertStringContainsString('uploads/albums/album-1/thumb/photo1.jpg', $output);
        $this->assertStringContainsString('uploads/albums/album-1/thumb/photo2.jpg', $output);
        $this->assertStringContainsString('gallery.js', $output);
        $this->assertStringContainsString('galleryImages', $output);
    }

    public function testShowThrows404ForNonExistentAlbum(): void
    {
        $this->expectException(\App\Core\AppException::class);
        $this->expectExceptionCode(404);

        $controller = new GalleryController();
        $controller->show('non-existent');
    }

    public function testShowDisplaysEmptyMessageForAlbumWithNoImages(): void
    {
        $this->pdo->exec("INSERT INTO albums (id, name, image_count) VALUES ('album-1', 'Üres album', 0)");

        ob_start();
        $controller = new GalleryController();
        $controller->show('album-1');
        $output = ob_get_clean();

        $this->assertStringContainsString('Üres album', $output);
        $this->assertStringContainsString('Ez az album jelenleg üres', $output);
        $this->assertStringNotContainsString('gallery.js', $output);
    }

    public function testShowIncludesImagePlaceholderOnError(): void
    {
        $this->pdo->exec("INSERT INTO albums (id, name, image_count) VALUES ('album-1', 'Teszt', 1)");
        $this->pdo->exec("INSERT INTO images (id, album_id, filename, thumbnail_path, full_path) VALUES ('img-1', 'album-1', 'test.jpg', 'uploads/albums/album-1/thumb/test.jpg', 'uploads/albums/album-1/full/test.jpg')");

        ob_start();
        $controller = new GalleryController();
        $controller->show('album-1');
        $output = ob_get_clean();

        $this->assertStringContainsString('onerror', $output);
        $this->assertStringContainsString('placeholder.svg', $output);
    }

    public function testIndexShowsCoverImagePlaceholder(): void
    {
        $this->pdo->exec("INSERT INTO albums (id, name, image_count, cover_image_id) VALUES ('album-1', 'Teszt album', 0, NULL)");

        ob_start();
        $controller = new GalleryController();
        $controller->index();
        $output = ob_get_clean();

        // Should show placeholder SVG icon when no cover image
        $this->assertStringContainsString('<svg', $output);
        $this->assertStringContainsString('Teszt album', $output);
    }
}
