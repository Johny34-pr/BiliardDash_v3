<?php
/**
 * Admin hírek listája
 *
 * @var array $news Hírek tömbje (id, title, published_at)
 */
?>
<header class="flex flex-wrap items-end justify-between gap-4 mb-7">
    <div>
        <p class="eyebrow mb-2">Tartalom</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Hírek kezelése</h1>
        <?php if (!empty($news)): ?>
            <p class="text-sand-500 mt-1"><?= count($news) ?> bejegyzés</p>
        <?php endif; ?>
    </div>
    <a href="/admin/hirek/uj" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
        </svg>
        Új hír
    </a>
</header>

<?php if (empty($news)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 01-2 2h-2m0-13v13M7 8h6M7 12h6M7 16h3"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Még nincsenek hírek</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">Az első bejegyzés létrehozásával indulhat a hírfolyam.</p>
        <a href="/admin/hirek/uj" class="btn btn-primary btn-sm mt-6">Első hír létrehozása</a>
    </div>

<?php else: ?>
    <div class="card overflow-hidden">
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Cím</th>
                        <th scope="col">Publikálás</th>
                        <th scope="col" class="text-right">Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($news as $item): ?>
                        <tr>
                            <td>
                                <span class="font-medium text-sand-900"><?= e($item['title']) ?></span>
                            </td>
                            <td class="whitespace-nowrap text-sand-500">
                                <time datetime="<?= e($item['published_at']) ?>">
                                    <?= date('Y. m. d. H:i', strtotime($item['published_at'])) ?>
                                </time>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/hirek/<?= e($item['id']) ?>" target="_blank" rel="noopener"
                                       class="btn btn-ghost btn-sm" title="Megnyitás a weboldalon">
                                        Megnézés
                                    </a>
                                    <a href="/admin/hirek/<?= e($item['id']) ?>/szerkeszt" class="btn btn-secondary btn-sm">
                                        Szerkesztés
                                    </a>
                                    <form method="POST" action="/admin/hirek/<?= e($item['id']) ?>/torol"
                                          data-confirm="Biztosan törlöd ezt a hírt? A művelet nem visszavonható.">
                                        <button type="submit" class="btn btn-danger btn-sm">Törlés</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
