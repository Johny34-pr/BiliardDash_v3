<?php
/**
 * Főoldal nézet - Hírlista
 *
 * Felépítés:
 *   1. Hero szekció (bevezető, arculati elem)
 *   2. Kiemelt (legfrissebb) hír nagy kártyán
 *   3. További hírek rácsban
 *
 * Reszponzív: egyoszlopos mobil, kétoszlopos tablet/asztali rács.
 *
 * @var array $news  Hírek tömbje (id, title, summary, published_at)
 * @var bool  $error Hiba történt-e a betöltés során
 */

$featured = null;
$rest = [];

if (!$error && !empty($news)) {
    $featured = $news[0];
    $rest = array_slice($news, 1);
}
?>

<!-- ===================== Hero ===================== -->
<section class="relative overflow-hidden rounded-4xl bg-billiard-green-900 text-white mb-12 md:mb-16 reveal">
    <!-- Dekoratív háttér: lágy zöld/arany fények -->
    <div class="absolute inset-0 opacity-70" aria-hidden="true">
        <div class="absolute -top-24 -right-16 w-80 h-80 rounded-full bg-billiard-green-600/35 blur-3xl"></div>
        <div class="absolute -bottom-28 -left-10 w-80 h-80 rounded-full bg-billiard-gold-500/15 blur-3xl"></div>
    </div>

    <div class="relative px-6 py-14 md:px-14 md:py-20 max-w-3xl">
        <p class="eyebrow text-billiard-gold-300 mb-4">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            Okányi Biliárd Klub
        </p>
        <h1 class="text-4xl md:text-[3.25rem] font-bold tracking-tightest leading-[1.08] mb-5">
            Hírek, versenyek és<br class="hidden sm:block">
            pillanatok az asztal mellől
        </h1>
        <p class="text-base md:text-lg text-white/70 leading-relaxed mb-8 max-w-xl">
            Kövesd a közösség eseményeit, böngészd a versenyekről készült fotókat,
            és nevezz online a következő megmérettetésre.
        </p>
        <div class="flex flex-wrap gap-3">
            <a href="/nevezes" class="btn btn-gold">
                Nevezés versenyre
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </a>
            <a href="/galeria" class="btn bg-white/10 text-white border-white/20 hover:bg-white/15">
                Galéria megtekintése
            </a>
        </div>
    </div>
</section>

<!-- ===================== Hírek ===================== -->
<section aria-labelledby="news-heading">
    <div class="flex items-end justify-between gap-4 mb-8">
        <div>
            <h2 id="news-heading" class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900 rule-gold">
                Legfrissebb hírek
            </h2>
        </div>
        <?php if (!$error && !empty($news)): ?>
            <p class="hidden sm:block text-sm text-sand-500 shrink-0 pb-1">
                <?= count($news) ?> hír
            </p>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error" role="alert">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <div>
                <p class="alert-title">A tartalom átmenetileg nem elérhető</p>
                <p class="text-sm mt-0.5">Kérjük, próbálja újra néhány perc múlva.</p>
            </div>
        </div>

    <?php elseif (empty($news)): ?>
        <div class="empty-state">
            <span class="empty-state-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 01-2 2h-2m0-13v13M7 8h6M7 12h6M7 16h3"/>
                </svg>
            </span>
            <p class="font-semibold text-sand-900">Jelenleg nincsenek hírek</p>
            <p class="text-sm text-sand-500 mt-1 max-w-sm">
                Amint megjelenik az első bejegyzés, itt fogod látni.
            </p>
        </div>

    <?php else: ?>
        <!-- Kiemelt hír -->
        <article class="card card-interactive overflow-hidden mb-6 reveal reveal-1">
            <a href="/hirek/<?= e($featured['id']) ?>" class="block p-6 md:p-9">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <span class="badge badge-gold">Legfrissebb</span>
                    <time class="text-sm text-sand-500" datetime="<?= e($featured['published_at']) ?>">
                        <?= date('Y. m. d.', strtotime($featured['published_at'])) ?>
                    </time>
                </div>
                <h3 class="text-2xl md:text-[1.75rem] font-bold tracking-tightest leading-snug text-billiard-green-900 mb-3">
                    <?= e($featured['title']) ?>
                </h3>
                <?php if (!empty($featured['summary'])): ?>
                    <p class="text-sand-700 leading-relaxed clamp-3 mb-5 max-w-2xl">
                        <?= e(mb_substr($featured['summary'], 0, 200)) ?>
                    </p>
                <?php endif; ?>
                <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600">
                    Tovább a hírhez
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                </span>
            </a>
        </article>

        <!-- További hírek -->
        <?php if (!empty($rest)): ?>
            <div class="grid gap-5 md:grid-cols-2">
                <?php foreach ($rest as $i => $item): ?>
                    <article class="card card-interactive overflow-hidden reveal reveal-<?= min($i + 2, 5) ?>">
                        <a href="/hirek/<?= e($item['id']) ?>" class="flex flex-col h-full p-5 md:p-6">
                            <time class="text-xs font-medium text-sand-500 mb-2.5" datetime="<?= e($item['published_at']) ?>">
                                <?= date('Y. m. d.', strtotime($item['published_at'])) ?>
                            </time>
                            <h3 class="text-lg font-semibold leading-snug text-billiard-green-900 mb-2 clamp-2">
                                <?= e($item['title']) ?>
                            </h3>
                            <?php if (!empty($item['summary'])): ?>
                                <p class="text-sm text-sand-700 leading-relaxed clamp-3">
                                    <?= e(mb_substr($item['summary'], 0, 200)) ?>
                                </p>
                            <?php endif; ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
