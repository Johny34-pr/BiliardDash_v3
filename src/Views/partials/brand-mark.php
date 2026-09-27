<?php
/**
 * Márkajel partial - Okányi Biliárd Klub
 *
 * A klub logója (public/assets/images/logo.png). Hat helyen jelenik meg: a
 * publikus fejlécben, az admin fejlécben, a láblécben, a szervezői belépésen
 * és a két hibaoldalon. Egyetlen partialból mindenhol ugyanaz a jelkép
 * látszik, és a logó cseréje egy fájl felülírása.
 *
 * A logó a saját hátterét hozza
 * -----------------------------
 * A mostani logó teljes vásznat kitöltő négyzet, opak halványsárga háttérrel
 * (a képpontok 98%-a teljesen opak, csak a külső egy képpontos élsimító
 * keret átlátszó). Ezért NINCS szükség alátét-színre, és a kép kitöltheti a
 * keretet: a lekerekített span itt MASZK, nem tábla.
 *
 * Az overflow-hidden azért kell, mert a logó sarka szögletes: nélküle a
 * négyzet sarkai kilógnának a lekerekített keretből.
 *
 * A bg-sand-50 megmarad tartaléknak: ha a logót később átlátszó hátterű
 * változatra cserélik, a sötétzöld fejlécen nem ütne át. Látható hatása a
 * mostani képnél nincs, mert a logó teljesen kitölti a keretet.
 *
 * A kép szándékosan nem lazy-load: a fejléc a látható területen van, a
 * késleltetett betöltés ott csak villanást okozna.
 *
 * FIGYELEM: a logó cseréje után le kell futtatni a
 * `php tools/generate-icons.php` parancsot. A böngészőfül ikonja, az iOS
 * kezdőképernyő ikonja és a közösségi megosztás képe GENERÁLT fájl, nem erre
 * a képre hivatkozik - magától nem változik.
 *
 * @var string|null $brandMarkSize  A keret méretosztályai, pl. 'w-9 h-9'
 * @var string|null $brandMarkClass Kiegészítő osztályok, pl. 'mb-4'
 * @var string|null $brandMarkFill  A logó kitöltése, pl. 'w-[88%] h-[88%]'
 */

// A hívó által beállított értékeket a partial után töröljük, hogy a
// következő beillesztés ne örökölje meg a korábbi méretet
$markSize = $brandMarkSize ?? 'w-9 h-9';
$markClass = $brandMarkClass ?? '';
$markFill = $brandMarkFill ?? 'w-full h-full';
?>
<span class="grid place-items-center shrink-0 overflow-hidden rounded-xl bg-sand-50 <?= e($markSize) ?> <?= e($markClass) ?>">
    <img src="<?= e(asset('images/logo.png')) ?>"
         alt=""
         class="<?= e($markFill) ?> object-contain"
         decoding="async"
         aria-hidden="true">
</span>
<?php
unset($brandMarkSize, $brandMarkClass, $brandMarkFill, $markSize, $markClass, $markFill);
