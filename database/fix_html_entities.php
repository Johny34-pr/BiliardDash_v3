<?php

declare(strict_types=1);

/**
 * Egyszeri adatjavítás: HTML entitások feloldása a hírekben
 *
 * Előzmény: a rich text szerkesztő korábban `entity_encoding: 'named'`
 * beállítással mentett, ezért az ékezetes karakterek névvel megadott HTML
 * entitásként kerültek az adatbázisba (pl. `&aacute;` az `á` helyett).
 * Ez a részletes hírnél nem látszott, az összefoglalóban viszont az e()
 * escape után szó szerint megjelent (`&amp;aacute;`).
 *
 * Ez a szkript valódi UTF-8 karakterré alakítja az entitásokat a `content`
 * mezőben, és újragenerálja az összefoglalókat.
 *
 * Használat:
 *   php database/fix_html_entities.php --dry-run   (csak kiírja, mit tenne)
 *   php database/fix_html_entities.php             (végrehajtja)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$dryRun = in_array('--dry-run', $argv ?? [], true);

$db = Database::getConnection();

/**
 * A HTML szerkezetet megőrizve oldja fel az entitásokat.
 *
 * A `&lt;`, `&gt;` és `&amp;` szándékosan kimarad: ezek feloldása
 * megváltoztatná a HTML jelentését, illetve XSS-t nyithatna.
 */
$decodePreservingMarkup = static function (string $html): string {
    // Strukturális entitások ideiglenes védelme
    $placeholders = [
        '&amp;' => "\x01AMP\x01",
        '&lt;' => "\x01LT\x01",
        '&gt;' => "\x01GT\x01",
    ];
    $protected = str_replace(array_keys($placeholders), array_values($placeholders), $html);

    // A többi entitás (ékezetek, &nbsp;, &hellip; stb.) feloldása
    $decoded = html_entity_decode($protected, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Nem törhető szóköz normál szóközre
    $decoded = str_replace("\xC2\xA0", ' ', $decoded);

    // Védett entitások visszaállítása
    return str_replace(array_values($placeholders), array_keys($placeholders), $decoded);
};

/** Az összefoglaló ugyanazzal a logikával készül, mint a NewsService-ben */
$makeSummary = static function (string $content): string {
    $stripped = strip_tags($content);
    $decoded = html_entity_decode($stripped, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $normalized = trim((string) preg_replace('/\s+/u', ' ', $decoded));

    return mb_substr($normalized, 0, 200);
};

$rows = $db->query('SELECT id, title, content FROM news')->fetchAll();

if ($rows === []) {
    echo "Nincs feldolgozandó hír.\n";
    exit(0);
}

$update = $db->prepare('UPDATE news SET title = :title, content = :content, summary = :summary WHERE id = :id');

$changed = 0;

foreach ($rows as $row) {
    $newTitle = $decodePreservingMarkup($row['title']);
    $newContent = $decodePreservingMarkup($row['content']);
    $newSummary = $makeSummary($newContent);

    $titleChanged = $newTitle !== $row['title'];
    $contentChanged = $newContent !== $row['content'];

    if (!$titleChanged && !$contentChanged) {
        // Az összefoglaló akkor is frissül, ha csak az generálódott hibásan
        $update->execute([
            ':title' => $newTitle,
            ':content' => $newContent,
            ':summary' => $newSummary,
            ':id' => $row['id'],
        ]);
        continue;
    }

    $changed++;

    echo "--- {$row['id']} ---\n";
    if ($titleChanged) {
        echo "  cím:  {$row['title']}\n";
        echo "     -> {$newTitle}\n";
    }
    if ($contentChanged) {
        echo "  tartalom: entitások feloldva (" . mb_strlen($row['content']) . " -> " . mb_strlen($newContent) . " karakter)\n";
    }
    echo "  új összefoglaló: " . mb_substr($newSummary, 0, 80) . "...\n";

    if (!$dryRun) {
        $update->execute([
            ':title' => $newTitle,
            ':content' => $newContent,
            ':summary' => $newSummary,
            ':id' => $row['id'],
        ]);
    }
}

echo "\n";
echo $dryRun
    ? "PRÓBA MÓD: {$changed} hír igényel javítást, semmi nem íródott az adatbázisba.\n"
    : "Kész: {$changed} hír javítva, az összefoglalók újragenerálva.\n";
