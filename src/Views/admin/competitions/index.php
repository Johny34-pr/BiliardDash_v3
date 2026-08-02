<?php
/**
 * Admin versenyek listája
 *
 * A táblázat mobilon vízszintesen görgethető (.table-scroll), így nem
 * keletkezik vízszintes oldalgörgetés (Requirement 7.1).
 *
 * @var array $competitions Versenyek tömbje
 */
$now = new DateTimeImmutable();
?>
<header class="flex flex-wrap items-end justify-between gap-4 mb-7">
    <div>
        <p class="eyebrow mb-2">Események</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Versenyek kezelése</h1>
        <?php if (!empty($competitions)): ?>
            <p class="text-sand-500 mt-1"><?= count($competitions) ?> verseny</p>
        <?php endif; ?>
    </div>
    <a href="/admin/versenyek/uj" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
        </svg>
        Új verseny
    </a>
</header>

<?php if (empty($competitions)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincsenek versenyek</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">Hozd létre az első versenyt, hogy megnyílhasson a nevezés.</p>
        <a href="/admin/versenyek/uj" class="btn btn-primary btn-sm mt-6">Első verseny létrehozása</a>
    </div>

<?php else: ?>
    <div class="card overflow-hidden">
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Verseny</th>
                        <th scope="col">Dátum</th>
                        <th scope="col">Helyszín</th>
                        <th scope="col">Nevezési határidő</th>
                        <th scope="col" class="text-center">Nevezők</th>
                        <th scope="col" class="text-right">Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competitions as $competition): ?>
                        <?php $isOpen = new DateTimeImmutable($competition['registration_deadline']) > $now; ?>
                        <tr>
                            <td>
                                <span class="font-medium text-sand-900"><?= e($competition['name']) ?></span>
                            </td>
                            <td class="whitespace-nowrap">
                                <time datetime="<?= e($competition['date']) ?>">
                                    <?= date('Y. m. d.', strtotime($competition['date'])) ?>
                                </time>
                            </td>
                            <td><?= e($competition['venue']) ?></td>
                            <td class="whitespace-nowrap">
                                <time datetime="<?= e($competition['registration_deadline']) ?>">
                                    <?= date('Y. m. d. H:i', strtotime($competition['registration_deadline'])) ?>
                                </time>
                                <span class="badge <?= $isOpen ? 'badge-green' : 'badge-neutral' ?> ml-1.5">
                                    <?= $isOpen ? 'Nyitott' : 'Lezárult' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-neutral tabular-nums">
                                    <?= (int)$competition['registrant_count'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/admin/versenyek/<?= e($competition['id']) ?>/nevezesek" class="btn btn-secondary btn-sm">
                                        Nevezők
                                    </a>
                                    <a href="/admin/versenyek/<?= e($competition['id']) ?>/szerkeszt" class="btn btn-secondary btn-sm">
                                        Szerkesztés
                                    </a>
                                    <form method="POST" action="/admin/versenyek/<?= e($competition['id']) ?>/torol"
                                          data-confirm="Biztosan törlöd a versenyt? Az összes hozzá tartozó nevezés is törlődik.">
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
