<?php
/**
 * Regisztráció - publikus felhasználói fiók
 *
 * @var array $errors Validációs hibák
 * @var array $data   Korábban megadott adatok (sticky form, jelszó nélkül)
 */

use App\Services\AuthService;

$minPassword = AuthService::MIN_PASSWORD_LENGTH;
?>
<div class="max-w-md mx-auto reveal">

    <header class="mb-7 text-center">
        <p class="eyebrow justify-center mb-3">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            Látogatói fiók
        </p>
        <h1 class="text-3xl font-bold tracking-tightest text-billiard-green-900">Regisztráció</h1>
        <p class="text-sand-500 mt-2">
            A megadott adatokkal töltjük elő a nevezési űrlapot.
        </p>
    </header>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error mb-6" role="alert">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span><?= e($errors['general']) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/regisztracio" class="card p-6 md:p-8 space-y-5" novalidate>

        <div>
            <label for="name" class="label">
                Teljes név <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="text" id="name" name="name" maxlength="100"
                   value="<?= e($data['name'] ?? '') ?>"
                   required autocomplete="name" autofocus
                   placeholder="pl. Kovács Péter"
                   class="field<?= isset($errors['name']) ? ' field-error' : '' ?>"
                   <?= isset($errors['name']) ? 'aria-describedby="name-error" aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['name'])): ?>
                <p id="name-error" class="field-message" role="alert"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="email" class="label">
                E-mail cím <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="email" id="email" name="email" maxlength="255"
                   value="<?= e($data['email'] ?? '') ?>"
                   required autocomplete="email"
                   placeholder="pl. nev@example.hu"
                   class="field<?= isset($errors['email']) ? ' field-error' : '' ?>"
                   <?= isset($errors['email']) ? 'aria-describedby="email-error" aria-invalid="true"' : 'aria-describedby="email-hint"' ?>>
            <?php if (isset($errors['email'])): ?>
                <p id="email-error" class="field-message" role="alert"><?= e($errors['email']) ?></p>
            <?php else: ?>
                <p id="email-hint" class="field-hint">Ezzel fogsz belépni.</p>
            <?php endif; ?>
        </div>

        <div>
            <label for="phone" class="label">
                Telefonszám <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="tel" id="phone" name="phone" maxlength="50"
                   value="<?= e($data['phone'] ?? '') ?>"
                   required autocomplete="tel"
                   placeholder="pl. +36 30 123 4567"
                   class="field<?= isset($errors['phone']) ? ' field-error' : '' ?>"
                   <?= isset($errors['phone']) ? 'aria-describedby="phone-error" aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['phone'])): ?>
                <p id="phone-error" class="field-message" role="alert"><?= e($errors['phone']) ?></p>
            <?php endif; ?>
        </div>

        <div class="pt-1 border-t border-sand-200"></div>

        <div>
            <label for="password" class="label">
                Jelszó <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="password" id="password" name="password"
                   required autocomplete="new-password"
                   placeholder="••••••••"
                   class="field<?= isset($errors['password']) ? ' field-error' : '' ?>"
                   <?= isset($errors['password']) ? 'aria-describedby="password-error" aria-invalid="true"' : 'aria-describedby="password-hint"' ?>>
            <?php if (isset($errors['password'])): ?>
                <p id="password-error" class="field-message" role="alert"><?= e($errors['password']) ?></p>
            <?php else: ?>
                <p id="password-hint" class="field-hint">Legalább <?= $minPassword ?> karakter.</p>
            <?php endif; ?>
        </div>

        <div>
            <label for="password_confirm" class="label">
                Jelszó megerősítése <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="password" id="password_confirm" name="password_confirm"
                   required autocomplete="new-password"
                   placeholder="••••••••"
                   class="field<?= isset($errors['passwordConfirm']) ? ' field-error' : '' ?>"
                   <?= isset($errors['passwordConfirm']) ? 'aria-describedby="password-confirm-error" aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['passwordConfirm'])): ?>
                <p id="password-confirm-error" class="field-message" role="alert"><?= e($errors['passwordConfirm']) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-full">Fiók létrehozása</button>
    </form>

    <p class="text-center text-sm text-sand-500 mt-6">
        Van már fiókod?
        <a href="/belepes" class="font-semibold text-billiard-green-600 hover:underline">Léptem be</a>
    </p>
</div>
