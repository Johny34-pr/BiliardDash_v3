<?php
/**
 * Verseny kártya - közös jelölés a versenylistákhoz
 *
 * Három helyen jelenik meg: a Nevezés oldalon, a Versenyeink > Közelgő
 * versenyek és a Versenyeink > Korábbi versenyek listában. Azért közös
 * partial, mert a kártya jelölése hosszú, és három példányban elkerülhetetlenül
 * elcsúszott volna egymástól.
 *
 * A nevezés állapotát a kártya MAGA számolja a verseny adataiból, nem a hívó
 * adja át: így nem fordulhat elő, hogy az egyik lista "Nevezés" gombot mutat
 * ott, ahol a másik már lezártat.
 *
 * @var array  $competition A verseny adatai
 * @var int    $cardIndex   Sorszám a megjelenési animációhoz
 * @var string $cardContext 'upcoming' (közelgő) vagy 'past' (lezárult)
 */

$monthsShort = ['jan', 'feb', 'márc', 'ápr', 'máj', 'jún', 'júl', 'aug', 'szep', 'okt', 'nov', 'dec'];
$now = new DateTimeImmutable();

$context = $cardContext ?? 'upcoming';
$index = $cardIndex ?? 0;

$date = new DateTimeImmutable($competition['date']);
$deadline = new DateTimeImmutable($competition['registration_deadline']);
$daysLeft = (int) $now->diff($deadline)->format('%r%a');

// Nyitódátum: üres érték = a nevezés a kiírástól nyitott
$opensAt = !empty($competition['registration_opens_at'])
    ? new DateTimeImmutable($competition['registration_opens_at'])
    : null;

$notYetOpen = $opensAt !== null && $opensAt > $now;
$deadlinePassed = $deadline < $now;
$canRegister = !$notYetOpen && !$deadlinePassed;

// A határidő közeledtét csak akkor jelezzük, ha egyáltalán lehet már nevezni
$isUrgent = $canRegister && $daysLeft >= 0 && $daysLeft <= 3;

$registrantUrl = '/nevezes/' . $competition['id'] . '/nevezok';
$protocolUrl = !empty($competition['protocol_path'])
    ? '/versenyeink/' . $competition['id'] . '/jegyzokonyv'
    : null;

/** Fájlméret olvasható alakban */
$readableSize = static function (int $bytes): string {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', ' ') . ' MB';
    }

    return max(1, (int) round($bytes / 1024)) . ' KB';
};

$isPast = $context === 'past';

/*
 * Az osztálylisták ITT állnak össze, nem a jelölésben lévő class attribútumon
 * belül.
 *
 * Miért: a stíluslap-generátor a class attribútumba írt PHP kifejezés minden
 * string-literálját osztálynévnek veszi. Egy ott elhelyezett
 * $isPast összehasonlításból tehát a hasonlított szöveg is osztálynévként
 * kerülne a listába, és feloldatlanként megállítaná a generálást.
 *
 * (Ugyanezért nem szerepel ebben a megjegyzésben sem idézőjelek közé írt
 * class attribútum - a generátor a kommentet sem különíti el.)
 */
$articleClass = 'card overflow-hidden reveal reveal-' . min($index + 1, 5)
    . ($isPast ? '' : ' card-interactive');

$dateBadgeClass = 'flex sm:flex-col items-center justify-center gap-2 sm:gap-0 shrink-0'
    . ' px-6 py-4 sm:py-6 sm:w-28 text-white '
    . ($isPast ? 'bg-sand-700' : 'bg-billiard-green-900');
?>
<article class="<?= $articleClass ?>">
    <div class="flex flex-col sm:flex-row">

        <!-- Dátum-jelvény -->
        <div class="<?= $dateBadgeClass ?>">
            <span class="text-3xl font-bold leading-none tracking-tightest">
                <?= $date->format('j') ?>
            </span>
            <span class="text-xs font-semibold uppercase tracking-wider text-billiard-gold-300 sm:mt-1">
                <?= $monthsShort[(int) $date->format('n') - 1] ?>
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

                    <?php if (!empty($competition['season_name'])): ?>
                        <span class="badge badge-neutral"><?= e($competition['season_name']) ?></span>
                    <?php endif; ?>

                    <?php if (!$isPast): ?>
                        <?php if ($notYetOpen): ?>
                            <span class="badge badge-gold">Nevezés hamarosan</span>
                        <?php elseif ($deadlinePassed): ?>
                            <!--
                                A verseny még hátravan, de nevezni már nem lehet.
                                Ezt ki kell írni, különben a látogató hiába
                                próbálkozna.
                            -->
                            <span class="badge badge-neutral">Nevezés lezárult</span>
                        <?php elseif ($isUrgent): ?>
                            <span class="badge badge-gold">
                                <?= $daysLeft === 0 ? 'Ma zárul' : $daysLeft . ' nap a határidőig' ?>
                            </span>
                        <?php endif; ?>
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

                    <?php if (!$isPast && $notYetOpen): ?>
                        <div class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-sand-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                            </svg>
                            <dt class="sr-only">Nevezés nyitása</dt>
                            <dd>
                                Nevezés nyitása:
                                <time datetime="<?= e($competition['registration_opens_at']) ?>" class="font-medium">
                                    <?= $opensAt->format('Y. m. d. H:i') ?>
                                </time>
                            </dd>
                        </div>
                    <?php endif; ?>

                    <?php if (!$isPast): ?>
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
                    <?php endif; ?>

                    <div class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sand-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        </svg>
                        <dt class="sr-only">Nevezők száma</dt>
                        <dd>
                            <a href="<?= e($registrantUrl) ?>"
                               class="font-medium text-billiard-green-600 hover:underline">
                                <?= (int) ($competition['registrant_count'] ?? 0) ?> nevező
                            </a>
                        </dd>
                    </div>
                </dl>

                <?php if ($isPast && $protocolUrl !== null): ?>
                    <!--
                        A jegyzőkönyv a lezárult verseny legfontosabb
                        melléklete, ezért külön, kiemelt sorban áll, nem a
                        többi adat közé bújtatva.
                    -->
                    <p class="mt-3">
                        <a href="<?= e($protocolUrl) ?>"
                           class="inline-flex items-center gap-2 text-sm font-semibold text-billiard-green-600 hover:text-billiard-green-700 transition-colors">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                            Jegyzőkönyv letöltése
                            <span class="font-normal text-sand-500">
                                (<?= $readableSize((int) ($competition['protocol_size'] ?? 0)) ?>)
                            </span>
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Műveletek -->
            <div class="shrink-0 flex flex-col gap-2">
                <?php if ($isPast): ?>
                    <a href="<?= e($registrantUrl) ?>" class="btn btn-secondary w-full md:w-auto">
                        Nevezők
                    </a>
                <?php elseif ($canRegister): ?>
                    <a href="/nevezes/<?= e($competition['id']) ?>" class="btn btn-primary w-full md:w-auto">
                        Nevezés
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="<?= e($registrantUrl) ?>" class="btn btn-secondary btn-sm w-full md:w-auto">
                        Nevezők
                    </a>
                <?php else: ?>
                    <!-- Még nem nyílt meg, vagy már lezárult: csak megtekintés -->
                    <a href="/nevezes/<?= e($competition['id']) ?>" class="btn btn-secondary w-full md:w-auto">
                        Részletek
                    </a>
                    <a href="<?= e($registrantUrl) ?>" class="btn btn-secondary btn-sm w-full md:w-auto">
                        Nevezők
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</article>
<?php
// A hívó által beállított értékeket töröljük, hogy a következő kártya ne
// örökölje meg őket
unset($cardIndex, $cardContext, $context, $index, $date, $deadline, $daysLeft,
      $opensAt, $notYetOpen, $deadlinePassed, $canRegister, $isUrgent, $isPast,
      $registrantUrl, $protocolUrl, $readableSize, $monthsShort, $now,
      $articleClass, $dateBadgeClass);
