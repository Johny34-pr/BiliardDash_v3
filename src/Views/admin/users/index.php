<?php
/**
 * Admin regisztrált felhasználók listája
 *
 * A nevezésszám azért látszik, hogy a törlés következménye átlátható
 * legyen: a fiók törlésekor a nevezések nem tűnnek el, vendégnevezéssé
 * válnak.
 *
 * @var array $users Fiókok (id, name, email, phone, city, created_at, registration_count)
 */
?>
<header class="mb-7">
    <p class="eyebrow mb-2">Közösség</p>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Felhasználók</h1>
    <?php if (!empty($users)): ?>
        <p class="text-sand-500 mt-1"><?= count($users) ?> regisztrált fiók</p>
    <?php endif; ?>
</header>

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
