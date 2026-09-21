<?php
/**
 * Galéria - Album lista nézet
 *
 * Minden album a borítóképével jelenik meg, alatta a névvel és a dobogóval.
 * A kártyára kattintva nyílik meg az album oldala, ahol a kép nagyban, a
 * helyezettek pedig mellette olvashatók.
 *
 * Reszponzív rács: 1 oszlop mobil, 2 tablet, 3 asztali.
 *
 * @var array $albums Albumok (id, name, cover_url, cover_alt, placements)
 */
$placementLimit = \App\Services\GalleryService::LIST_PLACEMENT_LIMIT;
?>

<!-- Oldalfejléc -->
<header class="mb-10 reveal">
    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Fotógaléria
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Versenyalbumok
        </h1>
        <?php if (!empty($albums)): ?>
            <p class="text-sm text-sand-500 pb-1"><?= count($albums) ?> album</p>
        <?php endif; ?>
    </div>
    <p class="text-sand-600 mt-4 max-w-reading leading-relaxed">
        Kattints egy albumra: a kép nagyban jelenik meg, mellette a verseny helyezettjeivel.
    </p>
</header>

<?php if (empty($albums)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Jelenleg nincsenek albumok</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            A versenyekről készült fotók és a helyezettek itt fognak megjelenni.
        </p>
    </div>

<?php else: ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($albums as $i => $album): ?>
            <article class="card card-interactive overflow-hidden reveal reveal-<?= min($i + 1, 5) ?>">
                <a href="/galeria/<?= e($album['id']) ?>" class="flex flex-col h-full">

                    <!-- Borítókép -->
                    <div class="relative aspect-[4/3] overflow-hidden bg-sand-200">
                        <?php if (!empty($album['cover_url'])): ?>
                            <img src="<?= e($album['cover_url']) ?>"
                                 alt="<?= e($album['cover_alt']) ?>"
                                 class="cover-zoom w-full h-full object-cover"
                                 width="400" height="300"
                                 onerror="this.onerror=null;this.src='/assets/images/placeholder.svg';this.classList.add('error-placeholder');"
                                 loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="w-full h-full grid place-items-center bg-sand-100 text-sand-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <!-- Nagyítás jelzése a borítón -->
                        <span class="absolute bottom-3 right-3 grid place-items-center w-9 h-9 rounded-full bg-billiard-green-950/70 text-white backdrop-blur-sm" aria-hidden="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"/>
                            </svg>
                        </span>
                    </div>

                    <!-- Album adatok -->
                    <div class="flex-1 flex flex-col p-5">
                        <h2 class="font-semibold text-billiard-green-900 leading-snug clamp-2">
                            <?= e($album['name']) ?>
                        </h2>

                        <?php if (!empty($album['placements'])): ?>
                            <!-- Dobogó: a teljes névsor az album oldalán olvasható -->
                            <ol class="mt-3 space-y-1.5">
                                <?php foreach (array_slice($album['placements'], 0, $placementLimit) as $placement): ?>
                                    <li class="flex items-center gap-2 text-sm">
                                        <span class="grid place-items-center w-5 h-5 shrink-0 rounded-full text-[0.625rem] font-bold
                                                     <?= $placement['position'] === 1 ? 'bg-billiard-gold-400 text-billiard-green-900' : 'bg-sand-200 text-sand-600' ?>">
                                            <?= (int) $placement['position'] ?>
                                        </span>
                                        <span class="text-sand-700 truncate"><?= e($placement['player_name']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>

                            <?php if (count($album['placements']) > $placementLimit): ?>
                                <p class="text-xs text-sand-500 mt-2">
                                    +<?= count($album['placements']) - $placementLimit ?> további helyezett
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-sm text-sand-500 mt-2">Helyezettek hamarosan</p>
                        <?php endif; ?>

                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600 mt-4 pt-3 border-t border-sand-200">
                            Album megnyitása
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </span>
                    </div>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
