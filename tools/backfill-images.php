<?php

declare(strict_types=1);

/**
 * Okányi Biliárd Klub - közepes méretű képek pótlása
 * =============================================================================
 *
 * A galéria kártyái és az album oldala egy közepes, legfeljebb 1200 képpont
 * élhosszú változatot mutatnak. Korábban ez a méret nem létezett: csak a
 * 200x200-as bélyegkép és a változatlan eredeti készült el, a kártya pedig a
 * bélyegképet feszítette ki négyszeres méretre - ettől volt lágy a borítókép.
 *
 * Az új feltöltéseknél a közepes méret automatikusan elkészül. Ez az eszköz a
 * KORÁBBAN feltöltött képeket pótolja: végigmegy azokon, amelyeknél a
 * medium_path még üres, elkészíti a fájlt, és beírja az útvonalat.
 *
 * Futtatás a projekt gyökeréből:
 *
 *     php tools/backfill-images.php            a pótlás elvégzése
 *     php tools/backfill-images.php --check    csak jelentés, írás nélkül
 *
 * Bármikor újrafuttatható: csak azokat a képeket dolgozza fel, amelyeknél a
 * medium_path üres, ezért a kész munkát nem végzi el újra.
 *
 * Ha egy forrásfájl hiányzik a lemezről, azt kihagyja és jelenti - a rekordot
 * nem törli, mert a döntés a szervezőé.
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Models\Image;
use App\Services\ImageService;

$root = dirname(__DIR__);
$checkOnly = in_array('--check', $argv, true);

Env::load($root . '/.env');

try {
    $db = Database::getConnection();
} catch (\Throwable $e) {
    fwrite(STDERR, 'Nem sikerült kapcsolódni az adatbázishoz: ' . $e->getMessage() . "\n");
    fwrite(STDERR, "Fut a MySQL kiszolgáló?\n");
    exit(1);
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Hiba: a GD kiterjesztés nem elérhető, kép nem méretezhető.\n");
    exit(1);
}

$imageModel = new Image($db);
$imageService = new ImageService();

$pending = $imageModel->findWithoutMedium();

echo "Közepes méretű képek pótlása\n";
echo '  Pótlásra váró kép: ' . count($pending) . "\n";
echo '  Leghosszabb oldal: ' . ImageService::MEDIUM_MAX_EDGE . " px\n\n";

if ($pending === []) {
    echo "Nincs mit pótolni: minden képhez van közepes méret.\n";
    exit(0);
}

$publicDir = $root . '/public/';

$done = 0;
$missingSource = [];
$failed = [];
$savedBytes = 0;

foreach ($pending as $image) {
    $relativeFull = $image['full_path'];
    $sourcePath = $publicDir . $relativeFull;

    if (!is_file($sourcePath)) {
        $missingSource[] = $relativeFull;
        continue;
    }

    $relativeMedium = 'uploads/albums/' . $image['album_id'] . '/medium/' . $image['filename'];
    $targetPath = $publicDir . $relativeMedium;

    if ($checkOnly) {
        $done++;
        continue;
    }

    if (!$imageService->createResized($sourcePath, $targetPath)) {
        $failed[] = $relativeFull;
        continue;
    }

    $imageModel->updateMediumPath($image['id'], $relativeMedium);

    $savedBytes += max(0, filesize($sourcePath) - filesize($targetPath));
    $done++;

    echo '  kész: ' . $relativeMedium . "\n";
}

echo "\n";

if ($checkOnly) {
    echo 'Feldolgozható lenne: ' . $done . " kép.\n";
} else {
    echo 'Elkészült: ' . $done . " kép.\n";

    if ($savedBytes > 0) {
        echo '  A megjelenített méret így ' . number_format($savedBytes / 1024 / 1024, 1)
            . " MB-tal kevesebb letöltés.\n";
    }
}

if ($missingSource !== []) {
    echo "\nHIÁNYZÓ FORRÁSFÁJL (" . count($missingSource) . " db) - a rekord megmaradt:\n";
    foreach (array_slice($missingSource, 0, 20) as $path) {
        echo '  - ' . $path . "\n";
    }
}

if ($failed !== []) {
    echo "\nNEM SIKERÜLT (" . count($failed) . " db):\n";
    foreach (array_slice($failed, 0, 20) as $path) {
        echo '  - ' . $path . "\n";
    }
    echo "\nEzeknél a megjelenítés a bélyegképre esik vissza, tehát az oldal\n";
    echo "működik, csak a borítókép marad lágy.\n";
}

exit($failed === [] ? 0 : 1);
