<?php
/**
 * Szerkeszthető tartalmi oldal megjelenítése (Rólunk, Emlékoldal, Adatkezelés)
 *
 * Ugyanazt az olvasásra optimalizált tipográfiát használja, mint a hírek:
 * szűk hasáb és az .article-body osztály stílusozza a rich text tartalmat.
 *
 * A tartalom escape nélkül kerül kiírásra, mert a szervezői szerkesztőből
 * származó HTML - ez az alkalmazásban egységes döntés (lásd news/show.php).
 *
 * @var array $page Oldal adatai (id, slug, title, content, updated_at)
 */
?>

<article class="max-w-reading mx-auto reveal">

    <header class="mb-8 pb-8 border-b border-sand-200">
        <p class="eyebrow mb-3">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            <?= e($page['title']) ?>
        </p>
        <h1 class="text-3xl md:text-[2.75rem] font-bold tracking-tightest leading-[1.12] text-billiard-green-900">
            <?= e($page['title']) ?>
        </h1>

        <?php if (!empty($page['updated_at'])): ?>
            <p class="text-sm text-sand-500 mt-4">
                Utolsó módosítás:
                <time datetime="<?= e($page['updated_at']) ?>">
                    <?= date('Y. m. d.', strtotime($page['updated_at'])) ?>
                </time>
            </p>
        <?php endif; ?>
    </header>

    <!-- Tartalom a szervezői szerkesztőből -->
    <div class="article-body">
        <?= $page['content'] ?>
    </div>

    <footer class="mt-12 pt-8 border-t border-sand-200 flex flex-wrap items-center gap-4">
        <a href="/" class="btn btn-secondary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Vissza a főoldalra
        </a>

        <?php if (\App\Core\Session::isAdmin()): ?>
            <a href="/admin/oldalak/<?= e($page['id']) ?>/szerkeszt" class="btn btn-ghost btn-sm">
                Oldal szerkesztése
            </a>
        <?php endif; ?>
    </footer>
</article>
