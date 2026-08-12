<?php
/**
 * Fórum - topikok listája
 *
 * A topikok a legutóbbi aktivitás szerint rendezve jelennek meg, így a
 * mozgásban lévő beszélgetések kerülnek előre.
 *
 * @var array      $topics      Látható topikok
 * @var int        $total       Összes látható topik
 * @var int        $page        Aktuális oldal
 * @var int        $totalPages  Oldalak száma
 * @var array|null $currentUser Bejelentkezett felhasználó, vagy null
 */

/** Rövid, magyar nyelvű relatív időjelölés */
$relativeTime = static function (string $timestamp): string {
    $diff = time() - strtotime($timestamp);

    return match (true) {
        $diff < 60 => 'most',
        $diff < 3600 => intdiv($diff, 60) . ' perccel ezelőtt',
        $diff < 86400 => intdiv($diff, 3600) . ' órával ezelőtt',
        $diff < 604800 => intdiv($diff, 86400) . ' nappal ezelőtt',
        default => date('Y. m. d.', strtotime($timestamp)),
    };
};

/** Névből monogram az avatarhoz */
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $letters !== '' ? $letters : '?';
};
?>

<div class="max-w-3xl mx-auto reveal">

    <!-- Oldalfejléc -->
    <header class="mb-9">
        <p class="eyebrow mb-3">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            Közösség
        </p>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
                Fórum
            </h1>
            <a href="/forum/uj" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
                </svg>
                Új topik
            </a>
        </div>
        <p class="text-sand-500 mt-4 max-w-xl">
            Nyiss témát vagy szólj hozzá a meglévőkhöz. Belépés nélkül is működik.
            <?php if ($total > 0): ?>
                Jelenleg <?= $total ?> topik van.
            <?php endif; ?>
        </p>
    </header>

    <?php if (empty($topics)): ?>
        <div class="empty-state">
            <span class="empty-state-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/>
                </svg>
            </span>
            <p class="font-semibold text-sand-900">Még nincs topik</p>
            <p class="text-sm text-sand-500 mt-1 max-w-sm">
                Nyisd meg az első témát, és indítsd el a beszélgetést.
            </p>
            <a href="/forum/uj" class="btn btn-primary btn-sm mt-6">Első topik nyitása</a>
        </div>

    <?php else: ?>
        <ol class="space-y-3">
            <?php foreach ($topics as $i => $topic): ?>
                <li class="card card-interactive overflow-hidden reveal reveal-<?= min($i + 1, 5) ?>">
                    <a href="/forum/<?= e($topic['id']) ?>" class="flex items-start gap-4 p-5">

                        <!-- Avatar monogrammal -->
                        <span class="grid place-items-center w-10 h-10 shrink-0 rounded-full text-xs font-bold
                                     <?= $topic['user_id'] !== null
                                         ? 'bg-billiard-green-800 text-white'
                                         : 'bg-sand-200 text-sand-600' ?>"
                              aria-hidden="true">
                            <?= e($initials($topic['author_name'])) ?>
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h2 class="font-semibold text-billiard-green-900 leading-snug">
                                    <?= e($topic['title']) ?>
                                </h2>
                                <?php if (((int) $topic['is_locked']) === 1): ?>
                                    <span class="badge badge-neutral">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                        </svg>
                                        Lezárva
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- A nyitó bejegyzés első sorai -->
                            <p class="text-sm text-sand-600 leading-relaxed clamp-2 mb-2">
                                <?= e($topic['body']) ?>
                            </p>

                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-sand-500">
                                <span><?= e($topic['author_name']) ?></span>
                                <span aria-hidden="true">&middot;</span>
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                                    </svg>
                                    <?= (int) $topic['comment_count'] ?> hozzászólás
                                </span>
                                <span aria-hidden="true">&middot;</span>
                                <time datetime="<?= e($topic['last_activity_at']) ?>">
                                    <?= e($relativeTime($topic['last_activity_at'])) ?>
                                </time>
                            </div>
                        </div>

                        <svg class="w-5 h-5 shrink-0 text-sand-400 mt-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>

        <!-- Lapozás -->
        <?php if ($totalPages > 1): ?>
            <nav class="flex items-center justify-between gap-4 mt-8" aria-label="Topikok lapozása">
                <?php if ($page > 1): ?>
                    <a href="/forum?oldal=<?= $page - 1 ?>" class="btn btn-secondary btn-sm">Előző</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <span class="text-sm text-sand-500 tabular-nums"><?= $page ?> / <?= $totalPages ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="/forum?oldal=<?= $page + 1 ?>" class="btn btn-secondary btn-sm">Következő</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
