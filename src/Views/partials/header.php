<?php
/**
 * Fejléc partial - Okányi Biliárd Klub weboldal
 *
 * Ragadós (sticky), áttetsző hátterű fejléc, amely egyetlen sávban tartalmazza
 * a márkajelet, a főnavigációt és az admin hivatkozásokat. Ezzel megszűnik a
 * korábbi kettős márkanév-megjelenítés (fejléc + külön navigációs sáv).
 *
 * A mobil menü panel a fejléc sávja alatt, teljes szélességben nyílik ki.
 */
?>
<header class="site-header sticky top-0 z-50 text-white">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16 md:h-[4.5rem] gap-4">

            <!-- Márkajel -->
            <a href="/" class="flex items-center gap-2.5 shrink-0" aria-label="Okányi Biliárd Klub - főoldal">
                <?php $brandMarkSize = 'w-9 h-9'; require __DIR__ . '/brand-mark.php'; ?>
                <span class="flex flex-col leading-none gap-0.5">
                    <span class="font-semibold tracking-tightest text-[1.0625rem] text-white">Okányi Biliárd Klub</span>
                    <span class="hidden sm:block text-[0.6875rem] font-medium tracking-wider uppercase text-white/45">Közösségi portál</span>
                </span>
            </a>

            <!-- Főnavigáció (asztali) + egyesített fiókmenü + hamburger -->
            <div class="flex items-center gap-2">
                <?php require __DIR__ . '/navigation.php'; ?>

                <!--
                    Egyetlen fiókmenü a kétféle azonosításhoz. Korábban két
                    párhuzamos sáv volt (látogatói és admin), két különböző
                    szóval a kilépésre, ami nem tette világossá, melyik gomb
                    melyik szerepre hat.
                -->
                <?php require __DIR__ . '/account-menu.php'; ?>

                <button id="menu-toggle" type="button"
                        class="md:hidden grid place-items-center w-11 h-11 rounded-xl text-white/85 hover:text-white hover:bg-white/10 transition-colors"
                        aria-label="Menü megnyitása" aria-expanded="false" aria-controls="mobile-menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobil menü: a fejléc sávja alatt, teljes szélességben -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-white/10 bg-billiard-green-900/95">
        <nav class="container mx-auto px-4 py-3 flex flex-col gap-1" aria-label="Mobil navigáció">
            <?php foreach ($navItems as $item): ?>
                <?php $active = isActive($item['url']); ?>
                <a href="<?= e($item['url']) ?>"
                   class="nav-link nav-link-mobile <?= $active ?>"
                   <?= $active !== '' ? 'aria-current="page"' : '' ?>>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>

            <!-- ==== Belépés / látogatói fiók (mobil) ==== -->
            <span class="mt-2 pt-3 px-4 border-t border-white/10 text-[0.6875rem] font-semibold uppercase tracking-wider text-white/40">
                Belépés
            </span>
            <?php if (\App\Core\Session::isUser()): ?>
                <?php $mobileUser = \App\Core\Session::user(); ?>
                <span class="px-4 pb-1 text-sm text-white/70">
                    Belépve: <span class="font-semibold text-white"><?= e($mobileUser['name']) ?></span>
                </span>
                <a href="/fiok" class="nav-link nav-link-mobile <?= isActive('/fiok') ?>">Nevezéseim</a>
                <a href="/kilepes" class="nav-link nav-link-mobile">Kilépés a fiókból</a>
            <?php else: ?>
                <a href="/belepes" class="nav-link nav-link-mobile <?= isActive('/belepes') ?>">Belépés</a>
                <a href="/regisztracio" class="nav-link nav-link-mobile <?= isActive('/regisztracio') ?>">Új fiók létrehozása</a>
            <?php endif; ?>

            <!-- ==== Szervezői hozzáférés (mobil) ==== -->
            <span class="mt-2 pt-3 px-4 border-t border-white/10 text-[0.6875rem] font-semibold uppercase tracking-wider text-white/40">
                Szervezői hozzáférés
            </span>
            <?php if (\App\Core\Session::isAdmin()): ?>
                <span class="px-4 pb-1 text-sm text-billiard-gold-300">Szervezői módban vagy</span>
                <a href="/admin" class="nav-link nav-link-mobile">Szervezői felület</a>
                <a href="/admin/logout" class="nav-link nav-link-mobile">Kilépés a szervezői módból</a>
            <?php else: ?>
                <a href="/admin/login" class="nav-link nav-link-mobile">Szervezői belépés</a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Arany hajszálvonal: elválasztja a fejlécet a tartalomtól -->
    <div class="h-px bg-gradient-to-r from-transparent via-billiard-gold-400/40 to-transparent"></div>
</header>
