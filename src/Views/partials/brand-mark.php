<?php
/**
 * Márkajel partial - Okányi Biliárd Klub
 *
 * A klub címere (public/assets/images/logo.png) világos táblán. Hat helyen
 * jelenik meg: a publikus fejlécben, az admin fejlécben, a láblécben, a
 * szervezői belépésen és a két hibaoldalon. Korábban mindegyik helyen külön
 * volt kirakva egy rajzolt biliárdgolyó; egyetlen partialból mindenhol
 * ugyanaz a jelkép látszik, és a logó cseréje egy fájl felülírása.
 *
 * Miért világos tábla
 * -------------------
 * A címer túlnyomóan kék, arany kerettel, és mind a hat hely sötétzöld
 * hátterű. Közvetlenül a zöldre helyezve a két sötét szín összemosódna. A
 * törtfehér tábla ugyanaz a megoldás, mint a böngészőfül ikonján
 * (tools/generate-icons.php), így a márka egységesen jelenik meg a fülön, a
 * kezdőképernyőn és az oldalon is.
 *
 * A kép szándékosan nem lazy-load: a fejléc a látható területen van, a
 * késleltetett betöltés ott csak villanást okozna.
 *
 * @var string|null $brandMarkSize  A tábla méretosztályai, pl. 'w-9 h-9'
 * @var string|null $brandMarkClass Kiegészítő osztályok, pl. 'mb-4'
 */

// A hívó által beállított értékeket a partial után töröljük, hogy a
// következő beillesztés ne örökölje meg a korábbi méretet
$markSize = $brandMarkSize ?? 'w-9 h-9';
$markClass = $brandMarkClass ?? '';
?>
<span class="grid place-items-center shrink-0 rounded-xl bg-sand-50 shadow-inset-line <?= e($markSize) ?> <?= e($markClass) ?>">
    <img src="<?= e(asset('images/logo.png')) ?>"
         alt=""
         class="w-[78%] h-[78%] object-contain"
         decoding="async"
         aria-hidden="true">
</span>
<?php
unset($brandMarkSize, $brandMarkClass, $markSize, $markClass);
