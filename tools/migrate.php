<?php

declare(strict_types=1);

/**
 * Okányi Biliárd Klub - migrációs futtató
 * =============================================================================
 *
 * A database/migrations könyvtár .sql fájljait alkalmazza fájlnév szerinti
 * sorrendben, és nyilvántartja, melyik futott már le. Így egy migráció nem
 * futhat le kétszer, és új környezetben egyetlen paranccsal felépül a séma.
 *
 * Használat a projekt gyökeréből:
 *
 *     php tools/migrate.php --status     Mi futott le és mi van hátra
 *     php tools/migrate.php              A hátralévő migrációk alkalmazása
 *     php tools/migrate.php --baseline [fájlnév]
 *                                        Lefutottnak jelölés futtatás nélkül.
 *                                        Fájlnév megadásával csak az addig
 *                                        tartó fájlokat (azt is beleértve).
 *
 * A --baseline egy már működő adatbázishoz kell, amelyre a migrációkat
 * korábban kézzel alkalmazták: enélkül a futtató újra megkísérelné őket,
 * és a "table already exists" hibába futna. A fájlnév azért adható meg,
 * mert jellemzően a régi migrációk vannak kézzel alkalmazva, az újak pedig
 * még nem - ezeket futtatni kell.
 *
 * Megjegyzés: a fájlokat egészben adjuk át a MySQL-nek, mert az utasításokat
 * nem lehet biztonságosan pontosvessző szerint szétvágni - a beszúrt HTML
 * tartalom is tartalmaz pontosvesszőt (pl. az &aacute; entitás végén).
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;

Env::load();

$config = require __DIR__ . '/../config/database.php';
$migrationsDir = __DIR__ . '/../database/migrations';

// Külön kapcsolat: a többutasításos futtatáshoz nem kell a Database
// osztály beállításkészlete, viszont kell a hibák kivételként dobása.
try {
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "Nem sikerült kapcsolódni az adatbázishoz: {$e->getMessage()}\n");
    fwrite(STDERR, "Fut a MySQL kiszolgáló?\n");
    exit(1);
}

// A nyilvántartó tábla önmagát hozza létre, hogy a futtató első
// használatakor ne kelljen kézi előkészítés.
$db->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        filename VARCHAR(255) PRIMARY KEY,
        applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

/** @var array<string> $files A migrációs fájlok névsorrendben */
$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files, SORT_STRING);
$files = array_map('basename', $files);

if ($files === []) {
    echo "Nincs migrációs fájl a database/migrations könyvtárban.\n";
    exit(0);
}

$applied = $db->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$pending = array_values(array_diff($files, $applied));

$mode = $argv[1] ?? '';

// ---------------------------------------------------------------------------
// --status: csak kiírás, semmit nem módosít
// ---------------------------------------------------------------------------
if ($mode === '--status') {
    echo "Migrációk állapota:\n";

    foreach ($files as $file) {
        $mark = in_array($file, $applied, true) ? '[lefutott]' : '[hátralévő]';
        printf("  %-12s %s\n", $mark, $file);
    }

    echo "\nÖsszesen: " . count($files) . ', hátralévő: ' . count($pending) . "\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// --baseline: meglévő adatbázis felvétele a nyilvántartásba
// ---------------------------------------------------------------------------
if ($mode === '--baseline') {
    $upTo = $argv[2] ?? null;

    if ($upTo !== null && !in_array($upTo, $files, true)) {
        fwrite(STDERR, "Nincs ilyen migrációs fájl: {$upTo}\n");
        exit(1);
    }

    // Fájlnév megadásakor csak az addig tartó, még nem nyilvántartott
    // fájlokat vesszük fel
    $toMark = $pending;

    if ($upTo !== null) {
        $limit = array_search($upTo, $files, true);
        $allowed = array_slice($files, 0, (int) $limit + 1);
        $toMark = array_values(array_intersect($pending, $allowed));
    }

    if ($toMark === []) {
        echo "Nincs felvehető migráció, nincs mit tenni.\n";
        exit(0);
    }

    $insert = $db->prepare('INSERT INTO schema_migrations (filename) VALUES (:filename)');

    foreach ($toMark as $file) {
        $insert->execute([':filename' => $file]);
        echo "  lefutottnak jelölve: {$file}\n";
    }

    echo "\n" . count($toMark) . " migráció felvéve a nyilvántartásba (futtatás nélkül).\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// Alapértelmezés: a hátralévő migrációk alkalmazása
// ---------------------------------------------------------------------------
if ($pending === []) {
    echo "Az adatbázis naprakész, nincs hátralévő migráció.\n";
    exit(0);
}

echo count($pending) . " hátralévő migráció alkalmazása...\n";

$insert = $db->prepare('INSERT INTO schema_migrations (filename) VALUES (:filename)');

foreach ($pending as $file) {
    $sql = file_get_contents($migrationsDir . '/' . $file);

    if ($sql === false || trim($sql) === '') {
        echo "  kihagyva (üres): {$file}\n";
        $insert->execute([':filename' => $file]);
        continue;
    }

    try {
        // A DDL utasításokat a MySQL nem tudja visszavonni, ezért nincs
        // tranzakció: hiba esetén a fájl félig alkalmazva maradhat, és a
        // nyilvántartásba sem kerül be. A hibát ilyenkor kézzel kell
        // rendezni, ezért a kiírás megnevezi az érintett fájlt.
        $statement = $db->query($sql);

        // A többutasításos futtatás eredményhalmazait végig kell léptetni,
        // különben a következő kérés "Cannot execute queries while other
        // unbuffered queries are active" hibát adna.
        if ($statement !== false) {
            do {
                $statement->fetchAll();
            } while ($statement->nextRowset());
        }

        $insert->execute([':filename' => $file]);
        echo "  alkalmazva: {$file}\n";
    } catch (PDOException $e) {
        fwrite(STDERR, "\nHIBA a következő migrációban: {$file}\n");
        fwrite(STDERR, $e->getMessage() . "\n");
        fwrite(STDERR, "\nA fájl NEM került a nyilvántartásba. Rendezd a hibát, majd futtasd újra.\n");
        exit(1);
    }
}

echo "\nKész. Alkalmazott migrációk: " . count($pending) . "\n";
