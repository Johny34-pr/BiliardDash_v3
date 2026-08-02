<?php
/**
 * Admin layout - Magyar Biliárd Weboldal
 *
 * A publikus oldallal egységes design tokeneket használ (head partial).
 * A navigáció mobilon vízszintesen görgethető, így nem tör el az elrendezés.
 *
 * @var string $pageTitle Oldal címe
 * @var string $content   Oldal tartalma
 */

$current = currentUrl();

$adminNav = [
    ['url' => '/admin',            'label' => 'Áttekintés', 'exact' => true],
    ['url' => '/admin/hirek',      'label' => 'Hírek',      'exact' => false],
    ['url' => '/admin/galeria',    'label' => 'Galéria',    'exact' => false],
    ['url' => '/admin/versenyek',  'label' => 'Versenyek',  'exact' => false],
];
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
</head>
<body class="min-h-screen flex flex-col bg-sand-100 text-sand-900 font-sans antialiased">

    <a href="#admin-content" class="skip-link">Ugrás a tartalomra</a>

    <!-- Admin fejléc -->
    <header class="site-header sticky top-0 z-50 text-white">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16 gap-4">

                <!-- Márkajel + admin jelölés -->
                <a href="/admin" class="flex items-center gap-2.5 shrink-0" aria-label="Admin áttekintés">
                    <span class="grid place-items-center w-9 h-9 rounded-full bg-gradient-to-br from-billiard-gold-300 to-billiard-gold-500">
                        <span class="grid place-items-center w-[18px] h-[18px] rounded-full bg-billiard-green-950">
                            <span class="text-[10px] font-bold leading-none text-billiard-gold-300">8</span>
                        </span>
                    </span>
                    <span class="flex flex-col leading-none gap-0.5">
                        <span class="font-semibold tracking-tightest text-[1.0625rem]">Magyar Biliárd</span>
                        <span class="text-[0.6875rem] font-medium tracking-wider uppercase text-billiard-gold-300/70">Adminisztráció</span>
                    </span>
                </a>

                <div class="flex items-center gap-2 shrink-0">
                    <a href="/" target="_blank" rel="noopener"
                       class="nav-link hidden sm:inline-flex" title="Weboldal megnyitása új lapon">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                        </svg>
                        Weboldal
                    </a>
                    <a href="/admin/logout"
                       class="btn btn-sm bg-white/10 text-white border-white/20 hover:bg-white/15">
                        Kilépés
                    </a>
                </div>
            </div>
        </div>

        <!-- Admin navigáció: mobilon vízszintesen görgethető -->
        <div class="border-t border-white/10">
            <nav class="container mx-auto px-4" aria-label="Admin navigáció">
                <ul class="flex items-center gap-1 overflow-x-auto py-1.5 -mx-1 px-1">
                    <?php foreach ($adminNav as $item): ?>
                        <?php
                        $isActive = $item['exact']
                            ? $current === $item['url']
                            : str_starts_with($current, $item['url']);
                        ?>
                        <li class="shrink-0">
                            <a href="<?= e($item['url']) ?>"
                               class="nav-link <?= $isActive ? 'nav-link-active' : '' ?>"
                               <?= $isActive ? 'aria-current="page"' : '' ?>>
                                <?= e($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>

        <div class="h-px bg-gradient-to-r from-transparent via-billiard-gold-400/40 to-transparent"></div>
    </header>

    <main id="admin-content" class="flex-1 container mx-auto px-4 py-8 md:py-10">
        <?php if ($flash = \App\Core\Session::getFlash('success')): ?>
            <div class="alert alert-success flash-message mb-6" role="status">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span><?= e($flash) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($flashError = \App\Core\Session::getFlash('error')): ?>
            <div class="alert alert-error flash-message mb-6" role="alert">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <span><?= e($flashError) ?></span>
            </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>

    <footer class="border-t border-sand-200 bg-white">
        <div class="container mx-auto px-4 py-5 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p class="text-xs text-sand-500">Magyar Biliárd &middot; Admin felület</p>
            <p class="text-xs text-sand-400">&copy; <?= date('Y') ?></p>
        </div>
    </footer>

    <script src="/assets/js/app.js"></script>
</body>
</html>
