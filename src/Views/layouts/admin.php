<?php
/**
 * Admin layout - Okányi Biliárd Klub weboldal
 *
 * A publikus oldallal egységes design tokeneket használ (head partial).
 * A navigáció mobilon vízszintesen görgethető, így nem tör el az elrendezés.
 *
 * @var string $pageTitle Oldal címe
 * @var string $content   Oldal tartalma
 */

$current = currentUrl();

// A szervezői felület nem való a keresőindexbe
$noIndex = true;

$adminNav = [
    ['url' => '/admin',            'label' => 'Áttekintés', 'exact' => true],
    ['url' => '/admin/hirek',      'label' => 'Hírek',      'exact' => false],
    ['url' => '/admin/galeria',    'label' => 'Galéria',    'exact' => false],
    ['url' => '/admin/versenyek',  'label' => 'Versenyek',  'exact' => false],
    ['url' => '/admin/oldalak',    'label' => 'Oldalak',    'exact' => false],
    ['url' => '/admin/felhasznalok', 'label' => 'Felhasználók', 'exact' => false],
];

// A fórum moderálása csak aktív modul esetén jelenik meg
if (forumEnabled()) {
    $adminNav[] = ['url' => '/admin/forum', 'label' => 'Fórum', 'exact' => false];
}

// A beállítások a lista végén: itt kapcsolhatók a modulok
$adminNav[] = ['url' => '/admin/beallitasok', 'label' => 'Beállítások', 'exact' => false];
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
                    <?php $brandMarkSize = 'w-9 h-9'; require __DIR__ . '/../partials/brand-mark.php'; ?>
                    <span class="flex flex-col leading-none gap-0.5">
                        <span class="font-semibold tracking-tightest text-[1.0625rem]">Okányi Biliárd Klub</span>
                        <span class="text-[0.6875rem] font-medium tracking-wider uppercase text-billiard-gold-300/70">Adminisztráció</span>
                    </span>
                </a>

                <div class="flex items-center gap-2 shrink-0">
                    <!--
                        Ha látogatói fiók is aktív, itt jelezzük: így nem
                        keveredik össze a két szerep, és látszik, hogy a
                        szervezői kilépés nem érinti a látogatói fiókot.
                    -->
                    <?php if (\App\Core\Session::isUser()): ?>
                        <?php $adminSideUser = \App\Core\Session::user(); ?>
                        <span class="hidden lg:inline-flex items-center gap-1.5 text-xs text-white/50 mr-1"
                              title="A látogatói fiókod is be van jelentkezve">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            <?= e($adminSideUser['name']) ?>
                        </span>
                    <?php endif; ?>

                    <a href="/" class="nav-link hidden sm:inline-flex" title="Vissza a publikus weboldalra">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                        Weboldal
                    </a>
                    <a href="/admin/logout"
                       class="btn btn-sm bg-white/10 text-white border-white/20 hover:bg-white/15"
                       title="Csak a szervezői hozzáférést zárja be">
                        Szervezői kilépés
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
            <p class="text-xs text-sand-500">Okányi Biliárd Klub &middot; Admin felület</p>
            <p class="text-xs text-sand-400">&copy; <?= date('Y') ?></p>
        </div>
    </footer>

    <!-- defer: a szkript nem tartja fel a megjelenítést -->
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
