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
     * Summary generálás: HTML tagek eltávolítása, entitások feloldása,
     * whitespace normalizálás, majd az első 200 karakter.
     *
     * Az entitások feloldása azért kell, mert a rich text szerkesztő
     * névvel megadott entitásokat is előállíthat (pl. `&aacute;`). Ezek
     * nyersen a nézetbe kerülve az e() escape után szó szerint látszanának
     * (`&amp;aacute;`), ezért itt valódi karakterré alakítjuk őket.
     */
    private function generateSummary(string $content): string
    {
        $stripped = strip_tags($content);
        $decoded = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Sortörések és többszörös szóközök egyetlen szóközre
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $decoded));

        return mb_substr($normalized, 0, 200);
    }
}
