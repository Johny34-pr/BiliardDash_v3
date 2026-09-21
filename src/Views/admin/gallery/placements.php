<?php
/**
 * Admin album helyezettek kezelése
 *
 * Egy oldalon a névsor, a felviteli űrlap és a borítókép választó, mert az
 * eredmény rögzítése jellemzően egy munkamenetben történik.
 *
 * @var array      $albumData  Album adatai (id, name, image_count)
 * @var array      $placements Helyezettek (id, position, player_name, note)
 * @var array      $images     Az album képei borítóválasztáshoz
 * @var array|null $cover      Az aktuális borítókép rekordja
 * @var array      $errors     Validációs hibák
 * @var array|null $editing    A szerkesztés alatt álló helyezés, vagy null
 */
$isEditing = $editing !== null;
$formAction = $isEditing
    ? '/admin/galeria/helyezett/' . $editing['id'] . '/szerkeszt'
    : '/admin/galeria/' . $albumData['id'] . '/helyezettek';
?>
<div class="max-w-4xl">

    <a href="/admin/galeria" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a galériához
    </a>

    <header class="flex flex-wrap items-end justify-between gap-4 mb-7">
        <div>
            <p class="eyebrow mb-2">Eredmény</p>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
                <?= e($albumData['name']) ?>
            </h1>
            <p class="text-sand-500 mt-1">
                <?= count($placements) ?> helyezett &middot; <?= (int) $albumData['image_count'] ?> kép
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="/galeria/<?= e($albumData['id']) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">
                Megnézés
            </a>
            <a href="/admin/galeria/<?= e($albumData['id']) ?>/feltolt" class="btn btn-secondary btn-sm">
                Kép feltöltése
            </a>
        </div>
    </header>

    <!-- ============ Helyezett felvitele / szerkesztése ============ -->
    <section class="card p-6 md:p-7 mb-8" aria-labelledby="placement-form-heading">
        <h2 id="placement-form-heading" class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-1">
            <?= $isEditing ? 'Helyezett módosítása' : 'Helyezett hozzáadása' ?>
        </h2>
        <p class="text-sm text-sand-500 mb-5">
            Ugyanaz a helyezés több játékosnál is megadható, ha osztott helyről van szó.
        </p>

        <form method="POST" action="<?= e($formAction) ?>" class="grid gap-4 sm:grid-cols-12 sm:items-start">

            <!-- Helyezés -->
            <div class="sm:col-span-2">
                <label for="position" class="label">
                    Helyezés <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                </label>
                <input type="number" id="position" name="position" min="1" max="999" required
                       value="<?= e($isEditing ? (string) $editing['position'] : (string) (count($placements) + 1)) ?>"
                       class="field<?= isset($errors['position']) ? ' field-error' : '' ?>"
                       <?= isset($errors['position']) ? 'aria-describedby="position-error" aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['position'])): ?>
                    <p id="position-error" class="field-message" role="alert"><?= e($errors['position']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Név -->
            <div class="sm:col-span-5">
                <label for="playerName" class="label">
                    Játékos neve <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                </label>
                <input type="text" id="playerName" name="playerName" maxlength="100" required
                       value="<?= e($isEditing ? $editing['player_name'] : '') ?>"
                       placeholder="pl. Kovács Péter"
                       class="field<?= isset($errors['playerName']) ? ' field-error' : '' ?>"
                       <?= isset($errors['playerName']) ? 'aria-describedby="playerName-error" aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['playerName'])): ?>
                    <p id="playerName-error" class="field-message" role="alert"><?= e($errors['playerName']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Megjegyzés -->
            <div class="sm:col-span-5">
                <label for="note" class="label">Megjegyzés</label>
                <input type="text" id="note" name="note" maxlength="150"
                       value="<?= e($isEditing ? (string) ($editing['note'] ?? '') : '') ?>"
                       placeholder="pl. egyesület vagy település"
                       class="field<?= isset($errors['note']) ? ' field-error' : '' ?>"
                       <?= isset($errors['note']) ? 'aria-describedby="note-error" aria-invalid="true"' : 'aria-describedby="note-hint"' ?>>
                <?php if (isset($errors['note'])): ?>
                    <p id="note-error" class="field-message" role="alert"><?= e($errors['note']) ?></p>
                <?php else: ?>
                    <p id="note-hint" class="field-hint">Nem kötelező.</p>
                <?php endif; ?>
            </div>

            <div class="sm:col-span-12 flex flex-wrap items-center gap-3 pt-2 border-t border-sand-200">
                <button type="submit" class="btn btn-primary mt-4">
                    <?php if ($isEditing): ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        Módosítás mentése
                    <?php else: ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
                        </svg>
                        Hozzáadás
                    <?php endif; ?>
                </button>
                <?php if ($isEditing): ?>
                    <a href="/admin/galeria/<?= e($albumData['id']) ?>/helyezettek" class="btn btn-ghost mt-4">Mégse</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- ============ Helyezettek névsora ============ -->
    <section class="mb-8" aria-labelledby="placement-list-heading">
        <h2 id="placement-list-heading" class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-4">
            Névsor
        </h2>

        <?php if (empty($placements)): ?>
            <div class="empty-state">
                <span class="empty-state-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.006 0H9.497m5.006 0a3 3 0 00-5.006 0M16.5 6.75V15m0-8.25a3 3 0 00-3-3h-3a3 3 0 00-3 3m9 0h1.5a1.5 1.5 0 011.5 1.5v1.5a3 3 0 01-3 3M7.5 6.75V15m0-8.25H6A1.5 1.5 0 004.5 8.25v1.5a3 3 0 003 3"/>
                    </svg>
                </span>
                <p class="font-semibold text-sand-900">Még nincs felvitt helyezett</p>
                <p class="text-sm text-sand-500 mt-1 max-w-sm">
                    A fenti űrlappal vidd fel az első helyezettet.
                </p>
            </div>
        <?php else: ?>
            <div class="card overflow-hidden">
                <div class="table-scroll">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-center">Hely</th>
                                <th scope="col">Játékos</th>
                                <th scope="col">Megjegyzés</th>
                                <th scope="col" class="text-right">Műveletek</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($placements as $placement): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge <?= (int) $placement['position'] === 1 ? 'badge-gold' : 'badge-neutral' ?> tabular-nums">
                                            <?= (int) $placement['position'] ?>.
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-medium text-sand-900"><?= e($placement['player_name']) ?></span>
                                    </td>
                                    <td class="text-sand-600"><?= e($placement['note'] ?? '') ?></td>
                                    <td>
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="/admin/galeria/<?= e($albumData['id']) ?>/helyezettek?szerkeszt=<?= e($placement['id']) ?>"
                                               class="btn btn-secondary btn-sm">
                                                Szerkesztés
                                            </a>
                                            <form method="POST" action="/admin/galeria/helyezett/<?= e($placement['id']) ?>/torol"
                                                  data-confirm="Biztosan törlöd <?= e($placement['player_name']) ?> helyezését?">
                                                <button type="submit" class="btn btn-danger btn-sm">Törlés</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============ Borítókép választása ============ -->
    <section aria-labelledby="cover-heading">
        <h2 id="cover-heading" class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-1">
            Borítókép
        </h2>
        <p class="text-sm text-sand-500 mb-4">
            Ez a kép jelenik meg a galéria listájában és az album oldalán. A többi
            feltöltött kép az albumban marad, de a publikus oldalon nem látszik.
        </p>

        <?php if (empty($images)): ?>
            <div class="card p-6">
                <p class="text-sm text-sand-500">
                    Ehhez az albumhoz még nincs kép.
                    <a href="/admin/galeria/<?= e($albumData['id']) ?>/feltolt" class="font-medium text-billiard-green-600 hover:underline">
                        Töltsd fel az elsőt
                    </a>.
                </p>
            </div>
        <?php else: ?>
            <div class="card p-6">
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
                    <?php foreach ($images as $image): ?>
                        <?php $isCover = $cover !== null && $cover['id'] === $image['id']; ?>
                        <form method="POST" action="/admin/galeria/<?= e($albumData['id']) ?>/boritokep">
                            <input type="hidden" name="image_id" value="<?= e($image['id']) ?>">
                            <button type="submit"
                                    class="relative block w-full aspect-square rounded-xl overflow-hidden bg-sand-200 transition-all
                                           <?= $isCover
                                                ? 'ring-2 ring-billiard-gold-500 ring-offset-2'
                                                : 'ring-1 ring-black/5 hover:ring-billiard-green-400' ?>"
                                    <?= $isCover ? 'aria-current="true" title="Ez a borítókép"' : 'title="Beállítás borítóképnek"' ?>>
                                <img src="/<?= e($image['thumbnail_path']) ?>"
                                     alt="<?= e($image['alt_text'] ?? $image['filename']) ?>"
                                     class="w-full h-full object-cover"
                                     width="200" height="200"
                                     onerror="this.onerror=null;this.src='/assets/images/placeholder.svg';"
                                     loading="lazy" decoding="async">

                                <?php if ($isCover): ?>
                                    <span class="absolute inset-x-0 bottom-0 py-1 bg-billiard-gold-500 text-billiard-green-900 text-[0.625rem] font-bold uppercase tracking-wide">
                                        Borító
                                    </span>
                                <?php endif; ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>
