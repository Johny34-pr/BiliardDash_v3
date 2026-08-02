<?php
/**
 * Admin verseny űrlap mezői - közös részlet
 *
 * A létrehozó és szerkesztő nézet is ezt tölti be, így a mezők, validációs
 * hibák és súgók egy helyen módosíthatók.
 *
 * @var string $formAction  Az űrlap action attribútuma
 * @var string $submitLabel A beküldő gomb felirata
 * @var array  $errors      Validációs hibák
 * @var array  $data        Űrlap adatok (name, date, venue, registrationDeadline)
 */
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

    <!-- Dátum és határidő egymás mellett asztali nézetben -->
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
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
