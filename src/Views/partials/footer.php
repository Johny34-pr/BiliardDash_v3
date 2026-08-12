<?php
/**
 * Lábléc partial - Magyar Biliárd Weboldal
 *
 * Háromhasábos elrendezés asztali nézetben, egymás alatti mobilon.
 * Sötétzöld alap, arany akcentus, visszafogott elválasztókkal.
 */
?>
<footer class="mt-20 bg-billiard-green-900 text-white/70">
    <!-- Arany hajszálvonal a lábléc tetején -->
    <div class="h-px bg-gradient-to-r from-transparent via-billiard-gold-400/30 to-transparent"></div>

    <div class="container mx-auto px-4 py-12">
        <div class="grid gap-10 md:grid-cols-3">

            <!-- Márka + rövid leírás -->
            <div>
                <div class="flex items-center gap-2.5 mb-3">
                    <span class="grid place-items-center w-8 h-8 rounded-full bg-gradient-to-br from-billiard-gold-300 to-billiard-gold-500">
                        <span class="grid place-items-center w-4 h-4 rounded-full bg-billiard-green-950">
                            <span class="text-[9px] font-bold leading-none text-billiard-gold-300">8</span>
                        </span>
                    </span>
                    <span class="font-semibold tracking-tightest text-white">Magyar Biliárd</span>
                </div>
                <p class="text-sm leading-relaxed max-w-xs text-white/55">
                    A magyar biliárd közösség hírei, eseményfotói és online versenynevezése egy helyen.
                </p>
            </div>

            <!-- Oldaltérkép -->
            <div>
                <h2 class="text-[0.6875rem] font-semibold uppercase tracking-wider text-billiard-gold-300/80 mb-3">
                    Oldalak
                </h2>
                <nav class="flex flex-col gap-1 -ml-3" aria-label="Lábléc navigáció">
                    <a href="/" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Hírek</a>
                    <a href="/galeria" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Galéria</a>
                    <a href="/nevezes" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Nevezés</a>
                    <a href="/forum" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Fórum</a>
                </nav>
            </div>

            <!-- Fiók és adminisztráció -->
            <div>
                <h2 class="text-[0.6875rem] font-semibold uppercase tracking-wider text-billiard-gold-300/80 mb-3">
                    Fiók
                </h2>
                <nav class="flex flex-col gap-1 -ml-3" aria-label="Fiók navigáció">
                    <?php if (\App\Core\Session::isUser()): ?>
                        <a href="/fiok" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Nevezéseim</a>
                        <a href="/kilepes" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Kilépés</a>
                    <?php else: ?>
                        <a href="/belepes" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Belépés</a>
                        <a href="/regisztracio" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Regisztráció</a>
                    <?php endif; ?>

                    <?php if (\App\Core\Session::isAdmin()): ?>
                        <a href="/admin" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm text-billiard-gold-300/90 hover:text-white hover:bg-white/5 transition-colors w-fit">Admin felület</a>
                    <?php else: ?>
                        <a href="/admin/login" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm text-white/40 hover:text-white hover:bg-white/5 transition-colors w-fit">Szervezői belépés</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <!-- Copyright sáv -->
        <div class="mt-10 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-white/45">
                &copy; <?= date('Y') ?> Magyar Biliárd. Minden jog fenntartva.
            </p>
            <p class="text-xs text-white/35">
                <!-- Készült PHP és MySQL alapon -->
            </p>
        </div>
    </div>
</footer>
