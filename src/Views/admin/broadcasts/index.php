<?php
/**
 * Admin közvetítések kezelése
 *
 * Egy oldalon a lista, a felvitel és a szerkesztés - ugyanúgy, mint a
 * szezonoknál: a közvetítés néhány mezőből áll, és a műveletei egy
 * kattintásosak.
 *
 * A VERSENY hozzárendelése a legfontosabb mező: a nyilvános oldalon a
 * látogató a verseny nevére keres, tehát a verseny nélküli közvetítés csak a
 * saját nevén található meg. Erre a lista figyelmeztet is.
 *
 * @var array       $broadcasts   Közvetítések, élő adás elöl
 * @var array       $competitions Versenyek a választóhoz (legfrissebb elöl)
 * @var array       $errors       Hibák: a felvitelnél 'new', szerkesztésnél a közvetítés azonosítója
 * @var array       $data         A felviteli űrlap előző értékei hiba után
 * @var string|null $editId       Melyik közvetítés szerkesztő űrlapja legyen nyitva
 * @var string      $menuLabel    A menüpont jelenlegi felirata
 */

/** A verseny választó feliratai: név és dátum, hogy az azonos nevűek is megkülönböztethetők legyenek */
$competitionLabel = static function (array $competition): string {
    return $competition['name'] . ' - ' . date('Y. m. d.', strtotime($competition['date']));
};

$liveCount = 0;

foreach ($broadcasts as $broadcast) {
    if ((int) $broadcast['is_live'] === 1) {
        $liveCount++;
    }
}
?>
<header class="mb-7">
    <p class="eyebrow mb-2">Események</p>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Közvetítések</h1>
    <p class="text-sand-500 mt-1">
        Az élő adások és a korábbiak hivatkozásai. A látogató a
        <strong><?= e($menuLabel) ?></strong> menüpontból éri el őket, és a
        verseny nevére keresve a régebbi adásokat is megtalálja.
    </p>
    <?php if ($broadcasts !== []): ?>
        <p class="text-sm text-sand-500 mt-2">
            <?= count($broadcasts) ?> közvetítés
            <?php if ($liveCount > 0): ?>
                &middot; <?= $liveCount ?> élő adásként jelölve
            <?php endif; ?>
        </p>
    <?php endif; ?>
</header>

<!-- Új közvetítés -->
<section class="card p-6 mb-7" aria-labelledby="new-broadcast-heading">
    <h2 id="new-broadcast-heading" class="font-semibold text-billiard-green-900 mb-4">Új közvetítés</h2>

    <?php if (isset($errors['new'])): ?>
        <div class="alert alert-error mb-5" role="alert">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span class="text-sm"><?= e($errors['new']) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/kozvetitesek/uj" class="space-y-4" novalidate>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="competition_id" class="label">Verseny</label>
                <select id="competition_id" name="competition_id" class="field"
                        aria-describedby="competition-hint">
                    <option value="">Nincs verseny (klubest, bemutató)</option>
                    <?php foreach ($competitions as $competition): ?>
                        <option value="<?= e($competition['id']) ?>"
                            <?= ($data['competitionId'] ?? '') === $competition['id'] ? 'selected' : '' ?>>
                            <?= e($competitionLabel($competition)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="competition-hint" class="field-hint">
                    Ez alapján keres a látogató. Verseny nélkül csak a
                    közvetítés neve alapján található meg.
                </p>
            </div>

            <div>
                <label for="broadcast_date" class="label">Az adás napja</label>
                <input type="date" id="broadcast_date" name="broadcast_date"
                       value="<?= e($data['date'] ?? '') ?>"
                       class="field" aria-describedby="date-hint">
                <p id="date-hint" class="field-hint">
                    Üresen hagyva a verseny napja, enélkül a mai dátum.
                </p>
            </div>
        </div>

        <div>
            <label for="url" class="label">Közvetítés címe</label>
            <input type="url" id="url" name="url" required
                   value="<?= e($data['url'] ?? '') ?>"
                   placeholder="https://www.youtube.com/live/..."
                   class="field<?= isset($errors['new']) ? ' field-error' : '' ?>"
                   aria-describedby="url-hint">
            <p id="url-hint" class="field-hint">
                A YouTube vagy Facebook adás címe, http:// vagy https://
                kezdettel. Más címsémát nem fogadunk el.
            </p>
        </div>

        <div>
            <label for="title" class="label">Közvetítés neve</label>
            <input type="text" id="title" name="title" maxlength="160"
                   value="<?= e($data['title'] ?? '') ?>"
                   placeholder="pl. Okány Kupa - döntő"
                   class="field" aria-describedby="title-hint">
            <p id="title-hint" class="field-hint">
                Üresen hagyva a verseny nevét veszi át. Akkor érdemes
                kitölteni, ha egy versenyről több adás is van.
            </p>
        </div>

        <label class="flex items-start gap-3 p-4 rounded-xl bg-sand-50 border border-sand-200 cursor-pointer">
            <input type="checkbox" name="is_live" value="1"
                   class="mt-0.5 w-5 h-5 shrink-0 rounded border-sand-300 text-billiard-green-700"
                   <?= !empty($data['isLive']) ? 'checked' : '' ?>>
            <span class="min-w-0">
                <span class="block font-semibold text-sand-900">Most élő adás</span>
                <span class="block text-sm text-sand-500 mt-0.5">
                    Az élő adás a lista élére kerül, és a főmenüben is jelzést
                    kap. Az adás végén vedd le a jelzést - a közvetítés a
                    listában marad.
                </span>
            </span>
        </label>

        <button type="submit" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
            </svg>
            Közvetítés felvétele
        </button>
    </form>
</section>

<?php if ($broadcasts === []): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincs közvetítés</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Az első közvetítés felvétele után megjelenik a menüpont a
            weboldalon, és onnantól minden korábbi adás visszakereshető marad.
        </p>
    </div>

<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($broadcasts as $broadcast): ?>
            <?php
            $isLive = (int) $broadcast['is_live'] === 1;
            $hasError = isset($errors[$broadcast['id']]);
            $competitionName = (string) ($broadcast['competition_name'] ?? '');
            $broadcastId = (string) $broadcast['id'];
            ?>
            <section class="card p-6" aria-labelledby="broadcast-<?= e($broadcastId) ?>">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="broadcast-<?= e($broadcastId) ?>" class="font-semibold text-billiard-green-900">
                            <?= e($broadcast['title']) ?>
                            <?php if ($isLive): ?>
                                <span class="badge badge-gold ml-1.5">Élő adás</span>
                            <?php endif; ?>
                        </h2>

                        <p class="text-sm text-sand-500 mt-0.5">
                            <time datetime="<?= e($broadcast['broadcast_date']) ?>">
                                <?= date('Y. m. d.', strtotime((string) $broadcast['broadcast_date'])) ?>
                            </time>
                            <?php if ($competitionName !== ''): ?>
                                &middot; <?= e($competitionName) ?>
                            <?php endif; ?>
                        </p>

                        <?php if ($competitionName === ''): ?>
                            <!--
                                A verseny nélküli közvetítés nem található meg
                                a verseny nevére keresve - ezt kimondjuk, mert
                                a felületen nem látszana a hiánya.
                            -->
                            <p class="text-sm text-billiard-gold-700 mt-1">
                                Nincs versenyhez kötve: csak a nevére keresve található meg.
                            </p>
                        <?php endif; ?>

                        <a href="<?= e($broadcast['url']) ?>" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 text-sm text-billiard-green-600 hover:underline mt-2 break-all">
                            <?= e($broadcast['url']) ?>
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                            </svg>
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <!--
                            Az élő jelzés kapcsolása: a rejtett mező a kívánt
                            ÁLLAPOTOT küldi, nem a váltás tényét, így egy
                            kétszer elküldött űrlap sem fordítja vissza.
                        -->
                        <form method="POST" action="/admin/kozvetitesek/<?= e($broadcastId) ?>/elo">
                            <input type="hidden" name="live" value="<?= $isLive ? '0' : '1' ?>">
                            <button type="submit" class="btn <?= $isLive ? 'btn-ghost' : 'btn-secondary' ?> btn-sm">
                                <?= $isLive ? 'Élő jelzés levétele' : 'Élőként jelölés' ?>
                            </button>
                        </form>

                        <form method="POST" action="/admin/kozvetitesek/<?= e($broadcastId) ?>/torol"
                              data-confirm="Törlöd a(z) &quot;<?= e($broadcast['title']) ?>&quot; közvetítést? A hivatkozás eltűnik az oldalról, a külső felvételt ez nem érinti.">
                            <button type="submit" class="btn btn-danger btn-sm">Törlés</button>
                        </form>
                    </div>
                </div>

                <!-- Szerkesztés: natív <details>, hiba után nyitva marad -->
                <details class="mt-4" <?= ($hasError || $editId === $broadcastId) ? 'open' : '' ?>>
                    <summary class="btn btn-secondary btn-sm w-fit">Szerkesztés</summary>

                    <form method="POST" action="/admin/kozvetitesek/<?= e($broadcastId) ?>/szerkeszt"
                          class="space-y-3 mt-3 p-4 rounded-xl bg-sand-50 border border-sand-200" novalidate>
                        <?php if ($hasError): ?>
                            <p class="field-message" role="alert"><?= e($errors[$broadcastId]) ?></p>
                        <?php endif; ?>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label for="competition-<?= e($broadcastId) ?>" class="label">Verseny</label>
                                <select id="competition-<?= e($broadcastId) ?>" name="competition_id" class="field">
                                    <option value="">Nincs verseny (klubest, bemutató)</option>
                                    <?php foreach ($competitions as $competition): ?>
                                        <option value="<?= e($competition['id']) ?>"
                                            <?= $broadcast['competition_id'] === $competition['id'] ? 'selected' : '' ?>>
                                            <?= e($competitionLabel($competition)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="date-<?= e($broadcastId) ?>" class="label">Az adás napja</label>
                                <input type="date" id="date-<?= e($broadcastId) ?>" name="broadcast_date"
                                       value="<?= e($broadcast['broadcast_date']) ?>" class="field">
                            </div>
                        </div>

                        <div>
                            <label for="url-<?= e($broadcastId) ?>" class="label">Közvetítés címe</label>
                            <input type="url" id="url-<?= e($broadcastId) ?>" name="url" required
                                   value="<?= e($broadcast['url']) ?>"
                                   class="field<?= $hasError ? ' field-error' : '' ?>">
                        </div>

                        <div>
                            <label for="title-<?= e($broadcastId) ?>" class="label">Közvetítés neve</label>
                            <input type="text" id="title-<?= e($broadcastId) ?>" name="title" maxlength="160"
                                   value="<?= e($broadcast['title']) ?>" class="field">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm">Mentés</button>
                    </form>
                </details>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
