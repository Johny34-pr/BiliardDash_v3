<?php
/**
 * Rövid használati útmutató a blogszerkesztő alá.
 *
 * A szerkesztő képességei nem mindegyike látszik az eszköztárból (például a
 * húzd-és-vidd képfeltöltés), ezért röviden összefoglaljuk őket.
 */

use App\Services\MediaService;

$maxImageMb = (int) (MediaService::MAX_IMAGE_BYTES / 1048576);
$maxDocMb = (int) (MediaService::MAX_DOCUMENT_BYTES / 1048576);
?>
<details class="editor-help mt-3">
    <summary>Mit tud a szerkesztő?</summary>

    <div class="editor-help-body">
        <dl>
            <dt>Kép beszúrása</dt>
            <dd>
                Húzd be a képet közvetlenül a szerkesztőbe, vagy illeszd be a
                vágólapról &mdash; azonnal feltöltődik. Fájlválasztóhoz használd a
                <strong>Kép</strong> gombot. Formátum: JPEG, PNG, GIF, WebP,
                legfeljebb <?= $maxImageMb ?> MB.
            </dd>

            <dt>Dokumentum csatolása</dt>
            <dd>
                A <strong>Csatolmány</strong> gombbal tölthetsz fel versenykiírást,
                eredménylistát és hasonlót. A hír szövegében letölthető, kiemelt
                hivatkozásként jelenik meg. Formátum: PDF, DOC(X), XLS(X), ODT,
                ODS, TXT, CSV, legfeljebb <?= $maxDocMb ?> MB.
            </dd>

            <dt>Korábbi feltöltések</dt>
            <dd>
                A <strong>Médiakönyvtár</strong> gomb megnyitja az eddig feltöltött
                képeket és dokumentumokat, így nem kell újra feltölteni őket.
            </dd>

            <dt>Belső hivatkozás</dt>
            <dd>
                A <strong>Hivatkozás</strong> gomb legördülőjében listából
                választhatsz: aloldalak, versenyek <em>nevezési űrlapja</em> és
                nevezői listája, hírek, galéria albumok és fórum topikok. Így nem
                kell URL-t beírni, és nem lesz törött hivatkozás.
            </dd>
        </dl>
    </div>
</details>
