<?php
/**
 * Közös <head> tartalom - Magyar Biliárd Weboldal
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

$siteName = 'Magyar Biliárd';
$defaultDescription = 'A magyar biliárd közösség hírei, fotógalériája és online versenynevezés. '
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
    Az SVG a modern böngészők elsődleges választása (élesen skálázódik és
    követi a színsémát), az ICO a régebbiek és a Google Search kedvéért van.
    A ?v= verziójelölés a böngésző gyorsítótárának felülírására szolgál,
    ha az ikon később változik.
-->
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="icon" href="/favicon.svg?v=1" type="image/svg+xml" sizes="any">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            /*
             * A tartalomsáv felső korlátja. Nélküle a container a
             * 2xl töréspontnál 1536px-ig szétnyílik, amitől a fejléc két
             * széle (márkajel és fiók gombok) túl messze kerül egymástól.
             */
            container: {
                center: true,
                padding: '1rem',
                screens: {
                    sm: '640px',
                    md: '768px',
                    lg: '1024px',
                    xl: '1180px',
                    '2xl': '1180px',
                },
            },
            extend: {
                colors: {
                    /* Biliárdposztó zöld - hűvös, mély, telt tónusskála */
                    'billiard-green': {
                        50:  '#f0faf5',
                        100: '#daf3e5',
                        200: '#b7e6cd',
                        300: '#86d1ac',
                        400: '#4eb586',
                        500: '#299868',
                        600: '#1a7a53',
                        700: '#166145',
                        800: '#154d38',
                        900: '#0c2f22',
                        950: '#061a13',
                    },
                    /* Meleg sárgaréz arany - akcentus szín */
                    'billiard-gold': {
                        50:  '#fdfaef',
                        100: '#faf1d3',
                        200: '#f4e0a4',
                        300: '#eccb70',
                        400: '#e5b544',
                        500: '#d99a26',
                        600: '#bf761d',
                        700: '#9e561b',
                        800: '#81441c',
                        900: '#6b391a',
                    },
                    /* Meleg semleges alapszínek - kevésbé steril, mint a szürke */
                    'sand': {
                        50:  '#fbfaf8',
                        100: '#f5f3ef',
                        200: '#e9e5dd',
                        300: '#d7d1c5',
                        400: '#b3aa9a',
                        500: '#8c8272',
                        600: '#6b6254',
                        700: '#524b40',
                        800: '#38332c',
                        900: '#1f1c18',
                    },
                },
                fontFamily: {
                    sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Arial', 'sans-serif'],
                },
                letterSpacing: {
                    tightest: '-0.035em',
                },
                boxShadow: {
                    'soft':  '0 1px 2px rgba(6,26,19,.04), 0 4px 16px -6px rgba(6,26,19,.08)',
                    'lift':  '0 2px 4px rgba(6,26,19,.04), 0 16px 32px -12px rgba(6,26,19,.16)',
                    'inset-line': 'inset 0 1px 0 rgba(255,255,255,.06)',
                },
                borderRadius: {
                    '4xl': '1.75rem',
                },
                maxWidth: {
                    'reading': '44rem',
                },
                transitionTimingFunction: {
                    'out-soft': 'cubic-bezier(0.22, 1, 0.36, 1)',
                },
            }
        }
    }
</script>

<link rel="stylesheet" href="/assets/css/app.css">
