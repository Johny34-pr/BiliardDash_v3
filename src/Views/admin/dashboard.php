<?php
/**
 * Admin dashboard - áttekintő oldal
 *
 * Statisztikai kártyák és gyors műveletek. Az emoji ikonok helyett
 * egységes SVG ikonkészletet használ.
 *
 * @var int $newsCount        Hírek száma
 * @var int $albumCount       Albumok száma
 * @var int $competitionCount Versenyek száma
 */

$stats = [
    [
        'label' => 'Hírek',
        'value' => $newsCount,
        'url'   => '/admin/hirek',
        'cta'   => 'Hírek kezelése',
        'icon'  => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 01-2 2h-2m0-13v13M7 8h6M7 12h6M7 16h3',
    ],
    [
        'label' => 'Albumok',
        'value' => $albumCount,
        'url'   => '/admin/galeria',
        'cta'   => 'Galéria kezelése',
        'icon'  => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z',
    ],
    [
        'label' => 'Versenyek',
        'value' => $competitionCount,
        'url'   => '/admin/versenyek',
        'cta'   => 'Versenyek kezelése',
        'icon'  => 'M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-7.842c.982.143 1.954.317 2.916.52a6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0',
    ],
];

$quickActions = [
    [
        'url'   => '/admin/hirek/uj',
        'title' => 'Új hír létrehozása',
        'desc'  => 'Bejegyzés írása szerkesztővel',
        'icon'  => 'M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125',
    ],
    [
        'url'   => '/admin/galeria',
        'title' => 'Album vagy képfeltöltés',
        'desc'  => 'Fotók rendezése albumokba',
        'icon'  => 'M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z',
    ],
    [
        'url'   => '/admin/versenyek/uj',
        'title' => 'Új verseny létrehozása',
        'desc'  => 'Esemény és nevezési határidő',
        'icon'  => 'M12 6v12m6-6H6',
    ],
];
?>

<header class="mb-8">
    <p class="eyebrow mb-2">Adminisztráció</p>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">Áttekintés</h1>
    <p class="text-sand-500 mt-1">A tartalom állapota és a gyakori műveletek.</p>
</header>

<!-- Statisztikák -->
<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 mb-8">
    <?php foreach ($stats as $i => $stat): ?>
        <a href="<?= e($stat['url']) ?>"
           class="card card-interactive flex flex-col p-6 reveal reveal-<?= $i + 1 ?>">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1.5">
                        <?= e($stat['label']) ?>
                    </p>
                    <p class="text-4xl font-bold leading-none tracking-tightest text-billiard-green-900">
                        <?= (int)$stat['value'] ?>
                    </p>
                </div>
                <span class="grid place-items-center w-11 h-11 rounded-xl bg-billiard-green-50 text-billiard-green-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= e($stat['icon']) ?>"/>
                    </svg>
                </span>
            </div>
            <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600 mt-auto">
                <?= e($stat['cta']) ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Gyors műveletek -->
<section class="card p-6 md:p-7" aria-labelledby="quick-actions">
    <h2 id="quick-actions" class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-5">
        Gyors műveletek
    </h2>
    <div class="grid gap-3 md:grid-cols-3">
        <?php foreach ($quickActions as $action): ?>
            <a href="<?= e($action['url']) ?>"
               class="flex items-start gap-3.5 p-4 rounded-xl border border-sand-200 hover:border-billiard-green-300 hover:bg-billiard-green-50/60 transition-colors">
                <span class="grid place-items-center w-9 h-9 rounded-lg bg-billiard-green-900 text-billiard-gold-300 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="<?= e($action['icon']) ?>"/>
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-sand-900"><?= e($action['title']) ?></span>
                    <span class="block text-xs text-sand-500 mt-0.5"><?= e($action['desc']) ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
