<?php
/**
 * Galéria - Album oldala
 *
 * Kéthasábos elrendezés asztali nézetben: balra a nagy borítókép, jobbra a
 * verseny helyezettjei. Mobilon a kép kerül előre, alatta a névsor.
 *
 * A képre kattintva teljes méretben nagyítható (lightbox). A bezárás
 * gomb visszavisz az albumok listájára.
 *
 * @var array      $album      Album adatai (id, name, image_count)
 * @var array|null $cover      A borítókép rekordja, vagy null kép nélküli albumnál
 * @var array      $placements Helyezettek (position, player_name, note)
 */

/** Az első három helyezés kiemelt színt kap */
$medalClass = static function (int $position): string {
    return match ($position) {
        1 => 'bg-billiard-gold-400 text-billiard-green-900',
        2 => 'bg-sand-300 text-sand-800',
        3 => 'bg-billiard-gold-700 text-white',
        default => 'bg-sand-200 text-sand-600',
    };
};
?>

<div class="reveal">

    <!-- Fejléc: cím és bezárás -->
    <header class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div class="min-w-0">
            <p class="eyebrow mb-3">
                <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
                Versenyalbum
            </p>
            <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900">
                <?= e($album['name']) ?>
            </h1>
        </div>

        <!--
            Bezárás: a galéria listájára visz vissza. Ez a kilépés útja az
            albumból, ezért hangsúlyos és a fejléc jobb szélén áll.
        -->
        <a href="/galeria"
           class="btn btn-secondary btn-sm shrink-0"
           aria-label="Album bezárása, vissza az albumokhoz">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Bezárás
        </a>
    </header>

    <div class="grid gap-8 lg:grid-cols-5 items-start">

        <!-- ============ Bal oldal: a kép ============ -->
        <div class="lg:col-span-3">
            <?php if ($cover !== null): ?>
                <!--
                    A lightbox a #gallery-grid konténerben lévő
                    [data-lightbox-index] elemekre figyel, ezért a nagyítható
                    kép ugyanezt a szerkezetet használja.
                -->
                <div id="gallery-grid">
                    <button type="button"
                            class="group block w-full overflow-hidden rounded-2xl bg-sand-200 ring-1 ring-black/5"
                            data-lightbox-index="0"
                            aria-label="Kép megnyitása teljes méretben">
                        <img src="/<?= e($cover['full_path']) ?>"
                             alt="<?= e($cover['alt_text'] ?? $album['name']) ?>"
                             class="w-full h-auto object-contain transition-transform duration-300 group-hover:scale-[1.02]"
                             onerror="this.onerror=null;this.src='/assets/images/placeholder.svg';this.classList.add('error-placeholder');"
                             decoding="async">
                    </button>
                </div>

                <p class="flex items-center gap-1.5 text-sm text-sand-500 mt-3">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/>
                    </svg>
                    Kattints a képre a teljes méretű megjelenítéshez.
                </p>

                <script>
                    // A lightbox egyetlen képet kap: az album borítóját
                    window.galleryImages = <?= json_encode(
                        [[
                            'full' => '/' . $cover['full_path'],
                            'alt' => $cover['alt_text'] ?? $album['name'],
                        ]],
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
                    ) ?>;
                </script>
                <script src="<?= e(asset('js/gallery.js')) ?>" defer></script>

            <?php else: ?>
                <div class="empty-state">
                    <span class="empty-state-icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                        </svg>
                    </span>
                    <p class="font-semibold text-sand-900">Ehhez az albumhoz még nincs kép</p>
                    <p class="text-sm text-sand-500 mt-1 max-w-sm">
                        Hamarosan felkerül a versenyről készült fotó.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============ Jobb oldal: helyezettek ============ -->
        <aside class="lg:col-span-2">
            <div class="card overflow-hidden lg:sticky lg:top-24">

                <div class="flex items-center gap-3 px-6 py-5 bg-billiard-green-900 text-white">
                    <span class="grid place-items-center w-10 h-10 shrink-0 rounded-xl bg-white/10" aria-hidden="true">
                        <svg class="w-5 h-5 text-billiard-gold-300" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.006 0H9.497m5.006 0a3 3 0 00-5.006 0M16.5 6.75V15m0-8.25a3 3 0 00-3-3h-3a3 3 0 00-3 3m9 0h1.5a1.5 1.5 0 011.5 1.5v1.5a3 3 0 01-3 3M7.5 6.75V15m0-8.25H6A1.5 1.5 0 004.5 8.25v1.5a3 3 0 003 3"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[0.6875rem] font-semibold uppercase tracking-wider text-billiard-gold-300/80">
                            Eredmény
                        </p>
                        <h2 class="font-semibold text-lg tracking-tightest">Helyezettek</h2>
                    </div>
                </div>

                <?php if (empty($placements)): ?>
                    <div class="px-6 py-8 text-center">
                        <p class="font-semibold text-sand-900">Még nincs felvitt eredmény</p>
                        <p class="text-sm text-sand-500 mt-1">
                            A helyezettek névsora hamarosan megjelenik.
                        </p>
                    </div>
                <?php else: ?>
                    <ol class="divide-y divide-sand-200">
                        <?php foreach ($placements as $placement): ?>
                            <li class="flex items-center gap-3.5 px-6 py-4">
                                <span class="grid place-items-center w-8 h-8 shrink-0 rounded-full text-sm font-bold <?= $medalClass((int) $placement['position']) ?>">
                                    <?= (int) $placement['position'] ?>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold text-sand-900 leading-snug">
                                        <?= e($placement['player_name']) ?>
                                    </span>
                                    <?php if (!empty($placement['note'])): ?>
                                        <span class="block text-sm text-sand-500 mt-0.5">
                                            <?= e($placement['note']) ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <?php if (\App\Core\Session::isAdmin()): ?>
                    <div class="px-6 py-4 bg-sand-50 border-t border-sand-200">
                        <a href="/admin/galeria/<?= e($album['id']) ?>/helyezettek"
                           class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600 hover:underline">
                            Helyezettek szerkesztése
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Másodlagos kilépés a névsor alatt, hosszú listák végén -->
            <a href="/galeria" class="btn btn-ghost btn-sm w-full mt-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Vissza az albumokhoz
            </a>
        </aside>
    </div>
</div>
