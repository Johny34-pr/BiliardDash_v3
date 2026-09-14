<?php

declare(strict_types=1);

/**
 * Magyar Biliárd - ikongenerátor
 * =============================================================================
 *
 * A public/favicon.svg alapján előállítja a raszteres ikonokat és a
 * közösségi megosztás előnézeti képét. A böngészők és a közösségi
 * platformok nem mind kezelik az SVG-t, ezért ezek a fájlok is kellenek.
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
 * Az élsimítást túlminta-vétel adja: a rajz a célméret négyszeresén készül,
 * majd arányosan lekicsinyítjük. A GD nem tud SVG-t raszterezni, ezért az
 * alakzatok itt is meg vannak rajzolva - a favicon.svg-vel egyező arányokkal.
 */

// -----------------------------------------------------------------------------
// Márkaszínek (a public/assets/css/app.css design tokenjeivel egyezően)
// -----------------------------------------------------------------------------
const GREEN_900 = [0x0c, 0x2f, 0x22];
const GREEN_950 = [0x06, 0x1a, 0x13];
const GOLD_400  = [0xe5, 0xb5, 0x44];
const GOLD_300  = [0xec, 0xcb, 0x70];
const SAND_50   = [0xfb, 0xfa, 0xf8];

/** Túlminta-vételi arány az élsimításhoz */
const SUPERSAMPLE = 4;

$publicDir = dirname(__DIR__) . '/public';

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
 * Vízszintes sáv körbe vágva - a golyó arany csíkja.
 *
 * Soronként kiszámoljuk a kör vízszintes kiterjedését, így a sáv pontosan
 * a golyó körvonalánál ér véget, külön maszkolás nélkül.
 */
function stripeClippedToCircle(
    \GdImage $image,
    float $cx,
    float $cy,
    float $radius,
    float $bandTop,
    float $bandBottom,
    int $color
): void {
    $from = (int) round(max($bandTop, $cy - $radius));
    $to = (int) round(min($bandBottom, $cy + $radius));

    for ($y = $from; $y <= $to; $y++) {
        // A pixel közepét vizsgáljuk, hogy a szél ne csúszjon el
        $dy = ($y + 0.5) - $cy;
        $inside = $radius * $radius - $dy * $dy;

        if ($inside <= 0) {
            continue;
        }

        $dx = sqrt($inside);
        imageline($image, (int) round($cx - $dx), $y, (int) round($cx + $dx) - 1, $y, $color);
    }
}

/**
 * A sávos biliárdgolyó ikon megrajzolása négyzetes vászonra.
 *
 * Az arányok a favicon.svg 64-es rácsához igazodnak:
 *   lekerekítés 14/64, golyó sugara 19/64, sáv 20..44/64, számmező 8/64.
 *
 * @param string $background A háttér fajtája:
 *                           'rounded' - lekerekített zöld négyzet (favicon)
 *                           'square'  - teljes zöld négyzet (apple-touch-icon,
 *                                       a sarkokat az iOS maga vágja le)
 *                           'none'    - csak a golyó, átlátszó háttérrel
 *                                       (sötét alapra helyezéshez)
 */
function drawIcon(int $size, string $background = 'rounded'): \GdImage
{
    $s = $size * SUPERSAMPLE;
    $canvas = createCanvas($s, $s);

    $green = color($canvas, GREEN_900);
    $gold = color($canvas, GOLD_400);
    $sand = color($canvas, SAND_50);

    // Háttér
    if ($background === 'rounded') {
        filledRoundedRect($canvas, 0, 0, $s, $s, (int) round($s * 14 / 64), $green);
    } elseif ($background === 'square') {
        imagefilledrectangle($canvas, 0, 0, $s, $s, $green);
    }

    // Golyó teste
    $center = $s / 2;
    $ballRadius = $s * 19 / 64;
    imagefilledellipse(
        $canvas,
        (int) round($center),
        (int) round($center),
        (int) round($ballRadius * 2),
        (int) round($ballRadius * 2),
        $sand
    );

    // Arany sáv a golyóra vágva
    stripeClippedToCircle($canvas, $center, $center, $ballRadius, $s * 20 / 64, $s * 44 / 64, $gold);

    // Számmező a sáv közepén
    $innerRadius = $s * 8 / 64;
    imagefilledellipse(
        $canvas,
        (int) round($center),
        (int) round($center),
        (int) round($innerRadius * 2),
        (int) round($innerRadius * 2),
        $sand
    );

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
 * jelenít meg. A kép sötétzöld alapon az ikont és a webhely nevét mutatja,
 * hogy a megosztott link a hírfolyamban felismerhető legyen.
 */
function drawOgImage(string $siteName, string $tagline, int $width = 1200, int $height = 630): \GdImage
{
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

    // Golyó a bal oldalon. Háttér nélkül rajzoljuk, mert a zöld keret
    // beleolvadna a szintén zöld háttérbe, és csak zavaró élt adna.
    $iconSize = 240;
    $iconX = 92;
    $iconY = (int) round(($height - $iconSize) / 2) - 10;

    $icon = drawIcon($iconSize, 'none');
    imagecopy($canvas, $icon, $iconX, $iconY, 0, 0, $iconSize, $iconSize);
    imagedestroy($icon);

    // Szöveg az ikon mellett
    $font = findFont();

    if ($font === null) {
        fwrite(STDERR, "  Figyelem: nem találtam TrueType betűtípust, a megosztási kép szöveg nélkül készül.\n");
        return $canvas;
    }

    $textX = $iconX + $iconSize + 52;

    imagettftext($canvas, 64, 0, $textX, $iconY + 122, color($canvas, SAND_50), $font, $siteName);
    imagettftext($canvas, 27, 0, $textX, $iconY + 180, color($canvas, GOLD_300), $font, $tagline);

    return $canvas;
}

// -----------------------------------------------------------------------------
// Generálás
// -----------------------------------------------------------------------------

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Hiba: a GD kiterjesztés nem elérhető.\n");
    exit(1);
}

echo "Ikonok generálása...\n";

// --- favicon.ico (16, 32, 48) ---
$icoSizes = [16, 32, 48];
$pngBySize = [];

foreach ($icoSizes as $size) {
    $icon = drawIcon($size);
    $pngBySize[$size] = pngToString($icon);
    imagedestroy($icon);
}

file_put_contents($publicDir . '/favicon.ico', buildIco($pngBySize));
echo '  public/favicon.ico (' . implode(', ', $icoSizes) . " px)\n";

// --- apple-touch-icon.png (180, teljes négyzet háttérrel) ---
$apple = drawIcon(180, 'square');
imagepng($apple, $publicDir . '/apple-touch-icon.png', 9);
imagedestroy($apple);
echo "  public/apple-touch-icon.png (180 px)\n";

// --- webmanifest ikonok ---
foreach ([192, 512] as $size) {
    $icon = drawIcon($size);
    imagepng($icon, $publicDir . '/icon-' . $size . '.png', 9);
    imagedestroy($icon);
    echo "  public/icon-{$size}.png ({$size} px)\n";
}

// --- közösségi megosztás előnézete ---
$og = drawOgImage('Magyar Biliárd', 'Hírek · Galéria · Online versenynevezés');
imagepng($og, $publicDir . '/assets/images/og-default.png', 9);
imagedestroy($og);
echo "  public/assets/images/og-default.png (1200x630)\n";

echo "Kész.\n";
