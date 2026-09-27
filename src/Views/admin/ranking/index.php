<?php
/**
 * Admin ranglista - versenyek a pontszámok szerkesztéséhez
 *
 * A ranglista SZÁMOLT eredmény a versenyenkénti pontokból, ezért itt nincs
 * "újraszámolás" művelet: a pontszám mentése után a nyilvános állás már a
 * friss értéket mutatja.
 *
 * @var array      $competitions  Minden verseny
 * @var array      $entryCounts   Verseny azonosító => felvitt pontszámok száma
 * @var array|null $currentSeason Az aktuális szezon, vagy null
 */
?>
<header class="flex flex-wrap items-end justify-between gap-4 mb-7">
    <div>
        <p class="eyebrow mb-2">Eredmények</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
            Ranglista pontszámok
        </h1>
        <p class="text-sand-500 mt-1">
            Válassz versenyt a pontszámok felviteléhez vagy módosításához.
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="/ranglista" target="_blank" rel="noopener" class="btn btn-secondary">
            Nyilvános ranglista
        </a>
        <a href="/admin/szezonok" class="btn btn-ghost">Szezonok</a>
    </div>
</header>

<div class="alert alert-info mb-7">
    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
    </svg>
    <div class="text-sm">
        <p class="alert-title mb-0.5">Nincs újraszámolás</p>
        <p>
            Az összesített állás a versenyenkénti pontokból számolódik, ezért
            minden javítás azonnal megjelenik a nyilvános ranglistán. Csak a
            verseny <strong>szezonja</strong> számít még: szezon nélküli verseny
            pontjai nem kerülnek be egyetlen ranglistába sem.
            <?php if ($currentSeason !== null): ?>
                Aktuális szezon: <strong><?= e($currentSeason['name']) ?></strong>.
            <?php else: ?>
                Jelenleg nincs kijelölt aktuális szezon.
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (empty($competitions)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.006 0H9.497m5.006 0a3 3 0 00-5.006 0M16.5 6.75V15m0-8.25a3 3 0 00-3-3h-3a3 3 0 00-3 3m9 0h1.5a1.5 1.5 0 011.5 1.5v1.5a3 3 0 01-3 3M7.5 6.75V15m0-8.25H6A1.5 1.5 0 004.5 8.25v1.5a3 3 0 003 3"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincsenek versenyek</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            A pontszámok versenyekhez tartoznak, ezért előbb hozz létre versenyt.
        </p>
        <a href="/admin/versenyek/uj" class="btn btn-primary btn-sm mt-6">Új verseny</a>
    </div>

<?php else: ?>
    <div class="card overflow-hidden">
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Verseny</th>
                        <th scope="col">Dátum</th>
                        <th scope="col">Szezon</th>
                        <th scope="col" class="text-center">Pontszámok</th>
                        <th scope="col" class="text-right">Művelet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competitions as $competition): ?>
                        <?php $count = $entryCounts[$competition['id']] ?? 0; ?>
                        <tr>
                            <th scope="row" class="font-medium text-sand-900">
                                <?= e($competition['name']) ?>
                            </th>
                            <td class="whitespace-nowrap">
                                <time datetime="<?= e($competition['date']) ?>">
                                    <?= date('Y. m. d.', strtotime($competition['date'])) ?>
                                </time>
                            </td>
                            <td>
                                <?php if (!empty($competition['season_name'])): ?>
                                    <span class="badge badge-neutral"><?= e($competition['season_name']) ?></span>
                                <?php else: ?>
                                    <!-- Szezon nélküli verseny pontjai nem kerülnek ranglistába -->
                                    <span class="badge badge-gold" title="A pontjai nem kerülnek ranglistába">
                                        Nincs szezon
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $count > 0 ? 'badge-green' : 'badge-neutral' ?> tabular-nums">
                                    <?= (int) $count ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/admin/ranglista/<?= e($competition['id']) ?>" class="btn btn-primary btn-sm">
                                        <?= $count > 0 ? 'Pontszámok' : 'Pontozás' ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
