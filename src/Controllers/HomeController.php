<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Services\NewsService;

class HomeController
{
    private NewsService $newsService;

    public function __construct()
    {
        $this->newsService = new NewsService(Database::getConnection());
    }

    public function index(): void
    {
        try {
            $news = $this->newsService->getLatestNews(10);
            $error = false;
        } catch (\Throwable $e) {
            error_log('[HomeController] Hír betöltési hiba: ' . $e->getMessage());
            $news = [];
            $error = true;
        }

        // A címben a márkanév áll elöl, mert a főoldal a webhely belépője.
        // A "Főoldal" szó a találati listában nem hordozott információt.
        $pageTitle = 'Okányi Biliárd Klub - hírek, galéria és online versenynevezés';
        $metaDescription = 'Az Okányi Biliárd Klub hivatalos oldala: friss hírek, '
            . 'versenybeszámolók, fotógaléria és online nevezés a nyitott versenyekre.';

        // Render view within layout
        ob_start();
        require __DIR__ . '/../Views/home/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
