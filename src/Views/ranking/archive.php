<?php
/**
 * Archív ranglista - a korábbi szezonok végleges állása
 *
 * Az archivált szezon eredménye már nem változik, ezért kerül külön oldalra:
 * az élen a futó évad pontversenye a lényeges. Innen minden korábbi szezon
 * teljes táblázata megnyitható.
 *
 * @var array $seasons Szezonok: [['season' => [...], 'playerCount' => int], ...]
 */
?>

<header class="mb-10 reveal">
    <a href="/ranglista" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza az aktuális ranglistához
    </a>

    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Archívum
    </p>
    <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
        Korábbi szezonok ranglistái
    </h1>
    <p class="text-sand-600 mt-4 max-w-reading leading-relaxed">
        A lezárt évadok végleges pontverseny-állása. Válassz egy szezont a
        teljes táblázat megtekintéséhez.
    </p>
</header>

<?php if ($seasons === []): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Az archívum még üres</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Ide a lezárt szezonok ranglistái kerülnek, amikor a szervező
            archiválja őket.
        </p>
    </div>

<?php else: ?>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($seasons as $i => $item): ?>
            <?php $season = $item['season']; ?>
            <article class="card card-interactive reveal reveal-<?= min($i + 1, 5) ?>">
                <a href="/ranglista/<?= e($season['id']) ?>" class="block p-6">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <span class="grid place-items-center w-10 h-10 shrink-0 rounded-xl bg-billiard-green-50 text-billiard-green-700" aria-hidden="true">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.006 0H9.497m5.006 0a3 3 0 00-5.006 0M16.5 6.75V15m0-8.25a3 3 0 00-3-3h-3a3 3 0 00-3 3m9 0h1.5a1.5 1.5 0 011.5 1.5v1.5a3 3 0 01-3 3M7.5 6.75V15m0-8.25H6A1.5 1.5 0 004.5 8.25v1.5a3 3 0 003 3"/>
                            </svg>
                        </span>
                        <span class="badge badge-neutral"><?= (int) $item['playerCount'] ?> játékos</span>
                    </div>

                    <h2 class="text-xl font-semibold tracking-tightest text-billiard-green-900">
                        <?= e($season['name']) ?>
                    </h2>

                    <?php if (!empty($season['starts_on'])): ?>
                        <p class="text-sm text-sand-500 mt-1">
                            Kezdete:
                            <time datetime="<?= e($season['starts_on']) ?>">
                                <?= date('Y. m. d.', strtotime($season['starts_on'])) ?>
                            </time>
                        </p>
                    <?php endif; ?>

                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600 mt-4 pt-3 border-t border-sand-200">
                        Ranglista megnyitása
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </span>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
