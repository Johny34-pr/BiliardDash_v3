<?php
/**
 * Galéria - Album lista nézet
 *
 * Reszponzív rács: 1 oszlop mobil, 2 tablet, 3 asztali (Requirement 7.1).
 * Az album kártyák borítóképe hoverre finoman nagyít.
 *
 * @var array $albums Albumok tömbje (id, name, image_count, cover_image_url)
 */
$totalImages = array_sum(array_map(static fn($a) => (int)($a['image_count'] ?? 0), $albums));
?>

<!-- Oldalfejléc -->
<header class="mb-10 reveal">
    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Fotógaléria
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Albumok
        </h1>
        <?php if (!empty($albums)): ?>
            <p class="text-sm text-sand-500 pb-1">
                <?= count($albums) ?> album &middot; <?= $totalImages ?> kép
            </p>
        <?php endif; ?>
    </div>
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
            Az eseményekről készült fotók albumokba rendezve fognak itt megjelenni.
        </p>
    </div>

<?php else: ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($albums as $i => $album): ?>
            <article class="card card-interactive overflow-hidden reveal reveal-<?= min($i + 1, 5) ?>">
                <a href="/galeria/<?= e($album['id']) ?>" class="flex flex-col h-full">

                    <!-- Borítókép -->
                    <div class="relative aspect-[4/3] overflow-hidden bg-sand-200">
                        <?php if (!empty($album['cover_image_url'])): ?>
                            <img src="<?= e($album['cover_image_url']) ?>"
                                 alt="<?= e($album['name']) ?> borítókép"
                                 class="cover-zoom w-full h-full object-cover"
                                 onerror="this.onerror=null;this.src='/assets/images/placeholder.svg';this.classList.add('error-placeholder');"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full grid place-items-center bg-sand-100 text-sand-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <!-- Képszám jelvény a borítón -->
                        <span class="absolute bottom-3 right-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-billiard-green-950/75 text-white text-xs font-semibold backdrop-blur-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159M2.25 18h19.5"/>
                            </svg>
                            <?= (int)$album['image_count'] ?>
                        </span>
                    </div>

                    <!-- Album adatok -->
                    <div class="flex-1 flex items-center justify-between gap-3 p-5">
                        <div class="min-w-0">
                            <h2 class="font-semibold text-billiard-green-900 leading-snug clamp-2">
                                <?= e($album['name']) ?>
                            </h2>
                            <p class="text-sm text-sand-500 mt-0.5">
                                <?= (int)$album['image_count'] ?> kép
                            </p>
                        </div>
                        <svg class="w-5 h-5 shrink-0 text-sand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </div>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
