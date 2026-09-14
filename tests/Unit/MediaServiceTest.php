<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\AppException;
use App\Services\MediaService;
use PHPUnit\Framework\TestCase;

/**
 * A szerkesztőbe feltöltött média validációjának tesztjei.
 *
 * A feltöltés a funkció biztonsági szempontból legérzékenyebb pontja, ezért
 * külön ellenőrizzük, hogy:
 *   - a fájltípus a tartalomból derül ki, nem a kliens állításából,
 *   - a generált fájlnév nem hordozza a feltöltött nevet (kettős kiterjesztés),
 *   - a nem engedélyezett formátumok elutasításra kerülnek.
 *
 * Nem a Tests\TestCase-ből származik, mert nem kell hozzá adatbázis.
 */
class MediaServiceTest extends TestCase
{
    private string $tempRoot;
    private MediaService $service;

    /** @var array<string> Törlésre váró ideiglenes fájlok */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Külön "public" gyökér, hogy a tesztek ne írjanak a valódi könyvtárba
        $this->tempRoot = sys_get_temp_dir() . '/mediatest_' . bin2hex(random_bytes(6));
        mkdir($this->tempRoot . '/uploads/media', 0777, true);

        $this->service = new MediaService($this->tempRoot);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        $this->removeDirectory($this->tempRoot);

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }

    /**
     * Feltöltésre kész $_FILES elem előállítása adott tartalommal.
     *
     * A `type` szándékosan megadható hamisan, hogy ellenőrizhető legyen:
     * a szolgáltatás nem erre támaszkodik.
     */
    private function fakeUpload(string $content, string $clientName, string $clientType): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($tmp, $content);
        $this->tempFiles[] = $tmp;

        return [
            'name' => $clientName,
            'tmp_name' => $tmp,
            'type' => $clientType,
            'size' => strlen($content),
            'error' => UPLOAD_ERR_OK,
        ];
    }

    /** Valódi, apró PNG előállítása GD-vel */
    private function pngBytes(int $width = 20, int $height = 20): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    // =================================================================
    // Kép feltöltés
    // =================================================================

    public function testStoresValidPngImage(): void
    {
        $file = $this->fakeUpload($this->pngBytes(), 'kep.png', 'image/png');

        $result = $this->service->storeImage($file);

        $this->assertStringEndsWith('.png', $result['url']);
        $this->assertFileExists($this->tempRoot . $result['url']);
        $this->assertSame('image/png', $result['type']);
    }

    public function testStoredFilenameDoesNotKeepTheUploadedName(): void
    {
        // Kettős kiterjesztéssel próbálkozó név
        $file = $this->fakeUpload($this->pngBytes(), 'kartekony.php.png', 'image/png');

        $result = $this->service->storeImage($file);

        $this->assertStringNotContainsString('kartekony', $result['filename']);
        $this->assertStringNotContainsString('.php', $result['filename']);
        // UUID + kiterjesztés alakú
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.png$/', $result['filename']);
    }

    public function testUrlIsOrganisedByYearAndMonth(): void
    {
        $file = $this->fakeUpload($this->pngBytes(), 'kep.png', 'image/png');

        $result = $this->service->storeImage($file);

        $this->assertStringStartsWith('/uploads/media/' . date('Y') . '/' . date('m') . '/', $result['url']);
    }

    public function testRejectsPhpDisguisedAsImage(): void
    {
        // A kliens képnek állítja, a tartalom viszont PHP kód
        $file = $this->fakeUpload('<?php echo "hack"; ?>', 'kep.png', 'image/png');

        $this->expectException(AppException::class);

        $this->service->storeImage($file);
    }

    public function testRejectsSvgImage(): void
    {
        // Az SVG szkriptet tartalmazhat, ezért nincs az engedélyezett listán
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $file = $this->fakeUpload($svg, 'kep.svg', 'image/svg+xml');

        $this->expectException(AppException::class);

        $this->service->storeImage($file);
    }

    public function testRejectsOversizedImage(): void
    {
        $file = $this->fakeUpload($this->pngBytes(), 'kep.png', 'image/png');
        // A méret mezőt a korlát felé állítjuk
        $file['size'] = MediaService::MAX_IMAGE_BYTES + 1;

        $this->expectException(AppException::class);

        $this->service->storeImage($file);
    }

    public function testRejectsFailedUpload(): void
    {
        $file = $this->fakeUpload($this->pngBytes(), 'kep.png', 'image/png');
        $file['error'] = UPLOAD_ERR_PARTIAL;

        $this->expectException(AppException::class);

        $this->service->storeImage($file);
    }

    // =================================================================
    // Dokumentum feltöltés
    // =================================================================

    public function testStoresValidPdfDocument(): void
    {
        // Minimális, de érvényes PDF fejléc, hogy a finfo felismerje
        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";
        $file = $this->fakeUpload($pdf, 'versenykiiras.pdf', 'application/pdf');

        $result = $this->service->storeDocument($file);

        $this->assertStringEndsWith('.pdf', $result['url']);
        $this->assertFileExists($this->tempRoot . $result['url']);
    }

    public function testStoresPlainTextDocument(): void
    {
        $file = $this->fakeUpload("Eredmenylista\nElso helyezett", 'eredmeny.txt', 'text/plain');

        $result = $this->service->storeDocument($file);

        $this->assertStringEndsWith('.txt', $result['url']);
    }

    public function testRejectsExecutableAsDocument(): void
    {
        $file = $this->fakeUpload('<?php system($_GET["c"]); ?>', 'doc.pdf', 'application/pdf');

        $this->expectException(AppException::class);

        $this->service->storeDocument($file);
    }

    public function testImageIsNotAcceptedAsDocument(): void
    {
        // A kép nincs a dokumentum-listán: a két végpont nem keverhető össze
        $file = $this->fakeUpload($this->pngBytes(), 'kep.png', 'image/png');

        $this->expectException(AppException::class);

        $this->service->storeDocument($file);
    }

    // =================================================================
    // Médiakönyvtár
    // =================================================================

    public function testLibraryIsEmptyInitially(): void
    {
        $this->assertSame([], $this->service->listMedia());
    }

    public function testLibraryListsUploadedItemsWithKind(): void
    {
        $this->service->storeImage($this->fakeUpload($this->pngBytes(), 'a.png', 'image/png'));
        $this->service->storeDocument($this->fakeUpload("szoveg", 'b.txt', 'text/plain'));

        $items = $this->service->listMedia();

        $this->assertCount(2, $items);

        $kinds = array_column($items, 'kind');
        $this->assertContains('image', $kinds);
        $this->assertContains('document', $kinds);
    }

    public function testLibraryIgnoresGitkeep(): void
    {
        file_put_contents($this->tempRoot . '/uploads/media/.gitkeep', '');

        $this->assertSame([], $this->service->listMedia());
    }

    // =================================================================
    // Méretformázás
    // =================================================================

    public function testFormatSize(): void
    {
        $this->assertSame('512 B', MediaService::formatSize(512));
        $this->assertSame('2 kB', MediaService::formatSize(2048));
        $this->assertSame('1.5 MB', MediaService::formatSize(1572864));
    }
}
