<?php
/**
 * Admin pontszámok egy versenyhez
 *
 * A lista és az űrlapok egy oldalon vannak, mert az eredmény felvitele
 * jellemzően egy munkamenetben történik, és így nem kell oldalak között
 * ugrálni.
 *
 * A ranglista számolt eredmény: minden itteni mentés azonnal megjelenik a
 * nyilvános állásban, külön újraszámolás nélkül.
 *
 * @var array       $competition A verseny adatai
 * @var array       $entries     Felvitt pontszámok, a legtöbb ponttal az élen
 * @var array       $errors      Validációs hibák (mező => üzenet)
 * @var array       $data        Az előző felvitel adatai (sticky form)
 * @var array|null  $editing     Az éppen szerkesztett pontszám, vagy null
 * @var array       $registrants A verseny nevezőinek nevei
 * @var array       $albums      Albumok, amelyekben van felvitt helyezett
 */

$fieldClass = static fn(bool $hasError): string => 'field' . ($hasError ? ' field-error' : '');

// Szerkesztésnél a meglévő értékek töltik elő az űrlapot, felvitelnél az
// esetleges hibás beküldés adatai
$formValues = $editing !== null
    ? [
        'playerName' => $data['playerName'] ?? $editing['player_name'],
        'points' => $data['points'] ?? (string) $editing['points'],
        'place' => $data['place'] ?? (string) ($editing['place'] ?? ''),
    ]
    : [
        'playerName' => $data['playerName'] ?? '',
        'points' => $data['points'] ?? '',
        'place' => $data['place'] ?? '',
    ];

$formAction = $editing !== null
    ? '/admin/ranglista/pont/' . $editing['id'] . '/szerkeszt'
    : '/admin/ranglista/' . $competition['id'];

$total = array_sum(array_column($entries, 'points'));
?>
<a href="/admin/ranglista" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
    </svg>
    Vissza a versenyekhez
</a>

<header class="mb-7">
    <p class="eyebrow mb-2">Pontozás</p>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
        <?= e($competition['name']) ?>
    </h1>
    <dl class="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-sm text-sand-500">
        <div class="inline-flex gap-1.5">
            <dt>Dátum:</dt>
            <dd class="font-medium text-sand-700">
                <?= date('Y. m. d.', strtotime($competition['date'])) ?>
            </dd>
        </div>
        <div class="inline-flex gap-1.5">
            <dt>Szezon:</dt>
            <dd class="font-medium text-sand-700">
                <?= !empty($competition['season_name']) ? e($competition['season_name']) : 'nincs megadva' ?>
            </dd>
        </div>
        <div class="inline-flex gap-1.5">
            <dt>Felvitt pontszám:</dt>
            <dd class="font-medium text-sand-700"><?= count($entries) ?> játékos, összesen <?= (int) $total ?> pont</dd>
        </div>
    </dl>
</header>

<?php if (empty($competition['season_name'])): ?>
    <!--
        Szezon nélküli verseny pontjai nem kerülnek be egyetlen ranglistába
        sem, ezért ezt előre jelezzük - különben a szervező hiába viszi fel őket.
    -->
    <div class="alert alert-warning mb-7" role="alert">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
        </svg>
        <div class="text-sm">
            <p class="alert-title mb-0.5">Ehhez a versenyhez nincs szezon rendelve</p>
            <p>
                A pontszámok felvihetők, de nem kerülnek be a ranglistába, amíg a
                versenynek nincs szezonja.
                <a href="/admin/versenyek/<?= e($competition['id']) ?>/szerkeszt" class="font-medium underline">
                    Szezon beállítása
                </a>
            </p>
        </div>
    </div>
<?php endif; ?>

<div class="grid gap-7 lg:grid-cols-5 items-start">

    <!-- ============ Bal oldal: pontszámok listája ============ -->
    <div class="lg:col-span-3">
        <?php if ($entries === []): ?>
            <div class="empty-state">
                <span class="empty-state-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.006 0H9.497m5.006 0a3 3 0 00-5.006 0M16.5 6.75V15m0-8.25a3 3 0 00-3-3h-3a3 3 0 00-3 3m9 0h1.5a1.5 1.5 0 011.5 1.5v1.5a3 3 0 01-3 3M7.5 6.75V15m0-8.25H6A1.5 1.5 0 004.5 8.25v1.5a3 3 0 003 3"/>
                    </svg>
                </span>
                <p class="font-semibold text-sand-900">Még nincs pontszám ezen a versenyen</p>
                <p class="text-sm text-sand-500 mt-1 max-w-sm">
                    Vidd fel kézzel a jobb oldali űrlapon, vagy vedd át a galéria
                    helyezettjeiből.
                </p>
            </div>
        <?php else: ?>
            <div class="card overflow-hidden">
                <div class="table-scroll">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Játékos</th>
                                <th scope="col" class="text-center">Helyezés</th>
                                <th scope="col" class="text-center">Pont</th>
                                <th scope="col" class="text-right">Műveletek</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr<?= ($editing !== null && $editing['id'] === $entry['id']) ? ' class="bg-billiard-gold-50"' : '' ?>>
                                    <th scope="row" class="font-medium text-sand-900">
                                        <?= e($entry['player_name']) ?>
                                    </th>
                                    <td class="text-center tabular-nums">
                                        <?= $entry['place'] !== null ? (int) $entry['place'] : '–' ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-green tabular-nums font-bold">
                                            <?= (int) $entry['points'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="/admin/ranglista/<?= e($competition['id']) ?>?szerkeszt=<?= e($entry['id']) ?>"
                                               class="btn btn-secondary btn-sm">Szerkesztés</a>
                                            <form method="POST" action="/admin/ranglista/pont/<?= e($entry['id']) ?>/torol"
                                                  data-confirm="Törlöd <?= e($entry['player_name']) ?> pontszámát erről a versenyről?">
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

        <?php if ($albums !== []): ?>
            <!--
                Átvétel a helyezettekből
                A szervező a galériában amúgy is felviszi a dobogót, ezért a
                nevek kétszeri beírása felesleges munka. A pontokat a
                helyezésből származtatjuk (1. hely 12 pont, 2. hely 9, és így
                tovább); minden érték utólag átírható. A már felvitt
                pontszámokat nem írja felül.
            -->
            <section class="card p-6 mt-6" aria-labelledby="import-heading">
                <h2 id="import-heading" class="font-semibold text-billiard-green-900 mb-1">
                    Átvétel a galéria helyezettjeiből
                </h2>
                <p class="text-sm text-sand-500 mb-4">
                    A helyezésekből pontszámot származtatunk (1. hely 12 pont, 2. hely 9,
                    3. hely 7, 4. hely 5, 5. hely 3, 6. hely 2, további helyezettek 1 pont).
                    Minden érték utólag átírható, és a már felvitt játékosok kimaradnak.
                </p>

                <form method="POST" action="/admin/ranglista/<?= e($competition['id']) ?>/atvetel"
                      class="flex flex-col sm:flex-row gap-3 sm:items-end">
                    <div class="flex-1">
                        <label for="album_id" class="label">Album</label>
                        <select id="album_id" name="album_id" required class="field">
                            <option value="">Válassz albumot…</option>
                            <?php foreach ($albums as $album): ?>
                                <option value="<?= e($album['id']) ?>">
                                    <?= e($album['name']) ?> (<?= count($album['placements']) ?> helyezett)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">Pontszámok átvétele</button>
                </form>
            </section>
        <?php endif; ?>
    </div>

    <!-- ============ Jobb oldal: felvitel és szerkesztés ============ -->
    <aside class="lg:col-span-2">
        <section class="card p-6 lg:sticky lg:top-24" aria-labelledby="entry-form-heading">
            <h2 id="entry-form-heading" class="font-semibold text-billiard-green-900 mb-4">
                <?= $editing !== null ? 'Pontszám módosítása' : 'Új pontszám' ?>
            </h2>

            <form method="POST" action="<?= e($formAction) ?>" class="space-y-4" novalidate>
                <div>
                    <label for="player_name" class="label">Játékos neve</label>
                    <input type="text" id="player_name" name="player_name"
                           value="<?= e($formValues['playerName']) ?>"
                           required maxlength="100"
                           <?= $registrants !== [] ? 'list="registrant-names"' : '' ?>
                           class="<?= $fieldClass(isset($errors['playerName'])) ?>"
                           <?= isset($errors['playerName'])
                                ? 'aria-describedby="player_name-error" aria-invalid="true"'
                                : ($registrants !== [] ? 'aria-describedby="player_name-hint"' : '') ?>>

                    <?php if ($registrants !== []): ?>
                        <!--
                            A nevezők nevei javaslatként: a datalist nem
                            korlátozza a beírást, csak gyorsítja - fiók nélküli
                            játékos neve is beírható.
                        -->
                        <datalist id="registrant-names">
                            <?php foreach (array_unique($registrants) as $name): ?>
                                <option value="<?= e($name) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    <?php endif; ?>

                    <?php if (isset($errors['playerName'])): ?>
                        <p id="player_name-error" class="field-message" role="alert"><?= e($errors['playerName']) ?></p>
                    <?php elseif ($registrants !== []): ?>
                        <p id="player_name-hint" class="field-hint">
                            A verseny nevezői javaslatként megjelennek, de bármilyen
                            név beírható.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="points" class="label">Pont</label>
                        <input type="number" id="points" name="points"
                               value="<?= e($formValues['points']) ?>"
                               required step="1"
                               class="<?= $fieldClass(isset($errors['points'])) ?>"
                               <?= isset($errors['points']) ? 'aria-describedby="points-error" aria-invalid="true"' : '' ?>>
                        <?php if (isset($errors['points'])): ?>
                            <p id="points-error" class="field-message" role="alert"><?= e($errors['points']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="place" class="label">Helyezés</label>
                        <input type="number" id="place" name="place"
                               value="<?= e($formValues['place']) ?>"
                               min="1" step="1"
                               class="<?= $fieldClass(isset($errors['place'])) ?>"
                               <?= isset($errors['place'])
                                    ? 'aria-describedby="place-error" aria-invalid="true"'
                                    : 'aria-describedby="place-hint"' ?>>
                        <?php if (isset($errors['place'])): ?>
                            <p id="place-error" class="field-message" role="alert"><?= e($errors['place']) ?></p>
                        <?php else: ?>
                            <p id="place-hint" class="field-hint">Elhagyható.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    <button type="submit" class="btn btn-primary">
                        <?= $editing !== null ? 'Mentés' : 'Felvitel' ?>
                    </button>
                    <?php if ($editing !== null): ?>
                        <a href="/admin/ranglista/<?= e($competition['id']) ?>" class="btn btn-ghost">Mégse</a>
                    <?php endif; ?>
                </div>

                <p class="field-hint pt-2 border-t border-sand-200">
                    A pont negatív is lehet (levonás). A ranglista a pontokból
                    számolódik, ezért a mentés azonnal megjelenik a nyilvános
                    állásban.
                </p>
            </form>
        </section>
    </aside>
</div>
