<?php
/**
 * Ranglista - a szezon pontversenyének állása
 *
 * A táblázat alakja: minden SOR egy játékos, minden OSZLOP egy verseny, a
 * végén az összesítés. Így mindenkinél versenyenként lebontva látszanak a
 * pontok, és egy pillantással összehasonlítható, ki hol szerzett többet.
 *
 * A táblázat mobilon vízszintesen görgethető (.table-scroll): a versenyek
 * száma a szezon során nő, ezért a tördelés helyett a görgetés a járható út.
 * A játékos neve és a helyezés az első két oszlopban marad, hogy görgetés
 * közben is látszódjon, melyik sort olvassuk.
 *
 * @var array|null $season        Az aktuális vagy kiválasztott szezon
 * @var string|null $seasonName   A szezon neve, vagy null
 * @var array      $competitions  Oszlopok: [['id','name','date'], ...]
 * @var array      $rows          Sorok: játékosonként pontok, összeg, helyezés
 * @var bool       $isArchived    Archivált szezont látunk-e
 * @var int        $archivedCount Hány archivált szezon van
 */

/** Az első három helyezés kiemelt színt kap, a galériával egyezően */
$medalClass = static function (int $position): string {
    return match ($position) {
        1 => 'bg-billiard-gold-400 text-billiard-green-900',
        2 => 'bg-sand-300 text-sand-800',
        3 => 'bg-billiard-gold-700 text-white',
        default => 'bg-sand-200 text-sand-600',
    };
};
?>

<header class="mb-8 reveal">
    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Pontverseny
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Ranglista
            <?php if ($seasonName !== null): ?>
                <span class="text-sand-500 font-semibold"><?= e($seasonName) ?></span>
            <?php endif; ?>
        </h1>
        <?php if ($rows !== []): ?>
            <p class="text-sm text-sand-500 pb-1">
                <?= count($rows) ?> játékos &middot; <?= count($competitions) ?> verseny
            </p>
        <?php endif; ?>
    </div>

    <p class="text-sand-600 mt-4 max-w-reading leading-relaxed">
        A szezon versenyein szerzett pontok. Minden sor egy játékos, minden
        oszlop egy verseny &ndash; a jobb szélen az összesítés.
    </p>

    <div class="flex flex-wrap items-center gap-3 mt-5">
        <?php if ($isArchived): ?>
            <span class="badge badge-gold">Archív szezon</span>
            <a href="/ranglista" class="btn btn-secondary btn-sm">Aktuális szezon</a>
        <?php endif; ?>

        <?php if ($archivedCount > 0): ?>
            <a href="/ranglista/archiv" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                </svg>
                Korábbi szezonok (<?= (int) $archivedCount ?>)
            </a>
        <?php endif; ?>
    </div>
</header>

<?php if ($season === null): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.006 0H9.497m5.006 0a3 3 0 00-5.006 0M16.5 6.75V15m0-8.25a3 3 0 00-3-3h-3a3 3 0 00-3 3m9 0h1.5a1.5 1.5 0 011.5 1.5v1.5a3 3 0 01-3 3M7.5 6.75V15m0-8.25H6A1.5 1.5 0 004.5 8.25v1.5a3 3 0 003 3"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Nincs kijelölt aktuális szezon</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            A pontverseny akkor jelenik meg, ha a szervező kijelölte a futó
            évadot.
        </p>
    </div>

<?php elseif ($rows === []): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.006 0H9.497m5.006 0a3 3 0 00-5.006 0M16.5 6.75V15m0-8.25a3 3 0 00-3-3h-3a3 3 0 00-3 3m9 0h1.5a1.5 1.5 0 011.5 1.5v1.5a3 3 0 01-3 3M7.5 6.75V15m0-8.25H6A1.5 1.5 0 004.5 8.25v1.5a3 3 0 003 3"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincs pontszám ebben a szezonban</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Az első verseny eredménye után itt jelenik meg az állás.
        </p>
    </div>

<?php else: ?>
    <div class="card overflow-hidden reveal">
        <div class="table-scroll">
            <table class="admin-table">
                <caption class="sr-only">
                    <?= e($seasonName ?? 'Aktuális szezon') ?> ranglistája versenyenkénti bontásban
                </caption>
                <thead>
                    <tr>
                        <th scope="col" class="text-center">#</th>
                        <th scope="col">Játékos</th>
                        <?php foreach ($competitions as $competition): ?>
                            <th scope="col" class="text-center">
                                <!--
                                    A verseny neve hosszú lehet, ezért a fejlécben
                                    a dátum áll, a teljes név pedig title-ben és
                                    képernyőolvasónak. Így az oszlopok nem
                                    szélesednek ki olvashatatlanná.
                                -->
                                <span class="block whitespace-nowrap" title="<?= e($competition['name']) ?>">
                                    <?= date('m. d.', strtotime($competition['date'])) ?>
                                </span>
                                <span class="sr-only"><?= e($competition['name']) ?></span>
                            </th>
                        <?php endforeach; ?>
                        <th scope="col" class="text-center">Összesen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="text-center">
                                <span class="grid place-items-center w-7 h-7 mx-auto rounded-full text-xs font-bold <?= $medalClass($row['position']) ?>">
                                    <?= (int) $row['position'] ?>
                                </span>
                            </td>
                            <th scope="row" class="font-medium text-sand-900 whitespace-nowrap">
                                <?= e($row['player_name']) ?>
                            </th>
                            <?php foreach ($competitions as $competition): ?>
                                <?php $points = $row['points'][$competition['id']] ?? null; ?>
                                <td class="text-center tabular-nums">
                                    <?php if ($points !== null): ?>
                                        <?= (int) $points ?>
                                    <?php else: ?>
                                        <!-- Nem indult ezen a versenyen -->
                                        <span class="text-sand-300" aria-label="nem indult">&ndash;</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="text-center">
                                <span class="badge badge-green tabular-nums font-bold">
                                    <?= (int) $row['total'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-sm text-sand-500 mt-5 max-w-reading">
        A fejlécben a verseny dátuma szerepel; a teljes név az oszlopra mutatva
        olvasható. A gondolatjel azt jelenti, hogy a játékos nem indult azon a
        versenyen. Az állás a versenyenkénti pontokból számolódik, ezért a
        szervezői javítás azonnal megjelenik itt is.
    </p>
<?php endif; ?>
