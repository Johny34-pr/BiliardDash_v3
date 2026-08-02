<?php

declare(strict_types=1);

namespace App\Services;

class ImageService
{
    /**
     * Bélyegkép generálás PHP GD library-vel.
     * Arányosan átméretez, majd középre vágja a megadott méretre.
     *
     * @param string $sourcePath Forrásfájl elérési útja
     * @param string $destPath Célfájl elérési útja
     * @param int $width Bélyegkép szélesség (alapértelmezett: 200px)
     * @param int $height Bélyegkép magasság (alapértelmezett: 200px)
     * @return bool Sikeres volt-e a generálás
     */
    public function createThumbnail(string $sourcePath, string $destPath, int $width = 200, int $height = 200): bool
    {
        // Forrásfájl típus megállapítása
        $imageInfo = @getimagesize($sourcePath);
        if ($imageInfo === false) {
            return false;
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // GD image resource létrehozása a típus alapján
        $sourceImage = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            default => false,
        };

        if ($sourceImage === false) {
            return false;
        }

        // Arányos méretezés kiszámítása: kitöltés a cél méretéhez (fill)
        $widthRatio = $width / $sourceWidth;
        $heightRatio = $height / $sourceHeight;
        $scale = max($widthRatio, $heightRatio);

        // Közbenső méret számítás
        $intermediateWidth = (int) ceil($sourceWidth * $scale);
        $intermediateHeight = (int) ceil($sourceHeight * $scale);

        // Közbenső (átméretezett) kép létrehozása
        $intermediateImage = imagecreatetruecolor($intermediateWidth, $intermediateHeight);
        if ($intermediateImage === false) {
            imagedestroy($sourceImage);
            return false;
        }

        // PNG átlátszóság megőrzése
        if ($mimeType === 'image/png') {
            imagealphablending($intermediateImage, false);
            imagesavealpha($intermediateImage, true);
            $transparent = imagecolorallocatealpha($intermediateImage, 0, 0, 0, 127);
            imagefilledrectangle($intermediateImage, 0, 0, $intermediateWidth, $intermediateHeight, $transparent);
        }

        // Átméretezés a közbenső képbe
        imagecopyresampled(
            $intermediateImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $intermediateWidth,
            $intermediateHeight,
            $sourceWidth,
            $sourceHeight
        );

        // Középre vágás a cél méretre
        $cropX = (int) floor(($intermediateWidth - $width) / 2);
        $cropY = (int) floor(($intermediateHeight - $height) / 2);

        $finalImage = imagecreatetruecolor($width, $height);
        if ($finalImage === false) {
            imagedestroy($sourceImage);
            imagedestroy($intermediateImage);
            return false;
        }

        // PNG átlátszóság megőrzése a végső képnél
        if ($mimeType === 'image/png') {
            imagealphablending($finalImage, false);
            imagesavealpha($finalImage, true);
            $transparent = imagecolorallocatealpha($finalImage, 0, 0, 0, 127);
            imagefilledrectangle($finalImage, 0, 0, $width, $height, $transparent);
        }

        // Vágás: közbenső képből a középső rész másolása
        imagecopy(
            $finalImage,
            $intermediateImage,
            0,
            0,
            $cropX,
            $cropY,
            $width,
            $height
        );

        // Cél könyvtár létrehozása ha nem létezik
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Mentés a cél formátumban
        $result = match ($mimeType) {
            'image/jpeg' => imagejpeg($finalImage, $destPath, 85),
            'image/png' => imagepng($finalImage, $destPath, 8),
            default => false,
        };

        // GD erőforrások felszabadítása
        imagedestroy($sourceImage);
        imagedestroy($intermediateImage);
        imagedestroy($finalImage);

        return $result;
    }

    /**
     * Ellenőrzi, hogy a megadott MIME típus támogatott képformátum-e.
     *
     * @param string $mimeType A fájl MIME típusa
     * @return bool Érvényes képtípus-e
     */
    public function isValidImageType(string $mimeType): bool
    {
        return in_array($mimeType, ['image/jpeg', 'image/png'], true);
    }

    /**
     * Ellenőrzi, hogy a fájl mérete a megengedett határ alatt van-e.
     *
     * @param int $size Fájl mérete bájtban
     * @param int $maxMB Maximális méret megabájtban (alapértelmezett: 10)
     * @return bool A méret elfogadható-e
     */
    public function isValidFileSize(int $size, int $maxMB = 10): bool
    {
        return $size <= $maxMB * 1024 * 1024;
    }

    /**
     * Kép fájlok törlése (teljes méretű és bélyegkép).
     * Hibákat elnyeli (@).
     *
     * @param string $fullPath Teljes méretű kép elérési útja
     * @param string $thumbPath Bélyegkép elérési útja
     */
    public function deleteImageFiles(string $fullPath, string $thumbPath): void
    {
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        if (file_exists($thumbPath)) {
            @unlink($thumbPath);
        }
    }
}
