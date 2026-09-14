<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Services\AuthService;
use App\Services\CommentService;
use App\Services\CompetitionService;
use App\Services\EmailService;
use App\Services\GalleryService;
use App\Services\ImageService;
use App\Services\NewsService;
use App\Services\TopicService;
use App\Services\ValidationService;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Alap tesztosztály az összes teszthez.
 *
 * SQLite in-memory adatbázist használ a gyors, izolált teszteléshez.
 * Minden teszt előtt újra létrehozza a táblákat, és minden teszt után
 * reseteli a Database kapcsolatot.
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected PDO $db;

    protected function setUp(): void
    {
        parent::setUp();

        // SQLite in-memory kapcsolat létrehozása
        $this->db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Az SQLite alapértelmezésben nem érvényesíti az idegen kulcsokat.
        // Bekapcsolva a teszt adatbázis az éles MySQL viselkedését tükrözi,
        // így az ON DELETE CASCADE és SET NULL szabályok is érvényesülnek.
        $this->db->exec('PRAGMA foreign_keys = ON');

        // Injektálás a Database singleton-ba
        Database::setConnection($this->db);

        // Táblák létrehozása
        $this->createTables();
    }

    protected function tearDown(): void
    {
        // Database connection reset
        Database::resetConnection();

        Mockery::close();

        parent::tearDown();
    }

    /**
     * Tábla tartalmának törlése.
     */
    protected function truncateTable(string $table): void
    {
        $this->db->exec("DELETE FROM {$table}");
    }

    /**
     * NewsService factory metódus.
     */
    protected function createNewsService(): NewsService
    {
        return new NewsService($this->db);
    }

    /**
     * GalleryService factory metódus.
     */
    protected function createGalleryService(): GalleryService
    {
        return new GalleryService($this->db, new ImageService());
    }

    /**
     * CompetitionService factory metódus mock EmailService-szel.
     */
    protected function createCompetitionService(): CompetitionService
    {
        $mockEmailService = Mockery::mock(EmailService::class);
        $mockEmailService->shouldReceive('sendRegistrationConfirmation')
            ->andReturn(true);

        return new CompetitionService($this->db, $mockEmailService);
    }

    /**
     * ValidationService factory metódus.
     */
    protected function createValidationService(): ValidationService
    {
        return new ValidationService();
    }

    /**
     * AuthService factory metódus.
     */
    protected function createAuthService(): AuthService
    {
        return new AuthService($this->db);
    }

    /**
     * CommentService factory metódus.
     */
    protected function createCommentService(): CommentService
    {
        return new CommentService($this->db);
    }

    /**
     * TopicService factory metódus.
     */
    protected function createTopicService(): TopicService
    {
        return new TopicService($this->db, $this->createCommentService());
    }

    /**
     * Teszt topik létrehozása, amelyhez hozzászólások fűzhetők.
     *
     * @return array<string,mixed> A létrehozott topik adatai
     */
    protected function createTestTopic(string $title = 'Teszt topik', bool $locked = false): array
    {
        $id = 'topic-' . bin2hex(random_bytes(4));

        $this->db->prepare(
            'INSERT INTO topics (id, user_id, author_name, title, body, is_locked)
             VALUES (?, NULL, ?, ?, ?, ?)'
        )->execute([$id, 'Teszt Szerző', $title, 'Nyitó bejegyzés', $locked ? 1 : 0]);

        return [
            'id' => $id,
            'title' => $title,
            'is_locked' => $locked ? 1 : 0,
            'is_hidden' => 0,
        ];
    }

    /**
     * SQLite-kompatibilis DDL: összes tábla létrehozása.
     */
    private function createTables(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS news (
                id TEXT PRIMARY KEY,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                summary TEXT,
                published_at TEXT NOT NULL,
                created_at TEXT DEFAULT (datetime(\'now\')),
                updated_at TEXT DEFAULT (datetime(\'now\'))
            )
        ');

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS albums (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                cover_image_id TEXT,
                image_count INTEGER DEFAULT 0,
                created_at TEXT DEFAULT (datetime(\'now\'))
            )
        ');

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS images (
                id TEXT PRIMARY KEY,
                album_id TEXT NOT NULL,
                filename TEXT NOT NULL,
                thumbnail_path TEXT NOT NULL,
                full_path TEXT NOT NULL,
                alt_text TEXT,
                uploaded_at TEXT DEFAULT (datetime(\'now\')),
                FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE
            )
        ');

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS competitions (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                date TEXT NOT NULL,
                venue TEXT NOT NULL,
                registration_opens_at TEXT DEFAULT NULL,
                registration_deadline TEXT NOT NULL,
                registrant_count INTEGER DEFAULT 0,
                created_at TEXT DEFAULT (datetime(\'now\')),
                updated_at TEXT DEFAULT (datetime(\'now\'))
            )
        ');

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                phone TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                created_at TEXT DEFAULT (datetime(\'now\')),
                updated_at TEXT DEFAULT (datetime(\'now\'))
            )
        ');

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS topics (
                id TEXT PRIMARY KEY,
                user_id TEXT DEFAULT NULL,
                author_name TEXT NOT NULL,
                title TEXT NOT NULL,
                body TEXT NOT NULL,
                comment_count INTEGER NOT NULL DEFAULT 0,
                is_locked INTEGER NOT NULL DEFAULT 0,
                is_hidden INTEGER NOT NULL DEFAULT 0,
                ip_hash TEXT DEFAULT NULL,
                created_at TEXT DEFAULT (datetime(\'now\')),
                last_activity_at TEXT DEFAULT (datetime(\'now\')),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ');

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS comments (
                id TEXT PRIMARY KEY,
                topic_id TEXT NOT NULL,
                user_id TEXT DEFAULT NULL,
                author_name TEXT NOT NULL,
                body TEXT NOT NULL,
                upvotes INTEGER NOT NULL DEFAULT 0,
                downvotes INTEGER NOT NULL DEFAULT 0,
                is_hidden INTEGER NOT NULL DEFAULT 0,
                ip_hash TEXT DEFAULT NULL,
                created_at TEXT DEFAULT (datetime(\'now\')),
                FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ');

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS comment_votes (
                id TEXT PRIMARY KEY,
                comment_id TEXT NOT NULL,
                voter_key TEXT NOT NULL,
                user_id TEXT DEFAULT NULL,
                value INTEGER NOT NULL,
                created_at TEXT DEFAULT (datetime(\'now\')),
                updated_at TEXT DEFAULT (datetime(\'now\')),
                UNIQUE (comment_id, voter_key),
                FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ');

        $this->db->exec('
            CREATE TABLE IF NOT EXISTS registrations (
                id TEXT PRIMARY KEY,
                competition_id TEXT NOT NULL,
                created_by_user_id TEXT DEFAULT NULL,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NOT NULL,
                registered_at TEXT DEFAULT (datetime(\'now\')),
                FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ');
    }
}
