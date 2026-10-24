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

    <!--
        Megosztás
        A cikk alatt, mert elolvasás után van értelme megosztani.

        A Facebook és a Messenger sima hivatkozás, tehát JavaScript nélkül is
        működik. A vágólapra másolás és a rendszer saját megosztó panelje
        szkriptet igényel: az utóbbi gombja rejtve indul, és csak akkor
        jelenik meg, ha a böngésző támogatja.

        Az előnézeti kép, a cím és a leírás a head.php Open Graph meta
        elemeiből jön - azokat a NewsController állítja be.
    -->
    <section class="mt-12 pt-8 border-t border-sand-200 no-print"
             aria-labelledby="share-heading"
             data-share
             data-share-url="<?= e(siteUrl('/hirek/' . $news['id'])) ?>"
             data-share-title="<?= e($news['title']) ?>">

        <h2 id="share-heading" class="text-sm font-semibold uppercase tracking-wider text-sand-500 mb-4">
            Megosztás
        </h2>

        <div class="flex flex-wrap items-center gap-2">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(siteUrl('/hirek/' . $news['id'])) ?>"
               target="_blank" rel="noopener noreferrer"
               class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.891h-2.33v6.987C18.343 21.128 22 16.991 22 12z"/>
                </svg>
                Facebook
            </a>

            <a href="mailto:?subject=<?= rawurlencode($news['title']) ?>&amp;body=<?= rawurlencode(siteUrl('/hirek/' . $news['id'])) ?>"
               class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
                E-mail
            </a>

            <button type="button" class="btn btn-secondary btn-sm js-share-copy">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                </svg>
                Hivatkozás másolása
            </button>

            <!-- Csak akkor jelenik meg, ha a böngésző támogatja -->
            <button type="button" class="btn btn-secondary btn-sm hidden js-share-native">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z"/>
                </svg>
                Megosztás&hellip;
            </button>
        </div>

        <p class="js-share-feedback hidden text-sm font-medium text-billiard-green-600 mt-3" role="status"></p>
    </section>

    <!-- Cikk lábléc -->
    <footer class="mt-10 pt-8 border-t border-sand-200 flex flex-wrap items-center justify-between gap-4">
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
