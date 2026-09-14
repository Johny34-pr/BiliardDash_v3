<?php
/**
 * Nyilvános nevezői lista
 *
 * Belépés nélkül is elérhető, ezért csak a nevezők nevét és a nevezés idejét
 * jeleníti meg. Az e-mail cím és a telefonszám személyes adat, azt kizárólag
 * a szervező látja az admin felületen.
 *
 * @var array $competition        Verseny adatai
 * @var array $registrants        Nevezők (full_name, registered_at)
 * @var bool  $deadlinePassed     Lejárt-e a nevezési határidő
 * @var bool  $registrationOpened Megnyílt-e már a nevezés
 */
$date = new DateTimeImmutable($competition['date']);
$deadline = new DateTimeImmutable($competition['registration_deadline']);

// A nevezés még nem nyílt meg: ilyenkor sem nevezni nem lehet, sem
// "Lezárult" állapotról nem beszélhetünk
$notYetOpen = !($registrationOpened ?? true);
$canRegister = !$deadlinePassed && !$notYetOpen;
?>

<div class="max-w-3xl mx-auto reveal">

    <a href="/nevezes" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a versenyekhez
    </a>

    <header class="mb-8">
        <p class="eyebrow mb-3">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            Nevezői lista
        </p>
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest leading-tight text-billiard-green-900">
            <?= e($competition['name']) ?>
        </h1>

        <dl class="flex flex-wrap gap-x-6 gap-y-1.5 mt-4 text-sm text-sand-700">
            <div class="inline-flex items-center gap-1.5">
                <dt class="text-sand-500">Dátum:</dt>
                <dd class="font-medium">
                    <time datetime="<?= e($competition['date']) ?>"><?= $date->format('Y. m. d.') ?></time>
                </dd>
            </div>
            <div class="inline-flex items-center gap-1.5">
                <dt class="text-sand-500">Helyszín:</dt>
                <dd class="font-medium"><?= e($competition['venue']) ?></dd>
            </div>
            <div class="inline-flex items-center gap-1.5">
                <dt class="text-sand-500">Nevezés:</dt>
                <dd>
                    <?php if ($notYetOpen): ?>
                        <span class="badge badge-gold">Hamarosan</span>
                    <?php else: ?>
                        <span class="badge <?= $deadlinePassed ? 'badge-neutral' : 'badge-green' ?>">
                            <?= $deadlinePassed ? 'Lezárult' : 'Nyitott' ?>
                        </span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
    </header>

    <?php if (empty($registrants)): ?>
        <div class="empty-state">
            <span class="empty-state-icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </span>
            <p class="font-semibold text-sand-900">Még nincs nevező</p>
            <p class="text-sm text-sand-500 mt-1 max-w-sm">
                <?php if ($notYetOpen): ?>
                    A nevezés még nem nyílt meg erre a versenyre.
                <?php elseif ($deadlinePassed): ?>
                    Erre a versenyre nem érkezett nevezés.
                <?php else: ?>
                    Legyél te az első, aki nevez erre a versenyre.
                <?php endif; ?>
            </p>
            <?php if ($canRegister): ?>
                <a href="/nevezes/<?= e($competition['id']) ?>" class="btn btn-primary btn-sm mt-6">Nevezés</a>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between gap-4 px-6 py-4 border-b border-sand-200">
                <h2 class="font-semibold text-billiard-green-900">
                    <?= count($registrants) ?> nevező
                </h2>
                <?php if ($canRegister): ?>
                    <a href="/nevezes/<?= e($competition['id']) ?>" class="btn btn-primary btn-sm">Nevezés</a>
                <?php endif; ?>
            </div>

            <ol class="divide-y divide-sand-200">
                <?php foreach ($registrants as $index => $registrant): ?>
                    <li class="flex items-center gap-4 px-6 py-3.5">
                        <span class="grid place-items-center w-7 h-7 shrink-0 rounded-full bg-sand-100 text-xs font-semibold text-sand-600 tabular-nums">
                            <?= $index + 1 ?>
                        </span>
                        <span class="flex-1 min-w-0 font-medium text-sand-900 truncate">
                            <?= e($registrant['full_name']) ?>
                        </span>
                        <time class="shrink-0 text-xs text-sand-500 tabular-nums"
                              datetime="<?= e($registrant['registered_at']) ?>">
                            <?= date('m. d. H:i', strtotime($registrant['registered_at'])) ?>
                        </time>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <p class="text-xs text-sand-500 mt-4">
            A listán a nevezők neve és a nevezés ideje szerepel. Az elérhetőségeket
            csak a szervező látja.
        </p>
    <?php endif; ?>
</div>
