<?php
/**
 * Galéria - Album képei nézet
 *
 * Bélyegkép rács lightbox triggerrel. A rács sűrűsége töréspontonként nő:
 * 2 oszlop mobil, 3 tablet, 4-5 asztali (Requirement 7.1).
 *
 * @var array $album  Album adatok (id, name, image_count)
 * @var array $images Képek tömbje (thumbnail_path, full_path, alt_text, filename)
 */
?>

<!-- Oldalfejléc -->
<header class="mb-10 reveal">
    <a href="/galeria" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a galériához
    </a>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-3">
                <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
                Album
            </p>
            <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
                <?= e($album['name']) ?>
            </h1>
        </div>
        <?php if (!empty($images)): ?>
            <p class="text-sm text-sand-500 pb-1"><?= count($images) ?> kép</p>
        <?php endif; ?>
    </div>
</header>

<?php if (empty($images)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Ez az album jelenleg üres</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Hamarosan felkerülnek az eseményről készült képek.
        </p>
        <a href="/galeria" class="btn btn-secondary btn-sm mt-6">Vissza a galériához</a>
    </div>

<?php else: ?>
    <p class="text-sm text-sand-500 mb-5">
        Kattints egy képre a nagyításhoz. Navigálás a nyílgombokkal vagy a nyíl billentyűkkel,
        mobilon húzással.
    </p>

    <div id="gallery-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-4">
        <?php foreach ($images as $index => $image): ?>
            <button type="button"
                    class="gallery-tile aspect-square reveal reveal-<?= min(intdiv($index, 3) + 1, 5) ?>"
                    data-lightbox-index="<?= $index ?>"
                    aria-label="Kép megnyitása nagyban: <?= e($image['alt_text'] ?? $image['filename']) ?>">
                <img src="/<?= e($image['thumbnail_path']) ?>"
                     alt="<?= e($image['alt_text'] ?? $image['filename']) ?>"
                     class="gallery-thumb w-full h-full object-cover"
                     onerror="this.onerror=null;this.src='/assets/images/placeholder.svg';this.classList.add('error-placeholder');"
                     loading="lazy">
            </button>
        <?php endforeach; ?>
    </div>

    <script>
        // Képek adatai a lightbox-hoz
        window.galleryImages = <?= json_encode(
            array_map(static function (array $img): array {
                return [
                    'full' => '/' . $img['full_path'],
                    'alt' => $img['alt_text'] ?? $img['filename'],
                ];
            }, $images),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?>;
    </script>
    <script src="/assets/js/gallery.js"></script>
<?php endif; ?>
