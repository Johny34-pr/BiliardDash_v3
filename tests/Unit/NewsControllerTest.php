<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\NewsController;
use App\Core\Database;
use PDO;
use PHPUnit\Framework\TestCase;

class NewsControllerTest extends TestCase
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

        Database::setConnection($this->pdo);

        // Start session for layout
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    protected function tearDown(): void
    {
        Database::resetConnection();
    }

    public function testShowRendersFullNewsArticle(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO news (id, title, content, summary, published_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(['test-id', 'Teszt Hír Cím', '<p>Teljes tartalom itt.</p>', 'Összefoglaló', '2025-02-10 09:00:00']);

        ob_start();
        $controller = new NewsController();
        $controller->show('test-id');
        $output = ob_get_clean();

        $this->assertStringContainsString('Teszt Hír Cím', $output);
        $this->assertStringContainsString('<p>Teljes tartalom itt.</p>', $output);
        $this->assertStringContainsString('2025. 02. 10.', $output);
    }

    public function testShowSetsCorrectPageTitle(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO news (id, title, content, summary, published_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(['test-id', 'Egyedi Cím', '<p>Tartalom</p>', 'Összefoglaló', '2025-02-10 09:00:00']);

        ob_start();
        $controller = new NewsController();
        $controller->show('test-id');
        $output = ob_get_clean();

        $this->assertStringContainsString('<title>Egyedi Cím - Magyar Biliárd</title>', $output);
    }

    public function testShowContainsBackLink(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO news (id, title, content, summary, published_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(['test-id', 'Vissza teszt', '<p>Tartalom</p>', 'Összefoglaló', '2025-02-10 09:00:00']);

        ob_start();
        $controller = new NewsController();
        $controller->show('test-id');
        $output = ob_get_clean();

        $this->assertStringContainsString('Vissza a főoldalra', $output);
        $this->assertStringContainsString('href="/"', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testShowReturns404ForNonExistentNews(): void
    {
        ob_start();
        $controller = new NewsController();
        $controller->show('nonexistent-id');
        $output = ob_get_clean();

        $this->assertStringContainsString('404', $output);
        $this->assertStringContainsString('Az oldal nem található', $output);
    }

    public function testShowEscapesHtmlInTitle(): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO news (id, title, content, summary, published_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(['xss-id', '<script>alert("xss")</script>', '<p>Safe content</p>', 'Összefoglaló', '2025-02-10 09:00:00']);

        ob_start();
        $controller = new NewsController();
        $controller->show('xss-id');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }
}
