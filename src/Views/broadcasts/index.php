<?php
/**
 * Közvetítések - élő adás és archívum
 *
 * Egyetlen lista, az élő adással az élén. A kereső a VERSENY NEVÉRE szűr (és
 * a közvetítés saját nevére), mert a régebbi adást a látogató így keresi.
 *
 * A lista nincs évek szerint csoportosítva, szemben a Korábbi versenyek
 * oldallal: ott a teljes névsor egyszerre látszik, itt viszont a keresés és a
 * lapozás a navigáció, és egy csoportcím lapozás közben félbevágva jelenne
 * meg.
 *
 * @var array  $broadcasts    A megjelenítendő közvetítések (élő elöl)
 * @var int    $total         A keresésre illeszkedő közvetítések száma
 * @var int    $libraryCount  Az összes közvetítés száma, keresés nélkül
 * @var array  $libraryState  Összesítés: total és live
 * @var string $term          A keresőszó, vagy üres string
 * @var string $searchQuery   A keresőszó query stringként, lapozáshoz
 * @var int    $page          Aktuális oldal
 * @var int    $totalPages    Oldalak száma
 */

$monthsShort = ['jan', 'feb', 'márc', 'ápr', 'máj', 'jún', 'júl', 'aug', 'szep', 'okt', 'nov', 'dec'];
$liveCount = $libraryState['live'] ?? 0;
?>

<header class="mb-8 reveal">
    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Közvetítés
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Közvetítések
        </h1>
        <?php if ($libraryCount > 0): ?>
            <p class="text-sm text-sand-500 pb-1">
                <?= $libraryCount ?> közvetítés
                <?php if ($liveCount > 0): ?>
                    &middot; <?= $liveCount ?> élő adás
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <p class="text-sand-600 mt-4 max-w-reading leading-relaxed">
        Az élő adás a lista élén található. A korábbi közvetítések is itt
        maradnak: a verseny nevére keresve bármelyik visszakereshető.
    </p>

    <div class="flex flex-wrap items-center gap-3 mt-5">
        <a href="/versenyeink/korabbi" class="btn btn-secondary btn-sm">Korábbi versenyek</a>
        <a href="/galeria" class="btn btn-ghost btn-sm">Galéria</a>
    </div>
</header>

<!--
    Kereső
    GET metódussal, hogy a találati lista címe megosztható és visszakereshető
    legyen (a böngésző előzményében is megmarad).
-->
<form method="GET" action="/kozvetitesek" role="search"
      class="card p-5 md:p-6 mb-8 reveal reveal-1">
    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
        <div class="flex-1 min-w-0">
            <label for="kereses" class="label">Keresés a verseny neve alapján</label>
            <input type="search" id="kereses" name="kereses" value="<?= e($term) ?>"
                   maxlength="100" placeholder="pl. Okány Kupa"
                   class="field" aria-describedby="kereses-hint">
            <p id="kereses-hint" class="field-hint">
                A verseny nevének egy része is elég. Ékezet és kis- vagy
                nagybetű nem számít.
            </p>
        </div>

        <div class="flex items-center gap-2 sm:pb-6">
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                Keresés
            </button>

            <?php if ($term !== ''): ?>
                <a href="/kozvetitesek" class="btn btn-ghost">Szűrés törlése</a>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if ($term !== '' && $total > 0): ?>
    <p class="text-sm text-sand-500 mb-5" role="status">
        <?= $total ?> közvetítés a keresésre: <strong class="text-sand-900"><?= e($term) ?></strong>
    </p>
<?php endif; ?>

<?php if ($broadcasts === []): ?>
    <?php if ($libraryCount === 0): ?>
        <!-- Még egyetlen közvetítés sincs felvéve -->
        <div class="empty-state">
            <span class="empty-state-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                </svg>
            </span>
            <p class="font-semibold text-sand-900">Még nincs közvetítés</p>
            <p class="text-sm text-sand-500 mt-1 max-w-sm">
                Amikor élő adás indul a teremből, itt jelenik meg - és az adás
                után is itt marad, hogy később visszakereshető legyen.
            </p>
            <a href="/versenyeink/kozelgo" class="btn btn-secondary btn-sm mt-6">Közelgő versenyek</a>
        </div>
    <?php else: ?>
        <!-- Van közvetítés, csak erre a keresésre nincs találat -->
        <div class="empty-state">
            <span class="empty-state-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </span>
            <p class="font-semibold text-sand-900">Nincs találat</p>
            <p class="text-sm text-sand-500 mt-1 max-w-sm">
                A(z) &bdquo;<?= e($term) ?>&rdquo; keresésre egyetlen közvetítés
                sem illeszkedik. Próbáld a verseny nevének rövidebb részével.
            </p>
            <a href="/kozvetitesek" class="btn btn-secondary btn-sm mt-6">
                Mind a <?= $libraryCount ?> közvetítés
            </a>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($broadcasts as $i => $broadcast): ?>
            <?php
            $isLive = (int) $broadcast['is_live'] === 1;
            $date = new DateTimeImmutable($broadcast['broadcast_date']);
            $competitionName = (string) ($broadcast['competition_name'] ?? '');

            /*
             * Az osztálylisták itt állnak össze, nem a class attribútumon
             * belül: a stíluslap-generátor a class attribútumba írt PHP
             * kifejezés minden szövegliterálját osztálynévnek veszi, így egy
             * ott elhelyezett összehasonlítás szövege is bekerülne a listába.
             * (Ugyanez az oka a _competition-card.php hasonló megoldásának.)
             */
            $cardClass = 'card overflow-hidden reveal reveal-' . min($i + 1, 5);
            $dateBadgeClass = 'flex sm:flex-col items-center justify-center gap-2 sm:gap-0 shrink-0'
                . ' px-6 py-4 sm:py-6 sm:w-28 text-white '
                . ($isLive ? 'bg-billiard-green-900' : 'bg-sand-700');
            $openButtonClass = 'btn w-full md:w-auto ' . ($isLive ? 'btn-gold' : 'btn-secondary');
            ?>
            <article class="<?= $cardClass ?>">
                <div class="flex flex-col sm:flex-row">

                    <!-- Dátum-jelvény, a verseny kártyákkal egyező formában -->
                    <div class="<?= $dateBadgeClass ?>">
                        <span class="text-3xl font-bold leading-none tracking-tightest">
                            <?= $date->format('j') ?>
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-billiard-gold-300 sm:mt-1">
                            <?= $monthsShort[(int) $date->format('n') - 1] ?>
                        </span>
                        <span class="text-xs text-white/45 sm:mt-0.5">
                            <?= $date->format('Y') ?>
                        </span>
                    </div>

                    <div class="flex-1 flex flex-col md:flex-row md:items-center gap-5 p-5 md:p-6">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <h2 class="text-lg md:text-xl font-semibold tracking-tightest text-billiard-green-900">
                                    <?= e($broadcast['title']) ?>
                                </h2>

                                <?php if ($isLive): ?>
                                    <span class="badge badge-gold">
                                        <span class="w-2 h-2 rounded-full bg-billiard-gold-700" aria-hidden="true"></span>
                                        Élő adás
                                    </span>
                                <?php endif; ?>
                            </div>

                            <dl class="flex flex-wrap gap-x-6 gap-y-1.5 text-sm text-sand-700">
                                <?php if ($competitionName !== ''): ?>
                                    <div class="inline-flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-sand-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/>
                                        </svg>
                                        <dt class="sr-only">Verseny</dt>
                                        <dd>
                                            <a href="/nevezes/<?= e($broadcast['competition_id']) ?>"
                                               class="font-medium text-billiard-green-600 hover:underline">
                                                <?= e($competitionName) ?>
                                            </a>
                                        </dd>
                                    </div>
                                <?php endif; ?>

                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-sand-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                                    </svg>
                                    <dt class="sr-only">Az adás napja</dt>
                                    <dd>
                                        <time datetime="<?= e($broadcast['broadcast_date']) ?>">
                                            <?= $date->format('Y. m. d.') ?>
                                        </time>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- A közvetítés külső oldalon fut, ezért új lapon nyílik -->
                        <div class="shrink-0">
                            <a href="<?= e($broadcast['url']) ?>" target="_blank" rel="noopener noreferrer"
                               class="<?= $openButtonClass ?>">
                                <?= $isLive ? 'Élő adás megnyitása' : 'Közvetítés megnyitása' ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                </svg>
                                <span class="sr-only">(új lapon nyílik)</span>
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <!--
        Lapozás
        A keresőszó a hivatkozásokba is bekerül, különben a második lapon
        elveszne a szűrés, és a látogató a teljes listában találná magát.
    -->
    <?php if ($totalPages > 1): ?>
        <?php $pageSeparator = $searchQuery !== '' ? '&amp;' : '?'; ?>
        <nav class="flex items-center justify-between gap-4 mt-8" aria-label="Közvetítések lapozása">
            <?php if ($page > 1): ?>
                <a href="/kozvetitesek<?= $searchQuery . $pageSeparator ?>oldal=<?= $page - 1 ?>"
                   class="btn btn-secondary btn-sm">Előző</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <span class="text-sm text-sand-500 tabular-nums"><?= $page ?> / <?= $totalPages ?></span>

            <?php if ($page < $totalPages): ?>
                <a href="/kozvetitesek<?= $searchQuery . $pageSeparator ?>oldal=<?= $page + 1 ?>"
                   class="btn btn-secondary btn-sm">Következő</a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
