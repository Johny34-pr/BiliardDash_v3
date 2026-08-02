<?php
/**
 * Admin galéria - képfeltöltő űrlap
 *
 * @var array $album  Az album adatai (id, name)
 * @var array $errors Validációs hibák (image)
 */
?>
<div class="max-w-xl">

    <a href="/admin/galeria" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a galériához
    </a>

    <header class="mb-7">
        <p class="eyebrow mb-2">Képfeltöltés</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
            Kép hozzáadása
        </h1>
        <p class="text-sand-500 mt-1">
            Album: <strong class="text-sand-900 font-semibold"><?= e($album['name']) ?></strong>
        </p>
    </header>

    <form action="/admin/galeria/<?= e($album['id']) ?>/feltolt" method="POST"
          enctype="multipart/form-data" class="card p-6 md:p-7 space-y-5">

        <div>
            <label for="image-file" class="label">Kép kiválasztása</label>
            <input type="file" id="image-file" name="image"
                   accept="image/jpeg,image/png" required
                   class="block w-full text-sm text-sand-500 rounded-xl border border-sand-300 p-2
                          file:mr-4 file:py-2.5 file:px-4 file:min-h-[38px]
                          file:rounded-lg file:border-0
                          file:text-sm file:font-semibold file:cursor-pointer
                          file:bg-billiard-green-800 file:text-white
                          hover:file:bg-billiard-green-700
                          <?= isset($errors['image']) ? 'border-red-500' : '' ?>"
                   <?= isset($errors['image']) ? 'aria-describedby="image-error" aria-invalid="true"' : 'aria-describedby="image-hint"' ?>>
            <?php if (isset($errors['image'])): ?>
                <p id="image-error" class="field-message" role="alert"><?= e($errors['image']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Feltöltési szabályok -->
        <div id="image-hint" class="alert alert-info">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
            </svg>
            <div class="text-sm">
                <p class="alert-title mb-1">Feltöltési feltételek</p>
                <ul class="space-y-0.5 text-sand-600">
                    <li>Formátum: JPEG vagy PNG</li>
                    <li>Maximális méret: 10 MB</li>
                    <li>A 200&times;200 pixeles bélyegkép automatikusan elkészül</li>
                </ul>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/>
                </svg>
                Feltöltés
            </button>
            <a href="/admin/galeria" class="btn btn-ghost">Mégse</a>
        </div>
    </form>
</div>
