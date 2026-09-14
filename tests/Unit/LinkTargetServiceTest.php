<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CompetitionService;
use App\Services\EmailService;
use App\Services\GalleryService;
use App\Services\ImageService;
use App\Services\LinkTargetService;
use App\Services\NewsService;
use Mockery;
use Tests\TestCase;

/**
 * A szerkesztő belső hivatkozáslistájának tesztjei.
 *
 * Ez a lista négy szolgáltatásból áll össze, ezért itt azt ellenőrizzük,
 * hogy minden csoport a helyes útvonalakat adja, és hogy az üres csoportok
 * kimaradnak (ne jelenjen meg használhatatlan menüpont).
 */
class LinkTargetServiceTest extends TestCase
{
    private LinkTargetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $mockEmail = Mockery::mock(EmailService::class);
        $mockEmail->shouldReceive('sendRegistrationConfirmation')->andReturn(true);

        $this->service = new LinkTargetService(
            new NewsService($this->db),
            new CompetitionService($this->db, $mockEmail),
            new GalleryService($this->db, new ImageService()),
            $this->createTopicService()
        );
    }

    /** A lista adott című csoportjának megkeresése */
    private function group(array $list, string $title): ?array
    {
        foreach ($list as $entry) {
            if ($entry['title'] === $title) {
                return $entry;
            }
        }

        return null;
    }

    /** A lista összes 'value' értéke, a beágyazott menükből is */
    private function allValues(array $list): array
    {
        $values = [];

        foreach ($list as $entry) {
            if (isset($entry['value'])) {
                $values[] = $entry['value'];
            }
            if (isset($entry['menu'])) {
                $values = array_merge($values, $this->allValues($entry['menu']));
            }
        }

        return $values;
    }

    // =================================================================
    // Alap oldalak
    // =================================================================

    public function testAlwaysContainsMainPages(): void
    {
        $values = $this->allValues($this->service->getLinkList());

        $this->assertContains('/', $values);
        $this->assertContains('/galeria', $values);
        $this->assertContains('/nevezes', $values);
        $this->assertContains('/forum', $values);
    }

    public function testEmptyGroupsAreOmitted(): void
    {
        // Üres adatbázisnál csak az alap oldalak szerepelnek
        $list = $this->service->getLinkList();

        $this->assertNull($this->group($list, 'Versenyek'));
        $this->assertNull($this->group($list, 'Hírek'));
        $this->assertNull($this->group($list, 'Galéria albumok'));
        $this->assertNull($this->group($list, 'Fórum topikok'));
    }

    // =================================================================
    // Versenyek
    // =================================================================

    public function testCompetitionOffersRegistrationFormAndRegistrantList(): void
    {
        $this->db->prepare(
            'INSERT INTO competitions (id, name, date, venue, registration_deadline)
             VALUES (?,?,?,?,?)'
        )->execute(['comp-1', 'Őszi Kupa', '2030-10-01', 'Budapest', '2030-09-20 12:00:00']);

        $list = $this->service->getLinkList();
        $group = $this->group($list, 'Versenyek');

        $this->assertNotNull($group);
        $this->assertCount(1, $group['menu']);

        // A verseny neve és dátuma a feliratban
        $this->assertStringContainsString('Őszi Kupa', $group['menu'][0]['title']);
        $this->assertStringContainsString('2030', $group['menu'][0]['title']);

        $values = $this->allValues($group['menu']);
        $this->assertContains('/nevezes/comp-1', $values);
        $this->assertContains('/nevezes/comp-1/nevezok', $values);
    }

    // =================================================================
    // Hírek, albumok, topikok
    // =================================================================

    public function testNewsAppearWithDetailUrl(): void
    {
        $this->db->prepare(
            'INSERT INTO news (id, title, content, summary, published_at) VALUES (?,?,?,?,?)'
        )->execute(['news-1', 'Fontos bejelentés', '<p>Tartalom</p>', 'Tartalom', '2026-01-01 10:00:00']);

        $group = $this->group($this->service->getLinkList(), 'Hírek');

        $this->assertNotNull($group);
        $this->assertSame('Fontos bejelentés', $group['menu'][0]['title']);
        $this->assertSame('/hirek/news-1', $group['menu'][0]['value']);
    }

    public function testAlbumsAppearWithImageCount(): void
    {
        $this->db->prepare('INSERT INTO albums (id, name, image_count) VALUES (?,?,?)')
            ->execute(['album-1', 'Döntő 2026', 7]);

        $group = $this->group($this->service->getLinkList(), 'Galéria albumok');

        $this->assertNotNull($group);
        $this->assertStringContainsString('Döntő 2026', $group['menu'][0]['title']);
        $this->assertStringContainsString('7 kép', $group['menu'][0]['title']);
        $this->assertSame('/galeria/album-1', $group['menu'][0]['value']);
    }

    public function testVisibleTopicsAppear(): void
    {
        $topic = $this->createTestTopic('Dákó ajánlás');

        $group = $this->group($this->service->getLinkList(), 'Fórum topikok');

        $this->assertNotNull($group);
        $this->assertSame('Dákó ajánlás', $group['menu'][0]['title']);
        $this->assertSame('/forum/' . $topic['id'], $group['menu'][0]['value']);
    }

    public function testHiddenTopicsAreNotOffered(): void
    {
        $topic = $this->createTestTopic('Elrejtett topik');
        $this->db->prepare('UPDATE topics SET is_hidden = 1 WHERE id = ?')->execute([$topic['id']]);

        $this->assertNull($this->group($this->service->getLinkList(), 'Fórum topikok'));
    }

    // =================================================================
    // Szerkezet
    // =================================================================

    public function testEveryEntryHasEitherValueOrMenu(): void
    {
        $this->db->prepare(
            'INSERT INTO competitions (id, name, date, venue, registration_deadline) VALUES (?,?,?,?,?)'
        )->execute(['comp-1', 'Kupa', '2030-10-01', 'Budapest', '2030-09-20 12:00:00']);

        $assertEntries = function (array $entries) use (&$assertEntries): void {
            foreach ($entries as $entry) {
                $this->assertArrayHasKey('title', $entry);
                $this->assertTrue(
                    isset($entry['value']) || isset($entry['menu']),
                    'A hivatkozáslista minden elemének kell "value" vagy "menu" kulcs: ' . $entry['title']
                );

                if (isset($entry['menu'])) {
                    $assertEntries($entry['menu']);
                }
            }
        };

        $assertEntries($this->service->getLinkList());
    }

    public function testAllUrlsAreSiteRelative(): void
    {
        $this->db->prepare(
            'INSERT INTO news (id, title, content, summary, published_at) VALUES (?,?,?,?,?)'
        )->execute(['news-1', 'Hír', '<p>x</p>', 'x', '2026-01-01 10:00:00']);

        foreach ($this->allValues($this->service->getLinkList()) as $value) {
            $this->assertStringStartsWith('/', $value, "Nem oldalon belüli útvonal: {$value}");
            $this->assertStringNotContainsString('//', $value, "Külső hivatkozásnak tűnik: {$value}");
        }
    }
}
