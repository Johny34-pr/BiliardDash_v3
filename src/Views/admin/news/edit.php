<?php
/**
 * Admin - Hír szerkesztése
 *
 * A publikálási dátum szerkesztéskor változatlan marad (Requirement 2.2).
 *
 * @var array $errors Validációs hibák (title, content)
 * @var array $data   Űrlap adatok (title, content)
 * @var array $news   Az eredeti hír adatai (id, published_at)
 */
?>
<div class="max-w-5xl">

    <a href="/admin/hirek" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a hírekhez
    </a>

    <header class="flex flex-wrap items-end justify-between gap-4 mb-7">
        <div>
            <p class="eyebrow mb-2">Szerkesztés</p>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Hír szerkesztése</h1>
        </div>
        <?php if (!empty($news['published_at'])): ?>
            <p class="text-sm text-sand-500 pb-1">
                Publikálva:
                <time datetime="<?= e($news['published_at']) ?>" class="font-medium text-sand-700">
                    <?= date('Y. m. d. H:i', strtotime($news['published_at'])) ?>
                </time>
            </p>
        <?php endif; ?>
    </header>

    <form method="POST" action="/admin/hirek/<?= e($news['id']) ?>/szerkeszt" class="card p-6 md:p-8 space-y-6">

        <!-- Cím -->
        <div>
            <label for="title" class="label">
                Cím <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="text" id="title" name="title" maxlength="200"
                   value="<?= e($data['title'] ?? '') ?>"
                   class="field<?= isset($errors['title']) ? ' field-error' : '' ?>"
                   <?= isset($errors['title']) ? 'aria-describedby="title-error" aria-invalid="true"' : 'aria-describedby="title-hint"' ?>>
            <?php if (isset($errors['title'])): ?>
                <p id="title-error" class="field-message" role="alert"><?= e($errors['title']) ?></p>
            <?php else: ?>
                <p id="title-hint" class="field-hint">Legfeljebb 200 karakter.</p>
            <?php endif; ?>
        </div>

        <!-- Tartalom -->
        <div>
            <label for="content" class="label">
                Tartalom <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <textarea id="content" name="content" rows="12"
                      class="field<?= isset($errors['content']) ? ' field-error' : '' ?>"
                      <?= isset($errors['content']) ? 'aria-describedby="content-error" aria-invalid="true"' : '' ?>><?= e($data['content'] ?? '') ?></textarea>
            <?php if (isset($errors['content'])): ?>
                <p id="content-error" class="field-message" role="alert"><?= e($errors['content']) ?></p>
            <?php else: ?>
                <p class="field-hint">A publikálási dátum a szerkesztés után változatlan marad.</p>
            <?php endif; ?>

            <?php require __DIR__ . '/../../partials/editor-help.php'; ?>
        </div>

        <!-- Műveletek -->
        <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-sand-200">
            <button type="submit" class="btn btn-primary mt-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Változások mentése
            </button>
            <a href="/hirek/<?= e($news['id']) ?>" target="_blank" rel="noopener" class="btn btn-secondary mt-4">
                Megnézés az oldalon
            </a>
            <a href="/admin/hirek" class="btn btn-ghost mt-4">Mégse</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../../partials/tinymce.php'; ?>
