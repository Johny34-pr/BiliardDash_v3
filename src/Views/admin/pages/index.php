<?php
/**
 * Admin tartalmi oldalak listája
 *
 * Az oldalak fix útvonalon élnek, ezért itt nincs "Új oldal" művelet és
 * törlés sem: csak a meglévők tartalma szerkeszthető. Új oldal felvétele
 * migrációval történik, hogy a menü hivatkozásai ne törhessenek el.
 *
 * @var array $pages Oldalak tömbje (id, slug, title, updated_at)
 */
?>
<header class="mb-7">
    <p class="eyebrow mb-2">Tartalom</p>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Oldalak kezelése</h1>
    <p class="text-sand-500 mt-1">
        A menüben és a láblécben megjelenő szöveges oldalak tartalma.
    </p>
</header>

<?php if (empty($pages)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Nincsenek tartalmi oldalak</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Az oldalakat migráció hozza létre. Ellenőrizd, hogy lefutott-e a
            007-es migráció.
        </p>
    </div>

<?php else: ?>
    <div class="card overflow-hidden">
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Oldal</th>
                        <th scope="col">Útvonal</th>
                        <th scope="col">Utolsó módosítás</th>
                        <th scope="col" class="text-right">Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <tr>
                            <td>
                                <span class="font-medium text-sand-900"><?= e($page['title']) ?></span>
                            </td>
                            <td>
                                <code class="text-sm text-sand-600">/<?= e($page['slug']) ?></code>
                            </td>
                            <td class="whitespace-nowrap">
                                <time datetime="<?= e($page['updated_at']) ?>">
                                    <?= date('Y. m. d. H:i', strtotime($page['updated_at'])) ?>
                                </time>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    <a href="/<?= e($page['slug']) ?>" target="_blank" rel="noopener"
                                       class="btn btn-ghost btn-sm">
                                        Megnézés
                                    </a>
                                    <a href="/admin/oldalak/<?= e($page['id']) ?>/szerkeszt"
                                       class="btn btn-secondary btn-sm">
                                        Szerkesztés
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-sm text-sand-500 mt-5">
        Az oldalak útvonala nem módosítható, mert a menü és a lábléc közvetlenül
        ezekre hivatkozik.
    </p>
<?php endif; ?>
