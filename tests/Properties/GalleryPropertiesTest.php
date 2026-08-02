<?php

declare(strict_types=1);

namespace Tests\Properties;

use Eris\Generators;
use Eris\TestTrait;
use App\Services\ImageService;
use Tests\TestCase;

/**
 * Property-based tesztek a galéria modulhoz.
 *
 * Validates: Requirements 3.1, 4.1, 4.2, 4.3, 4.5, 4.6, 4.7
 */
class GalleryPropertiesTest extends TestCase
{
    use TestTrait;

    private array $tempFiles = [];

    protected function tearDown(): void
    {
        // Temp fájlok takarítása
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = [];

        parent::tearDown();
    }

    /**
     * Feature: billiard-website, Property 5: Albumok fordított időrendi sorrendje
     *
     * For any collection of albums with different created_at dates,
     * getAlbums() returns them in reverse chronological order (created_at DESC).
     *
     * **Validates: Requirements 3.1**
     */
    public function testAlbumsReverseChronologicalOrder(): void
    {
        $galleryService = $this->createGalleryService();

        $this->forAll(
            Generators::choose(2, 10)
        )->then(function (int $count) use ($galleryService) {
            $this->truncateTable('images');
            $this->truncateTable('albums');

            // Create albums with distinct timestamps
            for ($i = 0; $i < $count; $i++) {
                $id = \Ramsey\Uuid\Uuid::uuid4()->toString();
                $name = 'Album ' . ($i + 1);
                // Insert directly with distinct timestamps to guarantee ordering
                $createdAt = date('Y-m-d H:i:s', strtotime("-{$i} hours"));
                $this->db->prepare(
                    'INSERT INTO albums (id, name, created_at) VALUES (:id, :name, :created_at)'
                )->execute([
                    ':id' => $id,
                    ':name' => $name,
                    ':created_at' => $createdAt,
                ]);
            }

            $albums = $galleryService->getAlbums();

            // Property: all albums are returned
            $this->assertCount($count, $albums);

            // Property: albums are in descending order by created_at
            for ($i = 1; $i < count($albums); $i++) {
                $this->assertGreaterThanOrEqual(
                    $albums[$i]['created_at'],
                    $albums[$i - 1]['created_at'],
                    'Albums should be in descending order by created_at'
                );
            }
        });
    }

    /**
     * Feature: billiard-website, Property 8: Képfeltöltés validáció
     *
     * For any file, the upload validation accepts it if and only if
     * the file type is JPEG or PNG AND the size is ≤10 MB.
     * All other cases are rejected with an appropriate error message.
     *
     * **Validates: Requirements 4.2, 4.5, 4.6**
     */
    public function testImageUploadValidation(): void
    {
        $validationService = $this->createValidationService();

        // Valid MIME types
        $validTypes = ['image/jpeg', 'image/png'];

        // Invalid MIME types
        $invalidTypes = [
            'image/gif', 'image/bmp', 'image/webp', 'application/pdf',
            'text/plain', 'application/zip', 'image/svg+xml',
        ];

        $maxSize = 10 * 1024 * 1024; // 10 MB

        // Case 1: Valid type + valid size => accepted
        $this->forAll(
            Generators::elements($validTypes),
            Generators::choose(1, $maxSize)
        )->then(function (string $type, int $size) use ($validationService) {
            $file = ['type' => $type, 'size' => $size];
            $validator = $validationService->validateImageUpload($file);

            $this->assertTrue(
                $validator->isValid(),
                "Valid type ({$type}) and valid size ({$size}) should be accepted"
            );
        });

        // Case 2: Invalid type => rejected
        $this->forAll(
            Generators::elements($invalidTypes),
            Generators::choose(1, $maxSize)
        )->then(function (string $type, int $size) use ($validationService) {
            $file = ['type' => $type, 'size' => $size];
            $validator = $validationService->validateImageUpload($file);

            $this->assertFalse(
                $validator->isValid(),
                "Invalid type ({$type}) should be rejected"
            );
            $this->assertNotNull($validator->getError('image'));
        });

        // Case 3: Valid type + oversized => rejected
        $this->forAll(
            Generators::elements($validTypes),
            Generators::choose($maxSize + 1, $maxSize * 3)
        )->then(function (string $type, int $size) use ($validationService) {
            $file = ['type' => $type, 'size' => $size];
            $validator = $validationService->validateImageUpload($file);

            $this->assertFalse(
                $validator->isValid(),
                "Oversized file ({$size} bytes) should be rejected"
            );
            $this->assertNotNull($validator->getError('image'));
        });
    }

    /**
     * Feature: billiard-website, Property 9: Bélyegkép generálás mérete
     *
     * For any valid JPEG or PNG image of arbitrary dimensions,
     * thumbnail generation always produces exactly a 200x200 pixel image.
     *
     * **Validates: Requirements 4.3**
     */
    public function testThumbnailGenerationSize(): void
    {
        $imageService = new ImageService();

        $this->forAll(
            Generators::choose(50, 2000),
            Generators::choose(50, 2000)
        )->then(function (int $width, int $height) use ($imageService) {
            // Create a test JPEG image using GD
            $sourceImage = \imagecreatetruecolor($width, $height);
            $color = \imagecolorallocate($sourceImage, rand(0, 255), rand(0, 255), rand(0, 255));
            \imagefill($sourceImage, 0, 0, $color);

            // Save source to temp file
            $sourcePath = \tempnam(\sys_get_temp_dir(), 'gallery_test_src_') . '.jpg';
            $this->tempFiles[] = $sourcePath;
            \imagejpeg($sourceImage, $sourcePath, 90);
            \imagedestroy($sourceImage);

            // Generate thumbnail
            $destPath = \tempnam(\sys_get_temp_dir(), 'gallery_test_thumb_') . '.jpg';
            $this->tempFiles[] = $destPath;

            $result = $imageService->createThumbnail($sourcePath, $destPath);

            // Property: thumbnail creation succeeds
            $this->assertTrue($result, "Thumbnail creation should succeed for {$width}x{$height} image");

            // Property: thumbnail is exactly 200x200
            $thumbInfo = \getimagesize($destPath);
            $this->assertNotFalse($thumbInfo, 'Thumbnail should be a valid image');
            $this->assertSame(200, $thumbInfo[0], "Thumbnail width should be 200px, got {$thumbInfo[0]}px");
            $this->assertSame(200, $thumbInfo[1], "Thumbnail height should be 200px, got {$thumbInfo[1]}px");
        });
    }

    /**
     * Feature: billiard-website, Property 10: Album létrehozás round-trip
     *
     * For any valid album name (1-100 characters, not whitespace-only),
     * creating an album and retrieving it returns the same name and a valid created_at date.
     *
     * **Validates: Requirements 4.1**
     */
    public function testAlbumCreationRoundTrip(): void
    {
        $galleryService = $this->createGalleryService();

        $this->forAll(
            Generators::suchThat(
                function (string $s) {
                    $trimmed = trim($s);
                    return mb_strlen($trimmed) > 0 && mb_strlen($trimmed) <= 100;
                },
                Generators::string()
            )
        )->then(function (string $name) use ($galleryService) {
            $this->truncateTable('images');
            $this->truncateTable('albums');

            $created = $galleryService->createAlbum($name);

            // Property: returned album has a valid ID
            $this->assertNotEmpty($created['id']);

            // Property: name is preserved
            $this->assertSame($name, $created['name']);

            // Property: created_at is set
            $this->assertNotEmpty($created['created_at']);

            // Property: image_count starts at 0
            $this->assertEquals(0, $created['image_count']);

            // Verify via getAlbums retrieval
            $albums = $galleryService->getAlbums();
            $this->assertCount(1, $albums);
            $this->assertSame($name, $albums[0]['name']);
            $this->assertSame($created['id'], $albums[0]['id']);
        });
    }

    /**
     * Feature: billiard-website, Property 11: Album név validáció elutasítja az üres neveket
     *
     * For any string that is empty or consists only of whitespace characters,
     * album creation validation rejects it and returns an error message.
     *
     * **Validates: Requirements 4.7**
     */
    public function testAlbumNameValidationRejectsEmptyNames(): void
    {
        $validationService = $this->createValidationService();

        // Generator for empty/whitespace-only strings
        $emptyOrWhitespace = Generators::elements(['', ' ', '  ', "\t", "\n", "   \t\n", "\r\n", "    "]);

        $this->forAll(
            $emptyOrWhitespace
        )->then(function (string $name) use ($validationService) {
            $validator = $validationService->validateAlbum(['name' => $name]);

            // Property: validation fails for empty/whitespace name
            $this->assertFalse(
                $validator->isValid(),
                "Validation should reject empty/whitespace-only album name: " . json_encode($name)
            );

            // Property: there is an error on the 'name' field
            $this->assertNotNull(
                $validator->getError('name'),
                "Name field should have a validation error for: " . json_encode($name)
            );
        });
    }
}
