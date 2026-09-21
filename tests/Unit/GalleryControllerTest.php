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

        // A galéria az albumok mellett a verseny helyezettjeit is megjeleníti
        $this->pdo->exec('
            CREATE TABLE album_placements (
                id TEXT PRIMARY KEY,
                album_id TEXT NOT NULL,
                position INTEGER NOT NULL,
                player_name TEXT NOT NULL,
                note TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // A layout a navigációhoz megkérdezi a kapcsolható modulok állapotát.
        // A tábla üresen marad, tehát a fórum kikapcsolt - ez az éles
        // alapértelmezés is.
        $this->pdo->exec('
            CREATE TABLE site_settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT
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
        $this->assertStringContainsString('/galeria/album-1', $output);
        $this->assertStringContainsString('/galeria/album-2', $output);

        // A lista a borítóképet és a helyezetteket mutatja, nem a képek
        // számát: egy album egy verseny eredményhirdetését jelenti.
        $this->assertStringNotContainsString('5 kép', $output);
        $this->assertStringContainsString('Album megnyitása', $output);
    }

    /**
     * Az album kártyáján a dobogó jelenik meg, a negyedik helyezettől
     * pedig összevont jelzés.
     */
    public function testIndexShowsPodiumOnAlbumCard(): void
    {
        $this->pdo->exec("INSERT INTO albums (id, name, image_count) VALUES ('album-1', 'Nyári verseny', 1)");

        $players = [
            ['p1', 1, 'Első Péter'],
            ['p2', 2, 'Második Pál'],
            ['p3', 3, 'Harmadik Anna'],
            ['p4', 4, 'Negyedik Béla'],
        ];

        foreach ($players as [$id, $position, $name]) {
            $this->pdo->exec(
                "INSERT INTO album_placements (id, album_id, position, player_name)
                 VALUES ('{$id}', 'album-1', {$position}, '{$name}')"
            );
        }

        ob_start();
        $controller = new GalleryController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('Első Péter', $output);
        $this->assertStringContainsString('Második Pál', $output);
        $this->assertStringContainsString('Harmadik Anna', $output);

        // A negyedik már nem a kártyán, csak összesítve jelenik meg
        $this->assertStringNotContainsString('Negyedik Béla', $output);
        $this->assertStringContainsString('további helyezett', $output);
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

        $this->assertStringContainsString('<title>Galéria - Okányi Biliárd Klub</title>', $output);
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

        // Az album oldala a borítóképet mutatja nagyban, nem bélyegkép
        // rácsot: a beállított borító hiányában a legfrissebb kép áll be.
        $this->assertStringContainsString('uploads/albums/album-1/full/', $output);
        $this->assertStringNotContainsString('thumb/photo1.jpg', $output);

        // A nagyításhoz a lightbox továbbra is betöltődik, egyetlen képpel
        $this->assertStringContainsString('gallery.js', $output);
        $this->assertStringContainsString('galleryImages', $output);
        $this->assertSame(1, substr_count($output, '"full":'));
    }

    /**
     * Az album oldala kéthasábos: balra a kép, jobbra a helyezettek, és a
     * bezárás visszavisz az albumok listájára.
     */
    public function testShowRendersPlacementsBesideImage(): void
    {
        $this->pdo->exec("INSERT INTO albums (id, name, image_count) VALUES ('album-1', 'Nyári verseny', 1)");
        $this->pdo->exec(
            "INSERT INTO images (id, album_id, filename, thumbnail_path, full_path)
             VALUES ('img-1', 'album-1', 'p.jpg', 'uploads/albums/album-1/thumb/p.jpg', 'uploads/albums/album-1/full/p.jpg')"
        );
        $this->pdo->exec(
            "INSERT INTO album_placements (id, album_id, position, player_name, note)
             VALUES ('pl-1', 'album-1', 1, 'Győztes Gábor', 'Okány')"
        );

        ob_start();
        $controller = new GalleryController();
        $controller->show('album-1');
        $output = ob_get_clean();

        $this->assertStringContainsString('Helyezettek', $output);
        $this->assertStringContainsString('Győztes Gábor', $output);
        $this->assertStringContainsString('Okány', $output);

        // Kéthasábos elrendezés és a kilépés útja
        $this->assertStringContainsString('lg:col-span-3', $output);
        $this->assertStringContainsString('lg:col-span-2', $output);
        $this->assertStringContainsString('Vissza az albumokhoz', $output);
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
        $this->assertStringContainsString('Ehhez az albumhoz még nincs kép', $output);
        $this->assertStringNotContainsString('gallery.js', $output);

        // Kép nélkül is látszik a helyezettek hasábja, üres állapotban
        $this->assertStringContainsString('Még nincs felvitt eredmény', $output);
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
