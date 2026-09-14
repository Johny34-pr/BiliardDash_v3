<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Database;
use App\Core\Session;
use App\Services\CompetitionService;
use App\Services\EmailService;
use App\Services\GalleryService;
use App\Services\ImageService;
use App\Services\LinkTargetService;
use App\Services\MediaService;
use App\Services\NewsService;
use App\Services\TopicService;
use App\Services\CommentService;

/**
 * A szerkesztő JSON végpontjai: képfeltöltés, dokumentumcsatolás,
 * médiakönyvtár és belső hivatkozáslista.
 *
 * Minden végpont szervezői hozzáférést igényel. Mivel ezeket a szerkesztő
 * JavaScriptje hívja, hibánál nem átirányítás történik, hanem JSON válasz a
 * megfelelő HTTP állapotkóddal - így a szerkesztő értelmes hibát tud mutatni.
 */
class AdminMediaController
{
    private MediaService $mediaService;

    public function __construct()
    {
        $this->mediaService = new MediaService();
    }

    /**
     * Kép feltöltése a szerkesztőből.
     *
     * A TinyMCE `images_upload_url` beállítása ide küld, és a válaszban egy
     * `location` kulcsot vár a beszúrandó URL-lel.
     */
    public function uploadImage(): void
    {
        $this->requireAdminJson();

        try {
            $file = $this->takeUploadedFile();
            $stored = $this->mediaService->storeImage($file);

            // A TinyMCE a "location" kulcsot használja
            $this->json([
                'location' => $stored['url'],
                'filename' => $stored['filename'],
            ]);
        } catch (AppException $e) {
            $this->json(['message' => $e->getMessage()], $this->statusFor($e));
        } catch (\Throwable $e) {
            error_log('[AdminMediaController] Képfeltöltési hiba: ' . $e->getMessage());
            $this->json(['message' => 'A kép feltöltése nem sikerült.'], 500);
        }
    }

    /**
     * Dokumentum feltöltése a szerkesztőből (csatolmány).
     */
    public function uploadDocument(): void
    {
        $this->requireAdminJson();

        try {
            $file = $this->takeUploadedFile();
            $stored = $this->mediaService->storeDocument($file);

            $this->json([
                'location' => $stored['url'],
                'filename' => $stored['filename'],
                'size' => $stored['size'],
                'sizeLabel' => MediaService::formatSize($stored['size']),
                // A szerkesztő ezt a nevet ajánlja fel a hivatkozás szövegének
                'originalName' => $this->originalName(),
            ]);
        } catch (AppException $e) {
            $this->json(['message' => $e->getMessage()], $this->statusFor($e));
        } catch (\Throwable $e) {
            error_log('[AdminMediaController] Dokumentum feltöltési hiba: ' . $e->getMessage());
            $this->json(['message' => 'A dokumentum feltöltése nem sikerült.'], 500);
        }
    }

    /**
     * A korábban feltöltött média listája a médiakönyvtár panelhez.
     */
    public function library(): void
    {
        $this->requireAdminJson();

        $items = array_map(
            static fn(array $item): array => $item + ['sizeLabel' => MediaService::formatSize($item['size'])],
            $this->mediaService->listMedia()
        );

        $this->json(['items' => $items]);
    }

    /**
     * Belső hivatkozási célok a szerkesztő hivatkozás-választójához.
     *
     * A TinyMCE `link_list` beállítása ezt a JSON-t tölti be.
     */
    public function linkList(): void
    {
        $this->requireAdminJson();

        $db = Database::getConnection();
        $mailConfig = require __DIR__ . '/../../config/mail.php';

        $commentService = new CommentService($db);

        $linkService = new LinkTargetService(
            new NewsService($db),
            new CompetitionService($db, new EmailService($mailConfig)),
            new GalleryService($db, new ImageService()),
            new TopicService($db, $commentService)
        );

        $this->json($linkService->getLinkList());
    }

    // =====================================================================
    // Segédmetódusok
    // =====================================================================

    /**
     * Szervezői hozzáférés megkövetelése JSON válasszal.
     *
     * Átirányítás helyett 401-et adunk, mert a hívó a szerkesztő
     * JavaScriptje, amely a HTML bejelentkező oldalt nem tudná értelmezni.
     */
    private function requireAdminJson(): void
    {
        if (!Session::isAdmin()) {
            $this->json(['message' => 'Ehhez szervezői belépés szükséges.'], 401);
        }
    }

    /**
     * A feltöltött fájl kiolvasása a kérésből.
     *
     * A TinyMCE `file` néven küldi, de a saját feltöltőnk `media` néven is
     * küldhet, ezért mindkettőt elfogadjuk.
     *
     * @throws AppException Ha nem érkezett fájl.
     */
    private function takeUploadedFile(): array
    {
        $file = $_FILES['file'] ?? $_FILES['media'] ?? null;

        if ($file === null || !is_array($file)) {
            throw AppException::validationError('Nem érkezett fájl.');
        }

        return $file;
    }

    /**
     * A feltöltött fájl eredeti neve, megjelenítésre megtisztítva.
     *
     * Csak a hivatkozás szövegének javaslatához használjuk, tárolásra nem.
     */
    private function originalName(): string
    {
        $raw = $_FILES['file']['name'] ?? $_FILES['media']['name'] ?? '';

        // Csak a fájlnév része, könyvtárrészek nélkül
        $name = basename((string) $raw);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return mb_substr(trim($name), 0, 120);
    }

    /**
     * AppException kódjának leképezése HTTP állapotkódra.
     */
    private function statusFor(AppException $e): int
    {
        $code = $e->getCode();

        return in_array($code, [400, 401, 403, 404, 413, 422], true) ? $code : 422;
    }

    /**
     * JSON válasz küldése és a futás befejezése.
     */
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
