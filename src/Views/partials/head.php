<?php
/**
 * Közös <head> tartalom - Okányi Biliárd Klub weboldal
 *
 * Egy helyen definiálja a keresőoptimalizálási meta adatokat és a design
 * tokeneket (színek, tipográfia, árnyékok), hogy a fő layout, az admin
 * layout és a hibaoldalak konzisztensek legyenek.
 *
 * @var string|null $pageTitle       Oldal címe (nyersen, escape nélkül)
 * @var string|null $metaDescription Oldalleírás a keresőknek és a megosztáshoz
 * @var string|null $canonical       Kanonikus URL felülírása (pl. lapozásnál)
 * @var string|null $ogImage         Megosztási kép útvonala vagy URL-je
 * @var string|null $ogType          Open Graph típus ('website' vagy 'article')
 * @var bool|null   $noIndex         Kizárás a keresőindexből
 * @var array|null  $structuredData  Kiegészítő JSON-LD adat (schema.org)
 */

$siteName = 'Okányi Biliárd Klub';
$defaultDescription = 'Az Okányi Biliárd Klub hírei, fotógalériája és online versenynevezés. '
    . 'Nézd meg a nyitott versenyeket, és nevezz online.';

$description = trim((string) ($metaDescription ?? '')) !== ''
    ? $metaDescription
    : $defaultDescription;

$title = $pageTitle ?? $siteName;
$canonicalHref = $canonical ?? canonicalUrl();

// Megosztási kép: oldalspecifikus, vagy a márkázott alapkép. Mindkét
// esetben abszolút URL-lé alakítva, mert a közösségi platformok a relatív
// útvonalat nem tudják feloldani.
$shareImage = siteUrl($ogImage ?? '/assets/images/og-default.png');

/*
 * Indexelés tiltása. A belépés mögötti és a felhasználó-specifikus
 * oldalaknak nincs keresési értéke, viszont duplikált vagy privát
 * tartalmat vinnének az indexbe. Az útvonal alapú szabály akkor is véd,
 * ha egy kontroller elfelejti beállítani a $noIndex változót.
 */
$privatePrefixes = ['/admin', '/fiok', '/belepes', '/regisztracio', '/kilepes'];
$currentPath = currentUrl();
$blockIndexing = !empty($noIndex);

foreach ($privatePrefixes as $prefix) {
    if ($currentPath === $prefix || str_starts_with($currentPath, $prefix . '/')) {
        $blockIndexing = true;
        break;
    }
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0c2f22">

<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<link rel="canonical" href="<?= e($canonicalHref) ?>">

<?php if ($blockIndexing): ?>
    <meta name="robots" content="noindex, nofollow">
<?php else: ?>
    <meta name="robots" content="index, follow, max-image-preview:large">
<?php endif; ?>

<!--
    Open Graph: ez alapján állítja össze az előnézetet a Facebook, a
    LinkedIn, a Messenger és a legtöbb chat alkalmazás. A kép 1200x630,
    ami mindenhol nagy kártyaként jelenik meg.
-->
<meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:locale" content="hu_HU">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonicalHref) ?>">
<meta property="og:image" content="<?= e($shareImage) ?>">
<meta property="og:image:alt" content="<?= e($siteName) ?>">

<!-- Twitter/X nagy képes kártya -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e($shareImage) ?>">

<?php
/*
 * Strukturált adat (schema.org, JSON-LD). Ebből ismeri fel a kereső a
 * webhely nevét és a tartalom típusát, ami segít a találati megjelenésben.
 * Csak indexelhető oldalakon adjuk ki.
 *
 * A JSON_HEX_TAG kötelező: nélküle egy tartalomból származó "</script>"
 * részlet kitörhetne a script elemből.
 */
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP;

if (!$blockIndexing):
    $websiteJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => siteUrl('/'),
        'inLanguage' => 'hu-HU',
        'description' => $defaultDescription,
    ];
    ?>
    <script type="application/ld+json"><?= json_encode($websiteJsonLd, $jsonFlags) ?></script>
    <?php if (!empty($structuredData)): ?>
        <script type="application/ld+json"><?= json_encode($structuredData, $jsonFlags) ?></script>
    <?php endif; ?>
<?php endif; ?>

<!--
    Weboldal ikonok
    Mindegyik a klub címerére (public/logo.png) épül, a
    `php tools/generate-icons.php` állítja elő őket. Az ICO a régebbi
    böngészők és a Google Search kedvéért van, a 192 pixeles PNG a modern
    böngészőknek, az apple-touch-icon az iOS kezdőképernyőjének.

    A ?v= verziójelölés a böngésző gyorsítótárának felülírására szolgál:
    az ikonokat a böngészők hosszan és makacsul tárolják.
-->
<link rel="icon" href="/favicon.ico?v=2" sizes="16x16 32x32 48x48">
<link rel="icon" href="/icon-192.png?v=2" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
<link rel="manifest" href="/site.webmanifest">

<?php
/*
 * Betűtípus: a Google Fonts stíluslapja külső kérés, amely alapesetben
 * megállítaná a megjelenítést, amíg meg nem érkezik. A media="print"
 * fogással a böngésző nem tekinti megjelenítéshez szükségesnek, betöltés
 * után pedig az onload átállítja élesre.
 *
 * Következmény: a szöveg először a rendszer betűtípusával jelenik meg,
 * majd átvált Interre. Ezt a display=swap amúgy is így kezelné, viszont
 * így az oldal érzékelhetően hamarabb rajzolódik ki.
 */
$fontHref = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="<?= e($fontHref) ?>">
<link rel="stylesheet" href="<?= e($fontHref) ?>" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="<?= e($fontHref) ?>"></noscript>

<?php
/*
 * Stíluslapok
 * ===========
 * A segédosztályok korábban a böngészőben, futásidőben készültek el egy
 * CDN-ről betöltött szkripttel, ami minden oldalletöltést késleltetett.
 * Most előre elkészített, tömörített fájlként érkeznek. A design tokenek a
 * tools/css/tokens.php fájlban élnek, az újraépítés parancsa
 * `php tools/build-css.php` - külső eszközlánc nélkül, csak PHP-vel.
 *
 * A sorrend számít: elsőként az alapréteg és a segédosztályok, utána az
 * app.css komponensei (.btn, .card, .field), amelyek felülírhatják azokat.
 *
 * A ?v= verziót az asset() helper teszi hozzá a fájl módosítási idejéből,
 * így a hosszú gyorsítótárazás mellett is azonnal érvényesül a módosítás.
 *
 * A magyarázat szándékosan PHP megjegyzés és nem HTML: a felépítés részletei
 * nem tartoznak a látogatóra, és minden oldalletöltéssel elmennének.
 */
?>
<link rel="stylesheet" href="<?= e(asset('css/tailwind.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
