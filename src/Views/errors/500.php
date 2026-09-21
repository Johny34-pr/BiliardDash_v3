<?php
/**
 * 500 - Szerverhiba
 *
 * Önálló oldal (a layout nélkül renderelődik az index.php hibakezelőjéből),
 * ezért a közös head partialt tölti be a design tokenekhez.
 *
 * @var \Throwable|null $e
 */
$pageTitle = '500 - Szerverhiba | Okányi Biliárd Klub';

// A hibaoldal nem tartalom: nem kerülhet a keresőindexbe
$noIndex = true;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
</head>
<body class="min-h-screen flex flex-col bg-billiard-green-900 text-white font-sans antialiased">

    <!-- Dekoratív háttérfények -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-32 -right-24 w-96 h-96 rounded-full bg-billiard-green-600/25 blur-3xl"></div>
        <div class="absolute -bottom-32 -left-24 w-96 h-96 rounded-full bg-billiard-gold-500/10 blur-3xl"></div>
    </div>

    <header class="relative container mx-auto px-4 py-6">
        <a href="/" class="inline-flex items-center gap-2.5" aria-label="Okányi Biliárd Klub - főoldal">
            <?php $brandMarkSize = 'w-9 h-9'; require __DIR__ . '/../partials/brand-mark.php'; ?>
            <span class="font-semibold tracking-tightest">Okányi Biliárd Klub</span>
        </a>
    </header>

    <main class="relative flex-1 grid place-items-center px-4 py-12">
        <div class="text-center max-w-md">
            <p class="text-[7rem] md:text-[9rem] font-bold leading-none tracking-tightest text-billiard-gold-400/90">
                500
            </p>
            <h1 class="text-2xl md:text-3xl font-bold tracking-tightest mt-2 mb-3">
                Váratlan hiba történt
            </h1>
            <p class="text-white/60 leading-relaxed mb-8">
                Dolgozunk a megoldáson. Kérjük, próbálja újra néhány perc múlva.
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="/" class="btn btn-gold">Vissza a főoldalra</a>
                <button type="button" onclick="location.reload()" class="btn bg-white/10 text-white border-white/20 hover:bg-white/15">
                    Újratöltés
                </button>
            </div>
        </div>
    </main>

    <footer class="relative container mx-auto px-4 py-6 text-center text-xs text-white/40">
        <p>&copy; <?= date('Y') ?> Okányi Biliárd Klub. Minden jog fenntartva.</p>
    </footer>
</body>
</html>
