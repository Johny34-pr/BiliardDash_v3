<?php
/**
 * Nevezési űrlap nézet
 *
 * Három használati mód:
 *   1. Vendégként (nincs belépés): üres űrlap, a nevezés kötetlen marad
 *   2. Belépve, "magamnak": a fiók adataival előtöltött űrlap
 *   3. Belépve, "másnak": üres űrlap, a nevezés a fiókhoz kötve marad
 *
 * A belépéssel rögzített nevezés a fiókban visszavonható a határidő lejártáig.
 *
 * @var array      $competition    Verseny adatai
 * @var array      $errors         Validációs hibák (mező => üzenet)
 * @var array      $data           Korábban megadott adatok (sticky form)
 * @var bool       $deadlinePassed Lejárt-e a határidő
 * @var bool       $duplicateError Dupla nevezés történt-e
 * @var bool       $success        Sikeres nevezés
 * @var string     $registerFor    'self' vagy 'other'
 * @var array|null $currentUser    A bejelentkezett felhasználó, vagy null
 */

$isDisabled = !empty($deadlinePassed);
$isLoggedIn = $currentUser !== null;
$forSelf = $registerFor === 'self';
$date = new DateTimeImmutable($competition['date']);
$deadline = new DateTimeImmutable($competition['registration_deadline']);

/** Egységes osztálylista egy beviteli mezőhöz, hibaállapot szerint */
$fieldClass = static fn(bool $hasError): string => 'field' . ($hasError ? ' field-error' : '');
?>

<div class="max-w-5xl mx-auto reveal">

    <!-- Visszalépés -->
    <a href="/nevezes" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a versenyekhez
    </a>

    <header class="mb-8">
        <p class="eyebrow mb-3">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            Nevezés
        </p>
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest leading-tight text-billiard-green-900">
            <?= e($competition['name']) ?>
        </h1>
    </header>

    <div class="grid gap-8 lg:grid-cols-5">

        <!-- ============ Bal oldal: űrlap és állapotjelzések ============ -->
        <div class="lg:col-span-3 order-2 lg:order-1">

            <?php if (!empty($success)): ?>
                <div class="alert alert-success mb-6" role="status">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="alert-title">Sikeres nevezés</p>
                        <p class="text-sm mt-0.5">
                            Visszaigazoló e-mailt küldtünk a megadott címre.
                            <?php if ($isLoggedIn): ?>
                                A nevezés a <a href="/fiok" class="font-medium underline">fiókodban</a> is megjelenik.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($isDisabled): ?>
                <div class="alert alert-warning mb-6" role="alert">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <div>
                        <p class="alert-title">A nevezés lezárult</p>
                        <p class="text-sm mt-0.5">A nevezési határidő lejárt, erre a versenyre már nem lehet nevezni.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($duplicateError)): ?>
                <div class="alert alert-error mb-6" role="alert">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <div>
                        <p class="alert-title">Ezzel az e-mail címmel már történt nevezés</p>
                        <p class="text-sm mt-0.5">Erre a versenyre már regisztráltak a megadott e-mail címmel.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error mb-6" role="alert">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <span><?= e($errors['general']) ?></span>
                </div>
            <?php endif; ?>

            <div class="card p-6 md:p-8">

                <?php if ($isLoggedIn && !$isDisabled): ?>
                    <!-- Mód választó: magamnak / másnak -->
                    <div class="mb-6">
                        <p class="label mb-2">Kinek nevezel?</p>
                        <div class="inline-flex p-1 rounded-xl bg-sand-100 border border-sand-200" role="group" aria-label="Nevezés célja">
                            <a href="/nevezes/<?= e($competition['id']) ?>?kinek=magamnak"
                               class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold transition-colors
                                      <?= $forSelf ? 'bg-white text-billiard-green-800 shadow-sm' : 'text-sand-600 hover:text-billiard-green-700' ?>"
                               <?= $forSelf ? 'aria-current="true"' : '' ?>>
                                Magamnak
                            </a>
                            <a href="/nevezes/<?= e($competition['id']) ?>?kinek=masnak"
                               class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold transition-colors
                                      <?= !$forSelf ? 'bg-white text-billiard-green-800 shadow-sm' : 'text-sand-600 hover:text-billiard-green-700' ?>"
                               <?= !$forSelf ? 'aria-current="true"' : '' ?>>
                                Másnak
                            </a>
                        </div>
                        <p class="field-hint">
                            <?= $forSelf
                                ? 'Az űrlapot a fiókod adataival töltöttük elő.'
                                : 'Add meg annak az adatait, aki játszani fog. A visszaigazolást ő kapja meg.' ?>
                        </p>
                    </div>
                <?php endif; ?>

                <h2 class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-1">
                    <?= $isLoggedIn && !$forSelf ? 'A nevező adatai' : 'Nevezési adatok' ?>
                </h2>
                <p class="text-sm text-sand-500 mb-6">
                    A <span class="text-billiard-gold-600 font-semibold">*</span>-gal jelölt mezők kitöltése kötelező.
                </p>

                <form method="POST" action="/nevezes/<?= e($competition['id']) ?>"
                      class="space-y-5" novalidate data-validate
                      <?= $isDisabled ? 'aria-disabled="true"' : '' ?>>

                    <input type="hidden" name="register_for" value="<?= $forSelf ? 'self' : 'other' ?>">

                    <!-- Teljes név -->
                    <div>
                        <label for="full_name" class="label">
                            Teljes név <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                        </label>
                        <input type="text" id="full_name" name="full_name"
                               value="<?= e($data['fullName'] ?? '') ?>"
                               required maxlength="100" autocomplete="<?= $forSelf ? 'name' : 'off' ?>"
                               placeholder="pl. Kovács Péter"
                               class="<?= $fieldClass(!empty($errors['fullName'])) ?>"
                               <?= !empty($errors['fullName']) ? 'aria-describedby="full_name-error" aria-invalid="true"' : '' ?>
                               <?= $isDisabled ? 'disabled' : '' ?>>
                        <?php if (!empty($errors['fullName'])): ?>
                            <p id="full_name-error" class="field-message" role="alert"><?= e($errors['fullName']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- E-mail cím -->
                    <div>
                        <label for="email" class="label">
                            E-mail cím <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                        </label>
                        <input type="email" id="email" name="email"
                               value="<?= e($data['email'] ?? '') ?>"
                               required autocomplete="<?= $forSelf ? 'email' : 'off' ?>"
                               placeholder="pl. nev@example.hu"
                               class="<?= $fieldClass(!empty($errors['email'])) ?>"
                               <?= !empty($errors['email']) ? 'aria-describedby="email-error" aria-invalid="true"' : 'aria-describedby="email-hint"' ?>
                               <?= $isDisabled ? 'disabled' : '' ?>>
                        <?php if (!empty($errors['email'])): ?>
                            <p id="email-error" class="field-message" role="alert"><?= e($errors['email']) ?></p>
                        <?php else: ?>
                            <p id="email-hint" class="field-hint">
                                <?= $isLoggedIn && !$forSelf
                                    ? 'A visszaigazolást erre a címre küldjük.'
                                    : 'Erre a címre küldjük a visszaigazolást.' ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Telefonszám -->
                    <div>
                        <label for="phone" class="label">
                            Telefonszám <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                        </label>
                        <input type="tel" id="phone" name="phone"
                               value="<?= e($data['phone'] ?? '') ?>"
                               required autocomplete="<?= $forSelf ? 'tel' : 'off' ?>"
                               placeholder="pl. +36 30 123 4567"
                               class="<?= $fieldClass(!empty($errors['phone'])) ?>"
                               <?= !empty($errors['phone']) ? 'aria-describedby="phone-error" aria-invalid="true"' : '' ?>
                               <?= $isDisabled ? 'disabled' : '' ?>>
                        <?php if (!empty($errors['phone'])): ?>
                            <p id="phone-error" class="field-message" role="alert"><?= e($errors['phone']) ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Beküldés -->
                    <div class="pt-2">
                        <button type="submit" class="btn btn-primary w-full" <?= $isDisabled ? 'disabled' : '' ?>>
                            <?= $isDisabled ? 'A nevezés lezárult' : 'Nevezés elküldése' ?>
                        </button>
                    </div>
                </form>
            </div>

            <?php if (!$isLoggedIn && !$isDisabled): ?>
                <!-- Vendégként is működik, de fiókkal több lehetőség van -->
                <div class="alert alert-info mt-6">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                    </svg>
                    <div class="text-sm">
                        <p class="alert-title mb-0.5">Nevezhetsz fiók nélkül is</p>
                        <p>
                            Ez az űrlap belépés nélkül is működik.
                            <a href="/belepes?tovabb=<?= urlencode('/nevezes/' . $competition['id']) ?>" class="font-medium underline">Belépve</a>
                            viszont előtöltjük az adataidat, másnak is nevezhetsz, és a nevezést később visszavonhatod.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============ Jobb oldal: verseny összefoglaló ============ -->
        <aside class="lg:col-span-2 order-1 lg:order-2">
            <div class="card overflow-hidden lg:sticky lg:top-24">

                <!-- Dátum sáv -->
                <div class="flex items-center gap-4 px-6 py-5 bg-billiard-green-900 text-white">
                    <span class="grid place-items-center w-12 h-12 rounded-xl bg-white/10 shrink-0">
                        <svg class="w-6 h-6 text-billiard-gold-300" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[0.6875rem] font-semibold uppercase tracking-wider text-billiard-gold-300/80">
                            A verseny napja
                        </p>
                        <p class="font-semibold text-lg tracking-tightest">
                            <time datetime="<?= e($competition['date']) ?>"><?= $date->format('Y. m. d.') ?></time>
                        </p>
                    </div>
                </div>

                <!-- Részletek -->
                <dl class="divide-y divide-sand-200">
                    <div class="px-6 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">Helyszín</dt>
                        <dd class="text-sand-900 font-medium"><?= e($competition['venue']) ?></dd>
                    </div>
                    <div class="px-6 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">Nevezési határidő</dt>
                        <dd class="text-sand-900 font-medium">
                            <time datetime="<?= e($competition['registration_deadline']) ?>">
                                <?= $deadline->format('Y. m. d. H:i') ?>
                            </time>
                            <?php if ($isDisabled): ?>
                                <span class="badge badge-neutral ml-1.5">Lezárult</span>
                            <?php else: ?>
                                <span class="badge badge-green ml-1.5">Nyitott</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <?php if (isset($competition['registrant_count'])): ?>
                        <div class="px-6 py-4">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">Eddigi nevezők</dt>
                            <dd class="text-sand-900 font-medium flex items-center justify-between gap-2">
                                <span><?= (int)$competition['registrant_count'] ?> fő</span>
                                <a href="/nevezes/<?= e($competition['id']) ?>/nevezok"
                                   class="text-sm font-semibold text-billiard-green-600 hover:underline">
                                    Lista
                                </a>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <?php if ($isLoggedIn): ?>
                    <div class="px-6 py-4 bg-sand-50 border-t border-sand-200">
                        <p class="text-xs text-sand-500">
                            Belépve: <span class="font-medium text-sand-700"><?= e($currentUser['name']) ?></span>
                        </p>
                        <a href="/fiok" class="text-sm font-semibold text-billiard-green-600 hover:underline">
                            Nevezéseim
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
