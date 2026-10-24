<?php
/**
 * Versenyeink > Közelgő versenyek
 *
 * Ugyanazt az adatkört mutatja, mint a Nevezés oldal, de a versenynaptár
 * szemszögéből: itt a "mikor és hol lesz" a kérdés, nem a nevezés. A
 * kártyajelölés közös (_competition-card.php), ezért a két oldal nem tud
 * elcsúszni egymástól.
 *
 * @var array $competitions Közelgő versenyek
 */
?>

<header class="mb-8 reveal">
    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Versenyeink
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Közelgő versenyek
        </h1>
        <?php if (!empty($competitions)): ?>
            <p class="text-sm text-sand-500 pb-1"><?= count($competitions) ?> verseny</p>
        <?php endif; ?>
    </div>

    <div class="flex flex-wrap items-center gap-3 mt-5">
        <a href="/versenyeink/korabbi" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            Korábbi versenyek
        </a>
        <a href="/nevezes" class="btn btn-ghost btn-sm">Nevezés</a>
    </div>
</header>

<?php if (empty($competitions)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincs kiírt verseny</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            A következő versenykiírás után itt jelenik meg a naptár.
        </p>
        <a href="/versenyeink/korabbi" class="btn btn-secondary btn-sm mt-6">Korábbi versenyek</a>
    </div>

<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($competitions as $i => $competition): ?>
            <?php
            $cardIndex = $i;
            $cardContext = 'upcoming';
            require __DIR__ . '/_competition-card.php';
            ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
