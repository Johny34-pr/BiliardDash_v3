<?php
/**
 * Admin tartalmi oldal szerkesztése
 *
 * A tartalom a hírekkel egyező rich text szerkesztőt használja: a textarea
 * azonosítója ezért kötelezően "content", a tinymce.php partial erre épül.
 *
 * @var array $page   Az oldal adatai (id, slug, title)
 * @var array $errors Validációs hibák
 * @var array $data   Űrlap adatok (title, content, metaDescription)
 */
?>
<div class="max-w-3xl">

    <a href="/admin/oldalak" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza az oldalakhoz
    </a>

    <header class="flex flex-wrap items-end justify-between gap-4 mb-7">
        <div>
            <p class="eyebrow mb-2">Szerkesztés</p>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
                <?= e($page['title']) ?>
            </h1>
            <p class="text-sand-500 mt-1">
                Publikus útvonal: <code class="text-sand-600">/<?= e($page['slug']) ?></code>
            </p>
        </div>
        <a href="/<?= e($page['slug']) ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
            Megnézés
        </a>
    </header>

    <form method="POST" action="/admin/oldalak/<?= e($page['id']) ?>/szerkeszt"
          class="card p-6 md:p-8 space-y-6">

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
                <p id="title-hint" class="field-hint">
                    Ez jelenik meg az oldal fejlécében és a böngésző fülén.
                </p>
            <?php endif; ?>
        </div>

        <!-- Tartalom -->
        <div>
            <label for="content" class="label">
                Tartalom <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <textarea id="content" name="content" rows="18"
                      class="field<?= isset($errors['content']) ? ' field-error' : '' ?>"
                      <?= isset($errors['content']) ? 'aria-describedby="content-error" aria-invalid="true"' : '' ?>><?= e($data['content'] ?? '') ?></textarea>
            <?php if (isset($errors['content'])): ?>
                <p id="content-error" class="field-message" role="alert"><?= e($errors['content']) ?></p>
            <?php endif; ?>

            <?php require __DIR__ . '/../../partials/editor-help.php'; ?>
        </div>

        <!-- Keresőoptimalizálási leírás -->
        <div>
            <label for="metaDescription" class="label">Leírás a keresőknek</label>
            <textarea id="metaDescription" name="metaDescription" rows="3" maxlength="300"
                      class="field<?= isset($errors['metaDescription']) ? ' field-error' : '' ?>"
                      <?= isset($errors['metaDescription']) ? 'aria-describedby="meta-error" aria-invalid="true"' : 'aria-describedby="meta-hint"' ?>><?= e($data['metaDescription'] ?? '') ?></textarea>
            <?php if (isset($errors['metaDescription'])): ?>
                <p id="meta-error" class="field-message" role="alert"><?= e($errors['metaDescription']) ?></p>
            <?php else: ?>
                <p id="meta-hint" class="field-hint">
                    Ez a szöveg jelenik meg a keresőtalálatban és a link megosztásakor.
                    Üresen hagyva a tartalom első mondataiból készül. Legfeljebb 300 karakter.
                </p>
            <?php endif; ?>
        </div>

        <!-- Műveletek -->
        <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-sand-200">
            <button type="submit" class="btn btn-primary mt-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Változások mentése
            </button>
            <a href="/admin/oldalak" class="btn btn-ghost mt-4">Mégse</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../../partials/tinymce.php'; ?>
