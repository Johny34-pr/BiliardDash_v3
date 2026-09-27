<?php
/**
 * Admin verseny nevezéseinek listája
 *
 * Itt lehet bárkit nevezni: a látogatók csak a saját nevükben nevezhetnek,
 * a szervező viszont a klub bármely tagját, illetve fiók nélküli játékost is
 * felvehet a névsorba.
 *
 * @var array $competition   Verseny adatok (id, name, date, venue, registrant_count)
 * @var array $registrations Nevezések tömbje (full_name, email, phone, registered_at)
 * @var array $users         Még nem nevezett fiókok (id, name, email, city)
 * @var array $errors        Az előző felvitel hibái (mező => üzenet)
 * @var array $data          Az előző felvitel adatai (sticky form)
 */

/** Egységes osztálylista egy beviteli mezőhöz, hibaállapot szerint */
$fieldClass = static fn(bool $hasError): string => 'field' . ($hasError ? ' field-error' : '');
?>
<a href="/admin/versenyek" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
    </svg>
    Vissza a versenyekhez
</a>

<header class="flex flex-wrap items-end justify-between gap-4 mb-7">
    <div class="min-w-0">
        <p class="eyebrow mb-2">Nevezések</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
            <?= e($competition['name']) ?>
        </h1>
        <dl class="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-sm text-sand-500">
            <div class="inline-flex gap-1.5">
                <dt>Dátum:</dt>
                <dd class="font-medium text-sand-700">
                    <time datetime="<?= e($competition['date']) ?>">
                        <?= date('Y. m. d.', strtotime($competition['date'])) ?>
                    </time>
                </dd>
            </div>
            <div class="inline-flex gap-1.5">
                <dt>Helyszín:</dt>
                <dd class="font-medium text-sand-700"><?= e($competition['venue']) ?></dd>
            </div>
            <div class="inline-flex gap-1.5">
                <dt>Összesen:</dt>
                <dd class="font-medium text-sand-700"><?= (int)$competition['registrant_count'] ?> nevező</dd>
            </div>
        </dl>
    </div>

    <?php if (!empty($registrations)): ?>
        <a href="/admin/versenyek/<?= e($competition['id']) ?>/export" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            CSV export
        </a>
    <?php endif; ?>
</header>

<!--
    Nevezés hozzáadása
    Natív <details>, tehát JavaScript nélkül is nyitható. Hiba után nyitva
    marad, hogy a hibaüzenet és a beírt adatok ne tűnjenek el a szem elől.
-->
<section class="card p-6 mb-7">
    <details <?= ($errors !== [] ? 'open' : '') ?>>
        <summary class="btn btn-primary w-fit">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Nevezés hozzáadása
        </summary>

        <p class="text-sm text-sand-500 mt-4">
            A látogatók csak a saját nevükben nevezhetnek. Szervezőként bárkit
            felvehetsz: a klub tagjait a listából, fiók nélküli játékost pedig
            kézzel.
        </p>

        <div class="grid gap-6 lg:grid-cols-2 mt-5">

            <!-- 1. Klub tagja: a nevezés a fiókjához kötődik -->
            <form method="POST" action="/admin/versenyek/<?= e($competition['id']) ?>/nevezesek"
                  class="p-4 rounded-xl bg-sand-50 border border-sand-200">
                <input type="hidden" name="mode" value="member">

                <h2 class="font-semibold text-billiard-green-900 mb-1">A klub tagja</h2>
                <p class="text-sm text-sand-500 mb-4">
                    A nevezés a fiókjához kötődik, ezért a tag a saját fiókjában
                    is látja, és a határidőig visszavonhatja.
                </p>

                <?php if ($users === []): ?>
                    <p class="field-hint">
                        Minden regisztrált tag nevezett már erre a versenyre,
                        vagy még nincs regisztrált fiók.
                    </p>
                <?php else: ?>
                    <label for="user_id" class="label">Tag kiválasztása</label>
                    <select id="user_id" name="user_id" required
                            class="<?= $fieldClass(isset($errors['user_id'])) ?>"
                            <?= isset($errors['user_id']) ? 'aria-describedby="user_id-error" aria-invalid="true"' : '' ?>>
                        <option value="">Válassz tagot…</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= e($user['id']) ?>">
                                <?= e($user['name']) ?> &middot; <?= e($user['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['user_id'])): ?>
                        <p id="user_id-error" class="field-message" role="alert"><?= e($errors['user_id']) ?></p>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-sm mt-4">Nevezés rögzítése</button>
                <?php endif; ?>
            </form>

            <!-- 2. Fiók nélküli játékos: vendégnevezés -->
            <form method="POST" action="/admin/versenyek/<?= e($competition['id']) ?>/nevezesek"
                  class="p-4 rounded-xl bg-sand-50 border border-sand-200 space-y-4"
                  novalidate>
                <input type="hidden" name="mode" value="guest">

                <div>
                    <h2 class="font-semibold text-billiard-green-900 mb-1">Fiók nélküli játékos</h2>
                    <p class="text-sm text-sand-500">
                        A nevezés vendégnevezésként kerül a névsorba, fiókhoz
                        nem kötődik.
                    </p>
                </div>

                <div>
                    <label for="guest_full_name" class="label">Teljes név</label>
                    <input type="text" id="guest_full_name" name="full_name"
                           value="<?= e($data['fullName'] ?? '') ?>"
                           required maxlength="100" autocomplete="off"
                           class="<?= $fieldClass(isset($errors['fullName'])) ?>"
                           <?= isset($errors['fullName']) ? 'aria-describedby="guest_full_name-error" aria-invalid="true"' : '' ?>>
                    <?php if (isset($errors['fullName'])): ?>
                        <p id="guest_full_name-error" class="field-message" role="alert"><?= e($errors['fullName']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="guest_email" class="label">E-mail cím</label>
                    <input type="email" id="guest_email" name="email"
                           value="<?= e($data['email'] ?? '') ?>"
                           required autocomplete="off"
                           class="<?= $fieldClass(isset($errors['email'])) ?>"
                           <?= isset($errors['email'])
                                ? 'aria-describedby="guest_email-error" aria-invalid="true"'
                                : 'aria-describedby="guest_email-hint"' ?>>
                    <?php if (isset($errors['email'])): ?>
                        <p id="guest_email-error" class="field-message" role="alert"><?= e($errors['email']) ?></p>
                    <?php else: ?>
                        <p id="guest_email-hint" class="field-hint">Ide megy a visszaigazolás.</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="guest_phone" class="label">Telefonszám</label>
                    <input type="tel" id="guest_phone" name="phone"
                           value="<?= e($data['phone'] ?? '') ?>"
                           required autocomplete="off"
                           class="<?= $fieldClass(isset($errors['phone'])) ?>"
                           <?= isset($errors['phone']) ? 'aria-describedby="guest_phone-error" aria-invalid="true"' : '' ?>>
                    <?php if (isset($errors['phone'])): ?>
                        <p id="guest_phone-error" class="field-message" role="alert"><?= e($errors['phone']) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-secondary btn-sm">Nevezés rögzítése</button>
            </form>
        </div>
    </details>
</section>

<?php if (empty($registrations)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Erre a versenyre még senki nem nevezett</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            A beérkező nevezések itt fognak megjelenni, és innen exportálhatod őket.
        </p>
    </div>

<?php else: ?>
    <div class="card overflow-hidden">
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col" class="w-12">#</th>
                        <th scope="col">Név</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Telefon</th>
                        <th scope="col">Rögzítés módja</th>
                        <th scope="col">Nevezés ideje</th>
                        <th scope="col" class="text-right">Művelet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $index => $registration): ?>
                        <tr>
                            <td class="text-sand-400 tabular-nums"><?= $index + 1 ?></td>
                            <td>
                                <span class="font-medium text-sand-900"><?= e($registration['full_name']) ?></span>
                            </td>
                            <td>
                                <a href="mailto:<?= e($registration['email']) ?>"
                                   class="text-billiard-green-600 hover:underline">
                                    <?= e($registration['email']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="tel:<?= e(preg_replace('/\s+/', '', $registration['phone'])) ?>"
                                   class="hover:text-billiard-green-700 transition-colors">
                                    <?= e($registration['phone']) ?>
                                </a>
                            </td>
                            <td class="whitespace-nowrap">
                                <?php if (empty($registration['created_by_user_id'])): ?>
                                    <span class="badge badge-neutral">Vendég</span>
                                <?php else: ?>
                                    <span class="badge badge-green">Fiókkal</span>
                                <?php endif; ?>
                            </td>
                            <td class="whitespace-nowrap text-sand-500">
                                <time datetime="<?= e($registration['registered_at']) ?>">
                                    <?= date('Y. m. d. H:i', strtotime($registration['registered_at'])) ?>
                                </time>
                            </td>
                            <td>
                                <div class="flex justify-end">
                                    <form method="POST" action="/admin/versenyek/nevezes/<?= e($registration['id']) ?>/torol"
                                          data-confirm="Biztosan törlöd <?= e($registration['full_name']) ?> nevezését? A művelet nem visszavonható.">
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
