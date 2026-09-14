<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use Ramsey\Uuid\Uuid;

/**
 * A szerkesztőbe beszúrt média (képek és dokumentumok) kezelése.
 *
 * Biztonsági alapelvek:
 *
 *   1. A fájl típusát a *tartalomból* állapítjuk meg (finfo), nem a kliens
 *      által küldött MIME típusból, ami hamisítható.
 *   2. A fájlnév mindig újonnan generált UUID, a kiterjesztés pedig a
 *      felismert típushoz tartozó engedélyezett érték. Így nem lehet
 *      kettős kiterjesztéssel (pl. `kep.php.jpg`) szkriptet feltölteni.
 *   3. Az SVG szándékosan nincs az engedélyezett képformátumok között, mert
 *      szkriptet tartalmazhat.
 *   4. A tárolási könyvtárban a PHP futtatás le van tiltva
 *      (public/uploads/.htaccess), ami az alkönyvtárakra is érvényes.
 */
class MediaService
{
    /** Engedélyezett képformátumok: felismert MIME => kiterjesztés */
    public const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Engedélyezett dokumentumformátumok: felismert MIME => kiterjesztés.
     *
     * Versenykiírás, eredménylista és hasonló csatolmányok tipikus formátumai.
     * Tömörített állományok (zip) szándékosan kimaradnak, mert a tartalmuk
     * feltöltéskor nem ellenőrizhető.
     */
    public const ALLOWED_DOCUMENT_TYPES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
    ];

    /** Méretkorlátok bájtban */
    public const MAX_IMAGE_BYTES = 10 * 1024 * 1024;   // 10 MB
    public const MAX_DOCUMENT_BYTES = 20 * 1024 * 1024; // 20 MB

    /** A média tárolási könyvtára a public/ alatt */
    private const MEDIA_DIR = 'uploads/media';

    private string $publicPath;

    public function __construct(?string $publicPath = null)
    {
        $this->publicPath = rtrim($publicPath ?? dirname(__DIR__, 2) . '/public', '/\\');
    }

    /**
     * Kép mentése a szerkesztőből.
     *
     * @param array $file A $_FILES egy eleme
     * @return array{url:string, filename:string, size:int, type:string}
     * @throws AppException Érvénytelen vagy túl nagy fájl esetén.
     */
    public function storeImage(array $file): array
    {
        $this->assertUploadOk($file);
        $this->assertSize($file, self::MAX_IMAGE_BYTES, 'A kép legfeljebb 10 MB lehet');

        $mime = $this->detectMime($file['tmp_name']);
        $extension = self::ALLOWED_IMAGE_TYPES[$mime] ?? null;

        if ($extension === null) {
            throw AppException::validationError(
                'Nem támogatott képformátum. Használható: JPEG, PNG, GIF, WebP.'
            );
        }

        // Valódi képként is értelmezhető-e; véd az álcázott fájlok ellen
        if (@getimagesize($file['tmp_name']) === false) {
            throw AppException::validationError('A fájl nem értelmezhető képként.');
        }

        return $this->store($file, $extension, $mime);
    }

    /**
     * Dokumentum mentése a szerkesztőből.
     *
     * @param array $file A $_FILES egy eleme
     * @return array{url:string, filename:string, size:int, type:string}
     * @throws AppException Érvénytelen vagy túl nagy fájl esetén.
     */
    public function storeDocument(array $file): array
    {
        $this->assertUploadOk($file);
        $this->assertSize($file, self::MAX_DOCUMENT_BYTES, 'A dokumentum legfeljebb 20 MB lehet');

        $mime = $this->detectMime($file['tmp_name']);
        $extension = self::ALLOWED_DOCUMENT_TYPES[$mime] ?? null;

        if ($extension === null) {
            throw AppException::validationError(
                'Nem támogatott dokumentumformátum. Használható: PDF, DOC(X), XLS(X), ODT, ODS, TXT, CSV.'
            );
        }

        return $this->store($file, $extension, $mime);
    }

    /**
     * A korábban feltöltött média listája, legfrissebb elöl.
     *
     * A szerkesztő médiakönyvtár párbeszédpanele ebből dolgozik, hogy a
     * korábbi feltöltések újra beszúrhatók legyenek.
     *
     * @return array<array{url:string, filename:string, kind:string, size:int, uploaded_at:int}>
     */
    public function listMedia(int $limit = 200): array
    {
        $root = $this->publicPath . '/' . self::MEDIA_DIR;

        if (!is_dir($root)) {
            return [];
        }

        $imageExtensions = array_values(self::ALLOWED_IMAGE_TYPES);
        $items = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $entry) {
            /** @var \SplFileInfo $entry */
            if (!$entry->isFile()) {
                continue;
            }

            $extension = strtolower($entry->getExtension());

            // A .gitkeep és bármi ismeretlen kimarad
            if (!in_array($extension, $imageExtensions, true)
                && !in_array($extension, array_values(self::ALLOWED_DOCUMENT_TYPES), true)
            ) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen($this->publicPath)));

            $items[] = [
                'url' => $relative,
                'filename' => $entry->getFilename(),
                'kind' => in_array($extension, $imageExtensions, true) ? 'image' : 'document',
                'size' => $entry->getSize(),
                'uploaded_at' => $entry->getMTime(),
            ];
        }

        // Legfrissebb elöl
        usort($items, static fn(array $a, array $b): int => $b['uploaded_at'] <=> $a['uploaded_at']);

        return array_slice($items, 0, $limit);
    }

    /**
     * Ember által olvasható fájlméret.
     */
    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' kB';
        }

        return $bytes . ' B';
    }

    // =====================================================================
    // Belső segédmetódusok
    // =====================================================================

    /**
     * A fájl mentése dátum szerint rendezett könyvtárba.
     *
     * @return array{url:string, filename:string, size:int, type:string}
     */
    private function store(array $file, string $extension, string $mime): array
    {
        // Év/hónap szerinti bontás: így a könyvtárak nem nőnek kezelhetetlenre
        $relativeDir = self::MEDIA_DIR . '/' . date('Y') . '/' . date('m');
        $absoluteDir = $this->publicPath . '/' . $relativeDir;

        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            throw new AppException('A feltöltési könyvtár nem hozható létre', AppException::SERVER_ERROR);
        }

        // A fájlnév mindig generált: a kliens által küldött nevet nem használjuk
        $filename = Uuid::uuid4()->toString() . '.' . $extension;
        $absolutePath = $absoluteDir . '/' . $filename;

        if (!$this->moveUploadedFile($file['tmp_name'], $absolutePath)) {
            throw new AppException('A fájl mentése nem sikerült', AppException::SERVER_ERROR);
        }

        @chmod($absolutePath, 0644);

        return [
            'url' => '/' . $relativeDir . '/' . $filename,
            'filename' => $filename,
            'size' => (int) $file['size'],
            'type' => $mime,
        ];
    }

    /**
     * Feltöltött fájl áthelyezése.
     *
     * Külön metódusban, hogy tesztben felülírható legyen: a
     * move_uploaded_file() csak valódi HTTP feltöltésnél működik.
     */
    protected function moveUploadedFile(string $from, string $to): bool
    {
        if (is_uploaded_file($from)) {
            return move_uploaded_file($from, $to);
        }

        // Teszt vagy CLI környezet: sima áthelyezés
        return rename($from, $to);
    }

    /**
     * A PHP feltöltési hibakódjának ellenőrzése.
     *
     * @throws AppException
     */
    private function assertUploadOk(array $file): void
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_OK) {
            if (empty($file['tmp_name']) || !is_readable($file['tmp_name'])) {
                throw AppException::validationError('A feltöltött fájl nem olvasható.');
            }

            return;
        }

        throw AppException::validationError(match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A fájl túl nagy.',
            UPLOAD_ERR_PARTIAL => 'A feltöltés megszakadt, próbáld újra.',
            UPLOAD_ERR_NO_FILE => 'Nem érkezett fájl.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'A kiszolgáló nem tudta elmenteni a fájlt.',
            default => 'A feltöltés nem sikerült.',
        });
    }

    /**
     * @throws AppException
     */
    private function assertSize(array $file, int $maxBytes, string $message): void
    {
        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            throw AppException::validationError($message);
        }
    }

    /**
     * A fájl valódi MIME típusa a tartalom alapján.
     */
    private function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            throw new AppException('A fájltípus nem ellenőrizhető', AppException::SERVER_ERROR);
        }

        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime !== false ? $mime : 'application/octet-stream';
    }
}
