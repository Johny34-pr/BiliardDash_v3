<?php
/**
 * Admin fórum moderálás - hozzászólások
 *
 * Az összes topik hozzászólásai egy helyen, a legfrissebbek elöl. Az elrejtés
 * az elsődleges eszköz, mert visszavonható: a hozzászólás eltűnik a publikus
 * listáról, de megmarad az adatbázisban.
 *
 * @var array $comments Összes hozzászólás (az elrejtettekkel együtt)
 */
$hiddenCount = 0;
foreach ($comments as $c) {
    $hiddenCount += ((int) $c['is_hidden']) === 1 ? 1 : 0;
}
$visibleCount = count($comments) - $hiddenCount;
?>

<header class="flex flex-wrap items-end justify-between gap-4 mb-7">
    <div>
        <p class="eyebrow mb-2">Közösség</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Fórum hozzászólások</h1>
        <?php if (!empty($comments)): ?>
            <p class="text-sand-500 mt-1">
                <?= $visibleCount ?> látható
                <?php if ($hiddenCount > 0): ?>&middot; <?= $hiddenCount ?> elrejtve<?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="flex items-center gap-2">
        <a href="/admin/forum" class="btn btn-secondary btn-sm">Topikok</a>
        <a href="/forum" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Fórum megnyitása</a>
    </div>
</header>

<?php if (empty($comments)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincs hozzászólás</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Amint valaki hozzászól egy topikhoz, itt tudod moderálni.
        </p>
    </div>

<?php else: ?>
    <div class="space-y-3">
        <?php foreach ($comments as $comment): ?>
            <?php
            $isHidden = ((int) $comment['is_hidden']) === 1;
            $score = (int) $comment['upvotes'] - (int) $comment['downvotes'];
            ?>
            <article class="card p-5 <?= $isHidden ? 'bg-sand-50 border-dashed' : '' ?>">
                <div class="flex flex-col md:flex-row md:items-start gap-4">

                    <div class="flex-1 min-w-0">
                        <!-- Melyik topikban -->
                        <a href="/forum/<?= e($comment['topic_id']) ?>" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 text-xs font-medium text-billiard-green-600 hover:underline mb-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
                            </svg>
                            <?= e($comment['topic_title']) ?>
                        </a>

                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="font-semibold text-billiard-green-900">
                                <?= e($comment['author_name']) ?>
                            </span>

                            <?php if ($comment['user_id'] === null): ?>
                                <span class="badge badge-neutral">Vendég</span>
                            <?php else: ?>
                                <span class="badge badge-green">Fiókkal</span>
                            <?php endif; ?>

                            <?php if ($isHidden): ?>
                                <span class="badge badge-gold">Elrejtve</span>
                            <?php endif; ?>

                            <time class="text-xs text-sand-500" datetime="<?= e($comment['created_at']) ?>">
                                <?= date('Y. m. d. H:i', strtotime($comment['created_at'])) ?>
                            </time>

                            <span class="text-xs tabular-nums <?= $score < 0 ? 'text-red-600 font-semibold' : 'text-sand-500' ?>"
                                  title="<?= (int) $comment['upvotes'] ?> felértékelés, <?= (int) $comment['downvotes'] ?> leértékelés">
                                <?= $score > 0 ? '+' . $score : $score ?> pont
                            </span>
                        </div>

                        <p class="text-sm <?= $isHidden ? 'text-sand-500' : 'text-sand-700' ?> leading-relaxed"
                           style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= e($comment['body']) ?></p>
                    </div>

                    <!-- Moderálási műveletek -->
                    <div class="flex items-center gap-2 shrink-0">
                        <form method="POST" action="/admin/forum/hozzaszolas/<?= e($comment['id']) ?>/elrejt">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <?= $isHidden ? 'Visszaállítás' : 'Elrejtés' ?>
                            </button>
                        </form>
                        <form method="POST" action="/admin/forum/hozzaszolas/<?= e($comment['id']) ?>/torol"
                              data-confirm="Biztosan véglegesen törlöd ezt a hozzászólást? A művelet nem visszavonható.">
                            <button type="submit" class="btn btn-danger btn-sm">Törlés</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="text-xs text-sand-500 mt-5">
        Az elrejtés visszavonható: a hozzászólás eltűnik a fórumról, de megmarad.
        A törlés véglegesen eltávolítja.
    </p>
<?php endif; ?>
