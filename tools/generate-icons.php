<?php

declare(strict_types=1);

/**
 * Okányi Biliárd Klub - ikongenerátor
 * =============================================================================
 *
 * A public/assets/images/logo.png (a klub címere) alapján előállítja az összes
 * ikonméretet és a közösségi megosztás előnézeti képét. Egyetlen forrásfájl
 * van, így ha a logó cserélődik, elég azt felülírni és ezt a szkriptet újra
 * lefuttatni.
 *
 * A logó ugyanebből a fájlból kerül a fejlécbe és a láblécbe is
 * (src/Views/partials/brand-mark.php), tehát egyetlen kép szolgál mindenre.
 *
 * Futtatás a projekt gyökeréből:
 *     php tools/generate-icons.php
 *
 * Előállított fájlok:
 *     public/favicon.ico                   16 + 32 + 48 px (PNG-be csomagolt ICO)
 *     public/apple-touch-icon.png          180 px, iOS kezdőképernyő
 *     public/icon-192.png                  192 px, webmanifest
 *     public/icon-512.png                  512 px, webmanifest
 *     public/assets/images/og-default.png  1200x630, közösségi megosztás
 *
 * Miért világos háttér az ikonokon
 * --------------------------------
 * A címer túlnyomóan kék, arany kerettel. Sötétzöld alapon a két sötét szín
 * összemosódna, és 16 pixelen felismerhetetlen lenne. A törtfehér (sand-50)
 * háttér minden méretben elválasztja a címert a böngésző fülétől, és egyezik
 * a webmanifest background_color értékével.
 *
 * Az élsimítást túlminta-vétel adja: a rajz a célméret négyszeresén készül,
 * majd arányosan lekicsinyítjük.
 */

// -----------------------------------------------------------------------------
// Márkaszínek (a tools/css/tokens.php design tokenjeivel egyezően)
// -----------------------------------------------------------------------------
const GREEN_900 = [0x0c, 0x2f, 0x22];
const GREEN_950 = [0x06, 0x1a, 0x13];
const GOLD_400  = [0xe5, 0xb5, 0x44];
const GOLD_300  = [0xec, 0xcb, 0x70];
const SAND_50   = [0xfb, 0xfa, 0xf8];

/** Túlminta-vételi arány az élsimításhoz */
const SUPERSAMPLE = 4;

/** A címer mekkora részét foglalja el a vászonnak (a maradék a levegő) */
const LOGO_FILL = 0.80;

$rootDir = dirname(__DIR__);
$publicDir = $rootDir . '/public';
$logoPath = $publicDir . '/assets/images/logo.png';

// -----------------------------------------------------------------------------
// Segédfüggvények
// -----------------------------------------------------------------------------

/**
 * Átlátszó hátterű, alfacsatornát megőrző vászon.
 *
 * A visszaadott képen az alphablending be van kapcsolva, hogy rajzolni
 * lehessen rá. Lekicsinyítés célpontjaként előtte ki kell kapcsolni,
 * különben az alfacsatorna nem őrződik meg helyesen.
 */
function createCanvas(int $width, int $height): \GdImage
{
    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocatealpha($image, 0, 0, 0, 127));
    imagealphablending($image, true);

    return $image;
}

/**
 * Szín lefoglalása egy [r, g, b] tömbből.
 *
 * @param array{0:int,1:int,2:int} $rgb
 */
function color(\GdImage $image, array $rgb, int $alpha = 0): int
{
    return imagecolorallocatealpha($image, $rgb[0], $rgb[1], $rgb[2], $alpha);
}

/**
 * Lekerekített, kitöltött négyszög.
 *
 * A GD-nek nincs beépített lekerekített négyszöge: két egymásra fektetett
 * téglalap és négy sarok-ellipszis adja ki a formát.
 */
function filledRoundedRect(\GdImage $image, int $x, int $y, int $width, int $height, int $radius, int $color): void
{
    $radius = min($radius, (int) floor(min($width, $height) / 2));
    $diameter = $radius * 2;

    imagefilledrectangle($image, $x, $y + $radius, $x + $width - 1, $y + $height - 1 - $radius, $color);
    imagefilledrectangle($image, $x + $radius, $y, $x + $width - 1 - $radius, $y + $height - 1, $color);

    imagefilledellipse($image, $x + $radius, $y + $radius, $diameter, $diameter, $color);
    imagefilledellipse($image, $x + $width - 1 - $radius, $y + $radius, $diameter, $diameter, $color);
    imagefilledellipse($image, $x + $radius, $y + $height - 1 - $radius, $diameter, $diameter, $color);
    imagefilledellipse($image, $x + $width - 1 - $radius, $y + $height - 1 - $radius, $diameter, $diameter, $color);
}

/**
 * A logó betöltése és az átlátszó szegély levágása.
 *
 * A vágás nélkül a forrásfájl körüli üres sáv is beleszámítana a méretezésbe,
 * és a címer a vártnál kisebben, esetleg középről elcsúszva jelenne meg. Így
 * viszont a tényleges rajz határai adják a méretet, bármilyen logóval.
 *
 * A képet statikusan tároljuk: minden ikonmérethez ugyanez a forrás kell.
 */
function loadLogo(string $path): \GdImage
{
    static $trimmed = null;

    if ($trimmed instanceof \GdImage) {
        return $trimmed;
    }

    $source = @imagecreatefrompng($path);

    if ($source === false) {
        fwrite(STDERR, 'Hiba: nem sikerült beolvasni a logót: ' . $path . "\n");
        exit(1);
    }

    imagealphablending($source, false);
    imagesavealpha($source, true);

    $width = imagesx($source);
    $height = imagesy($source);

    // Az átlátszatlan képpontok befoglaló téglalapja
    $minX = $width;
    $minY = $height;
    $maxX = -1;
    $maxY = -1;

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            // A teljesen átlátszó (127) és a szinte átlátszó pontok nem
            // számítanak: azok a tömörítés maradványai, nem a rajz része
            $alpha = (imagecolorat($source, $x, $y) >> 24) & 0x7F;

            if ($alpha > 120) {
                continue;
            }

            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }

    if ($maxX < 0) {
        fwrite(STDERR, "Hiba: a logó teljesen átlátszó.\n");
        exit(1);
    }

    $cropWidth = $maxX - $minX + 1;
    $cropHeight = $maxY - $minY + 1;

    $trimmed = createCanvas($cropWidth, $cropHeight);
    imagealphablending($trimmed, false);
    imagecopy($trimmed, $source, 0, 0, $minX, $minY, $cropWidth, $cropHeight);
    imagealphablending($trimmed, true);
    imagedestroy($source);

    return $trimmed;
}

/**
 * A címer arányos beillesztése egy négyzetes vászon közepére.
 *
 * Az arányt megtartjuk: a hosszabbik oldal tölti ki a rendelkezésre álló
 * területet, a rövidebbik középre kerül. Így a logó soha nem nyúlik meg.
 */
function placeLogo(\GdImage $canvas, string $logoPath, int $size, float $fill): void
{
    $logo = loadLogo($logoPath);

    $logoWidth = imagesx($logo);
    $logoHeight = imagesy($logo);

    $box = $size * $fill;
    $scale = min($box / $logoWidth, $box / $logoHeight);

    $targetWidth = max(1, (int) round($logoWidth * $scale));
    $targetHeight = max(1, (int) round($logoHeight * $scale));

    imagecopyresampled(
        $canvas,
        $logo,
        (int) round(($size - $targetWidth) / 2),
        (int) round(($size - $targetHeight) / 2),
        0,
        0,
        $targetWidth,
        $targetHeight,
        $logoWidth,
        $logoHeight
    );
}

/**
 * Egy ikonméret előállítása: háttér + a klub címere.
 *
 * @param string $background A háttér fajtája:
 *                           'rounded' - lekerekített törtfehér négyzet
 *                                       (favicon, webmanifest)
 *                           'square'  - teljes törtfehér négyzet
 *                                       (apple-touch-icon, a sarkokat az
 *                                       iOS maga vágja le)
 *                           'none'    - csak a címer, átlátszó háttérrel
 */
function drawIcon(int $size, string $logoPath, string $background = 'rounded'): \GdImage
{
    $s = $size * SUPERSAMPLE;
    $canvas = createCanvas($s, $s);

    $sand = color($canvas, SAND_50);

    if ($background === 'rounded') {
        filledRoundedRect($canvas, 0, 0, $s, $s, (int) round($s * 14 / 64), $sand);
    } elseif ($background === 'square') {
        imagefilledrectangle($canvas, 0, 0, $s, $s, $sand);
    }

    placeLogo($canvas, $logoPath, $s, LOGO_FILL);

    // Lekicsinyítés a célméretre - ez adja az élsimítást.
    // A célképen az alphablending kikapcsolva, hogy az alfa átmásolódjon.
    $out = createCanvas($size, $size);
    imagealphablending($out, false);
    imagecopyresampled($out, $canvas, 0, 0, 0, 0, $size, $size, $s, $s);
    imagedestroy($canvas);

    return $out;
}

/**
 * ICO fájl összeállítása több PNG-ből.
 *
 * A Vista óta minden érintett böngésző elfogadja a PNG tartalmú ICO-t,
 * ezért nem kell BMP-t építeni. Szerkezet: 6 bájt fejléc, méretenként
 * 16 bájt könyvtárbejegyzés, végül a PNG adatok egymás után.
 *
 * @param array<int, string> $pngBySize méret => PNG bináris tartalom
 */
function buildIco(array $pngBySize): string
{
    $count = count($pngBySize);

    // ICONDIR: fenntartott (0), típus (1 = ikon), képek száma
    $header = pack('vvv', 0, 1, $count);

    $directory = '';
    $data = '';
    $offset = 6 + $count * 16;

    foreach ($pngBySize as $size => $png) {
        // A 256 pixeles méretet 0 jelöli az ICO formátumban
        $dimension = $size >= 256 ? 0 : $size;

        // ICONDIRENTRY: szélesség, magasság, paletta, fenntartott,
        // színsíkok, bitmélység, adathossz, adat eltolása
        $directory .= pack('CCCCvvVV', $dimension, $dimension, 0, 0, 1, 32, strlen($png), $offset);

        $data .= $png;
        $offset += strlen($png);
    }

    return $header . $directory . $data;
}

/**
 * PNG kiírása bináris stringbe.
 */
function pngToString(\GdImage $image): string
{
    ob_start();
    imagepng($image, null, 9);

    return (string) ob_get_clean();
}

/**
 * Az első elérhető, félkövér TrueType betűtípus kiválasztása.
 *
 * A telepített betűtípusok környezetenként mások, ezért több jelöltet
 * végigpróbálunk. Betűtípus nélkül a megosztási kép szöveg nélkül készül el.
 */
function findFont(): ?string
{
    $candidates = [
        __DIR__ . '/fonts/Inter-Bold.ttf',
        'C:/Windows/Fonts/segoeuib.ttf',
        'C:/Windows/Fonts/arialbd.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
    ];

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

/**
 * Közösségi megosztás előnézeti képe (Open Graph / Twitter Card).
 *
 * 1200x630 az a méret, amelyet a Facebook és a Twitter is nagy kártyaként
 * jelenít meg. A kép sötétzöld alapon a klub címerét és nevét mutatja, hogy
 * a megosztott link a hírfolyamban felismerhető legyen.
 */
function drawOgImage(
    string $logoPath,
    string $siteName,
    string $tagline,
    int $width = 1200,
    int $height = 630
): \GdImage {
    $canvas = imagecreatetruecolor($width, $height);
    imagealphablending($canvas, true);

    // Háttér: finom függőleges átmenet a két legmélyebb zöld között
    for ($y = 0; $y < $height; $y++) {
        $t = $y / max(1, $height - 1);
        $shade = imagecolorallocate(
            $canvas,
            (int) round(GREEN_900[0] + (GREEN_950[0] - GREEN_900[0]) * $t),
            (int) round(GREEN_900[1] + (GREEN_950[1] - GREEN_900[1]) * $t),
            (int) round(GREEN_900[2] + (GREEN_950[2] - GREEN_900[2]) * $t)
        );
        imagefilledrectangle($canvas, 0, $y, $width - 1, $y, $shade);
    }

    // Arany zárósáv alul - a fejléc jelzővonalának képi megfelelője
    imagefilledrectangle($canvas, 0, $height - 12, $width - 1, $height - 1, color($canvas, GOLD_400));

    // A címer világos táblán, a bal oldalon. A tábla azért kell, mert a kék
    // címer a sötétzöld háttéren beleolvadna a felületbe.
    $panelSize = 260;
    $panelX = 92;
    $panelY = (int) round(($height - $panelSize) / 2) - 6;

    $panel = drawIcon($panelSize, $logoPath, 'rounded');
    imagecopy($canvas, $panel, $panelX, $panelY, 0, 0, $panelSize, $panelSize);
    imagedestroy($panel);

    // Szöveg a címer mellett
    $font = findFont();

    if ($font === null) {
        fwrite(STDERR, "  Figyelem: nem találtam TrueType betűtípust, a megosztási kép szöveg nélkül készül.\n");
        return $canvas;
    }

    $textX = $panelX + $panelSize + 56;

    // A klubnév két sorban fér ki olvasható méretben: a "Okányi Biliárd"
    // és a "Klub" külön sorba kerül, hogy ne kelljen apró betűre váltani.
    imagettftext($canvas, 58, 0, $textX, $panelY + 104, color($canvas, SAND_50), $font, 'Okányi');
    imagettftext($canvas, 58, 0, $textX, $panelY + 174, color($canvas, SAND_50), $font, 'Biliárd Klub');
    imagettftext($canvas, 25, 0, $textX, $panelY + 230, color($canvas, GOLD_300), $font, $tagline);

    return $canvas;
}

// -----------------------------------------------------------------------------
// Generálás
// -----------------------------------------------------------------------------

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Hiba: a GD kiterjesztés nem elérhető.\n");
    exit(1);
}

if (!is_file($logoPath)) {
    fwrite(STDERR, "Hiba: nem található a logó: public/assets/images/logo.png\n");
    exit(1);
}

echo "Ikonok generálása a public/assets/images/logo.png alapján...\n";

$logo = loadLogo($logoPath);
echo '  A címer hasznos területe: ' . imagesx($logo) . 'x' . imagesy($logo) . " px\n";

// --- favicon.ico (16, 32, 48) ---
$icoSizes = [16, 32, 48];
$pngBySize = [];

foreach ($icoSizes as $size) {
    $icon = drawIcon($size, $logoPath);
    $pngBySize[$size] = pngToString($icon);
    imagedestroy($icon);
}

file_put_contents($publicDir . '/favicon.ico', buildIco($pngBySize));
echo '  public/favicon.ico (' . implode(', ', $icoSizes) . " px)\n";

// --- apple-touch-icon.png (180, teljes négyzet háttérrel) ---
$apple = drawIcon(180, $logoPath, 'square');
imagepng($apple, $publicDir . '/apple-touch-icon.png', 9);
imagedestroy($apple);
echo "  public/apple-touch-icon.png (180 px)\n";

// --- webmanifest ikonok ---
foreach ([192, 512] as $size) {
    $icon = drawIcon($size, $logoPath);
    imagepng($icon, $publicDir . '/icon-' . $size . '.png', 9);
    imagedestroy($icon);
    echo "  public/icon-{$size}.png ({$size} px)\n";
}

// --- közösségi megosztás előnézete ---
$og = drawOgImage($logoPath, 'Okányi Biliárd Klub', 'Hírek · Galéria · Online versenynevezés');
imagepng($og, $publicDir . '/assets/images/og-default.png', 9);
imagedestroy($og);
echo "  public/assets/images/og-default.png (1200x630)\n";

echo "Kész.\n";
