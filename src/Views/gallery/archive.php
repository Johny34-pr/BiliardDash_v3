<?php
/**
 * Archív galéria - a korábbi szezonok versenyalbumai
 *
 * Az archiválás nem törlés: az album oldala ugyanúgy elérhető marad, csak az
 * aktuális galéria listájából kerül ki. Így a friss versenyek látszanak
 * elöl, a régiek pedig visszakereshetők maradnak.
 *
 * A rendező elv a szezon, nem a feltöltés dátuma: az archívumot évadonként
 * érdemes böngészni. A szezon nélküli albumok a lista végén, külön
 * csoportban jelennek meg - így azok sem tűnnek el, amelyekhez a szervező
 * még nem adott meg évadot.
 *
 * @var array $groups     Szezononkénti csoportok: [['season' => ?string, 'albums' => array], ...]
 * @var int   $albumCount Az archivált albumok összesen
 */
?>

<header class="mb-10 reveal">
    <a href="/galeria" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza az aktuális albumokhoz
    </a>

    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Archívum
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Korábbi szezonok
        </h1>
        <?php if ($albumCount > 0): ?>
            <p class="text-sm text-sand-500 pb-1"><?= (int) $albumCount ?> album</p>
        <?php endif; ?>
    </div>
    <p class="text-sand-600 mt-4 max-w-reading leading-relaxed">
        A korábbi évadok versenyeinek fotói és helyezettjei. Az albumok
        ugyanúgy megnyithatók, mint az aktuális galériában.
    </p>
</header>

<?php if ($groups === []): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Az archívum még üres</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Ide a korábbi szezonok albumai kerülnek, amikor a szervező
            archiválja őket.
        </p>
    </div>

<?php else: ?>
    <?php foreach ($groups as $group): ?>
        <section class="mb-12" aria-labelledby="season-<?= e($group['season'] ?? 'nincs') ?>">
            <div class="flex items-center gap-3 mb-5">
                <h2 id="season-<?= e($group['season'] ?? 'nincs') ?>"
                    class="text-xl font-semibold tracking-tightest text-billiard-green-900">
                    <?= e($group['season'] ?? 'Szezon megadása nélkül') ?>
                </h2>
                <span class="badge badge-neutral"><?= count($group['albums']) ?> album</span>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($group['albums'] as $i => $album): ?>
                    <?php
                    $cardIndex = $i;
                    require __DIR__ . '/_album-card.php';
                    ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
