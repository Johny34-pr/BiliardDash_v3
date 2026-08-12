<?php
/**
 * Fiókom - a felhasználó által rögzített nevezések
 *
 * Tartalmazza a saját nevezéseket és azokat is, amelyeket a felhasználó
 * másnak vitt fel. A visszavonás csak a nevezési határidő lejártáig
 * lehetséges, utána a névsor véglegesnek tekintendő.
 *
 * @var array $user          A bejelentkezett felhasználó (id, name, email, phone)
 * @var array $registrations Nevezések a verseny adataival együtt
 */
$now = new DateTimeImmutable();
$monthsShort = ['jan', 'feb', 'márc', 'ápr', 'máj', 'jún', 'júl', 'aug', 'szep', 'okt', 'nov', 'dec'];

// A saját nevezés az, ahol a nevező e-maile megegyezik a fiók e-mailjével
$ownEmail = mb_strtolower($user['email']);
?>

<div class="max-w-4xl mx-auto reveal">

    <!-- Fiók fejléc -->
    <header class="mb-9">
        <p class="eyebrow mb-3">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            Látogatói fiók
        </p>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900">
                    <?= e($user['name']) ?>
                </h1>
                <p class="text-sand-500 mt-1"><?= e($user['email']) ?></p>
            </div>
            <div class="flex items-center gap-2">
                <a href="/nevezes" class="btn btn-primary btn-sm">Új nevezés</a>
                <a href="/kilepes" class="btn btn-ghost btn-sm">Kilépés a fiókból</a>
            </div>
        </div>

        <?php if (\App\Core\Session::isAdmin()): ?>
            <!-- A két szerep elhatárolása azoknak, akik egyszerre mindkettőben vannak -->
            <div class="alert alert-info mt-5">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
                <div class="text-sm">
                    <p class="alert-title mb-0.5">Szervezői hozzáférésed is aktív</p>
                    <p>
                        Ez az oldal a saját nevezéseidet mutatja. Az összes nevezés kezeléséhez
                        a <a href="/admin/versenyek" class="font-medium underline">szervezői felület</a> kell.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </header>

    <!-- Nevezések -->
    <section aria-labelledby="my-registrations">
        <div class="flex items-end justify-between gap-4 mb-6">
            <h2 id="my-registrations" class="text-2xl font-bold tracking-tightest text-billiard-green-900 rule-gold">
                Nevezéseim
            </h2>
            <?php if (!empty($registrations)): ?>
                <p class="text-sm text-sand-500 pb-1"><?= count($registrations) ?> nevezés</p>
            <?php endif; ?>
        </div>

        <?php if (empty($registrations)): ?>
            <div class="empty-state">
                <span class="empty-state-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                    </svg>
                </span>
                <p class="font-semibold text-sand-900">Még nincs nevezésed</p>
                <p class="text-sm text-sand-500 mt-1 max-w-sm">
                    Nézd meg a nyitott versenyeket, és nevezz magadnak vagy akár másnak.
                </p>
                <a href="/nevezes" class="btn btn-primary btn-sm mt-6">Nyitott versenyek</a>
            </div>

        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($registrations as $i => $reg): ?>
                    <?php
                    $date = new DateTimeImmutable($reg['competition_date']);
                    $deadline = new DateTimeImmutable($reg['registration_deadline']);
                    $canCancel = $deadline > $now;
                    $isOwn = mb_strtolower($reg['email']) === $ownEmail;
                    ?>
                    <article class="card overflow-hidden reveal reveal-<?= min($i + 1, 5) ?>">
                        <div class="flex flex-col sm:flex-row">

                            <!-- Dátum-jelvény -->
                            <div class="flex sm:flex-col items-center justify-center gap-2 sm:gap-0 shrink-0
                                        px-6 py-4 sm:py-6 sm:w-28 bg-billiard-green-900 text-white">
                                <span class="text-3xl font-bold leading-none tracking-tightest">
                                    <?= $date->format('j') ?>
                                </span>
                                <span class="text-xs font-semibold uppercase tracking-wider text-billiard-gold-300 sm:mt-1">
                                    <?= $monthsShort[(int)$date->format('n') - 1] ?>
                                </span>
                                <span class="text-xs text-white/45 sm:mt-0.5"><?= $date->format('Y') ?></span>
                            </div>

                            <!-- Adatok -->
                            <div class="flex-1 flex flex-col md:flex-row md:items-center gap-5 p-5 md:p-6">
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <h3 class="text-lg font-semibold tracking-tightest text-billiard-green-900">
                                            <?= e($reg['competition_name']) ?>
                                        </h3>
                                        <span class="badge <?= $isOwn ? 'badge-green' : 'badge-gold' ?>">
                                            <?= $isOwn ? 'Saját nevezés' : 'Más nevében' ?>
                                        </span>
                                        <?php if (!$canCancel): ?>
                                            <span class="badge badge-neutral">Véglegesítve</span>
                                        <?php endif; ?>
                                    </div>

                                    <dl class="space-y-1 text-sm text-sand-700">
                                        <?php if (!$isOwn): ?>
                                            <div class="inline-flex gap-1.5 mr-5">
                                                <dt class="text-sand-500">Nevező:</dt>
                                                <dd class="font-medium"><?= e($reg['full_name']) ?></dd>
                                            </div>
                                            <div class="inline-flex gap-1.5">
                                                <dt class="text-sand-500">E-mail:</dt>
                                                <dd class="font-medium"><?= e($reg['email']) ?></dd>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex flex-wrap gap-x-5 gap-y-1 pt-0.5">
                                            <div class="inline-flex gap-1.5">
                                                <dt class="text-sand-500">Helyszín:</dt>
                                                <dd><?= e($reg['competition_venue']) ?></dd>
                                            </div>
                                            <div class="inline-flex gap-1.5">
                                                <dt class="text-sand-500">Határidő:</dt>
                                                <dd>
                                                    <time datetime="<?= e($reg['registration_deadline']) ?>">
                                                        <?= $deadline->format('Y. m. d. H:i') ?>
                                                    </time>
                                                </dd>
                                            </div>
                                        </div>
                                    </dl>
                                </div>

                                <!-- Visszavonás -->
                                <div class="shrink-0">
                                    <?php if ($canCancel): ?>
                                        <form method="POST"
                                              action="/fiok/nevezes/<?= e($reg['id']) ?>/visszavonas"
                                              data-confirm="Biztosan visszavonod ezt a nevezést?<?= $isOwn ? '' : ' A nevező: ' . addslashes($reg['full_name']) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm w-full md:w-auto">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                </svg>
                                                Visszavonás
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <p class="text-xs text-sand-500 max-w-[11rem] md:text-right">
                                            A határidő lejárt, a visszavonáshoz keresd a szervezőt.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
