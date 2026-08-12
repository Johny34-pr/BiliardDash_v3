<?php
/**
 * Fejléc partial - Magyar Biliárd Weboldal
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
            <a href="/" class="flex items-center gap-2.5 shrink-0" aria-label="Magyar Biliárd - főoldal">
                <span class="grid place-items-center w-9 h-9 rounded-full bg-gradient-to-br from-billiard-gold-300 to-billiard-gold-500 shadow-inset-line">
                    <!-- Biliárdgolyó jelkép: arany körben sötét szám-mező -->
                    <span class="grid place-items-center w-[18px] h-[18px] rounded-full bg-billiard-green-950">
                        <span class="text-[10px] font-bold leading-none text-billiard-gold-300">8</span>
                    </span>
                </span>
                <span class="flex flex-col leading-none gap-0.5">
                    <span class="font-semibold tracking-tightest text-[1.0625rem] text-white">Magyar Biliárd</span>
                    <span class="hidden sm:block text-[0.6875rem] font-medium tracking-wider uppercase text-white/45">Közösségi portál</span>
                </span>
            </a>

            <!-- Főnavigáció (asztali) + fiók/admin sáv + hamburger -->
            <div class="flex items-center gap-2">
                <?php require __DIR__ . '/navigation.php'; ?>

                <!-- Felhasználói fiók (publikus) -->
                <div class="hidden md:flex items-center gap-1 ml-2 pl-3 border-l border-white/15">
                    <?php if (\App\Core\Session::isUser()): ?>
                        <?php $currentUser = \App\Core\Session::user(); ?>
                        <a href="/fiok" class="nav-link <?= isActive('/fiok') ?>" title="Nevezéseim">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            <?= e($currentUser['name']) ?>
                        </a>
                        <a href="/kilepes" class="nav-link">Kilépés</a>
                    <?php else: ?>
                        <a href="/belepes" class="nav-link <?= isActive('/belepes') ?>">Belépés</a>
                        <a href="/regisztracio" class="btn btn-sm btn-gold ml-1">Regisztráció</a>
                    <?php endif; ?>
                </div>

                <?php if (\App\Core\Session::isAdmin()): ?>
                    <div class="hidden md:flex items-center gap-1 pl-3 border-l border-white/15">
                        <a href="/admin" class="nav-link text-billiard-gold-300">Admin</a>
                        <a href="/admin/logout" class="nav-link">Kijelentkezés</a>
                    </div>
                <?php endif; ?>

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

            <!-- Fiók (mobil) -->
            <span class="mt-2 pt-3 px-4 border-t border-white/10 text-[0.6875rem] font-semibold uppercase tracking-wider text-white/40">
                Fiók
            </span>
            <?php if (\App\Core\Session::isUser()): ?>
                <a href="/fiok" class="nav-link nav-link-mobile <?= isActive('/fiok') ?>">Nevezéseim</a>
                <a href="/kilepes" class="nav-link nav-link-mobile">Kilépés</a>
            <?php else: ?>
                <a href="/belepes" class="nav-link nav-link-mobile <?= isActive('/belepes') ?>">Belépés</a>
                <a href="/regisztracio" class="nav-link nav-link-mobile <?= isActive('/regisztracio') ?>">Regisztráció</a>
            <?php endif; ?>

            <?php if (\App\Core\Session::isAdmin()): ?>
                <span class="mt-2 pt-3 px-4 border-t border-white/10 text-[0.6875rem] font-semibold uppercase tracking-wider text-white/40">
                    Adminisztráció
                </span>
                <a href="/admin" class="nav-link nav-link-mobile">Admin felület</a>
                <a href="/admin/logout" class="nav-link nav-link-mobile">Kijelentkezés</a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Arany hajszálvonal: elválasztja a fejlécet a tartalomtól -->
    <div class="h-px bg-gradient-to-r from-transparent via-billiard-gold-400/40 to-transparent"></div>
</header>
