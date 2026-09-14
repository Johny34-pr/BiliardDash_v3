<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Services\NewsService;

class NewsController
{
    private NewsService $newsService;

    public function __construct()
    {
        $this->newsService = new NewsService(Database::getConnection());
    }

    public function show(string $id): void
    {
        $news = $this->newsService->getNewsById($id);

        if ($news === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        // A címet nyersen adjuk át: az escape-elés a head partial dolga.
        // Kétszeres e() hívásból korábban "&amp;" jelent meg a fülön.
        $pageTitle = $news['title'] . ' - Magyar Biliárd';

        // A hír bevezetője adja a keresőnek és a megosztásnak a leírást.
        // A summary már tag- és entitásmentes szöveg (NewsService), csak
        // a hosszát kell a keresők által megjelenített méretre vágni.
        $metaDescription = $this->buildDescription($news);
        $ogType = 'article';

        // Strukturált adat: így a hír cikként, dátummal jelenhet meg
        // a találati listában.
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $news['title'],
            'description' => $metaDescription,
            'datePublished' => date(DATE_ATOM, strtotime($news['published_at'])),
            'dateModified' => date(DATE_ATOM, strtotime($news['updated_at'] ?? $news['published_at'])),
            'mainEntityOfPage' => siteUrl('/hirek/' . $news['id']),
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Magyar Biliárd',
                'url' => siteUrl('/'),
            ],
        ];

        // Render view within layout
        ob_start();
        require __DIR__ . '/../Views/news/show.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Oldalleírás előállítása egy hírből.
     *
     * A keresők kb. 160 karaktert jelenítenek meg, ezért ennél hosszabb
     * szöveget szóhatáron vágunk, hogy ne szakadjon félbe egy szó.
     * Ha nincs bevezető (régi rekord), a címre esünk vissza.
     */
    private function buildDescription(array $news): string
    {
        $summary = trim((string) ($news['summary'] ?? ''));

        if ($summary === '') {
            return $news['title'];
        }

        if (mb_strlen($summary) <= 160) {
            return $summary;
        }

        $cut = mb_substr($summary, 0, 160);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > 100) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " ,.;:-") . '…';
    }
}
