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

        $pageTitle = e($news['title']) . ' - Magyar Biliárd';

        // Render view within layout
        ob_start();
        require __DIR__ . '/../Views/news/show.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
