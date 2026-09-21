<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\HomeController;
use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;

class HomeControllerTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->pdo->exec('
            CREATE TABLE news (
                id TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                summary TEXT,
                published_at TEXT NOT NULL,
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

        // Start session for layout (Session::getFlash uses $_SESSION)
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    protected function tearDown(): void
    {
        Database::resetConnection();
    }

    public function testIndexRendersNewsCards(): void
    {
        // Insert test news
        $stmt = $this->pdo->prepare(
            'INSERT INTO news (id, title, content, summary, published_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(['id-1', 'Teszt Hír 1', '<p>Tartalom 1</p>', 'Összefoglaló 1', '2025-01-15 10:00:00']);
        $stmt->execute(['id-2', 'Teszt Hír 2', '<p>Tartalom 2</p>', 'Összefoglaló 2', '2025-01-16 10:00:00']);

        ob_start();
        $controller = new HomeController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('Teszt Hír 1', $output);
        $this->assertStringContainsString('Teszt Hír 2', $output);
        $this->assertStringContainsString('/hirek/id-1', $output);
        $this->assertStringContainsString('/hirek/id-2', $output);
        $this->assertStringContainsString('Összefoglaló 1', $output);
        $this->assertStringContainsString('Összefoglaló 2', $output);
    }

    public function testIndexShowsEmptyMessageWhenNoNews(): void
    {
        ob_start();
        $controller = new HomeController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('Jelenleg nincsenek hírek', $output);
    }

    public function testIndexLimitsTo10News(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO news (id, title, content, summary, published_at) VALUES (?, ?, ?, ?, ?)'
        );

        for ($i = 1; $i <= 12; $i++) {
            $stmt->execute(["id-{$i}", "Hír {$i}", "<p>Tartalom</p>", "Összefoglaló {$i}", "2025-01-{$i} 10:00:00"]);
        }

        ob_start();
        $controller = new HomeController();
        $controller->index();
        $output = ob_get_clean();

        // Should show latest 10 (id-12 through id-3), not id-1 or id-2
        $this->assertStringContainsString('Hír 12', $output);
        $this->assertStringContainsString('Hír 3', $output);
        $this->assertStringNotContainsString('>Hír 1<', $output);
        $this->assertStringNotContainsString('>Hír 2<', $output);
    }

    public function testIndexDisplaysDateFormatted(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO news (id, title, content, summary, published_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(['id-1', 'Dátum teszt', '<p>Tartalom</p>', 'Összefoglaló', '2025-03-20 14:30:00']);

        ob_start();
        $controller = new HomeController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('2025. 03. 20.', $output);
    }

    /**
     * A főoldal címe a márkanévvel kezdődik és a fő szolgáltatásokat is
     * megnevezi, mert ez a szöveg jelenik meg a keresőtalálat fejléceként.
     * A korábbi "Főoldal" előtag nem hordozott információt.
     */
    public function testIndexSetsCorrectPageTitle(): void
    {
        ob_start();
        $controller = new HomeController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '<title>Okányi Biliárd Klub - hírek, galéria és online versenynevezés</title>',
            $output
        );
    }

    /**
     * A keresőoptimalizálás alapelemei minden oldalon jelen vannak:
     * saját oldalleírás, kanonikus URL és közösségi megosztási adatok.
     */
    public function testIndexOutputsSeoMetaTags(): void
    {
        ob_start();
        $controller = new HomeController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('name="description"', $output);
        $this->assertStringContainsString('rel="canonical"', $output);
        $this->assertStringContainsString('property="og:title"', $output);
        $this->assertStringContainsString('property="og:image"', $output);
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $output);
        $this->assertStringContainsString('name="robots" content="index, follow', $output);

        // Ikonok: a favicon és a webmanifest bekötése
        $this->assertStringContainsString('rel="icon"', $output);
        $this->assertStringContainsString('/site.webmanifest', $output);
    }

    public function testIndexShowsErrorMessageOnDatabaseFailure(): void
    {
        // Drop the news table to cause an error
        $this->pdo->exec('DROP TABLE news');

        ob_start();
        $controller = new HomeController();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('A tartalom átmenetileg nem elérhető', $output);
    }
}
