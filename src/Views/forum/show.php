<?php
/**
 * Fórum - egy topik nyitó bejegyzése és hozzászólásai
 *
 * A hozzászólások időrendben jelennek meg (legkorábbi elöl), így a
 * beszélgetés felülről lefelé követhető.
 *
 * @var array      $topic         A topik adatai
 * @var array      $comments      A topik hozzászólásai (aktuális oldal)
 * @var int        $total         Összes hozzászólás a topikban
 * @var int        $page          Aktuális oldal
 * @var int        $totalPages    Oldalak száma
 * @var array      $errors        Validációs hibák
 * @var array      $data          Korábban beküldött adatok (sticky form)
 * @var array|null $currentUser   Bejelentkezett felhasználó, vagy null
 * @var array      $allowedEmojis Választható emojik
 * @var int        $maxLength     A hozzászólás maximális hossza
 * @var array      $myVotes       hozzászólás azonosító => a látogató szavazata
 */
$isGuest = $currentUser === null;
$isLocked = ((int) $topic['is_locked']) === 1;

/** Rövid, magyar nyelvű relatív időjelölés */
$relativeTime = static function (string $timestamp): string {
    $diff = time() - strtotime($timestamp);

    return match (true) {
        $diff < 60 => 'most',
        $diff < 3600 => intdiv($diff, 60) . ' perccel ezelőtt',
        $diff < 86400 => intdiv($diff, 3600) . ' órával ezelőtt',
        $diff < 604800 => intdiv($diff, 86400) . ' nappal ezelőtt',
        default => date('Y. m. d.', strtotime($timestamp)),
    };
};

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

    <!-- ===================== Nyitó bejegyzés ===================== -->
    <article class="card p-6 md:p-7 mb-8">
        <header class="mb-4">
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <h1 class="text-2xl md:text-3xl font-bold tracking-tightest leading-snug text-billiard-green-900">
                    <?= e($topic['title']) ?>
                </h1>
                <?php if ($isLocked): ?>
                    <span class="badge badge-neutral">Lezárva</span>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-3">
                <span class="grid place-items-center w-9 h-9 shrink-0 rounded-full text-xs font-bold
                             <?= $topic['user_id'] !== null
                                 ? 'bg-billiard-green-800 text-white'
                                 : 'bg-sand-200 text-sand-600' ?>"
                      aria-hidden="true">
                    <?= e($initials($topic['author_name'])) ?>
                </span>
                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-sm">
                    <span class="font-semibold text-billiard-green-900"><?= e($topic['author_name']) ?></span>
                    <?php if ($topic['user_id'] === null): ?>
                        <span class="badge badge-neutral">Vendég</span>
                    <?php endif; ?>
                    <time class="text-xs text-sand-500" datetime="<?= e($topic['created_at']) ?>"
                          title="<?= date('Y. m. d. H:i', strtotime($topic['created_at'])) ?>">
                        <?= e($relativeTime($topic['created_at'])) ?>
                    </time>
                </div>
            </div>
        </header>

        <!--
            A tartalom escape-elve kerül kimenetre, a sortöréseket a CSS
            tartja meg, így nem kell nl2br és nem nyílik HTML injektálási
            lehetőség.
        -->
        <p class="text-sand-700 leading-relaxed" style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= e($topic['body']) ?></p>
    </article>

    <!-- ===================== Hozzászólások ===================== -->
    <section id="hozzaszolasok" aria-labelledby="comment-list" class="scroll-mt-24">
        <div class="flex items-end justify-between gap-4 mb-5">
            <h2 id="comment-list" class="text-xl font-bold tracking-tightest text-billiard-green-900">
                Hozzászólások
            </h2>
            <?php if ($total > 0): ?>
                <p class="text-sm text-sand-500 pb-0.5"><?= $total ?> darab</p>
            <?php endif; ?>
        </div>

        <?php if (empty($comments)): ?>
            <div class="empty-state mb-8">
                <span class="empty-state-icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"/>
                    </svg>
                </span>
                <p class="font-semibold text-sand-900">Még nincs hozzászólás</p>
                <p class="text-sm text-sand-500 mt-1 max-w-sm">
                    <?= $isLocked
                        ? 'Ez a topik le van zárva, nem fogad új hozzászólást.'
                        : 'Legyél te az első, aki válaszol ebben a topikban.' ?>
                </p>
            </div>

        <?php else: ?>
            <ol class="space-y-3 mb-8">
                <?php foreach ($comments as $comment): ?>
                    <?php
                    $myVote = $myVotes[$comment['id']] ?? 0;
                    $score = (int) $comment['upvotes'] - (int) $comment['downvotes'];
                    ?>
                    <li class="card p-5 scroll-mt-24" id="hozzaszolas-<?= e($comment['id']) ?>">
                        <div class="flex items-start gap-3 sm:gap-4">

                            <!-- Értékelés: fel, pontszám, le -->
                            <div class="flex flex-col items-center gap-0.5 shrink-0 -mt-0.5">
                                <form method="POST" action="/forum/hozzaszolas/<?= e($comment['id']) ?>/ertekeles">
                                    <input type="hidden" name="ertekeles" value="fel">
                                    <input type="hidden" name="topik" value="<?= e($topic['id']) ?>">
                                    <input type="hidden" name="oldal" value="<?= $page ?>">
                                    <button type="submit"
                                            class="grid place-items-center w-9 h-9 min-h-0 min-w-0 rounded-lg transition-colors
                                                   <?= $myVote === 1
                                                       ? 'bg-billiard-green-100 text-billiard-green-700'
                                                       : 'text-sand-400 hover:bg-sand-100 hover:text-billiard-green-600' ?>"
                                            aria-label="Felértékelés<?= $myVote === 1 ? ' visszavonása' : '' ?>"
                                            aria-pressed="<?= $myVote === 1 ? 'true' : 'false' ?>"
                                            title="<?= $myVote === 1 ? 'Felértékelés visszavonása' : 'Felértékelés' ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"/>
                                        </svg>
                                    </button>
                                </form>

                                <span class="text-sm font-bold tabular-nums leading-none
                                             <?= match (true) {
                                                 $score > 0 => 'text-billiard-green-700',
                                                 $score < 0 => 'text-red-600',
                                                 default => 'text-sand-500',
                                             } ?>"
                                      title="<?= (int) $comment['upvotes'] ?> fel, <?= (int) $comment['downvotes'] ?> le">
                                    <?= $score > 0 ? '+' . $score : $score ?>
                                </span>

                                <form method="POST" action="/forum/hozzaszolas/<?= e($comment['id']) ?>/ertekeles">
                                    <input type="hidden" name="ertekeles" value="le">
                                    <input type="hidden" name="topik" value="<?= e($topic['id']) ?>">
                                    <input type="hidden" name="oldal" value="<?= $page ?>">
                                    <button type="submit"
                                            class="grid place-items-center w-9 h-9 min-h-0 min-w-0 rounded-lg transition-colors
                                                   <?= $myVote === -1
                                                       ? 'bg-red-100 text-red-600'
                                                       : 'text-sand-400 hover:bg-sand-100 hover:text-red-500' ?>"
                                            aria-label="Leértékelés<?= $myVote === -1 ? ' visszavonása' : '' ?>"
                                            aria-pressed="<?= $myVote === -1 ? 'true' : 'false' ?>"
                                            title="<?= $myVote === -1 ? 'Leértékelés visszavonása' : 'Leértékelés' ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            <!-- Avatar monogrammal -->
                            <span class="hidden sm:grid place-items-center w-10 h-10 shrink-0 rounded-full text-xs font-bold
                                         <?= $comment['user_id'] !== null
                                             ? 'bg-billiard-green-800 text-white'
                                             : 'bg-sand-200 text-sand-600' ?>"
                                  aria-hidden="true">
                                <?= e($initials($comment['author_name'])) ?>
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-1.5">
                                    <span class="font-semibold text-billiard-green-900">
                                        <?= e($comment['author_name']) ?>
                                    </span>
                                    <?php if ($comment['user_id'] === null): ?>
                                        <span class="badge badge-neutral">Vendég</span>
                                    <?php endif; ?>
                                    <time class="text-xs text-sand-500" datetime="<?= e($comment['created_at']) ?>"
                                          title="<?= date('Y. m. d. H:i', strtotime($comment['created_at'])) ?>">
                                        <?= e($relativeTime($comment['created_at'])) ?>
                                    </time>
                                </div>

                                <p class="text-sand-700 leading-relaxed" style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= e($comment['body']) ?></p>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>

            <!-- Lapozás -->
            <?php if ($totalPages > 1): ?>
                <nav class="flex items-center justify-between gap-4 mb-8" aria-label="Hozzászólások lapozása">
                    <?php if ($page > 1): ?>
                        <a href="/forum/<?= e($topic['id']) ?>?oldal=<?= $page - 1 ?>#hozzaszolasok" class="btn btn-secondary btn-sm">Előző</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>

                    <span class="text-sm text-sand-500 tabular-nums"><?= $page ?> / <?= $totalPages ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="/forum/<?= e($topic['id']) ?>?oldal=<?= $page + 1 ?>#hozzaszolasok" class="btn btn-secondary btn-sm">Következő</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <!-- ===================== Válasz űrlap ===================== -->
    <?php if ($isLocked): ?>
        <div class="alert alert-warning" role="status">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
            <div>
                <p class="alert-title">A topik le van zárva</p>
                <p class="text-sm mt-0.5">Olvasható marad, de új hozzászólást nem fogad.</p>
            </div>
        </div>

    <?php else: ?>
        <section class="card p-6 md:p-7" aria-labelledby="new-comment">
            <h2 id="new-comment" class="text-lg font-semibold tracking-tightest text-billiard-green-900 mb-5">
                Válasz a topikra
            </h2>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error mb-5" role="alert">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <span><?= e($errors['general']) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="/forum/<?= e($topic['id']) ?>/hozzaszolas" class="space-y-5" novalidate>

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
                            néven szólsz hozzá
                        </p>
                    </div>
                <?php endif; ?>

                <div>
                    <div class="flex items-end justify-between gap-3 mb-1.5">
                        <label for="body" class="label mb-0">
                            Hozzászólás <span class="text-billiard-gold-600" aria-hidden="true">*</span>
                        </label>
                        <span id="body-counter" class="text-xs text-sand-500 tabular-nums" aria-live="polite">
                            0 / <?= $maxLength ?>
                        </span>
                    </div>

                    <textarea id="body" name="body" rows="6" maxlength="<?= $maxLength ?>"
                              required
                              placeholder="Írd le, mit gondolsz…"
                              class="field w-full block resize-y leading-relaxed<?= isset($errors['body']) ? ' field-error' : '' ?>"
                              <?= isset($errors['body']) ? 'aria-describedby="body-error" aria-invalid="true"' : 'aria-describedby="body-hint"' ?>><?= e($data['body'] ?? '') ?></textarea>

                    <?php if (isset($errors['body'])): ?>
                        <p id="body-error" class="field-message" role="alert"><?= e($errors['body']) ?></p>
                    <?php else: ?>
                        <p id="body-hint" class="field-hint">
                            Csak egyszerű szöveg és a lenti emojik használhatók.
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
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                        </svg>
                        Hozzászólás
                    </button>
                    <?php if ($isGuest): ?>
                        <p class="text-xs text-sand-500 mt-4">
                            <a href="/belepes?tovabb=<?= urlencode('/forum/' . $topic['id']) ?>" class="font-medium text-billiard-green-600 hover:underline">Belépve</a>
                            nem kell megadnod a nevedet.
                        </p>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    <?php endif; ?>
</div>
