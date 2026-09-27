<?php
/**
 * Galéria - Album lista nézet
 *
 * Minden album a borítóképével jelenik meg, alatta a névvel és a dobogóval.
 * A kártyára kattintva nyílik meg az album oldala, ahol a kép nagyban, a
 * helyezettek pedig mellette olvashatók.
 *
 * Csak az aktuális albumok látszanak itt; a korábbi évadok az archívumba
 * kerülnek (/galeria/archiv), hogy a friss versenyek legyenek elöl.
 *
 * Reszponzív rács: 1 oszlop mobil, 2 tablet, 3 asztali.
 *
 * @var array $albums        Albumok (id, name, cover_url, cover_alt, placements)
 * @var int   $archivedCount Az archivált albumok száma
 */
?>

<!-- Oldalfejléc -->
<header class="mb-10 reveal">
    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Fotógaléria
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Versenyalbumok
        </h1>
        <?php if (!empty($albums)): ?>
            <p class="text-sm text-sand-500 pb-1"><?= count($albums) ?> album</p>
        <?php endif; ?>
    </div>
    <p class="text-sand-600 mt-4 max-w-reading leading-relaxed">
        Kattints egy albumra: a kép nagyban jelenik meg, mellette a verseny helyezettjeivel.
    </p>

    <?php if (!empty($archivedCount)): ?>
        <!-- Az archívum linkje csak akkor jelenik meg, ha van benne album -->
        <a href="/galeria/archiv" class="btn btn-secondary btn-sm mt-5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            Korábbi szezonok (<?= (int) $archivedCount ?>)
        </a>
    <?php endif; ?>
</header>

<?php if (empty($albums)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Jelenleg nincsenek albumok</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            A versenyekről készült fotók és a helyezettek itt fognak megjelenni.
        </p>
    </div>

<?php else: ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($albums as $i => $album): ?>
            <?php
            $cardIndex = $i;
            require __DIR__ . '/_album-card.php';
            ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
