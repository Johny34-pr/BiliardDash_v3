<?php
/**
 * Nevezési űrlap nézet
 *
 * A nevezés belépéshez kötött, és mindenki a saját nevében nevez. Ezért
 * három állapota van:
 *
 *   1. Vendégként: az űrlap helyett belépésre hívó tájékoztatás. A verseny
 *      adatai láthatók maradnak, mert a versenykiírás nyilvános információ.
 *   2. Belépve, még nem nevezett: a fiók neve és e-mail címe csak olvasható
 *      módon jelenik meg, szerkeszthető egyedül a telefonszám.
 *   3. Belépve, már nevezett: visszajelzés és hivatkozás a fiókra, ahol a
 *      nevezés a határidő lejártáig visszavonható.
 *
 * Aki más helyett szeretne nevezni, a szervezőt kéri meg: a szervezői
 * felületen bárki felvihető, fiók nélküli játékos is.
 *
 * @var array      $competition        Verseny adatai
 * @var array      $errors             Validációs hibák (mező => üzenet)
 * @var array      $data               Korábban megadott adatok (sticky form)
 * @var bool       $deadlinePassed     Lejárt-e a határidő
 * @var bool       $registrationOpened Megnyílt-e már a nevezés
 * @var bool       $duplicateError     Dupla nevezés történt-e
 * @var bool       $alreadyRegistered  Nevezett-e már ez a fiók
 * @var bool       $success            Sikeres nevezés
 * @var array|null $currentUser        A bejelentkezett felhasználó, vagy null
 */

// A nevezés két okból lehet zárt: még nem nyílt meg, vagy már lejárt.
// Az űrlap mindkét esetben tiltott, de más magyarázattal.
$notYetOpen = !($registrationOpened ?? true);
$isDisabled = !empty($deadlinePassed) || $notYetOpen;
$isLoggedIn = $currentUser !== null;
$hasRegistered = !empty($alreadyRegistered);
$date = new DateTimeImmutable($competition['date']);
$deadline = new DateTimeImmutable($competition['registration_deadline']);
$opensAt = !empty($competition['registration_opens_at'])
    ? new DateTimeImmutable($competition['registration_opens_at'])
    : null;

// A belépés után ide térjen vissza a látogató
$loginTarget = '/belepes?tovabb=' . urlencode('/nevezes/' . $competition['id']);

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
                            Visszaigazoló e-mailt küldtünk a fiókodhoz tartozó címre.
                            A nevezés a <a href="/fiok" class="font-medium underline">fiókodban</a> is megjelenik,
                            és a határidő lejártáig visszavonható.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($notYetOpen): ?>
                <div class="alert alert-info mb-6" role="status">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="alert-title">A nevezés még nem nyílt meg</p>
                        <p class="text-sm mt-0.5">
                            <?php if ($opensAt !== null): ?>
                                A nevezés
                                <time datetime="<?= e($competition['registration_opens_at']) ?>" class="font-semibold">
                                    <?= $opensAt->format('Y. m. d. H:i') ?>
                                </time>
                                időpontban nyílik meg. Térj vissza akkor, és add le a nevezésedet.
                            <?php else: ?>
                                Erre a versenyre egyelőre nem lehet nevezni.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php elseif ($isDisabled): ?>
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

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error mb-6" role="alert">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <span><?= e($errors['general']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!$isLoggedIn): ?>
                <!--
                    Vendég: a nevezés belépéshez kötött. Az űrlap helyett a
                    belépés útját mutatjuk, mert egy kitöltött, majd
                    elutasított űrlap csak elvesztegetett munka lenne.
                -->
                <div class="card p-6 md:p-8">
                    <span class="grid place-items-center w-12 h-12 rounded-xl bg-billiard-green-50 text-billiard-green-700 mb-4" aria-hidden="true">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                    </span>

                    <h2 class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-2">
                        A nevezéshez belépés kell
                    </h2>
                    <p class="text-sand-600 leading-relaxed mb-5">
                        A nevezés a fiókodhoz kötődik: így a neved egyértelmű a
                        nevezői listán, a nevezésed pedig megjelenik a
                        fiókodban, ahol a határidő lejártáig visszavonhatod.
                    </p>

                    <div class="flex flex-wrap gap-3">
                        <a href="<?= e($loginTarget) ?>" class="btn btn-primary">
                            Belépés
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </a>
                        <a href="/regisztracio" class="btn btn-secondary">Új fiók létrehozása</a>
                    </div>

                    <p class="field-hint mt-5">
                        Nincs fiókod, és nem is szeretnél? Keresd a versenyszervezőt a
                        <a href="/tarshonlapok" class="font-medium text-billiard-green-600 hover:underline">kapcsolat oldalon</a> -
                        ő fiók nélkül is fel tud venni a nevezők közé.
                    </p>
                </div>

            <?php elseif ($hasRegistered): ?>
                <!-- Már nevezett ez a fiók: a nevezés a fiókban kezelhető -->
                <div class="card p-6 md:p-8">
                    <span class="grid place-items-center w-12 h-12 rounded-xl bg-billiard-green-50 text-billiard-green-700 mb-4" aria-hidden="true">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>

                    <h2 class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-2">
                        Erre a versenyre már neveztél
                    </h2>
                    <p class="text-sand-600 leading-relaxed mb-5">
                        A nevezésed rögzítve van. Egy fiókkal versenyenként egyszer
                        lehet nevezni, ezért új nevezést nem tudsz leadni.
                    </p>

                    <div class="flex flex-wrap gap-3">
                        <a href="/fiok" class="btn btn-primary">Nevezéseim</a>
                        <a href="/nevezes/<?= e($competition['id']) ?>/nevezok" class="btn btn-secondary">
                            Nevezői lista
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div class="card p-6 md:p-8">

                    <h2 class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-1">
                        Nevezési adatok
                    </h2>
                    <p class="text-sm text-sand-500 mb-6">
                        A neved és az e-mail címed a fiókodból származik. Ha
                        változtatnál rajtuk, azt a fiókodban tedd meg.
                    </p>

                    <!--
                        A név és az e-mail cím csak olvasható: mindenki a saját
                        nevében nevez, ezért ezek az adatok nem az űrlapról,
                        hanem a fiókból kerülnek a nevezésre. Nem disabled
                        input, hanem egyszerű szöveg - így nem tűnik úgy,
                        mintha csak ideiglenesen lennének tiltva.
                    -->
                    <dl class="rounded-xl bg-sand-50 border border-sand-200 divide-y divide-sand-200 mb-5">
                        <div class="flex items-baseline justify-between gap-4 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500">Nevező</dt>
                            <dd class="font-semibold text-sand-900 text-right"><?= e($currentUser['name']) ?></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500">E-mail</dt>
                            <dd class="text-sand-900 text-right break-all"><?= e($currentUser['email']) ?></dd>
                        </div>
                    </dl>

                    <form method="POST" action="/nevezes/<?= e($competition['id']) ?>"
                          class="space-y-5" novalidate data-validate
                          <?= $isDisabled ? 'aria-disabled="true"' : '' ?>>

                        <!-- Telefonszám: az egyetlen szerkeszthető mező -->
                        <div>
                            <label for="phone" class="label">
                                Telefonszám <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                            </label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= e($data['phone'] ?? '') ?>"
                                   required autocomplete="tel"
                                   placeholder="pl. +36 30 123 4567"
                                   class="<?= $fieldClass(!empty($errors['phone'])) ?>"
                                   <?= !empty($errors['phone'])
                                        ? 'aria-describedby="phone-error" aria-invalid="true"'
                                        : 'aria-describedby="phone-hint"' ?>
                                   <?= $isDisabled ? 'disabled' : '' ?>>
                            <?php if (!empty($errors['phone'])): ?>
                                <p id="phone-error" class="field-message" role="alert"><?= e($errors['phone']) ?></p>
                            <?php else: ?>
                                <p id="phone-hint" class="field-hint">
                                    A verseny napján elérhető számot add meg. A fiókodban tárolt
                                    számot töltöttük elő, de itt módosíthatod.
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Beküldés -->
                        <div class="pt-2">
                            <button type="submit" class="btn btn-primary w-full" <?= $isDisabled ? 'disabled' : '' ?>>
                                <?php if ($notYetOpen): ?>
                                    A nevezés még nem nyílt meg
                                <?php elseif ($isDisabled): ?>
                                    A nevezés lezárult
                                <?php else: ?>
                                    Nevezés elküldése
                                <?php endif; ?>
                            </button>
                        </div>
                    </form>

                    <p class="field-hint mt-5 pt-5 border-t border-sand-200">
                        Más helyett nem tudsz nevezni. Ha valakinek nincs fiókja,
                        a versenyszervező fel tudja venni a nevezők közé -
                        elérhetőségei a
                        <a href="/tarshonlapok" class="font-medium text-billiard-green-600 hover:underline">kapcsolat oldalon</a>.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============ Jobb oldal: verseny összefoglaló ============ -->
        <aside class="lg:col-span-2 order-1 lg:order-2">
            <div class="card overflow-hidden sticky-under-header">

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
                    <?php if ($opensAt !== null): ?>
                        <div class="px-6 py-4">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">Nevezés nyitása</dt>
                            <dd class="text-sand-900 font-medium">
                                <time datetime="<?= e($competition['registration_opens_at']) ?>">
                                    <?= $opensAt->format('Y. m. d. H:i') ?>
                                </time>
                            </dd>
                        </div>
                    <?php endif; ?>
                    <div class="px-6 py-4">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">Nevezési határidő</dt>
                        <dd class="text-sand-900 font-medium">
                            <time datetime="<?= e($competition['registration_deadline']) ?>">
                                <?= $deadline->format('Y. m. d. H:i') ?>
                            </time>
                            <?php if ($notYetOpen): ?>
                                <span class="badge badge-gold ml-1.5">Hamarosan</span>
                            <?php elseif ($isDisabled): ?>
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
