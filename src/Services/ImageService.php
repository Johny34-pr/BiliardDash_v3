<?php

declare(strict_types=1);

namespace App\Services;

class ImageService
{
    /**
     * A megjelenítéshez készülő közepes méret leghosszabb oldala.
     *
     * A galéria kártyái és az album oldala ezt a változatot mutatják. 1200 px
     * azért elég, mert a legnagyobb megjelenítési hely a kártyarács egy
     * eleme, illetve az album oldal bal hasábja - mindkettő 800 CSS pixel
     * alatt van, tehát a kétszeres képpontsűrűségű kijelzőn is éles.
     */
    public const MEDIUM_MAX_EDGE = 1200;

    /**
     * Arányos átméretezés vágás nélkül, a megadott leghosszabb oldalra.
     *
     * Ez a bélyegképtől két dologban tér el, és mindkettő szándékos:
     *
     *   - NEM vág: a kép teljes tartalma megmarad, csak kisebb lesz. A
     *     bélyegkép négyzetre vágott, ezért listaelemként kifeszítve lágy és
     *     levágott; ez a változat viszont az eredeti képarányt őrzi.
     *
     *   - NEM nagyít: ha a forrás már kisebb a korlátnál, a fájl változatlan
     *     másolatként kerül a célra. Felskálázástól a kép nem lesz élesebb,
     *     csak nagyobb, ezért ilyenkor nincs értelme újratömöríteni sem.
     *
     * A JPEG kvalitás 82: a 85-höz képest érdemben kisebb fájl, szemmel nem
     * megkülönböztethető eredménnyel.
     *
     * @param string $sourcePath Forrásfájl elérési útja
     * @param string $destPath Célfájl elérési útja
     * @param int $maxEdge A leghosszabb oldal megengedett hossza képpontban
     * @return bool Sikeres volt-e a generálás
     */
    public function createResized(string $sourcePath, string $destPath, int $maxEdge = self::MEDIUM_MAX_EDGE): bool
    {
        $imageInfo = @getimagesize($sourcePath);

        if ($imageInfo === false) {
            return false;
        }

        $sourceWidth = (int) $imageInfo[0];
        $sourceHeight = (int) $imageInfo[1];
        $mimeType = (string) $imageInfo['mime'];

        if (!$this->isValidImageType($mimeType)) {
            return false;
        }

        $destDir = dirname($destPath);
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            return false;
        }

        // A korlát alatti kép marad, ahogy van: a másolat pontosan annyit ér,
        // mint egy felskálázott és újratömörített változat, csak nem romlik
        if ($sourceWidth <= $maxEdge && $sourceHeight <= $maxEdge) {
            return copy($sourcePath, $destPath);
        }

        $sourceImage = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            default => false,
        };

        if ($sourceImage === false) {
            return false;
        }

        $scale = $maxEdge / max($sourceWidth, $sourceHeight);
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($target === false) {
            imagedestroy($sourceImage);
            return false;
        }

        // PNG átlátszóság megőrzése
        if ($mimeType === 'image/png') {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        imagecopyresampled(
            $target,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $result = match ($mimeType) {
            'image/jpeg' => imagejpeg($target, $destPath, 82),
            'image/png' => imagepng($target, $destPath, 8),
            default => false,
        };

        imagedestroy($sourceImage);
        imagedestroy($target);

        if ($result === false) {
            return false;
        }

        /*
         * Ha az átméretezett fájl nagyobb lett az eredetinél, az eredetit
         * használjuk helyette. Ez tömör, kevés színt használó PNG-nél fordul
         * elő: az újratömörítés rosszabb eredményt ad, mint amit a feltöltött
         * fájl már elért. Ilyenkor az eredeti minden szempontból jobb -
         * kevesebb letöltés ÉS több képpont egyszerre.
         */
        $sourceSize = @filesize($sourcePath);
        $destSize = @filesize($destPath);

        if ($sourceSize !== false && $destSize !== false && $destSize >= $sourceSize) {
            return copy($sourcePath, $destPath);
        }

        return true;
    }

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
     * Kép fájlok törlése (teljes méretű, közepes és bélyegkép).
     *
     * A hibákat elnyeli (@): ha egy fájl már nincs a lemezen, az nem ok arra,
     * hogy a rekord törlése megszakadjon.
     *
     * @param string      $fullPath   Teljes méretű kép elérési útja
     * @param string      $thumbPath  Bélyegkép elérési útja
     * @param string|null $mediumPath Közepes méret, ha készült hozzá
     */
    public function deleteImageFiles(string $fullPath, string $thumbPath, ?string $mediumPath = null): void
    {
        foreach ([$fullPath, $thumbPath, $mediumPath] as $path) {
            if ($path !== null && $path !== '' && file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
