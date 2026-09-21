<?php
/**
 * Lábléc partial - Okányi Biliárd Klub weboldal
 *
 * Háromhasábos elrendezés asztali nézetben, egymás alatti mobilon.
 * Sötétzöld alap, arany akcentus, visszafogott elválasztókkal.
 *
 * A Facebook hivatkozások a config/contact.php fájlból jönnek; a még
 * kitöltetlen címűek kimaradnak, hogy ne keletkezzen üres hivatkozás.
 */

$footerSocial = socialLinks();
$footerContact = contactConfig();
$footerEmail = $footerContact['venue']['email'] ?? '';

/** Lábléc menüpontok. A fórum csak aktív modulként jelenik meg. */
$footerPages = [
    ['url' => '/',             'label' => 'Főoldal'],
    ['url' => '/galeria',      'label' => 'Galéria'],
    ['url' => '/nevezes',      'label' => 'Nevezés'],
    ['url' => '/rolunk',       'label' => 'Rólunk'],
    ['url' => '/emlekoldal',   'label' => 'Emlékoldal'],
    ['url' => '/tarshonlapok', 'label' => 'Társhonlapok'],
];

if (forumEnabled()) {
    $footerPages[] = ['url' => '/forum', 'label' => 'Fórum'];
}
?>
<footer class="mt-20 bg-billiard-green-900 text-white/70">
    <!-- Arany hajszálvonal a lábléc tetején -->
    <div class="h-px bg-gradient-to-r from-transparent via-billiard-gold-400/30 to-transparent"></div>

    <div class="container mx-auto px-4 py-12">
        <div class="grid gap-10 md:grid-cols-3">

            <!-- Márka + rövid leírás + közösségi oldalak -->
            <div>
                <div class="flex items-center gap-2.5 mb-3">
                    <?php $brandMarkSize = 'w-8 h-8'; require __DIR__ . '/brand-mark.php'; ?>
                    <span class="font-semibold tracking-tightest text-white">Okányi Biliárd Klub</span>
                </div>
                <p class="text-sm leading-relaxed max-w-xs text-white/55">
                    Az Okányi Biliárd Klub hírei, eseményfotói és online versenynevezése egy helyen.
                </p>

                <?php if ($footerSocial !== []): ?>
                    <h2 class="text-[0.6875rem] font-semibold uppercase tracking-wider text-billiard-gold-300/80 mt-6 mb-3">
                        Kövess minket
                    </h2>
                    <ul class="flex flex-wrap items-center gap-2" aria-label="Közösségi oldalak">
                        <?php foreach ($footerSocial as $link): ?>
                            <li>
                                <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer"
                                   class="grid place-items-center w-10 h-10 rounded-full bg-white/10 text-white/80 hover:bg-[#1877F2] hover:text-white transition-colors"
                                   title="<?= e($link['label']) ?>">
                                    <span class="sr-only"><?= e($link['label']) ?></span>
                                    <svg class="w-[18px] h-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 3.925 23.094 9.101 24v-8.437H6.627v-3.49h2.474V9.9c0-2.99 1.796-4.64 4.533-4.64 1.312 0 2.686.235 2.686.235v2.953H14.94c-1.36 0-1.785.848-1.785 1.717v2.058h3.328l-.532 3.49h-2.796V24C20.075 23.094 24 18.1 24 12.073z"/>
                                    </svg>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($footerEmail !== ''): ?>
                    <a href="mailto:<?= e($footerEmail) ?>"
                       class="inline-flex items-center gap-2 mt-5 text-sm text-white/60 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                        <?= e($footerEmail) ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Oldaltérkép -->
            <div>
                <h2 class="text-[0.6875rem] font-semibold uppercase tracking-wider text-billiard-gold-300/80 mb-3">
                    Oldalak
                </h2>
                <nav class="flex flex-col gap-1 -ml-3" aria-label="Lábléc navigáció">
                    <?php foreach ($footerPages as $page): ?>
                        <a href="<?= e($page['url']) ?>"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">
                            <?= e($page['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>

            <!--
                A kétféle azonosítás külön hasábban, megnevezve: korábban egy
                "Fiók" cím alá került mindkettő, ami azt sugallta, hogy
                ugyanolyan belépésről van szó.
            -->
            <div>
                <h2 class="text-[0.6875rem] font-semibold uppercase tracking-wider text-billiard-gold-300/80 mb-3">
                    Belépés
                </h2>
                <p class="text-xs leading-relaxed text-white/40 mb-2 max-w-xs">
                    Nevezésekhez. Nem kötelező, de kényelmesebb vele.
                </p>
                <nav class="flex flex-col gap-1 -ml-3" aria-label="Látogatói fiók navigáció">
                    <?php if (\App\Core\Session::isUser()): ?>
                        <a href="/fiok" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Nevezéseim</a>
                        <a href="/kilepes" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Kilépés a fiókból</a>
                    <?php else: ?>
                        <a href="/belepes" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Belépés</a>
                        <a href="/regisztracio" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Új fiók létrehozása</a>
                    <?php endif; ?>
                </nav>

                <h2 class="text-[0.6875rem] font-semibold uppercase tracking-wider text-billiard-gold-300/80 mt-6 mb-3">
                    Szervezői hozzáférés
                </h2>
                <p class="text-xs leading-relaxed text-white/40 mb-2 max-w-xs">
                    Csak szervezőknek, a látogatói fióktól függetlenül.
                </p>
                <nav class="flex flex-col gap-1 -ml-3" aria-label="Szervezői navigáció">
                    <?php if (\App\Core\Session::isAdmin()): ?>
                        <a href="/admin" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm text-billiard-gold-300 hover:text-white hover:bg-white/5 transition-colors w-fit">Szervezői felület</a>
                        <a href="/admin/logout" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm hover:text-white hover:bg-white/5 transition-colors w-fit">Kilépés a szervezői módból</a>
                    <?php else: ?>
                        <a href="/admin/login" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm text-white/45 hover:text-white hover:bg-white/5 transition-colors w-fit">Szervezői belépés</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <!-- Copyright sáv + jogi hivatkozás -->
        <div class="mt-10 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-white/45">
                &copy; <?= date('Y') ?> Okányi Biliárd Klub. Minden jog fenntartva.
            </p>
            <nav class="flex items-center gap-4" aria-label="Jogi információk">
                <a href="/adatkezeles" class="text-xs text-white/45 hover:text-white transition-colors">
                    Adatkezelési tájékoztató
                </a>
                <a href="/tarshonlapok" class="text-xs text-white/45 hover:text-white transition-colors">
                    Kapcsolat
                </a>
            </nav>
        </div>
    </div>
</footer>
