<?php
/**
 * Verseny lista - Nevezés oldal
 *
 * A közelgő versenyeket mutatja: amelyeknek a napja még nem volt. A szűrés
 * szándékosan a verseny DÁTUMÁRA megy, nem a nevezési határidőre - így a
 * verseny a határidő lejárta után is itt marad a verseny napjáig, és a
 * nevezői névsor a helyszínen is elérhető. Ahol már nem lehet nevezni, a
 * kártya ezt kiírja.
 *
 * @var array $competitions Közelgő versenyek
 */
?>

<!-- Oldalfejléc -->
<header class="mb-10 reveal">
    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Nevezés
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Közelgő versenyek
        </h1>
        <?php if (!empty($competitions)): ?>
            <p class="text-sm text-sand-500 pb-1"><?= count($competitions) ?> verseny</p>
        <?php endif; ?>
    </div>
    <p class="text-sand-600 mt-4 max-w-reading leading-relaxed">
        A kiírt versenyek a verseny napjáig itt maradnak. Ahol a nevezési
        határidő már lejárt, a nevezői névsor továbbra is megtekinthető.
    </p>
</header>

<?php if (empty($competitions)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Jelenleg nincs kiírt verseny</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Amint megjelenik egy új versenykiírás, itt fogod megtalálni.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-2 mt-6">
            <a href="/versenyeink/korabbi" class="btn btn-secondary btn-sm">Korábbi versenyek</a>
            <a href="/" class="btn btn-ghost btn-sm">Hírek</a>
        </div>
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

    <p class="text-sm text-sand-500 mt-8">
        A korábbi versenyek eredményei és jegyzőkönyvei a
        <a href="/versenyeink/korabbi" class="font-medium text-billiard-green-600 hover:underline">Korábbi
        versenyek</a> oldalon találhatók.
    </p>
<?php endif; ?>
