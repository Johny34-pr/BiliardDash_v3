<?php
/**
 * Admin fórum moderálás - topikok
 *
 * Három eszköz áll rendelkezésre, növekvő súlyú sorrendben:
 *   - Lezárás: a topik olvasható marad, de nem fogad új hozzászólást
 *   - Elrejtés: eltűnik a publikus listáról, de megmarad (visszavonható)
 *   - Törlés: véglegesen eltávolítja a topikot és a hozzászólásait
 *
 * @var array $topics Összes topik (az elrejtettekkel együtt)
 */
$hiddenCount = 0;
$lockedCount = 0;
foreach ($topics as $t) {
    $hiddenCount += ((int) $t['is_hidden']) === 1 ? 1 : 0;
    $lockedCount += ((int) $t['is_locked']) === 1 ? 1 : 0;
}
$visibleCount = count($topics) - $hiddenCount;
?>

<header class="flex flex-wrap items-end justify-between gap-4 mb-7">
    <div>
        <p class="eyebrow mb-2">Közösség</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Fórum topikok</h1>
        <?php if (!empty($topics)): ?>
            <p class="text-sand-500 mt-1">
                <?= $visibleCount ?> látható
                <?php if ($hiddenCount > 0): ?>&middot; <?= $hiddenCount ?> elrejtve<?php endif; ?>
                <?php if ($lockedCount > 0): ?>&middot; <?= $lockedCount ?> lezárva<?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="flex items-center gap-2">
        <a href="/admin/forum/hozzaszolasok" class="btn btn-secondary btn-sm">Hozzászólások</a>
        <a href="/forum" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Fórum megnyitása</a>
    </div>
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
            Amint valaki témát nyit a fórumon, itt tudod moderálni.
        </p>
    </div>

<?php else: ?>
    <div class="space-y-3">
        <?php foreach ($topics as $topic): ?>
            <?php
            $isHidden = ((int) $topic['is_hidden']) === 1;
            $isLocked = ((int) $topic['is_locked']) === 1;
            ?>
            <article class="card p-5 <?= $isHidden ? 'bg-sand-50 border-dashed' : '' ?>">
                <div class="flex flex-col lg:flex-row lg:items-start gap-4">

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <a href="/forum/<?= e($topic['id']) ?>" target="_blank" rel="noopener"
                               class="font-semibold text-billiard-green-900 hover:underline">
                                <?= e($topic['title']) ?>
                            </a>

                            <?php if ($isHidden): ?>
                                <span class="badge badge-gold">Elrejtve</span>
                            <?php endif; ?>
                            <?php if ($isLocked): ?>
                                <span class="badge badge-neutral">Lezárva</span>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-sand-500 mb-2">
                            <span><?= e($topic['author_name']) ?></span>
                            <?php if ($topic['user_id'] === null): ?>
                                <span class="badge badge-neutral">Vendég</span>
                            <?php else: ?>
                                <span class="badge badge-green">Fiókkal</span>
                            <?php endif; ?>
                            <span aria-hidden="true">&middot;</span>
                            <span><?= (int) $topic['comment_count'] ?> hozzászólás</span>
                            <span aria-hidden="true">&middot;</span>
                            <time datetime="<?= e($topic['created_at']) ?>">
                                <?= date('Y. m. d. H:i', strtotime($topic['created_at'])) ?>
                            </time>
                        </div>

                        <p class="text-sm <?= $isHidden ? 'text-sand-500' : 'text-sand-700' ?> leading-relaxed clamp-3"
                           style="overflow-wrap: anywhere;"><?= e($topic['body']) ?></p>
                    </div>

                    <!-- Moderálási műveletek -->
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <form method="POST" action="/admin/forum/<?= e($topic['id']) ?>/lezar">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <?= $isLocked ? 'Újranyitás' : 'Lezárás' ?>
                            </button>
                        </form>
                        <form method="POST" action="/admin/forum/<?= e($topic['id']) ?>/elrejt">
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <?= $isHidden ? 'Visszaállítás' : 'Elrejtés' ?>
                            </button>
                        </form>
                        <form method="POST" action="/admin/forum/<?= e($topic['id']) ?>/torol"
                              data-confirm="Biztosan véglegesen törlöd ezt a topikot? A hozzászólásai is törlődnek, a művelet nem visszavonható.">
                            <button type="submit" class="btn btn-danger btn-sm">Törlés</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <p class="text-xs text-sand-500 mt-5">
        A lezárás és az elrejtés visszavonható. A törlés véglegesen eltávolítja a
        topikot és az összes hozzászólását.
    </p>
<?php endif; ?>
