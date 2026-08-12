<?php
/**
 * Fiókmenü partial - Magyar Biliárd Weboldal
 *
 * Egyetlen belépési pont a kétféle azonosításhoz, hogy ne két párhuzamos
 * sáv jelenjen meg a fejlécben:
 *
 *   1. Látogatói fiók - nevezésekhez és a fórumhoz
 *   2. Szervezői hozzáférés - tartalomkezeléshez (admin)
 *
 * A kettő egymástól független: lehet valaki csak látogató, csak szervező,
 * mindkettő, vagy egyik sem. A menü ezért mindig mindkét csoportot
 * megnevezve mutatja, így egyértelmű, melyik művelet melyik szerepre hat.
 */

use App\Core\Session;

$isUser = Session::isUser();
$isAdmin = Session::isAdmin();
$user = Session::user();

/** A menügomb felirata a jelenlegi állapot szerint */
$buttonLabel = match (true) {
    $isUser => $user['name'],
    $isAdmin => 'Szervező',
    default => 'Fiók',
};

/** Névből monogram az avatarhoz */
$menuInitials = static function (string $name): string {
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $letters !== '' ? $letters : '?';
};
?>
<div class="account-menu hidden md:block relative ml-2 pl-3 border-l border-white/15">

    <button id="account-toggle" type="button"
            class="account-trigger nav-link"
            aria-expanded="false" aria-controls="account-panel" aria-haspopup="true">

        <?php if ($isUser): ?>
            <span class="grid place-items-center w-6 h-6 shrink-0 rounded-full bg-white/15 text-[0.625rem] font-bold" aria-hidden="true">
                <?= e($menuInitials($user['name'])) ?>
            </span>
        <?php else: ?>
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
            </svg>
        <?php endif; ?>

        <span class="max-w-[9rem] truncate"><?= e($buttonLabel) ?></span>

        <?php if ($isAdmin): ?>
            <!-- Jelzés, hogy szervezői hozzáférés is aktív -->
            <span class="badge-admin" title="Szervezői hozzáférés aktív">Szervező</span>
        <?php endif; ?>

        <svg class="account-chevron w-3.5 h-3.5 shrink-0 text-white/50" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    <!-- Lenyíló panel -->
    <div id="account-panel" class="account-panel hidden" role="menu" aria-labelledby="account-toggle">

        <!-- ============ 1. Látogatói fiók ============ -->
        <div class="account-section">
            <p class="account-section-title">Látogatói fiók</p>

            <?php if ($isUser): ?>
                <div class="account-identity">
                    <span class="grid place-items-center w-9 h-9 shrink-0 rounded-full bg-billiard-green-800 text-white text-xs font-bold" aria-hidden="true">
                        <?= e($menuInitials($user['name'])) ?>
                    </span>
                    <span class="min-w-0">
                        <span class="block font-semibold text-sand-900 truncate"><?= e($user['name']) ?></span>
                        <span class="block text-xs text-sand-500 truncate"><?= e($user['email']) ?></span>
                    </span>
                </div>

                <a href="/fiok" class="account-item" role="menuitem">
                    <svg class="w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                    </svg>
                    Nevezéseim
                </a>
                <a href="/kilepes" class="account-item" role="menuitem">
                    <svg class="w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    Kilépés a fiókból
                </a>
            <?php else: ?>
                <p class="account-hint">
                    Nevezéshez és a fórumhoz nem kötelező, de belépve előtöltjük az
                    adataidat, és visszavonhatod a nevezéseidet.
                </p>
                <a href="/belepes" class="account-item" role="menuitem">
                    <svg class="w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25"/>
                    </svg>
                    Belépés
                </a>
                <a href="/regisztracio" class="account-item" role="menuitem">
                    <svg class="w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                    </svg>
                    Új fiók létrehozása
                </a>
            <?php endif; ?>
        </div>

        <!-- ============ 2. Szervezői hozzáférés ============ -->
        <div class="account-section account-section-admin">
            <p class="account-section-title">Szervezői hozzáférés</p>

            <?php if ($isAdmin): ?>
                <p class="account-hint account-hint-active">
                    Szervezői módban vagy: kezelheted a híreket, a galériát, a
                    versenyeket és a fórumot.
                </p>
                <a href="/admin" class="account-item account-item-admin" role="menuitem">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
                    </svg>
                    Szervezői felület
                </a>
                <a href="/admin/logout" class="account-item" role="menuitem">
                    <svg class="w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    Kilépés a szervezői módból
                </a>
            <?php else: ?>
                <p class="account-hint">
                    Csak a szervezőknek. A látogatói fióktól független, külön jelszóval.
                </p>
                <a href="/admin/login" class="account-item" role="menuitem">
                    <svg class="w-4 h-4 text-sand-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                    </svg>
                    Szervezői belépés
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
