<?php
/**
 * Fő layout - Magyar Biliárd Weboldal
 *
 * A navigáció a fejléc partial része, ezért itt már nem kerül külön betöltésre.
 *
 * @var string      $pageTitle Oldal címe
 * @var string      $content   Oldal tartalma (kontrollerben pufferelve)
 * @var string|null $metaDescription Opcionális oldalleírás
 */
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
</head>
<body class="min-h-screen flex flex-col bg-sand-50 text-sand-900 font-sans antialiased">

    <a href="#main-content" class="skip-link">Ugrás a tartalomra</a>

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main id="main-content" class="flex-1 container mx-auto px-4 py-10 md:py-14">
        <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
            <div class="alert alert-success flash-message mb-8" role="status">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><?= e($flash) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($flashError = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-error flash-message mb-8" role="alert">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <span><?= e($flashError) ?></span>
            </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>

    <script src="/assets/js/app.js"></script>
</body>
</html>
