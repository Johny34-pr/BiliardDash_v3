<?php
/**
 * Admin verseny szerkesztése
 *
 * A mezőket a közös _form.php részlet rendereli.
 * A szerkesztés megőrzi a meglévő nevezéseket (Requirement 6.6).
 *
 * @var array $errors      Validációs hibák
 * @var array $data        Űrlap adatok (name, date, venue, registrationOpensAt, registrationDeadline)
 * @var array $competition Verseny adatok (id, registrant_count)
 */
$formAction = '/admin/versenyek/' . $competition['id'] . '/szerkeszt';
$submitLabel = 'Változások mentése';
?>
<div class="max-w-2xl">

    <a href="/admin/versenyek" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a versenyekhez
    </a>

    <header class="flex flex-wrap items-end justify-between gap-4 mb-7">
        <div>
            <p class="eyebrow mb-2">Szerkesztés</p>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
                Verseny szerkesztése
            </h1>
        </div>
        <?php if (isset($competition['registrant_count'])): ?>
            <a href="/admin/versenyek/<?= e($competition['id']) ?>/nevezesek"
               class="btn btn-secondary btn-sm">
                <?= (int)$competition['registrant_count'] ?> nevező
            </a>
        <?php endif; ?>
    </header>

    <?php if (!empty($competition['registrant_count'])): ?>
        <div class="alert alert-info mb-6">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
            </svg>
            <span class="text-sm">
                A módosítás nem érinti a már beérkezett nevezéseket.
            </span>
        </div>
    <?php endif; ?>

    <?php require __DIR__ . '/_form.php'; ?>
</div>
