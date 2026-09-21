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
];

// A fórum kapcsolható modul: csak akkor kerül a menübe, ha a szervező
// bekapcsolta. Kikapcsolt állapotban az útvonalai sem léteznek, ezért egy
// itt hagyott menüpont törött hivatkozás lenne.
if (forumEnabled()) {
    $navItems[] = ['url' => '/forum', 'label' => 'Fórum'];
}
?>
<ul id="nav-menu" class="hidden md:flex items-center gap-1" aria-label="Főnavigáció">
    <?php foreach ($navItems as $item): ?>
        <?php $active = isActive($item['url']); ?>
        <li>
            <a href="<?= e($item['url']) ?>"
               class="nav-link <?= $active ?>"
               <?= $active !== '' ? 'aria-current="page"' : '' ?>>
                <?= e($item['label']) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
