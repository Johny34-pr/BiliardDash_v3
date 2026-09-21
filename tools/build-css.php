<?php

declare(strict_types=1);

/**
 * Okányi Biliárd Klub - stíluslap generátor (tiszta PHP)
 * =============================================================================
 *
 * Végigolvassa a nézeteket, összegyűjti a bennük használt segédosztályokat,
 * és előállítja belőlük a public/assets/css/tailwind.css fájlt.
 *
 * Használat a projekt gyökeréből:
 *
 *     php tools/build-css.php            a stíluslap előállítása
 *     php tools/build-css.php --check    csak ellenőrzés, írás nélkül
 *
 * Miért így
 * ---------
 * A stílusok korábban a böngészőben, futásidőben készültek el egy CDN-ről
 * betöltött JavaScript segítségével, ami minden oldalletöltést késleltetett.
 * Ez az eszköz ugyanazt az eredményt előre elkészíti, külső eszközlánc
 * (Node, npm) nélkül: csak PHP kell hozzá, ami a kiszolgálón amúgy is fut.
 *
 * Felépítés
 * ---------
 *   tools/css/tokens.php          színek, méretlépcsők, árnyékok
 *   tools/css/UtilityResolver.php osztálynév → CSS deklarációk
 *   resources/css/base.css        alapréteg (normalizálás, CSS változók)
 *   public/assets/css/app.css     kézzel írt komponensosztályok
 *
 * A kimenet: base.css + a generált segédosztályok. Az app.css külön fájl
 * marad, hogy generálás nélkül is szerkeszthető legyen.
 *
 * Ha egy osztályt nem ismer fel, kiírja a nevét és a fájlt, ahol találta.
 * Így egy elgépelt osztálynév nem marad csendben stílus nélkül.
 */

require __DIR__ . '/css/UtilityResolver.php';

$root = dirname(__DIR__);
$checkOnly = in_array('--check', $argv, true);

$tokens = require __DIR__ . '/css/tokens.php';
$resolver = new UtilityResolver($tokens);

// -----------------------------------------------------------------------------
// 1. Osztálynevek összegyűjtése
// -----------------------------------------------------------------------------

/**
 * A vizsgált forrásfájlok: a nézetek és a böngészőben futó szkriptek.
 *
 * @return array<string, string> útvonal => tartalom
 */
function collectSources(string $root): array
{
    $sources = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root . '/src/Views', FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $sources[$file->getPathname()] = (string) file_get_contents($file->getPathname());
        }
    }

    foreach (glob($root . '/public/assets/js/*.js') ?: [] as $js) {
        $sources[$js] = (string) file_get_contents($js);
    }

    return $sources;
}

/**
 * Használható osztálynévnek látszik-e a token.
 *
 * A class attribútumokban PHP kifejezések is állnak, és azokból óhatatlanul
 * bekerülnek olyan szövegrészek, amelyek nem osztálynevek. Ezeket itt
 * szűrjük ki:
 *
 *   - útvonal argumentumok, pl. isActive('/belepes') → "/belepes"
 *   - befejezetlen töredék, ha PHP kifejezés fűződik az osztálynévhez,
 *     pl. class="reveal-<?= $i ?>" → "reveal-"
 *   - változót tartalmazó maradék
 */
function looksLikeClass(string $token): bool
{
    if ($token === '' || str_starts_with($token, '/') || str_ends_with($token, '-')) {
        return false;
    }

    return !str_contains($token, '$') && !str_contains($token, '(');
}

/**
 * Osztálynevek kinyerése egy forrásfájlból, két bizonyossági szinten.
 *
 * "certain" - class="..." attribútumból származik, tehát biztosan
 *             osztálynévnek szánták. Ha ilyet nem tudunk feloldani, azt
 *             hibaként jelentjük, mert vagy elírás, vagy hiányzó feloldó.
 *
 * "possible" - a fájl bármely idézőjeles szövegéből származik. Erre azért
 *              van szükség, mert osztálynevek nem csak attribútumban
 *              keletkeznek: a nézetek PHP kódja is összeállít osztálylistát
 *              (pl. az érem színeit egy match kifejezés adja), és a
 *              JavaScript is ad osztályt futásidőben. Ezek közül a fel nem
 *              oldható szövegek csendben kimaradnak - egy magyar szó vagy
 *              egy mezőnév nem hiba.
 *
 * A tömbindexeket eltávolítjuk: a $errors['city'] kifejezésben a "city"
 * mezőnév, nem osztálynév.
 *
 * @return array{certain:array<string>, possible:array<string>}
 */
function extractClasses(string $source): array
{
    $certain = [];
    $possible = [];

    /** Egy szóközzel tagolt osztálylista feldarabolása */
    $split = static function (string $value) use (&$possible): array {
        $tokens = [];

        foreach (preg_split('/\s+/', trim($value)) ?: [] as $token) {
            if (looksLikeClass($token)) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    };

    // 1. class="..." attribútumok - a szándék biztos
    preg_match_all('/class="([^"]*)"/s', $source, $attrs);

    foreach ($attrs[1] as $attr) {
        $attr = (string) preg_replace_callback(
            '/<\?(?:php|=)(.*?)\?>/s',
            static function (array $m): string {
                $expression = (string) preg_replace('/\[\s*(\'[^\']*\'|"[^"]*")\s*\]/', '[]', $m[1]);

                preg_match_all("/'([^']*)'|\"([^\"]*)\"/", $expression, $strings);

                return ' ' . implode(' ', array_merge($strings[1], $strings[2])) . ' ';
            },
            $attr
        );

        foreach ($split($attr) as $token) {
            $certain[] = $token;
        }
    }

    // 2. A fájl minden idézőjeles szövege - itt csak lehetőségről van szó
    $withoutSubscripts = (string) preg_replace('/\[\s*(\'[^\']*\'|"[^"]*")\s*\]/', '[]', $source);

    preg_match_all("/'([^'\n]*)'|\"([^\"\n]*)\"/", $withoutSubscripts, $strings);

    foreach (array_merge($strings[1], $strings[2]) as $value) {
        foreach ($split($value) as $token) {
            $possible[] = $token;
        }
    }

    return ['certain' => $certain, 'possible' => $possible];
}

/**
 * Egy szelektor alanya egyetlen osztály-e.
 *
 * Az alany a szelektor utolsó egysége: a ".card > .card-title" szabály a
 * címet stílusozza, a kártyát csak feltételként említi. Az egység akkor
 * "saját" osztálydefiníció, ha egyetlen osztályból áll, legfeljebb
 * pszeudo-jelölőkkel. Így a ".field.border-red-500" nem számít: az csak
 * akkor hat, ha a .field is ott van, tehát a .border-red-500 segédosztályt
 * továbbra is a generátornak kell előállítania.
 *
 * @return string|null az osztály neve, vagy null ha nem egyszerű definíció
 */
function selectorSubjectClass(string $selector): ?string
{
    // A leszármazott/gyermek/testvér kapcsolók mentén az utolsó egység kell
    $units = preg_split('/\s*[>+~]\s*|\s+/', trim($selector)) ?: [];
    $subject = (string) end($units);

    if (preg_match('/^\.([a-zA-Z][a-zA-Z0-9_-]*)(::?[a-zA-Z-]+)*$/', $subject, $m) !== 1) {
        return null;
    }

    return $m[1];
}

/**
 * Az app.css-ben szereplő osztályok két szinten.
 *
 * "owned" - az app.css maga adja a szabályt, ezért a generátornak nem kell
 *           előállítania (pl. .btn, .field-error).
 *
 * "referenced" - az app.css csak hivatkozik rá összetett szelektorban.
 *           Ilyenkor a segédosztály szabálya továbbra is kell, de a nevet
 *           nem jelentjük ismeretlennek, hiszen van hozzá stílus.
 *
 * A szétválasztás két korábbi hibát javít: a "#mobile-menu:not(.hidden)"
 * miatt kimaradt a .hidden szabály (nem záródott a mobil menü), a
 * ".field.border-red-500" miatt pedig a piros mezőszegély.
 *
 * @return array{owned:array<string,true>, referenced:array<string,true>}
 */
function componentClasses(string $root): array
{
    $css = (string) file_get_contents($root . '/public/assets/css/app.css');

    // Megjegyzések eltávolítása, hogy a bennük szereplő nevek ne zavarjanak
    $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);

    $owned = [];
    $referenced = [];

    // Minden szabályfej: a "{" előtti rész
    preg_match_all('/([^{}]+)\{/', $css, $heads);

    foreach ($heads[1] as $head) {
        // Az @media és hasonló feltételek nem szelektorok
        if (str_contains($head, '@')) {
            continue;
        }

        foreach (explode(',', $head) as $selector) {
            // A :not(...) és társai belsejét kivágjuk: ott hivatkozás van,
            // nem definíció
            $selector = (string) preg_replace('/:(not|is|where|has)\([^)]*\)/', '', $selector);

            preg_match_all('/\.([a-zA-Z][a-zA-Z0-9_-]*)/', $selector, $m);

            foreach ($m[1] as $class) {
                $referenced[$class] = true;
            }

            $subject = selectorSubjectClass($selector);

            if ($subject !== null) {
                $owned[$subject] = true;
            }
        }
    }

    return ['owned' => $owned, 'referenced' => $referenced];
}

$sources = collectSources($root);
$components = componentClasses($root);
$ownedByAppCss = $components['owned'];
$styledByAppCss = $components['referenced'];

/** @var array<string, array<string, true>> osztály => a fájlok, ahol szerepel */
$usage = [];

/** @var array<string, true> Amelyik osztály class attribútumból jött */
$certainClasses = [];

foreach ($sources as $path => $source) {
    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    $extracted = extractClasses($source);

    foreach ($extracted['certain'] as $class) {
        $usage[$class][$relative] = true;
        $certainClasses[$class] = true;
    }

    foreach ($extracted['possible'] as $class) {
        $usage[$class][$relative] = true;
    }
}

ksort($usage);

// -----------------------------------------------------------------------------
// 2. Variantok feldolgozása és a szabályok összeállítása
// -----------------------------------------------------------------------------

/**
 * A támogatott állapot-variantok: osztálynév előtag => szelektor utótag.
 */
$stateVariants = [
    'hover' => ':hover',
    'focus' => ':focus',
    'active' => ':active',
    'visited' => ':visited',
    'disabled' => ':disabled',
    'focus-within' => ':focus-within',
    'focus-visible' => ':focus-visible',
    'first' => ':first-child',
    'last' => ':last-child',
    'odd' => ':nth-child(odd)',
    'even' => ':nth-child(even)',
    'file' => '::file-selector-button',
    'placeholder' => '::placeholder',
    'before' => '::before',
    'after' => '::after',
];

/** A csoport (szülő) állapotára reagáló variantok */
$groupVariants = [
    'group-hover' => '.group:hover ',
    'group-focus' => '.group:focus ',
    'group-focus-within' => '.group:focus-within ',
];

/**
 * CSS szelektorba illeszkedő osztálynév: a különleges karaktereket
 * visszaperjel védi (pl. .md\:flex, .mt-\[1\.85rem\], .bg-white\/10).
 */
function escapeClass(string $class): string
{
    return (string) preg_replace('/([^a-zA-Z0-9_-])/', '\\\\$1', $class);
}

/** @var array<string, array<string, string>> média => szelektor => törzs */
$rules = ['' => []];
foreach ($tokens['screens'] as $min) {
    $rules['(min-width:' . $min . ')'] = [];
}

$unknown = [];
$generated = 0;

/**
 * A "js-" előtagú osztályok szándékosan stílus nélküliek: a JavaScript
 * használja őket kapaszkodóként (pl. .js-emoji gombok). Nem hiba, ha nincs
 * hozzájuk CSS szabály.
 */
$isScriptHook = static fn(string $class): bool => str_starts_with($class, 'js-');

foreach ($usage as $class => $files) {
    // A számnak látszó kulcsokat a PHP egésszé alakítja (pl. a "-1" token),
    // ezért szövegként dolgozunk vele
    $class = (string) $class;

    // Az app.css saját komponensosztályait és a JavaScript kapaszkodókat
    // kihagyjuk
    if (isset($ownedByAppCss[$class]) || $isScriptHook($class)) {
        continue;
    }

    $bare = $class;
    $media = '';
    $selectorPrefix = '';
    $pseudoClasses = '';
    $pseudoElement = '';
    $recognisedVariants = true;

    // Variant előtagok leválasztása balról jobbra
    while (preg_match('/^([a-z0-9-]+):(.+)$/', $bare, $m) === 1) {
        $variant = $m[1];

        if (isset($tokens['screens'][$variant])) {
            $media = '(min-width:' . $tokens['screens'][$variant] . ')';
        } elseif (isset($stateVariants[$variant])) {
            // A pszeudo-elem (::before, ::file-selector-button) a szelektor
            // végére kerül, különben érvénytelen lenne a sorrend
            if (str_starts_with($stateVariants[$variant], '::')) {
                $pseudoElement = $stateVariants[$variant];
            } else {
                $pseudoClasses .= $stateVariants[$variant];
            }
        } elseif (isset($groupVariants[$variant])) {
            $selectorPrefix = $groupVariants[$variant] . $selectorPrefix;
        } else {
            $recognisedVariants = false;
            break;
        }

        $bare = $m[2];
    }

    // Csak a class attribútumból származó, fel nem oldott osztály hiba.
    // A többi szöveg (mezőnév, magyar szó, szerkesztő beállítás) nem az.
    if (!$recognisedVariants) {
        if (isset($certainClasses[$class]) && !isset($styledByAppCss[$class])) {
            $unknown[$class] = array_keys($files);
        }
        continue;
    }

    $resolved = $resolver->resolve($bare);

    if ($resolved === null) {
        if (isset($certainClasses[$class]) && !isset($styledByAppCss[$class])) {
            $unknown[$class] = array_keys($files);
        }
        continue;
    }

    if ($resolved['decls'] === []) {
        // Jelölő osztály (pl. "group"): nincs saját stílusa
        continue;
    }

    // A pszeudo-elem megelőzi az állapotot: a "hover:file:" jelölés a
    // fájlválasztó gombra hat, amikor azt húzza rá a mutató, ezért
    // ::file-selector-button:hover a helyes sorrend.
    $selector = $selectorPrefix . '.' . escapeClass($class)
        . $resolved['suffix'] . $pseudoElement . $pseudoClasses;

    $body = '';
    foreach ($resolved['decls'] as $property => $value) {
        $body .= $property . ':' . $value . ';';
    }

    $rules[$media][$selector] = rtrim($body, ';');
    $generated++;

    // A container töréspontonként kap felső korlátot
    if ($bare === 'container' && $media === '') {
        foreach ($tokens['screens'] as $min) {
            $rules['(min-width:' . $min . ')']['.' . escapeClass($class)] = 'max-width:' . $min;
        }
    }
}

// -----------------------------------------------------------------------------
// 3. Kimenet összeállítása
// -----------------------------------------------------------------------------

$basePath = $root . '/resources/css/base.css';

if (!is_file($basePath)) {
    fwrite(STDERR, "Nem található az alapréteg: resources/css/base.css\n");
    exit(1);
}

$output = (string) file_get_contents($basePath);

// A média nélküli szabályok elöl, utána a töréspontok növő sorrendben:
// így a nagyobb kijelzőre szánt szabály írja felül a kisebbet.
foreach ($rules as $media => $mediaRules) {
    if ($mediaRules === []) {
        continue;
    }

    ksort($mediaRules);

    $block = '';
    foreach ($mediaRules as $selector => $body) {
        $block .= $selector . '{' . $body . '}';
    }

    $output .= $media === '' ? $block : '@media ' . $media . '{' . $block . '}';
}

// -----------------------------------------------------------------------------
// 4. Jelentés
// -----------------------------------------------------------------------------

echo "Stíluslap generálás\n";
echo '  Átvizsgált forrásfájlok: ' . count($sources) . "\n";
echo '  Talált osztálynevek: ' . count($usage) . "\n";
echo '  Ebből komponens (app.css): ' . count(array_intersect_key($usage, $ownedByAppCss)) . "\n";
echo '  Előállított szabályok: ' . $generated . "\n";

if ($unknown !== []) {
    echo "\nISMERETLEN OSZTÁLYOK (" . count($unknown) . " db) - ezekhez nem készült stílus:\n";

    foreach ($unknown as $class => $files) {
        echo '  - ' . $class . "\n";
        foreach (array_slice($files, 0, 3) as $file) {
            echo '      ' . $file . "\n";
        }
    }

    echo "\nHa elírás, javítsd a sablonban. Ha új segédosztály kell,\n";
    echo "vedd fel a tools/css/UtilityResolver.php feloldói közé.\n";
}

if ($checkOnly) {
    echo "\n--check mód: a fájl nem került kiírásra.\n";
    exit($unknown === [] ? 0 : 1);
}

$targetPath = $root . '/public/assets/css/tailwind.css';
file_put_contents($targetPath, $output);

echo "\nKiírva: public/assets/css/tailwind.css ("
    . number_format(strlen($output) / 1024, 1) . " KB)\n";

exit($unknown === [] ? 0 : 1);
