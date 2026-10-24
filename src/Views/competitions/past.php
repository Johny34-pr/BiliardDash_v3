<?php
/**
 * Versenyeink > Korábbi versenyek
 *
 * A lezárult versenyek, a legfrissebb elöl. Itt a jegyzőkönyv a fő tartalom:
 * amelyik versenyhez a szervező feltöltötte, ott letölthető a kártyáról.
 *
 * A versenyek évek szerint vannak csoportosítva, mert szezononként több
 * tucat verseny gyűlik össze, és egy folyamatos lista néhány év után
 * átláthatatlan lenne.
 *
 * @var array $competitionsByYear Év => versenyek, a legfrissebb évvel kezdve
 * @var int   $totalCount         Az összes lezárult verseny száma
 * @var int   $protocolCount      Ahány versenyhez van jegyzőkönyv
 */
?>

<header class="mb-8 reveal">
    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Versenyeink
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Korábbi versenyek
        </h1>
        <?php if ($totalCount > 0): ?>
            <p class="text-sm text-sand-500 pb-1">
                <?= $totalCount ?> verseny
                <?php if ($protocolCount > 0): ?>
                    &middot; <?= $protocolCount ?> jegyzőkönyv
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <p class="text-sand-600 mt-4 max-w-reading leading-relaxed">
        A lezárult versenyek névsora és eredménye. Ahol elérhető, a verseny
        jegyzőkönyve is letölthető.
    </p>

    <div class="flex flex-wrap items-center gap-3 mt-5">
        <a href="/versenyeink/kozelgo" class="btn btn-secondary btn-sm">Közelgő versenyek</a>
        <?php if (rankingEnabled()): ?>
            <a href="/ranglista" class="btn btn-ghost btn-sm">Ranglista</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($totalCount === 0): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincs lezárult verseny</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Az első verseny lezárása után itt gyűlnek a névsorok és a
            jegyzőkönyvek.
        </p>
        <a href="/versenyeink/kozelgo" class="btn btn-secondary btn-sm mt-6">Közelgő versenyek</a>
    </div>

<?php else: ?>
    <?php $cardCounter = 0; ?>
    <?php foreach ($competitionsByYear as $year => $competitions): ?>
        <section class="mb-10" aria-labelledby="ev-<?= (int) $year ?>">
            <h2 id="ev-<?= (int) $year ?>"
                class="flex items-center gap-3 text-xl font-bold tracking-tightest text-billiard-green-900 mb-4">
                <?= (int) $year ?>
                <span class="flex-1 h-px bg-sand-200" aria-hidden="true"></span>
                <span class="text-sm font-medium text-sand-500"><?= count($competitions) ?> verseny</span>
            </h2>

            <div class="space-y-4">
                <?php foreach ($competitions as $competition): ?>
                    <?php
                    $cardIndex = $cardCounter++;
                    $cardContext = 'past';
                    require __DIR__ . '/_competition-card.php';
                    ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
