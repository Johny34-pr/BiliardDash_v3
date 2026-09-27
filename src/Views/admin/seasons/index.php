<?php
/**
 * Admin szezonok kezelése
 *
 * A szezon két helyen rendez: a galéria archívumában (melyik évad albumai) és
 * a ranglistán (melyik évad pontjai). Egy oldalon van a lista, a létrehozás
 * és a szerkesztés, mert kevés szezon van, és a műveletek egy kattintásosak.
 *
 * @var array       $seasons Szezonok a legfrissebbel az élen
 * @var array       $errors  Hibák: a létrehozásnál 'name', szerkesztésnél a szezon azonosítója
 * @var string|null $editId  Melyik szezon szerkesztő űrlapja legyen nyitva
 */
?>
<header class="mb-7">
    <p class="eyebrow mb-2">Működés</p>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Szezonok</h1>
    <p class="text-sand-500 mt-1">
        A szezon fogja össze egy évad versenyeit és albumait. Ebből áll össze a
        ranglista és a galéria archívuma.
    </p>
</header>

<!-- Új szezon -->
<section class="card p-6 mb-7" aria-labelledby="new-season-heading">
    <h2 id="new-season-heading" class="font-semibold text-billiard-green-900 mb-4">Új szezon</h2>

    <form method="POST" action="/admin/szezonok/uj" class="grid gap-4 sm:grid-cols-[1fr_auto_auto] sm:items-start" novalidate>
        <div>
            <label for="season-name" class="label">Szezon neve</label>
            <input type="text" id="season-name" name="name" required maxlength="60"
                   placeholder="pl. 2027/2028"
                   class="field<?= isset($errors['name']) ? ' field-error' : '' ?>"
                   <?= isset($errors['name']) ? 'aria-describedby="season-name-error" aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['name'])): ?>
                <p id="season-name-error" class="field-message" role="alert"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="sm:w-44">
            <label for="season-starts" class="label">Kezdete</label>
            <!--
                A kezdődátum adja a szezonok sorrendjét: a név szerinti
                rendezés ("2026/2027" vs "2027/2028") csak véletlenül lenne
                helyes. Elhagyható, de érdemes megadni.
            -->
            <input type="date" id="season-starts" name="starts_on" class="field">
        </div>

        <button type="submit" class="btn btn-primary sm:mt-[1.7rem]">Létrehozás</button>

        <label class="flex items-center gap-2.5 sm:col-span-3 cursor-pointer">
            <input type="checkbox" name="is_current" value="1"
                   class="w-5 h-5 rounded border-sand-300 text-billiard-green-700">
            <span class="text-sm text-sand-700">
                Ez legyen az aktuális szezon (a korábbiról lekerül a jelölés)
            </span>
        </label>
    </form>
</section>

<?php if ($seasons === []): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincs szezon</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Hozd létre az első évadot, hogy a versenyek és albumok hozzá
            rendelhetők legyenek.
        </p>
    </div>

<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($seasons as $season): ?>
            <?php
            $isCurrent = (int) $season['is_current'] === 1;
            $isArchived = (int) $season['is_archived'] === 1;
            $hasError = isset($errors[$season['id']]);
            ?>
            <section class="card p-6" aria-labelledby="season-<?= e($season['id']) ?>">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="season-<?= e($season['id']) ?>" class="font-semibold text-billiard-green-900">
                            <?= e($season['name']) ?>
                            <?php if ($isCurrent): ?>
                                <span class="badge badge-green ml-1.5">Aktuális</span>
                            <?php endif; ?>
                            <?php if ($isArchived): ?>
                                <span class="badge badge-gold ml-1.5">Archív</span>
                            <?php endif; ?>
                        </h2>
                        <p class="text-sm text-sand-500 mt-0.5">
                            <?php if (!empty($season['starts_on'])): ?>
                                Kezdete: <?= date('Y. m. d.', strtotime($season['starts_on'])) ?>
                            <?php else: ?>
                                Kezdődátum nincs megadva
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <a href="/ranglista/<?= e($season['id']) ?>" target="_blank" rel="noopener"
                           class="btn btn-ghost btn-sm">Ranglista</a>

                        <?php if (!$isCurrent): ?>
                            <form method="POST" action="/admin/szezonok/<?= e($season['id']) ?>/aktualis">
                                <button type="submit" class="btn btn-secondary btn-sm">Aktuálissá</button>
                            </form>
                        <?php endif; ?>

                        <!--
                            Az aktuális szezon nem archiválható: amíg fut, az
                            eredményei változhatnak, tehát nem véglegesek.
                        -->
                        <?php if (!$isCurrent): ?>
                            <form method="POST" action="/admin/szezonok/<?= e($season['id']) ?>/archivalas">
                                <input type="hidden" name="archived" value="<?= $isArchived ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-ghost btn-sm">
                                    <?= $isArchived ? 'Visszahelyezés' : 'Archiválás' ?>
                                </button>
                            </form>

                            <form method="POST" action="/admin/szezonok/<?= e($season['id']) ?>/torol"
                                  data-confirm="Törlöd a(z) &quot;<?= e($season['name']) ?>&quot; szezont? A versenyek és albumok megmaradnak, csak szezon nélkülivé válnak.">
                                <button type="submit" class="btn btn-danger btn-sm">Törlés</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Átnevezés: natív <details>, hiba után nyitva marad -->
                <details class="mt-4" <?= ($hasError || $editId === $season['id']) ? 'open' : '' ?>>
                    <summary class="btn btn-secondary btn-sm w-fit">Szerkesztés</summary>

                    <form method="POST" action="/admin/szezonok/<?= e($season['id']) ?>/szerkeszt"
                          class="grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-start mt-3 p-4 rounded-xl bg-sand-50 border border-sand-200"
                          novalidate>
                        <div>
                            <label for="season-name-<?= e($season['id']) ?>" class="label">Név</label>
                            <input type="text" id="season-name-<?= e($season['id']) ?>" name="name"
                                   value="<?= e($season['name']) ?>" required maxlength="60"
                                   class="field<?= $hasError ? ' field-error' : '' ?>"
                                   <?= $hasError ? 'aria-describedby="season-err-' . e($season['id']) . '" aria-invalid="true"' : '' ?>>
                            <?php if ($hasError): ?>
                                <p id="season-err-<?= e($season['id']) ?>" class="field-message" role="alert">
                                    <?= e($errors[$season['id']]) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="sm:w-44">
                            <label for="season-starts-<?= e($season['id']) ?>" class="label">Kezdete</label>
                            <input type="date" id="season-starts-<?= e($season['id']) ?>" name="starts_on"
                                   value="<?= e($season['starts_on'] ?? '') ?>" class="field">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm sm:mt-[1.7rem]">Mentés</button>
                    </form>
                </details>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
