<?php
/**
 * Társhonlapok és kapcsolat - statikus oldal
 *
 * Az adatok a config/contact.php fájlból jönnek. Minden mező kihagyásra
 * kerül, ha nincs kitöltve: így egy hiányzó telefonszám vagy Facebook cím
 * nem hagy üres sort vagy törött hivatkozást az oldalon.
 *
 * @var array $contact A config/contact.php tartalma
 */

$partners = $contact['partners'] ?? [];
$venue = $contact['venue'] ?? [];
$person = $contact['contact_person'] ?? [];
$social = socialLinks();

/** Kitöltött-e egy érték */
$filled = static fn(?string $value): bool => trim((string) $value) !== '';
?>

<div class="max-w-reading mx-auto reveal">

    <header class="mb-10">
        <p class="eyebrow mb-3">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            Kapcsolat
        </p>
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900 rule-gold">
            Társhonlapok
        </h1>
        <p class="text-sand-600 mt-4 leading-relaxed">
            A biliárdhoz kapcsolódó szervezetek oldalai, a terem elérhetőségei
            és a közösségi csoportjaink.
        </p>
    </header>

    <!-- ================= Társszervezetek ================= -->
    <section class="space-y-4 mb-12" aria-labelledby="partners-heading">
        <h2 id="partners-heading" class="text-xl font-semibold tracking-tightest text-billiard-green-900 mb-4">
            Kapcsolódó szervezetek
        </h2>

        <?php foreach ($partners as $partner): ?>
            <article class="card p-6">
                <div class="flex flex-wrap items-baseline justify-between gap-3 mb-2">
                    <h3 class="text-lg font-semibold text-billiard-green-900">
                        <?= e($partner['name']) ?>
                    </h3>

                    <?php if ($filled($partner['url'] ?? '')): ?>
                        <a href="<?= e($partner['url']) ?>" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1.5 text-sm font-semibold text-billiard-green-600 hover:underline">
                            <?= e(preg_replace('#^https?://(www\.)?#', '', rtrim($partner['url'], '/'))) ?>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>

                <p class="text-sand-600 leading-relaxed"><?= e($partner['description']) ?></p>

                <?php if (!$filled($partner['url'] ?? '')): ?>
                    <p class="field-hint mt-2">A honlap címe hamarosan elérhető lesz.</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

    <!-- ================= 1. A terem elérhetőségei ================= -->
    <section class="mb-12" aria-labelledby="venue-heading">
        <h2 id="venue-heading" class="flex items-baseline gap-2.5 text-xl font-semibold tracking-tightest text-billiard-green-900 mb-4">
            <span class="grid place-items-center w-7 h-7 shrink-0 rounded-full bg-billiard-green-900 text-white text-sm font-bold" aria-hidden="true">1</span>
            <?= e($venue['name'] ?? 'A biliárdterem') ?> elérhetőségei
        </h2>

        <div class="card p-6">
            <dl class="divide-y divide-sand-200 -my-4">
                <?php if ($filled($venue['address'] ?? '') || $filled($venue['city'] ?? '')): ?>
                    <div class="py-4">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">Cím</dt>
                        <dd class="text-sand-900 font-medium">
                            <?= e(trim(($venue['city'] ?? '') . ' ' . ($venue['address'] ?? ''))) ?>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if ($filled($venue['phone'] ?? '')): ?>
                    <div class="py-4">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">Telefon</dt>
                        <dd>
                            <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $venue['phone'])) ?>"
                               class="font-medium text-billiard-green-700 hover:underline">
                                <?= e($venue['phone']) ?>
                            </a>
                        </dd>
                    </div>
                <?php endif; ?>

                <?php if ($filled($venue['opening_hours'] ?? '')): ?>
                    <div class="py-4">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">Nyitvatartás</dt>
                        <dd class="text-sand-900 font-medium"><?= e($venue['opening_hours']) ?></dd>
                    </div>
                <?php endif; ?>

                <?php if ($filled($venue['email'] ?? '')): ?>
                    <div class="py-4">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-sand-500 mb-1">E-mail</dt>
                        <dd>
                            <a href="mailto:<?= e($venue['email']) ?>"
                               class="font-medium text-billiard-green-700 hover:underline">
                                <?= e($venue['email']) ?>
                            </a>
                        </dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
    </section>

    <!-- ================= 2. Kapcsolat ================= -->
    <section class="mb-12" aria-labelledby="person-heading">
        <h2 id="person-heading" class="flex items-baseline gap-2.5 text-xl font-semibold tracking-tightest text-billiard-green-900 mb-4">
            <span class="grid place-items-center w-7 h-7 shrink-0 rounded-full bg-billiard-green-900 text-white text-sm font-bold" aria-hidden="true">2</span>
            Kapcsolat
        </h2>

        <div class="card p-6">
            <p class="text-lg font-semibold text-billiard-green-900"><?= e($person['name'] ?? '') ?></p>
            <?php if ($filled($person['role'] ?? '')): ?>
                <p class="text-sm text-sand-500 mt-0.5"><?= e($person['role']) ?></p>
            <?php endif; ?>

            <div class="flex flex-wrap gap-x-8 gap-y-3 mt-4">
                <?php if ($filled($person['phone'] ?? '')): ?>
                    <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $person['phone'])) ?>"
                       class="inline-flex items-center gap-2 font-medium text-billiard-green-700 hover:underline">
                        <svg class="w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                        <?= e($person['phone']) ?>
                    </a>
                <?php endif; ?>

                <?php if ($filled($person['email'] ?? '')): ?>
                    <a href="mailto:<?= e($person['email']) ?>"
                       class="inline-flex items-center gap-2 font-medium text-billiard-green-700 hover:underline">
                        <svg class="w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                        <?= e($person['email']) ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!$filled($person['phone'] ?? '')): ?>
                <p class="field-hint mt-3">Telefonos elérhetőség hamarosan.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= 3. E-mail és közösségi oldalak ================= -->
    <section aria-labelledby="social-heading">
        <h2 id="social-heading" class="flex items-baseline gap-2.5 text-xl font-semibold tracking-tightest text-billiard-green-900 mb-4">
            <span class="grid place-items-center w-7 h-7 shrink-0 rounded-full bg-billiard-green-900 text-white text-sm font-bold" aria-hidden="true">3</span>
            E-mail és közösségi oldalak
        </h2>

        <div class="card p-6 space-y-1">
            <!-- Központi e-mail cím -->
            <a href="mailto:<?= e($venue['email'] ?? 'info@okanyibiliard.hu') ?>"
               class="flex items-center gap-3 -mx-2 px-2 py-3 rounded-xl hover:bg-sand-50 transition-colors">
                <span class="grid place-items-center w-10 h-10 shrink-0 rounded-full bg-billiard-green-50 text-billiard-green-700" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                </span>
                <span class="min-w-0">
                    <span class="block font-semibold text-sand-900"><?= e($venue['email'] ?? 'info@okanyibiliard.hu') ?></span>
                    <span class="block text-sm text-sand-500">Írj nekünk bármilyen kérdéssel</span>
                </span>
            </a>

            <?php if ($social !== []): ?>
                <?php foreach ($social as $link): ?>
                    <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-3 -mx-2 px-2 py-3 rounded-xl hover:bg-sand-50 transition-colors">
                        <span class="grid place-items-center w-10 h-10 shrink-0 rounded-full bg-[#1877F2]/10 text-[#1877F2]" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 3.925 23.094 9.101 24v-8.437H6.627v-3.49h2.474V9.9c0-2.99 1.796-4.64 4.533-4.64 1.312 0 2.686.235 2.686.235v2.953H14.94c-1.36 0-1.785.848-1.785 1.717v2.058h3.328l-.532 3.49h-2.796V24C20.075 23.094 24 18.1 24 12.073z"/>
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-sand-900"><?= e($link['label']) ?></span>
                            <span class="block text-sm text-sand-500"><?= e($link['description']) ?></span>
                        </span>
                        <svg class="w-4 h-4 ml-auto shrink-0 text-sand-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="field-hint pt-2">A Facebook oldal és a csoportok hivatkozásai hamarosan felkerülnek.</p>
            <?php endif; ?>
        </div>

        <p class="text-sm text-sand-500 mt-6">
            Az adataid kezeléséről az
            <a href="/adatkezeles" class="font-medium text-billiard-green-600 hover:underline">adatkezelési tájékoztatóban</a>
            olvashatsz.
        </p>
    </section>
</div>
