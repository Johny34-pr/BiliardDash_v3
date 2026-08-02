<?php
/**
 * Hír részletes nézet
 *
 * Olvasásra optimalizált tipográfia: szűk hasáb (max-w-reading),
 * nagyobb sortávolság, az .article-body osztály stílusozza a rich text
 * tartalmat (címsorok, listák, hivatkozások, idézetek).
 *
 * @var array $news Hír adatok (id, title, content, published_at)
 */
?>

<article class="max-w-reading mx-auto reveal">

    <!-- Visszalépés -->
    <a href="/" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-8">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a főoldalra
    </a>

    <!-- Cikk fejléc -->
    <header class="mb-8 pb-8 border-b border-sand-200">
        <time class="inline-flex items-center gap-1.5 text-sm font-medium text-billiard-green-600 mb-4"
              datetime="<?= e($news['published_at']) ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
            <?= date('Y. m. d.', strtotime($news['published_at'])) ?>
        </time>

        <h1 class="text-3xl md:text-[2.75rem] font-bold tracking-tightest leading-[1.12] text-billiard-green-900">
            <?= e($news['title']) ?>
        </h1>
    </header>

    <!-- Cikk tartalom (rich text az admin szerkesztőből) -->
    <div class="article-body">
        <?= $news['content'] ?>
    </div>

    <!-- Cikk lábléc -->
    <footer class="mt-12 pt-8 border-t border-sand-200 flex flex-wrap items-center justify-between gap-4">
        <a href="/" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Összes hír
        </a>
        <a href="/nevezes" class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600 hover:text-billiard-green-700 transition-colors">
            Versenyek és nevezés
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
            </svg>
        </a>
    </footer>
</article>
