<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\News;
use PDO;
use Ramsey\Uuid\Uuid;

class NewsService
{
    private News $newsModel;

    public function __construct(private PDO $db)
    {
        $this->newsModel = new News($db);
    }

    /**
     * Legfrissebb hírek lekérdezése.
     *
     * @return array<array{id:string, title:string, summary:?string, published_at:string, created_at:string, updated_at:string}>
     */
    public function getLatestNews(int $limit = 10): array
    {
        return $this->newsModel->findLatest($limit);
    }

    /**
     * Egy hír lekérdezése ID alapján.
     *
     * @return array{id:string, title:string, content:string, summary:?string, published_at:string, created_at:string, updated_at:string}|null
     */
    public function getNewsById(string $id): ?array
    {
        return $this->newsModel->findById($id);
    }

    /**
     * Új hír létrehozása UUID-vel, automatikus summary generálással.
     *
     * @return array{id:string, title:string, content:string, summary:string, published_at:string, created_at:string, updated_at:string}
     */
    public function createNews(string $title, string $content): array
    {
        $id = Uuid::uuid4()->toString();
        $summary = $this->generateSummary($content);
        $publishedAt = date('Y-m-d H:i:s');

        $this->newsModel->create($id, $title, $content, $summary, $publishedAt);

        return $this->newsModel->findById($id);
    }

    /**
     * Hír frissítése. Megőrzi az eredeti published_at dátumot.
     *
     * @return array{id:string, title:string, content:string, summary:string, published_at:string, created_at:string, updated_at:string}
     */
    public function updateNews(string $id, string $title, string $content): array
    {
        $summary = $this->generateSummary($content);

        $this->newsModel->update($id, $title, $content, $summary);

        return $this->newsModel->findById($id);
    }

    /**
     * Hír törlése.
     */
    public function deleteNews(string $id): void
    {
        $this->newsModel->delete($id);
    }

    /**
     * Summary generálás: HTML tagek eltávolítása, trim, első 200 karakter.
     */
    private function generateSummary(string $content): string
    {
        $stripped = strip_tags($content);
        $trimmed = trim($stripped);

        return mb_substr($trimmed, 0, 200);
    }
}
