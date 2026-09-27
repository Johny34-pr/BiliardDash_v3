<?php
/**
 * Navigációs menüpontok (asztali) - Okányi Biliárd Klub weboldal
 *
 * Ezt a partialt a header.php illeszti be, hogy a márkanév és a menü
 * egyetlen ragadós fejlécben jelenjen meg.
 *
 * Beállítja a $navItems tömböt, amelyet a header.php a mobil menü
 * rendereléséhez is felhasznál (így a menüpontok egy helyen vannak).
 *
 * - Aktív menüpont jelzés az isActive() helperrel (Requirement 8.3)
 * - 44x44px minimum érintési célterületek (Requirement 7.5)
 */

$navItems = [
    ['url' => '/',            'label' => 'Főoldal'],
    ['url' => '/galeria',     'label' => 'Galéria'],
    ['url' => '/nevezes',     'label' => 'Nevezés'],
    ['url' => '/rolunk',      'label' => 'Rólunk'],
    ['url' => '/emlekoldal',  'label' => 'Emlékoldal'],
    ['url' => '/tarshonlapok', 'label' => 'Társhonlapok'],
    ['url' => '/csapataink', 'label' => 'Csapataink'],
];

// A ranglista kapcsolható modul, alapértelmezetten bekapcsolva. A Nevezés
// után a helye, mert tartalmilag a versenyekhez tartozik.
if (rankingEnabled()) {
    array_splice($navItems, 3, 0, [['url' => '/ranglista', 'label' => 'Ranglista']]);
}

// A fórum kapcsolható modul: csak akkor kerül a menübe, ha a szervező
// bekapcsolta. Kikapcsolt állapotban az útvonalai sem léteznek, ezért egy
// itt hagyott menüpont törött hivatkozás lenne.
if (forumEnabled()) {
    $navItems[] = ['url' => '/forum', 'label' => 'Fórum'];
}

// Közvetítés: KÜLSŐ hivatkozás, ezért 'external' jelzést kap. Csak akkor
// jelenik meg, ha a szervező megadott hozzá címet a beállításokban - így
// nem lehet üres menüpont.
$broadcast = broadcastLink();

if ($broadcast !== null) {
    $navItems[] = [
        'url' => $broadcast['url'],
        'label' => $broadcast['label'],
        'external' => true,
    ];
}
?>
<!--
    A fejléc alsó sávja. Középre igazítva, hogy a fölötte lévő márkajellel egy
    tengelyen álljon. A flex-wrap azért kell, mert a menüpontok száma változó
    (ranglista, fórum, Közvetítés kapcsolható): szűkebb asztali szélességen a
    vízszintes túlcsordulás helyett tördelődik.
-->
<ul id="nav-menu" class="hidden md:flex flex-wrap items-center justify-center gap-1 pb-2.5" aria-label="Főnavigáció">
    <?php foreach ($navItems as $item): ?>
        <?php
        $external = !empty($item['external']);
        // Külső címnél az isActive() amúgy sem találhat egyezést, mert az
        // aktuális útvonal csak az oldalon belüli rész
        $active = $external ? '' : isActive($item['url']);
        ?>
        <li>
            <a href="<?= e($item['url']) ?>"
               class="nav-link <?= $active ?>"
               <?= $active !== '' ? 'aria-current="page"' : '' ?>
               <?= $external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                <?= e($item['label']) ?>
                <?php if ($external): ?>
                    <!-- Jelzés, hogy a hivatkozás új lapon, külső oldalra visz -->
                    <svg class="inline-block w-3 h-3 ml-1 -mt-0.5 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                    </svg>
                    <span class="sr-only">(új lapon nyílik)</span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
