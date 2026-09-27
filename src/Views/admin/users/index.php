<?php
/**
 * Admin regisztrált felhasználók listája
 *
 * A nevezésszám azért látszik, hogy a törlés következménye átlátható
 * legyen: a fiók törlésekor a nevezések nem tűnnek el, vendégnevezéssé
 * válnak.
 *
 * @var array      $users       Fiókok (id, name, email, phone, city, created_at, registration_count)
 * @var array|null $newPassword Épp visszaállított jelszó: ['name' => ..., 'password' => ...]
 */
?>
<header class="mb-7">
    <p class="eyebrow mb-2">Közösség</p>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Felhasználók</h1>
    <?php if (!empty($users)): ?>
        <p class="text-sand-500 mt-1"><?= count($users) ?> regisztrált fiók</p>
    <?php endif; ?>
</header>

<?php if (!empty($newPassword)): ?>
    <!--
        A visszaállított jelszó EGYETLEN alkalommal, itt jelenik meg: tárolni
        csak a lenyomatát tároljuk, tehát később már nem előhívható. Ezért
        kap kiemelt, jól olvasható megjelenítést, és ezért mondjuk ki, hogy
        most kell átadni.
    -->
    <section class="card p-6 mb-7 border-2 border-billiard-gold-400" role="status">
        <div class="flex items-start gap-3">
            <span class="grid place-items-center w-10 h-10 shrink-0 rounded-xl bg-billiard-gold-100 text-billiard-gold-700" aria-hidden="true">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                </svg>
            </span>
            <div class="min-w-0">
                <h2 class="font-semibold text-billiard-green-900">
                    <?= e($newPassword['name']) ?> új jelszava
                </h2>
                <p class="text-sm text-sand-600 mt-1">
                    Add át neki telefonon vagy személyesen. Ez a jelszó
                    <strong>csak most látható</strong>: az oldal elhagyása után
                    nem hívható elő újra, mert csak a lenyomatát tároljuk.
                </p>

                <p class="mt-3">
                    <code class="inline-block px-4 py-2.5 rounded-xl bg-sand-100 border border-sand-300 font-mono text-lg tracking-wider text-sand-900 select-all">
                        <?= e($newPassword['password']) ?>
                    </code>
                </p>

                <p class="text-sm text-sand-500 mt-3">
                    A tag e-mailben értesítést kapott a változásról, de a jelszót
                    az nem tartalmazza. Minden eszközén kiléptettük.
                </p>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (empty($users)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincs regisztrált fiók</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            A látogatók a nyilvános regisztrációs oldalon hozhatnak létre fiókot.
            Nevezni fiók nélkül is lehet.
        </p>
    </div>

<?php else: ?>
    <div class="card overflow-hidden">
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Név</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Telefon</th>
                        <th scope="col">Település</th>
                        <th scope="col" class="text-center">Nevezés</th>
                        <th scope="col">Regisztrált</th>
                        <th scope="col" class="text-right">Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <span class="font-medium text-sand-900"><?= e($user['name']) ?></span>
                            </td>
                            <td>
                                <a href="mailto:<?= e($user['email']) ?>" class="text-billiard-green-700 hover:underline">
                                    <?= e($user['email']) ?>
                                </a>
                            </td>
                            <td class="whitespace-nowrap"><?= e($user['phone']) ?></td>
                            <td>
                                <?php if (trim($user['city']) !== ''): ?>
                                    <?= e($user['city']) ?>
                                <?php else: ?>
                                    <!-- A település csak később lett kötelező, a régi fiókoknál üres -->
                                    <span class="text-sand-400" title="A regisztrációkor még nem volt kötelező">
                                        nincs megadva
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-neutral tabular-nums">
                                    <?= (int) $user['registration_count'] ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <time datetime="<?= e($user['created_at']) ?>">
                                    <?= date('Y. m. d.', strtotime($user['created_at'])) ?>
                                </time>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/admin/felhasznalok/<?= e($user['id']) ?>/szerkeszt"
                                       class="btn btn-secondary btn-sm">
                                        Szerkesztés
                                    </a>

                                    <!--
                                        Jelszó visszaállítása: a rendszer új,
                                        véletlen jelszót ad, és megmutatja - a
                                        szervező adja át a tagnak. Megerősítést
                                        kér, mert a régi jelszó azonnal érvénytelen.
                                    -->
                                    <form method="POST" action="/admin/felhasznalok/<?= e($user['id']) ?>/jelszo"
                                          data-confirm="Új jelszót állítunk be <?= e($user['name']) ?> fiókjához. A mostani jelszava azonnal érvénytelen lesz, és minden eszközén kilép. Folytatod?">
                                        <button type="submit" class="btn btn-ghost btn-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                            </svg>
                                            Jelszó
                                        </button>
                                    </form>

                                    <form method="POST" action="/admin/felhasznalok/<?= e($user['id']) ?>/torol"
                                          data-confirm="Biztosan törlöd <?= e($user['name']) ?> fiókját? A nevezései nem törlődnek, vendégnevezésként megmaradnak.">
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

    <p class="text-sm text-sand-500 mt-5">
        A fiók törlése nem törli a hozzá tartozó nevezéseket: azok vendégnevezésként
        megmaradnak a versenyek névsorában.
    </p>
<?php endif; ?>
