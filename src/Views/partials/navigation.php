<?php
/**
 * Navigációs menüpontok (asztali) - Magyar Biliárd Weboldal
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
    ['url' => '/',        'label' => 'Hírek'],
    ['url' => '/galeria', 'label' => 'Galéria'],
    ['url' => '/nevezes', 'label' => 'Nevezés'],
];
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
