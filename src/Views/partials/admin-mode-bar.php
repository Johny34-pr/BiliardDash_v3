<?php
/**
 * Szervezői mód jelzősáv - Magyar Biliárd Weboldal
 *
 * A publikus oldalak tetején jelenik meg, ha szervezői hozzáférés aktív.
 * Így nem fordulhat elő, hogy valaki észrevétlenül marad bejelentkezve a
 * tartalomkezeléshez, és az sem, hogy a látogatói és a szervezői szerepet
 * összekeverje: a sáv egyértelműen megnevezi, melyikről van szó.
 *
 * Csak akkor töltődik be, ha Session::isAdmin() igaz.
 */
?>
<div class="admin-mode-bar" role="status">
    <div class="container mx-auto px-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2">
        <p class="flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <span>
                <span class="font-semibold">Szervezői módban böngészel.</span>
                <span class="hidden sm:inline text-billiard-green-950/70">
                    Ez a látogatói fiókodtól független hozzáférés.
                </span>
            </span>
        </p>

        <span class="flex items-center gap-3 text-sm font-semibold">
            <a href="/admin" class="underline underline-offset-2 hover:no-underline">Szervezői felület</a>
            <span class="text-billiard-green-950/30" aria-hidden="true">|</span>
            <a href="/admin/logout" class="underline underline-offset-2 hover:no-underline">Kilépés a szervezői módból</a>
        </span>
    </div>
</div>
