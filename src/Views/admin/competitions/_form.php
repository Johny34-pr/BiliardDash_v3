<?php
/**
 * Admin verseny űrlap mezői - közös részlet
 *
 * A létrehozó és szerkesztő nézet is ezt tölti be, így a mezők, validációs
 * hibák és súgók egy helyen módosíthatók.
 *
 * @var string    $formAction         Az űrlap action attribútuma
 * @var string    $submitLabel        A beküldő gomb felirata
 * @var array     $errors             Validációs hibák
 * @var array     $data               Űrlap adatok (name, date, venue, registrationOpensAt, registrationDeadline, seasonId)
 * @var array     $seasonOptions      Szezonok: azonosító => megjelenő név
 * @var bool|null $showNotifyOption   Megjelenjen-e az "értesítés kiküldése" mező
 * @var int|null  $recipientCount     Hány tag kapná meg az értesítést
 */
$seasonOptions = $seasonOptions ?? [];
$selectedSeason = (string) ($data['seasonId'] ?? '');
?>
<form method="POST" action="<?= e($formAction) ?>" class="card p-6 md:p-8 space-y-6" novalidate data-validate>

    <!-- Verseny neve -->
    <div>
        <label for="name" class="label">
            Verseny neve <span class="text-billiard-gold-600" aria-hidden="true">*</span>
        </label>
        <input type="text" id="name" name="name" maxlength="100" required
               value="<?= e($data['name'] ?? '') ?>"
               placeholder="pl. Országos Egyéni Bajnokság"
               class="field<?= isset($errors['name']) ? ' field-error' : '' ?>"
               <?= isset($errors['name']) ? 'aria-describedby="name-error" aria-invalid="true"' : '' ?>>
        <?php if (isset($errors['name'])): ?>
            <p id="name-error" class="field-message" role="alert"><?= e($errors['name']) ?></p>
        <?php else: ?>
            <p class="field-hint">Legfeljebb 100 karakter.</p>
        <?php endif; ?>
    </div>

    <!-- Verseny dátuma -->
    <div class="sm:max-w-xs">
        <label for="date" class="label">
            Verseny dátuma <span class="text-billiard-gold-600" aria-hidden="true">*</span>
        </label>
        <input type="date" id="date" name="date" required
               value="<?= e($data['date'] ?? '') ?>"
               class="field<?= isset($errors['date']) ? ' field-error' : '' ?>"
               <?= isset($errors['date']) ? 'aria-describedby="date-error" aria-invalid="true"' : '' ?>>
        <?php if (isset($errors['date'])): ?>
            <p id="date-error" class="field-message" role="alert"><?= e($errors['date']) ?></p>
        <?php endif; ?>
    </div>

    <!--
        Szezon
        Ez dönti el, melyik ranglistába számítanak be a verseny pontjai: a
        RankingEntry::findBySeason() a verseny season_id-je alapján szűr.
        Szezon nélkül a felvitt pontszámok egyetlen ranglistán sem jelennek
        meg - ezért van kiírva a mező alatt.

        Az archivált szezonok is választhatók: a korábbi évek versenyeit
        utólag kell a megfelelő, már lezárt szezonhoz rendelni, különben a
        régi ranglisták üresen maradnának.
    -->
    <div class="sm:max-w-sm">
        <label for="seasonId" class="label">Szezon</label>

        <?php if ($seasonOptions === []): ?>
            <p class="field-hint">
                Még nincs szezon.
                <a href="/admin/szezonok" class="font-medium text-billiard-green-700 underline">
                    Hozz létre egyet
                </a>, hogy a verseny pontjai bekerülhessenek a ranglistába.
            </p>
        <?php else: ?>
            <select id="seasonId" name="seasonId"
                    class="field<?= isset($errors['seasonId']) ? ' field-error' : '' ?>"
                    <?= isset($errors['seasonId'])
                         ? 'aria-describedby="season-error" aria-invalid="true"'
                         : 'aria-describedby="season-hint"' ?>>
                <option value="">Nincs szezon (nem számít a ranglistába)</option>
                <?php foreach ($seasonOptions as $seasonId => $seasonLabel): ?>
                    <option value="<?= e((string) $seasonId) ?>"
                            <?= $selectedSeason === (string) $seasonId ? 'selected' : '' ?>>
                        <?= e($seasonLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (isset($errors['seasonId'])): ?>
                <p id="season-error" class="field-message" role="alert"><?= e($errors['seasonId']) ?></p>
            <?php else: ?>
                <p id="season-hint" class="field-hint">
                    A verseny pontszámai ennek a szezonnak a ranglistájába
                    számítanak be. Szezon nélkül a pontok nem jelennek meg
                    egyetlen ranglistán sem.
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!--
        Nevezési időablak: mikortól és meddig lehet nevezni.
        A nyitódátum nem kötelező - üresen hagyva a nevezés a kiírástól
        azonnal nyitott, ez a korábbi működés.
    -->
    <fieldset class="pt-2 border-t border-sand-200">
        <legend class="text-sm font-semibold text-billiard-green-900 pt-4 pb-1">Nevezési időablak</legend>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="registrationOpensAt" class="label">Nevezés nyitása</label>
                <input type="datetime-local" id="registrationOpensAt" name="registrationOpensAt"
                       value="<?= e($data['registrationOpensAt'] ?? '') ?>"
                       class="field<?= isset($errors['registrationOpensAt']) ? ' field-error' : '' ?>"
                       <?= isset($errors['registrationOpensAt']) ? 'aria-describedby="opens-error" aria-invalid="true"' : 'aria-describedby="opens-hint"' ?>>
                <?php if (isset($errors['registrationOpensAt'])): ?>
                    <p id="opens-error" class="field-message" role="alert"><?= e($errors['registrationOpensAt']) ?></p>
                <?php else: ?>
                    <p id="opens-hint" class="field-hint">
                        Ettől az időponttól lehet nevezni. Üresen hagyva a nevezés azonnal nyitott,
                        a verseny addig is látszik a listában.
                    </p>
                <?php endif; ?>
            </div>

            <div>
                <label for="registrationDeadline" class="label">
                    Nevezési határidő <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                </label>
                <input type="datetime-local" id="registrationDeadline" name="registrationDeadline" required
                       value="<?= e($data['registrationDeadline'] ?? '') ?>"
                       class="field<?= isset($errors['registrationDeadline']) ? ' field-error' : '' ?>"
                       <?= isset($errors['registrationDeadline']) ? 'aria-describedby="deadline-error" aria-invalid="true"' : 'aria-describedby="deadline-hint"' ?>>
                <?php if (isset($errors['registrationDeadline'])): ?>
                    <p id="deadline-error" class="field-message" role="alert"><?= e($errors['registrationDeadline']) ?></p>
                <?php else: ?>
                    <p id="deadline-hint" class="field-hint">Eddig lehet online nevezni.</p>
                <?php endif; ?>
            </div>
        </div>
    </fieldset>

    <!-- Helyszín -->
    <div>
        <label for="venue" class="label">
            Helyszín <span class="text-billiard-gold-600" aria-hidden="true">*</span>
        </label>
        <input type="text" id="venue" name="venue" maxlength="200" required
               value="<?= e($data['venue'] ?? '') ?>"
               placeholder="pl. Budapest, Biliárd Aréna"
               class="field<?= isset($errors['venue']) ? ' field-error' : '' ?>"
               <?= isset($errors['venue']) ? 'aria-describedby="venue-error" aria-invalid="true"' : '' ?>>
        <?php if (isset($errors['venue'])): ?>
            <p id="venue-error" class="field-message" role="alert"><?= e($errors['venue']) ?></p>
        <?php else: ?>
            <p class="field-hint">Legfeljebb 200 karakter.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($showNotifyOption)): ?>
        <!--
            Értesítés a tagoknak
            Csak a LÉTREHOZÓ űrlapon jelenik meg: a szerkesztésnél egy
            bejelölt mező azt sugallná, hogy minden mentés újra körlevelet
            küld. Utólag a versenylistából küldhető ki.

            A mező alapból be van jelölve, mert a versenykiírás rendes menete
            az, hogy a tagok értesülnek róla.
        -->
        <div class="pt-2 border-t border-sand-200">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="notify" value="1" checked
                       class="mt-1 w-5 h-5 rounded border-sand-300 text-billiard-green-600"
                       aria-describedby="notify-hint">
                <span>
                    <span class="font-medium text-sand-900">Értesítés kiküldése a tagoknak</span>
                    <span id="notify-hint" class="block text-sm text-sand-500 mt-0.5">
                        <?php if (!empty($recipientCount)): ?>
                            <?= (int) $recipientCount ?> regisztrált tag kap e-mailt a versenykiírásról.
                        <?php else: ?>
                            Jelenleg nincs regisztrált tag, akinek ki lehetne küldeni.
                        <?php endif; ?>
                        A nevezés megnyílásáról külön levél megy ki.
                    </span>
                </span>
            </label>
        </div>
    <?php endif; ?>

    <!-- Műveletek -->
    <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-sand-200">
        <button type="submit" class="btn btn-primary mt-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            <?= e($submitLabel) ?>
        </button>
        <a href="/admin/versenyek" class="btn btn-ghost mt-4">Mégse</a>
    </div>
</form>
