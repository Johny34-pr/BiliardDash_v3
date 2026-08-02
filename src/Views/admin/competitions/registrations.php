<?php
/**
 * Admin verseny nevezéseinek listája
 *
 * @var array $competition   Verseny adatok (id, name, date, venue, registrant_count)
 * @var array $registrations Nevezések tömbje (full_name, email, phone, registered_at)
 */
?>
<a href="/admin/versenyek" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
    </svg>
    Vissza a versenyekhez
</a>

<header class="flex flex-wrap items-end justify-between gap-4 mb-7">
    <div class="min-w-0">
        <p class="eyebrow mb-2">Nevezések</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
            <?= e($competition['name']) ?>
        </h1>
        <dl class="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-sm text-sand-500">
            <div class="inline-flex gap-1.5">
                <dt>Dátum:</dt>
                <dd class="font-medium text-sand-700">
                    <time datetime="<?= e($competition['date']) ?>">
                        <?= date('Y. m. d.', strtotime($competition['date'])) ?>
                    </time>
                </dd>
            </div>
            <div class="inline-flex gap-1.5">
                <dt>Helyszín:</dt>
                <dd class="font-medium text-sand-700"><?= e($competition['venue']) ?></dd>
            </div>
            <div class="inline-flex gap-1.5">
                <dt>Összesen:</dt>
                <dd class="font-medium text-sand-700"><?= (int)$competition['registrant_count'] ?> nevező</dd>
            </div>
        </dl>
    </div>

    <?php if (!empty($registrations)): ?>
        <a href="/admin/versenyek/<?= e($competition['id']) ?>/export" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            CSV export
        </a>
    <?php endif; ?>
</header>

<?php if (empty($registrations)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Erre a versenyre még senki nem nevezett</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            A beérkező nevezések itt fognak megjelenni, és innen exportálhatod őket.
        </p>
    </div>

<?php else: ?>
    <div class="card overflow-hidden">
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col" class="w-12">#</th>
                        <th scope="col">Név</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Telefon</th>
                        <th scope="col">Nevezés ideje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $index => $registration): ?>
                        <tr>
                            <td class="text-sand-400 tabular-nums"><?= $index + 1 ?></td>
                            <td>
                                <span class="font-medium text-sand-900"><?= e($registration['full_name']) ?></span>
                            </td>
                            <td>
                                <a href="mailto:<?= e($registration['email']) ?>"
                                   class="text-billiard-green-600 hover:underline">
                                    <?= e($registration['email']) ?>
                                </a>
                            </td>
                            <td>
                                <a href="tel:<?= e(preg_replace('/\s+/', '', $registration['phone'])) ?>"
                                   class="hover:text-billiard-green-700 transition-colors">
                                    <?= e($registration['phone']) ?>
                                </a>
                            </td>
                            <td class="whitespace-nowrap text-sand-500">
                                <time datetime="<?= e($registration['registered_at']) ?>">
                                    <?= date('Y. m. d. H:i', strtotime($registration['registered_at'])) ?>
                                </time>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
