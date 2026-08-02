<?php
/**
 * Admin új verseny létrehozása
 *
 * A mezőket a közös _form.php részlet rendereli.
 *
 * @var array $errors Validációs hibák
 * @var array $data   Űrlap adatok (name, date, venue, registrationDeadline)
 */
$formAction = '/admin/versenyek/uj';
$submitLabel = 'Verseny létrehozása';
?>
<div class="max-w-2xl">

    <a href="/admin/versenyek" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a versenyekhez
    </a>

    <header class="mb-7">
        <p class="eyebrow mb-2">Új esemény</p>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tightest text-billiard-green-900">
            Új verseny létrehozása
        </h1>
        <p class="text-sand-500 mt-1">
            A verseny a nevezési határidő lejártáig megjelenik a nyitott versenyek listájában.
        </p>
    </header>

    <?php require __DIR__ . '/_form.php'; ?>
</div>
