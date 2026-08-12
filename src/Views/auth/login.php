<?php
/**
 * Belépés - publikus felhasználói fiók
 *
 * @var array $errors Validációs és hitelesítési hibák
 * @var array $data   Korábban megadott adatok (sticky form)
 */
?>
<div class="max-w-md mx-auto reveal">

    <header class="mb-7 text-center">
        <p class="eyebrow justify-center mb-3">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            Látogatói fiók
        </p>
        <h1 class="text-3xl font-bold tracking-tightest text-billiard-green-900">Belépés</h1>
        <p class="text-sand-500 mt-2">
            Belépve gyorsabban nevezhetsz, és később visszavonhatod a nevezéseidet.
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

    <form method="POST" action="/belepes" class="card p-6 md:p-8 space-y-5" novalidate>

        <div>
            <label for="email" class="label">E-mail cím</label>
            <input type="email" id="email" name="email"
                   value="<?= e($data['email'] ?? '') ?>"
                   required autocomplete="email" autofocus
                   placeholder="pl. nev@example.hu"
                   class="field<?= isset($errors['email']) ? ' field-error' : '' ?>"
                   <?= isset($errors['email']) ? 'aria-describedby="email-error" aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['email'])): ?>
                <p id="email-error" class="field-message" role="alert"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="password" class="label">Jelszó</label>
            <input type="password" id="password" name="password"
                   required autocomplete="current-password"
                   placeholder="••••••••"
                   class="field<?= isset($errors['password']) ? ' field-error' : '' ?>"
                   <?= isset($errors['password']) ? 'aria-describedby="password-error" aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['password'])): ?>
                <p id="password-error" class="field-message" role="alert"><?= e($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary w-full">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25"/>
            </svg>
            Belépés
        </button>
    </form>

    <p class="text-center text-sm text-sand-500 mt-6">
        Még nincs fiókod?
        <a href="/regisztracio" class="font-semibold text-billiard-green-600 hover:underline">Regisztrálj</a>
    </p>

    <!-- A vendégnevezés továbbra is elérhető marad -->
    <div class="alert alert-info mt-6">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <div class="text-sm">
            <p class="alert-title mb-0.5">Fiók nélkül is nevezhetsz</p>
            <p>
                A <a href="/nevezes" class="font-medium underline">versenyekre</a> belépés nélkül is
                le tudod adni a nevezést. Fiókkal viszont visszavonhatod, és másnak is nevezhetsz.
            </p>
        </div>
    </div>

    <!-- Elhatárolás a szervezői hozzáféréstől, hogy ne keveredjen össze -->
    <div class="mt-8 pt-6 border-t border-sand-200 text-center">
        <p class="text-sm text-sand-500">
            Szervező vagy? A tartalomkezeléshez
            <a href="/admin/login" class="font-semibold text-billiard-green-600 hover:underline">szervezői belépés</a>
            szükséges, külön jelszóval.
        </p>
    </div>
</div>
