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

    <!--
        Jegyzőkönyv
        Külön űrlap, mert fájlfeltöltés: a verseny adatainak mentése és a
        dokumentum cserélése két független művelet. Ha egy űrlapban lennének,
        minden adatmódosításnál újra ki kellene választani a fájlt.
    -->
    <section class="mt-10" aria-labelledby="protocol-heading">
        <h2 id="protocol-heading" class="text-xl font-semibold tracking-tightest text-billiard-green-900 mb-2">
            Jegyzőkönyv
        </h2>
        <p class="text-sm text-sand-500 mb-4 max-w-reading">
            A feltöltött jegyzőkönyv a <strong>Versenyeink &rsaquo; Korábbi
            versenyek</strong> oldalon jelenik meg letölthető hivatkozásként,
            a verseny napja után.
        </p>

        <div class="card p-6">
            <?php if (!empty($competition['protocol_path'])): ?>
                <?php
                $protocolBytes = (int) ($competition['protocol_size'] ?? 0);
                $protocolSize = $protocolBytes >= 1048576
                    ? number_format($protocolBytes / 1048576, 1, ',', ' ') . ' MB'
                    : max(1, (int) round($protocolBytes / 1024)) . ' KB';
                ?>
                <div class="flex flex-wrap items-start justify-between gap-4 pb-5 mb-5 border-b border-sand-200">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">
                            Jelenlegi fájl
                        </p>
                        <a href="/versenyeink/<?= e($competition['id']) ?>/jegyzokonyv"
                           class="font-medium text-billiard-green-700 hover:underline break-all">
                            <?= e((string) $competition['protocol_name']) ?>
                        </a>
                        <p class="text-sm text-sand-500 mt-1">
                            <?= e($protocolSize) ?>
                            <?php if (!empty($competition['protocol_uploaded_at'])): ?>
                                &middot; feltöltve
                                <?= date('Y. m. d. H:i', strtotime((string) $competition['protocol_uploaded_at'])) ?>
                            <?php endif; ?>
                        </p>
                    </div>

                    <form method="POST"
                          action="/admin/versenyek/<?= e($competition['id']) ?>/jegyzokonyv/torol"
                          data-confirm="Biztosan törlöd a jegyzőkönyvet? A fájl véglegesen törlődik.">
                        <button type="submit" class="btn btn-danger btn-sm">Törlés</button>
                    </form>
                </div>
            <?php endif; ?>

            <form method="POST"
                  action="/admin/versenyek/<?= e($competition['id']) ?>/jegyzokonyv"
                  enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="protocol" class="label">
                        <?= !empty($competition['protocol_path']) ? 'Csere új fájlra' : 'Jegyzőkönyv kiválasztása' ?>
                    </label>
                    <input type="file" id="protocol" name="protocol" required
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.ods,.txt,.csv"
                           class="field" aria-describedby="protocol-hint">
                    <p id="protocol-hint" class="field-hint">
                        PDF, DOC(X), XLS(X), ODT, ODS, TXT vagy CSV, legfeljebb 20 MB.
                        <?php if (!empty($competition['protocol_path'])): ?>
                            Az új fájl felülírja a jelenlegit, a régi törlődik.
                        <?php endif; ?>
                    </p>
                </div>

                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    <?= !empty($competition['protocol_path']) ? 'Csere' : 'Feltöltés' ?>
                </button>
            </form>
        </div>
    </section>
</div>
