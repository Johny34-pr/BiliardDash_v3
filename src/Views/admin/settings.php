<?php
/**
 * Admin oldalbeállítások - kapcsolható funkciók
 *
 * A kapcsolók azonnal érvényesek: a bekapcsolt modul útvonalai a következő
 * kéréstől elérhetők, a kikapcsolté 404-et adnak.
 *
 * @var bool   $forumEnabled   Aktív-e a fórum modul
 * @var bool   $rankingEnabled Aktív-e a ranglista modul
 * @var string $broadcastUrl   A közvetítés menüpont címe (üres = nincs menüpont)
 * @var string $broadcastLabel A közvetítés menüpont felirata
 */
?>
<header class="mb-7">
    <p class="eyebrow mb-2">Működés</p>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Beállítások</h1>
    <p class="text-sand-500 mt-1">Az oldal kapcsolható funkciói és a közvetítés hivatkozása.</p>
</header>

<form method="POST" action="/admin/beallitasok" class="max-w-2xl">

    <section class="card p-6 md:p-7" aria-labelledby="modules-heading">
        <h2 id="modules-heading" class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-1">
            Modulok
        </h2>
        <p class="text-sm text-sand-500 mb-6">
            A kikapcsolt modul eltűnik a menüből, és az oldalai sem nyithatók meg
            közvetlen hivatkozással sem. A már felvitt tartalom nem törlődik:
            visszakapcsolás után minden a helyén lesz.
        </p>

        <!-- Fórum kapcsoló -->
        <div class="flex items-start gap-4 p-4 rounded-xl bg-sand-50 border border-sand-200">
            <input type="checkbox" id="forum_enabled" name="forum_enabled" value="1"
                   class="mt-0.5 w-5 h-5 shrink-0 rounded border-sand-300 text-billiard-green-700 focus:ring-billiard-green-600"
                   <?= $forumEnabled ? 'checked' : '' ?>
                   aria-describedby="forum-enabled-hint">
            <div class="min-w-0">
                <label for="forum_enabled" class="font-semibold text-sand-900 cursor-pointer">
                    Fórum
                </label>
                <span class="badge <?= $forumEnabled ? 'badge-green' : 'badge-neutral' ?> ml-2">
                    <?= $forumEnabled ? 'Aktív' : 'Inaktív' ?>
                </span>
                <p id="forum-enabled-hint" class="text-sm text-sand-500 mt-1">
                    Topikok és hozzászólások a látogatóknak. Bekapcsolva megjelenik a
                    főmenüben, és a szervezői felületen is megnyílik a moderálás.
                </p>
                <?php if ($forumEnabled): ?>
                    <a href="/admin/forum" class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600 hover:underline mt-2">
                        Fórum moderálása
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ranglista kapcsoló -->
        <div class="flex items-start gap-4 p-4 rounded-xl bg-sand-50 border border-sand-200 mt-4">
            <input type="checkbox" id="ranking_enabled" name="ranking_enabled" value="1"
                   class="mt-0.5 w-5 h-5 shrink-0 rounded border-sand-300 text-billiard-green-700 focus:ring-billiard-green-600"
                   <?= $rankingEnabled ? 'checked' : '' ?>
                   aria-describedby="ranking-enabled-hint">
            <div class="min-w-0">
                <label for="ranking_enabled" class="font-semibold text-sand-900 cursor-pointer">
                    Ranglista
                </label>
                <span class="badge <?= $rankingEnabled ? 'badge-green' : 'badge-neutral' ?> ml-2">
                    <?= $rankingEnabled ? 'Aktív' : 'Inaktív' ?>
                </span>
                <p id="ranking-enabled-hint" class="text-sm text-sand-500 mt-1">
                    A szezon pontversenye versenyenkénti bontásban. A pontszámok a
                    szervezői felületen szerkeszthetők, az összesítés automatikusan
                    követi őket.
                </p>
                <?php if ($rankingEnabled): ?>
                    <a href="/admin/ranglista" class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600 hover:underline mt-2">
                        Pontszámok kezelése
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-6 pt-5 border-t border-sand-200">
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Beállítások mentése
            </button>
            <a href="/admin" class="btn btn-ghost">Mégse</a>
        </div>
    </section>

    <!--
        Közvetítés
        Nem kapcsoló, hanem cím: a menüpont akkor jelenik meg, ha van megadva
        hivatkozás. Így nem lehet "bekapcsolva, de üres" állapotba jutni, ami
        törött menüpontot adna.
    -->
    <section class="card p-6 md:p-7 mt-6" aria-labelledby="broadcast-heading">
        <h2 id="broadcast-heading" class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-1">
            Közvetítés
        </h2>
        <p class="text-sm text-sand-500 mb-6">
            A menüpont egy külső oldalra visz, új lapon megnyitva - például egy
            YouTube vagy Facebook élő közvetítésre. Ha a címet üresen hagyod, a
            menüpont nem jelenik meg.
        </p>

        <div class="space-y-4">
            <div>
                <label for="broadcast_url" class="label">Közvetítés címe</label>
                <input type="url" id="broadcast_url" name="broadcast_url"
                       value="<?= e($broadcastUrl) ?>"
                       placeholder="https://www.youtube.com/@..."
                       class="field" aria-describedby="broadcast-url-hint">
                <p id="broadcast-url-hint" class="field-hint">
                    http:// vagy https:// kezdettel. Más címsémát nem fogadunk el.
                </p>
            </div>

            <div>
                <label for="broadcast_label" class="label">Menüpont felirata</label>
                <input type="text" id="broadcast_label" name="broadcast_label"
                       value="<?= e($broadcastLabel) ?>"
                       maxlength="30" placeholder="Közvetítés"
                       class="field" aria-describedby="broadcast-label-hint">
                <p id="broadcast-label-hint" class="field-hint">
                    Üresen hagyva a menüpont felirata &bdquo;Közvetítés&rdquo; lesz.
                </p>
            </div>
        </div>

        <?php if ($broadcastUrl !== ''): ?>
            <a href="<?= e($broadcastUrl) ?>" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600 hover:underline mt-4">
                Hivatkozás kipróbálása
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                </svg>
            </a>
        <?php endif; ?>

        <div class="flex flex-wrap items-center gap-3 mt-6 pt-5 border-t border-sand-200">
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Beállítások mentése
            </button>
        </div>
    </section>
</form>
