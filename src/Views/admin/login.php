<?php
/**
 * Admin bejelentkezési űrlap
 *
 * Önálló, középre igazított oldal az admin navigáció nélkül.
 *
 * @var string|null $pageTitle Oldal címe
 * @var string|null $error     Hibaüzenet
 */
$pageTitle = $pageTitle ?? 'Admin belépés - Magyar Biliárd';
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

    <main class="relative flex-1 grid place-items-center px-4 py-12">
        <div class="w-full max-w-sm">

            <!-- Márkajel -->
            <div class="flex flex-col items-center text-center mb-8">
                <span class="grid place-items-center w-12 h-12 rounded-full bg-gradient-to-br from-billiard-gold-300 to-billiard-gold-500 mb-4">
                    <span class="grid place-items-center w-6 h-6 rounded-full bg-billiard-green-950">
                        <span class="text-xs font-bold leading-none text-billiard-gold-300">8</span>
                    </span>
                </span>
                <h1 class="text-xl font-semibold tracking-tightest">Magyar Biliárd</h1>
                <p class="text-sm text-white/50 mt-1">Adminisztrációs felület</p>
            </div>

            <!-- Bejelentkező kártya -->
            <div class="bg-white rounded-2xl shadow-lift p-7 text-sand-900">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error mb-5" role="alert">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                        </svg>
                        <span class="text-sm"><?= e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/login" class="space-y-5">
                    <div>
                        <label for="password" class="label">Jelszó</label>
                        <input type="password" id="password" name="password"
                               required autofocus autocomplete="current-password"
                               placeholder="••••••••"
                               class="field">
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                        </svg>
                        Belépés
                    </button>
                </form>
            </div>

            <p class="text-center mt-6">
                <a href="/" class="inline-flex items-center gap-1.5 text-sm text-white/50 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                    Vissza a főoldalra
                </a>
            </p>
        </div>
    </main>
</body>
</html>
