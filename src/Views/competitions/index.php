<?php
/**
 * Verseny lista nézet - Nyitott versenyek
 *
 * Megjeleníti a nyitott versenyeket dátum szerinti növekvő sorrendben.
 * Minden versenynél: dátum-jelvény, név, helyszín, nevezési határidő,
 * nevezők száma és a nevezés gomb.
 *
 * @var array $competitions Nyitott versenyek tömbje
 */

/** Magyar hónapnevek rövidítése a dátum-jelvényhez */
$monthsShort = ['jan', 'feb', 'márc', 'ápr', 'máj', 'jún', 'júl', 'aug', 'szep', 'okt', 'nov', 'dec'];
$now = new DateTimeImmutable();
?>

<!-- Oldalfejléc -->
<header class="mb-10 reveal">
    <p class="eyebrow mb-3">
        <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
        Nevezés
    </p>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Nyitott versenyek
        </h1>
        <?php if (!empty($competitions)): ?>
            <p class="text-sm text-sand-500 pb-1"><?= count($competitions) ?> verseny</p>
        <?php endif; ?>
    </div>
</header>

<?php if (empty($competitions)): ?>
    <div class="empty-state">
        <span class="empty-state-icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </span>
        <p class="font-semibold text-sand-900">Jelenleg nincs nyitott verseny</p>
        <p class="text-sm text-sand-500 mt-1 max-w-sm">
            Amint megnyílik a nevezés egy versenyre, itt fogod megtalálni.
        </p>
        <a href="/" class="btn btn-secondary btn-sm mt-6">Hírek megtekintése</a>
    </div>

<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($competitions as $i => $competition): ?>
            <?php
            $date = new DateTimeImmutable($competition['date']);
            $deadline = new DateTimeImmutable($competition['registration_deadline']);
            $daysLeft = (int)$now->diff($deadline)->format('%r%a');
            $isUrgent = $daysLeft >= 0 && $daysLeft <= 3;
            ?>
            <article class="card card-interactive overflow-hidden reveal reveal-<?= min($i + 1, 5) ?>">
                <div class="flex flex-col sm:flex-row">

                    <!-- Dátum-jelvény -->
                    <div class="flex sm:flex-col items-center justify-center gap-2 sm:gap-0 shrink-0
                                px-6 py-4 sm:py-6 sm:w-28
                                bg-billiard-green-900 text-white">
                        <span class="text-3xl font-bold leading-none tracking-tightest">
                            <?= $date->format('j') ?>
                        </span>
                        <span class="text-xs font-semibold uppercase tracking-wider text-billiard-gold-300 sm:mt-1">
                            <?= $monthsShort[(int)$date->format('n') - 1] ?>
                        </span>
                        <span class="text-xs text-white/45 sm:mt-0.5">
                            <?= $date->format('Y') ?>
                        </span>
                    </div>

                    <!-- Verseny adatok -->
                    <div class="flex-1 flex flex-col md:flex-row md:items-center gap-5 p-5 md:p-6">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <h2 class="text-lg md:text-xl font-semibold tracking-tightest text-billiard-green-900">
                                    <?= e($competition['name']) ?>
                                </h2>
                                <?php if ($isUrgent): ?>
                                    <span class="badge badge-gold">
                                        <?= $daysLeft === 0 ? 'Ma zárul' : $daysLeft . ' nap a határidőig' ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <dl class="flex flex-wrap gap-x-6 gap-y-1.5 text-sm text-sand-700">
                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-sand-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                                    </svg>
                                    <dt class="sr-only">Helyszín</dt>
                                    <dd><?= e($competition['venue']) ?></dd>
                                </div>

                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-sand-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <dt class="sr-only">Nevezési határidő</dt>
                                    <dd>
                                        Határidő:
                                        <time datetime="<?= e($competition['registration_deadline']) ?>" class="font-medium">
                                            <?= $deadline->format('Y. m. d. H:i') ?>
                                        </time>
                                    </dd>
                                </div>

                                <div class="inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-sand-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                    </svg>
                                    <dt class="sr-only">Nevezők száma</dt>
                                    <dd><?= (int)($competition['registrant_count'] ?? 0) ?> nevező</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Nevezés gomb -->
                        <div class="shrink-0">
                            <a href="/nevezes/<?= e($competition['id']) ?>" class="btn btn-primary w-full md:w-auto">
                                Nevezés
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
