<?php
/**
 * Blogszerkesztő betöltése (TinyMCE)
 *
 * A hír létrehozó és szerkesztő nézet is ezt tölti be. A tényleges
 * beállítás a public/assets/js/editor.js fájlban van; ez a partial csak a
 * kiszolgálóoldali adatokat adja át a szerkesztő textarea data-attribútumain.
 *
 * Így a végpontok és a méretkorlátok PHP oldalon, egy helyen módosíthatók,
 * a JavaScript pedig gyorsítótárazható külön fájl marad.
 *
 * Fontos: a textarea (#content) a hívó nézetben jön létre, ezért az
 * attribútumokat itt, utólag tesszük rá.
 *
 * Requirement 2.4: félkövér, dőlt, felsorolás és hivatkozás formázás.
 */

use App\Services\MediaService;

/*
 * A TinyMCE CDN kulcsa a .env fájlból jön (TINYMCE_API_KEY), nem a
 * forráskódból - így nem kerül verziókövetésbe.
 *
 * Kulcs nélkül a szerkesztő a 'no-api-key' azonosítóval is betölthető és
 * használható, csak figyelmeztetést jelenít meg. Ilyenkor a szerkesztő alatt
 * jelezzük a szervezőnek, mit kell beállítani.
 */
$appConfig = require __DIR__ . '/../../../config/app.php';
$tinymceApiKey = $appConfig['tinymce_api_key'] ?? '';
$hasTinymceKey = $tinymceApiKey !== '';
$tinymceCdnKey = $hasTinymceKey ? $tinymceApiKey : 'no-api-key';

/** A szerkesztő által hívott végpontok */
$editorEndpoints = [
    'upload-image' => '/admin/media/kep',
    'upload-document' => '/admin/media/dokumentum',
    'media-library' => '/admin/media/lista',
    'link-list' => '/admin/media/hivatkozasok',
];
?>

<!-- A szerkesztő beállításai a textarea data-attribútumain keresztül -->
<script>
    (function () {
        var textarea = document.getElementById('content');
        if (!textarea) {
            return;
        }

        var settings = <?= json_encode([
            'data-upload-image' => $editorEndpoints['upload-image'],
            'data-upload-document' => $editorEndpoints['upload-document'],
            'data-media-library' => $editorEndpoints['media-library'],
            'data-link-list' => $editorEndpoints['link-list'],
            'data-max-image-mb' => (int) (MediaService::MAX_IMAGE_BYTES / 1048576),
            'data-max-document-mb' => (int) (MediaService::MAX_DOCUMENT_BYTES / 1048576),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        Object.keys(settings).forEach(function (name) {
            textarea.setAttribute(name, settings[name]);
        });
    })();
</script>

<script src="https://cdn.tiny.cloud/1/<?= e($tinymceCdnKey) ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script src="<?= e(asset('js/editor.js')) ?>"></script>

<?php if (!$hasTinymceKey): ?>
    <!-- Beállítási emlékeztető: a szerkesztő működik, de figyelmeztetést mutat -->
    <div class="alert alert-warning mt-3" role="status">
        <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <div class="text-sm">
            <p class="alert-title mb-0.5">A szerkesztőhöz nincs API kulcs beállítva</p>
            <p>
                A szerkesztő működik, de a TinyMCE figyelmeztetést jelenít meg. A kulcs a
                <code>.env</code> fájl <code>TINYMCE_API_KEY</code> beállításába kerül.
            </p>
        </div>
    </div>
<?php endif; ?>
