<?php
/**
 * Fórum - új topik nyitása
 *
 * Vendégként és belépve is használható. A cím és a nyitó bejegyzés is
 * egyszerű szöveg lehet a megengedett emojikkal.
 *
 * @var array      $errors         Validációs hibák
 * @var array      $data           Korábban beküldött adatok (sticky form)
 * @var array|null $currentUser    Bejelentkezett felhasználó, vagy null
 * @var array      $allowedEmojis  Választható emojik
 * @var int        $maxTitleLength A cím maximális hossza
 * @var int        $maxBodyLength  A nyitó bejegyzés maximális hossza
 */
$isGuest = $currentUser === null;

/** Névből monogram az avatarhoz */
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }

    return $letters !== '' ? $letters : '?';
};
?>

<div class="max-w-3xl mx-auto reveal">

    <a href="/forum" class="inline-flex items-center gap-1.5 text-sm font-medium text-sand-500 hover:text-billiard-green-700 transition-colors mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Vissza a fórumra
    </a>

    <header class="mb-7">
        <p class="eyebrow mb-3">
            <span class="w-6 h-px bg-billiard-gold-400" aria-hidden="true"></span>
            Új téma
        </p>
        <h1 class="text-3xl md:text-[2.5rem] font-bold tracking-tightest text-billiard-green-900">
            Topik nyitása
        </h1>
        <p class="text-sand-500 mt-2">
            Adj a témának beszédes címet, hogy mások könnyen megtalálják.
        </p>
    </header>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error mb-6" role="alert">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span><?= e($errors['general']) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="/forum/uj" class="card p-6 md:p-8 space-y-6" novalidate>

        <!-- Szerző -->
        <?php if ($isGuest): ?>
            <div class="sm:max-w-xs">
                <label for="author_name" class="label">
                    Neved <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                </label>
                <input type="text" id="author_name" name="author_name"
                       value="<?= e($data['authorName'] ?? '') ?>"
                       required maxlength="60" autocomplete="nickname"
                       placeholder="pl. Péter"
                       class="field<?= isset($errors['authorName']) ? ' field-error' : '' ?>"
                       <?= isset($errors['authorName']) ? 'aria-describedby="author_name-error" aria-invalid="true"' : '' ?>>
                <?php if (isset($errors['authorName'])): ?>
                    <p id="author_name-error" class="field-message" role="alert"><?= e($errors['authorName']) ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="flex items-center gap-3 p-3 rounded-xl bg-billiard-green-50 border border-billiard-green-100">
                <span class="grid place-items-center w-9 h-9 shrink-0 rounded-full bg-billiard-green-800 text-white text-xs font-bold">
                    <?= e($initials($currentUser['name'])) ?>
                </span>
                <p class="text-sm text-sand-700">
                    <span class="font-semibold text-billiard-green-900"><?= e($currentUser['name']) ?></span>
                    néven nyitod a topikot
                </p>
            </div>
        <?php endif; ?>

        <!-- Cím -->
        <div>
            <label for="title" class="label">
                A topik címe <span class="text-billiard-gold-600" aria-hidden="true">*</span>
            </label>
            <input type="text" id="title" name="title"
                   value="<?= e($data['title'] ?? '') ?>"
                   required maxlength="<?= $maxTitleLength ?>"
                   placeholder="pl. Melyik dákót ajánljátok kezdőnek?"
                   class="field<?= isset($errors['title']) ? ' field-error' : '' ?>"
                   <?= isset($errors['title']) ? 'aria-describedby="title-error" aria-invalid="true"' : 'aria-describedby="title-hint"' ?>>
            <?php if (isset($errors['title'])): ?>
                <p id="title-error" class="field-message" role="alert"><?= e($errors['title']) ?></p>
            <?php else: ?>
                <p id="title-hint" class="field-hint">Legfeljebb <?= $maxTitleLength ?> karakter.</p>
            <?php endif; ?>
        </div>

        <!-- Nyitó bejegyzés -->
        <div>
            <div class="flex items-end justify-between gap-3 mb-1.5">
                <label for="body" class="label mb-0">
                    Nyitó bejegyzés <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                </label>
                <span id="body-counter" class="text-xs text-sand-500 tabular-nums" aria-live="polite">
                    0 / <?= $maxBodyLength ?>
                </span>
            </div>

            <textarea id="body" name="body" rows="9" maxlength="<?= $maxBodyLength ?>"
                      required
                      placeholder="Írd le, miről szeretnél beszélgetni…"
                      class="field w-full block resize-y leading-relaxed<?= isset($errors['body']) ? ' field-error' : '' ?>"
                      <?= isset($errors['body']) ? 'aria-describedby="body-error" aria-invalid="true"' : 'aria-describedby="body-hint"' ?>><?= e($data['body'] ?? '') ?></textarea>

            <?php if (isset($errors['body'])): ?>
                <p id="body-error" class="field-message" role="alert"><?= e($errors['body']) ?></p>
            <?php else: ?>
                <p id="body-hint" class="field-hint">
                    Csak egyszerű szöveg és a lenti emojik használhatók. A formázás és a
                    hivatkozások eltávolításra kerülnek.
                </p>
            <?php endif; ?>
        </div>

        <!-- Emoji választó -->
        <div>
            <p class="label mb-2" id="emoji-label">Emoji beszúrása</p>
            <div class="flex flex-wrap gap-1.5" role="group" aria-labelledby="emoji-label">
                <?php foreach ($allowedEmojis as $emoji): ?>
                    <button type="button"
                            class="js-emoji grid place-items-center w-10 h-10 min-h-0 min-w-0 rounded-xl border border-sand-200 bg-white text-lg leading-none hover:bg-sand-100 hover:border-sand-300 transition-colors"
                            data-emoji="<?= e($emoji) ?>"
                            aria-label="<?= e($emoji) ?> beszúrása">
                        <?= e($emoji) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-sand-200">
            <button type="submit" class="btn btn-primary mt-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"/>
                </svg>
                Topik létrehozása
            </button>
            <a href="/forum" class="btn btn-ghost mt-4">Mégse</a>
        </div>
    </form>

    <?php if ($isGuest): ?>
        <div class="alert alert-info mt-6">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
            </svg>
            <div class="text-sm">
                <p class="alert-title mb-0.5">Topikot fiók nélkül is nyithatsz</p>
                <p>
                    <a href="/belepes?tovabb=<?= urlencode('/forum/uj') ?>" class="font-medium underline">Belépve</a>
                    nem kell megadnod a nevedet minden alkalommal.
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>
