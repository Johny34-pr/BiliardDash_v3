<?php
/**
 * Fejléc partial - Okányi Biliárd Klub weboldal
 *
 * Ragadós (sticky), áttetsző hátterű fejléc. Asztali nézetben KÉT sávból áll:
 * fölül a nagy márkajel a sáv közepén, alatta a középre igazított
 * főnavigáció. Mobilon egyetlen sáv marad (márkajel balra, hamburger jobbra).
 *
 * Miért két sáv asztali nézetben
 * ------------------------------
 * A márkajel csak úgy kerülhet a menü vízszintes közepére, ha nem a
 * menüpontokkal egy sorban áll: egy sorban a menü szélessége mindig
 * elcsúsztatná. A menüpontok száma ráadásul változó (a ranglista, a fórum és
 * a Közvetítés kapcsolható), ezért a menüt kettéosztani a logó két oldalára
 * törékeny lenne - a felezőpont minden be- és kikapcsolásnál elmozdulna.
 *
 * Miért marad mobilon egy sáv
 * ---------------------------
 * Ott a sávban nincs meg a hely: egy középre igazított márkanév 320
 * képpontos szélességnél összeérne a jobb szélre kiemelt hamburgerrel. A
 * függőleges hely is szűkebb, egy második sáv a tartalomból vinne el.
 *
 * A mobil menü panel a fejléc sávja alatt, teljes szélességben nyílik ki.
 */
?>
<header class="site-header sticky top-0 z-50 text-white">
    <div class="container mx-auto px-4">

        <!-- Felső sáv: márkajel (asztali nézetben középen) -->
        <div class="relative flex items-center justify-between gap-4 h-16 md:h-auto md:justify-center md:py-3">

            <!-- Márkajel -->
            <a href="/" class="flex items-center gap-3 md:gap-4 shrink-0" aria-label="Okányi Biliárd Klub - főoldal">
                <?php
                $brandMarkSize = 'w-12 h-12 md:w-16 md:h-16';
                require __DIR__ . '/brand-mark.php';
                ?>
                <span class="flex flex-col leading-none gap-1">
                    <span class="font-semibold tracking-tightest text-[1.0625rem] md:text-2xl text-white">Okányi Biliárd Klub</span>
                    <span class="hidden sm:block text-[0.6875rem] md:text-xs font-medium tracking-wider uppercase text-white/45">Közösségi portál</span>
                </span>
            </a>

            <!--
                Egyesített fiókmenü és a hamburger. Asztali nézetben a sáv jobb
                szélére kiemelve (absolute), különben a szélességük
                elcsúsztatná a márkajelet a valódi középről.

                Egyetlen fiókmenü a kétféle azonosításhoz. Korábban két
                párhuzamos sáv volt (látogatói és admin), két különböző
                szóval a kilépésre, ami nem tette világossá, melyik gomb
                melyik szerepre hat.
            -->
            <div class="flex items-center gap-2 md:absolute md:inset-y-0 md:right-0">
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

        <!--
            Alsó sáv: főnavigáció, középre igazítva. A $navItems tömböt is ez
            állítja be, amit alább a mobil menü panel újrahasznosít - ezért
            kell a panel ELŐTT beillesztve lennie.
        -->
        <?php require __DIR__ . '/navigation.php'; ?>
    </div>

    <!-- Mobil menü: a fejléc sávja alatt, teljes szélességben -->
    <div id="mobile-menu" class="hidden md:hidden border-t border-white/10 bg-billiard-green-900/95">
        <nav class="container mx-auto px-4 py-3 flex flex-col gap-1" aria-label="Mobil navigáció">
            <?php foreach ($navItems as $item): ?>
                <?php
                $external = !empty($item['external']);
                $active = $external ? '' : isActive($item['url']);
                ?>
                <a href="<?= e($item['url']) ?>"
                   class="nav-link nav-link-mobile <?= $active ?>"
                   <?= $active !== '' ? 'aria-current="page"' : '' ?>
                   <?= $external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>>
                    <?= e($item['label']) ?>
                    <?php if ($external): ?>
                        <svg class="inline-block w-3 h-3 ml-1 -mt-0.5 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                        <span class="sr-only">(új lapon nyílik)</span>
                    <?php endif; ?>
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
