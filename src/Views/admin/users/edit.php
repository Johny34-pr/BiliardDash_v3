<?php
/**
 * Admin fiók szerkesztése
 *
 * A jelszó mező üresen hagyható: ilyenkor a meglévő jelszó marad érvényben.
 *
 * @var array $user   A szerkesztett fiók (id, name, created_at)
 * @var array $errors Validációs hibák
 * @var array $data   Űrlap adatok (name, email, phone, city)
 */
?>
<div class="max-w-2xl">

    <a href="/admin/felhasznalok" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a felhasználókhoz
    </a>

    <header class="mb-7">
        <p class="eyebrow mb-2">Szerkesztés</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
            <?= e($user['name']) ?>
        </h1>
        <p class="text-sand-500 mt-1">
            Regisztrált:
            <time datetime="<?= e($user['created_at']) ?>">
                <?= date('Y. m. d.', strtotime($user['created_at'])) ?>
            </time>
        </p>
    </header>

    <?php if (isset($errors['general'])): ?>
        <div class="alert alert-error mb-6" role="alert">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span><?= e($errors['general']) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/admin/felhasznalok/<?= e($user['id']) ?>/szerkeszt"
          class="card p-6 md:p-8 space-y-5">

        <!-- Név -->
        <div>
            <label for="name" class="label">
                Név <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="text" id="name" name="name" maxlength="100" required
                   value="<?= e($data['name'] ?? '') ?>"
                   class="field<?= isset($errors['name']) ? ' field-error' : '' ?>"
                   <?= isset($errors['name']) ? 'aria-describedby="name-error" aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['name'])): ?>
                <p id="name-error" class="field-message" role="alert"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <!-- E-mail -->
        <div>
            <label for="email" class="label">
                E-mail cím <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="email" id="email" name="email" maxlength="255" required
                   value="<?= e($data['email'] ?? '') ?>"
                   class="field<?= isset($errors['email']) ? ' field-error' : '' ?>"
                   <?= isset($errors['email']) ? 'aria-describedby="email-error" aria-invalid="true"' : 'aria-describedby="email-hint"' ?>>
            <?php if (isset($errors['email'])): ?>
                <p id="email-error" class="field-message" role="alert"><?= e($errors['email']) ?></p>
            <?php else: ?>
                <p id="email-hint" class="field-hint">Ezzel a címmel lép be a felhasználó.</p>
            <?php endif; ?>
        </div>

        <!-- Telefon -->
        <div>
            <label for="phone" class="label">
                Telefonszám <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="tel" id="phone" name="phone" maxlength="50" required
                   value="<?= e($data['phone'] ?? '') ?>"
                   class="field<?= isset($errors['phone']) ? ' field-error' : '' ?>"
                   <?= isset($errors['phone']) ? 'aria-describedby="phone-error" aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['phone'])): ?>
                <p id="phone-error" class="field-message" role="alert"><?= e($errors['phone']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Település -->
        <div>
            <label for="city" class="label">
                Település <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="text" id="city" name="city" maxlength="100" required
                   value="<?= e($data['city'] ?? '') ?>"
                   placeholder="pl. Okány"
                   class="field<?= isset($errors['city']) ? ' field-error' : '' ?>"
                   <?= isset($errors['city']) ? 'aria-describedby="city-error" aria-invalid="true"' : 'aria-describedby="city-hint"' ?>>
            <?php if (isset($errors['city'])): ?>
                <p id="city-error" class="field-message" role="alert"><?= e($errors['city']) ?></p>
            <?php else: ?>
                <p id="city-hint" class="field-hint">
                    A régebbi fiókoknál üres lehet, mert a regisztrációkor még nem volt kötelező.
                </p>
            <?php endif; ?>
        </div>

        <div class="pt-1 border-t border-sand-200"></div>

        <!--
            Jelszó csere. Üresen hagyva a meglévő jelszó marad. Új jelszó
            megadása minden eszközön megszünteti a megjegyzett belépést.
        -->
        <div>
            <label for="password" class="label">Új jelszó</label>
            <input type="password" id="password" name="password" autocomplete="new-password"
                   placeholder="Hagyd üresen, ha nem változik"
                   class="field<?= isset($errors['password']) ? ' field-error' : '' ?>"
                   <?= isset($errors['password']) ? 'aria-describedby="password-error" aria-invalid="true"' : 'aria-describedby="password-hint"' ?>>
            <?php if (isset($errors['password'])): ?>
                <p id="password-error" class="field-message" role="alert"><?= e($errors['password']) ?></p>
            <?php else: ?>
                <p id="password-hint" class="field-hint">
                    Legalább <?= \App\Services\AuthService::MIN_PASSWORD_LENGTH ?> karakter.
                    Új jelszó esetén a felhasználó minden eszközön kilép.
                </p>
            <?php endif; ?>
        </div>

        <div>
            <label for="password_confirm" class="label">Új jelszó megerősítése</label>
            <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password"
                   placeholder="••••••••"
                   class="field<?= isset($errors['passwordConfirm']) ? ' field-error' : '' ?>"
                   <?= isset($errors['passwordConfirm']) ? 'aria-describedby="password-confirm-error" aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['passwordConfirm'])): ?>
                <p id="password-confirm-error" class="field-message" role="alert"><?= e($errors['passwordConfirm']) ?></p>
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
            <a href="/admin/felhasznalok" class="btn btn-ghost mt-4">Mégse</a>
        </div>
    </form>

    <!--
        Jelszó visszaállítása
        Külön űrlap a fenti adatmentéstől, mert más a kimenete: itt a
        rendszer MEGMUTATJA a beállított jelszót, hogy a szervező át tudja
        adni. A fenti mezőpár ezzel szemben csendben cserél jelszót.

        A mező üresen hagyható: ilyenkor a rendszer generál egyet. Saját
        jelszót akkor érdemes írni, ha telefonon kell lediktálni - egy
        véletlen karaktersort nehéz félreértés nélkül átadni.
    -->
    <section class="mt-10" aria-labelledby="reset-heading">
        <h2 id="reset-heading" class="text-xl font-semibold tracking-tightest text-billiard-green-900 mb-2">
            Jelszó visszaállítása
        </h2>
        <p class="text-sm text-sand-500 mb-4 max-w-reading">
            A beállított jelszót a mentés után <strong>egyszer megmutatjuk</strong>,
            hogy át tudd adni. A tag minden eszközén kilép, és e-mailben
            értesítést kap a változásról &ndash; a jelszót az e-mail nem
            tartalmazza.
        </p>

        <div class="card p-6">
            <form method="POST" action="/admin/felhasznalok/<?= e($user['id']) ?>/jelszo"
                  class="space-y-4 max-w-md"
                  data-confirm="Új jelszót állítunk be <?= e($user['name']) ?> fiókjához. A mostani jelszava azonnal érvénytelen lesz. Folytatod?">
                <div>
                    <label for="reset_password" class="label">Saját jelszó (nem kötelező)</label>
                    <input type="password" id="reset_password" name="password"
                           autocomplete="new-password"
                           minlength="<?= \App\Services\AuthService::MIN_PASSWORD_LENGTH ?>"
                           placeholder="Hagyd üresen a generáláshoz"
                           class="field" aria-describedby="reset_password-hint">
                    <p id="reset_password-hint" class="field-hint">
                        Legalább <?= \App\Services\AuthService::MIN_PASSWORD_LENGTH ?> karakter.
                        Üresen hagyva a rendszer könnyen diktálható jelszót generál,
                        amelyből kimaradnak az összekeverhető karakterek (0/O, 1/l/I).
                    </p>
                </div>

                <button type="submit" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                    Jelszó beállítása
                </button>
            </form>
        </div>
    </section>
</div>
