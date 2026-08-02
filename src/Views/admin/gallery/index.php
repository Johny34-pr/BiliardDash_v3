<?php
/**
 * Admin galéria kezelés - albumok listája
 *
 * Album létrehozó űrlap, majd albumonként a képek rácsa törlés gombbal.
 *
 * @var array $albums      Albumok listája (id, name, image_count)
 * @var array $albumImages Album képek (albumId => images[])
 * @var array $errors      Validációs hibák (name)
 */
?>
<header class="mb-7">
    <p class="eyebrow mb-2">Tartalom</p>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Galéria kezelése</h1>
    <p class="text-sand-500 mt-1">Albumok létrehozása, képek feltöltése és törlése.</p>
</header>

<!-- Új album létrehozása -->
<section class="card p-6 md:p-7 mb-8" aria-labelledby="new-album">
    <h2 id="new-album" class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-4">
        Új album létrehozása
    </h2>
    <form action="/admin/galeria/uj" method="POST" class="flex flex-col sm:flex-row gap-4 sm:items-start">
        <div class="flex-1">
            <label for="album-name" class="label">Album neve</label>
            <input type="text" id="album-name" name="name" required maxlength="100"
                   placeholder="pl. Országos döntő 2026"
                   class="field<?= isset($errors['name']) ? ' field-error' : '' ?>"
                   <?= isset($errors['name']) ? 'aria-describedby="album-name-error" aria-invalid="true"' : 'aria-describedby="album-name-hint"' ?>>
            <?php if (isset($errors['name'])): ?>
                <p id="album-name-error" class="field-message" role="alert"><?= e($errors['name']) ?></p>
            <?php else: ?>
                <p id="album-name-hint" class="field-hint">1-100 karakter, nem lehet csak szóköz.</p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary sm:mt-[1.85rem]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
            </svg>
            Létrehozás
        </button>
    </form>
</section>

<!-- Albumok listája -->
<?php if (empty($albums)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincsenek albumok</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">Hozd létre az elsőt a fenti űrlap segítségével.</p>
    </div>

<?php else: ?>
    <div class="space-y-5">
        <?php foreach ($albums as $album): ?>
            <?php $images = $albumImages[$album['id']] ?? []; ?>
            <section class="card overflow-hidden" aria-labelledby="album-<?= e($album['id']) ?>">

                <!-- Album fejléc -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-5 border-b border-sand-200">
                    <div class="min-w-0">
                        <h2 id="album-<?= e($album['id']) ?>" class="font-semibold text-billiard-green-900 truncate">
                            <?= e($album['name']) ?>
                        </h2>
                        <p class="text-sm text-sand-500 mt-0.5"><?= (int)$album['image_count'] ?> kép</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="/galeria/<?= e($album['id']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">
                            Megnézés
                        </a>
                        <a href="/admin/galeria/<?= e($album['id']) ?>/feltolt" class="btn btn-primary btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/>
                            </svg>
                            Feltöltés
                        </a>
                    </div>
                </div>

                <!-- Képek rácsa -->
                <div class="p-6">
                    <?php if (empty($images)): ?>
                        <p class="text-sm text-sand-500">
                            Még nincsenek képek ebben az albumban.
                            <a href="/admin/galeria/<?= e($album['id']) ?>/feltolt" class="font-medium text-billiard-green-600 hover:underline">
                                Töltsd fel az elsőt
                            </a>.
                        </p>
                    <?php else: ?>
                        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                            <?php foreach ($images as $image): ?>
                                <div class="group relative aspect-square rounded-xl overflow-hidden bg-sand-200 ring-1 ring-black/5">
                                    <img src="/<?= e($image['thumbnail_path']) ?>"
                                         alt="<?= e($image['alt_text'] ?? $image['filename']) ?>"
                                         class="w-full h-full object-cover"
                                         onerror="this.onerror=null;this.src='/assets/images/placeholder.svg';this.classList.add('error-placeholder');"
                                         loading="lazy">

                                    <!-- Törlés gomb: mobilon mindig látszik, asztalin hoverre -->
                                    <form action="/admin/galeria/kep/<?= e($image['id']) ?>/torol" method="POST"
                                          class="absolute top-1.5 right-1.5 opacity-100 md:opacity-0 md:group-hover:opacity-100 md:focus-within:opacity-100 transition-opacity"
                                          data-confirm="Biztosan törlöd ezt a képet? A bélyegkép is törlődik.">
                                        <button type="submit"
                                                class="grid place-items-center w-7 h-7 min-h-0 min-w-0 rounded-full bg-red-600 text-white shadow-lg hover:bg-red-700 transition-colors"
                                                aria-label="Kép törlése" title="Kép törlése">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
