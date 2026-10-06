-- ============================================================================
-- Okányi Biliárd Klub weboldal - Önkiszolgáló fióktörlés az adatkezelésben
-- Migráció: 012_privacy_self_service_deletion.sql
--
-- Leírás:
--   A tag mostantól a fiók oldalán maga is törölheti a fiókját. A tájékoztató
--   eddig azt írta, hogy a törlést e-mailben kell kérni, és nem mondta meg,
--   mi történik a már leadott nevezésekkel. Ez a migráció a "4. Meddig
--   tároljuk" és a "6. Milyen jogaid vannak" szakaszt pontosítja a valós
--   működésre.
--
--   Miért kell kiírni, mi marad meg: a törlés visszafordíthatatlan, és a
--   lezárult versenyek névsorában a NÉV szándékosan megmarad. Ha ezt a
--   tájékoztató nem mondja ki, a tag joggal hihetné, hogy minden adata
--   eltűnt.
--
-- KÉTFÉLE KÓDOLÁS
--   A 007-es és a 011-es migráció HTML entitásokkal írta a szöveget
--   (&aacute; stb.), de ha a szervező egyszer megnyitja és elmenti az oldalt
--   a szerkesztőben, a TinyMCE valódi UTF-8 karakterekre normalizálja. Ezért
--   egy entitásokra illesztő REPLACE ilyenkor NÉMÁN nem talál semmit: a
--   migráció "alkalmazva" státuszt kap, de nem változtat. Minden cserét ezért
--   MINDKÉT alakra lefuttatunk.
--
-- IDEMPOTENCIA
--   A két módosítás KÜLÖN, csak ASCII karakterekből álló jelölőt kap
--   (data-privacy-note="..."). Mindegyik utasítás a SAJÁT jelölőjére
--   vizsgál, ezért
--     - az újrafuttatás nem duplikál,
--     - és ha az egyik cserének nincs horgonya, a másik akkor is lefut, majd
--       később a hiányzó önállóan pótolható.
--   A jelölő ASCII, tehát a tartalom kódolásától függetlenül megtalálható.
--
--   FIGYELEM: a szöveg jogi felülvizsgálatot igényel. A lezárult versenyek
--   névsorának megőrzése jogos érdekre épül (sporteredmény nyilvántartása),
--   de ezt jogásznak kell megerősítenie.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. A megőrzési idő pontosítása - valódi UTF-8 karakteres tartalomnál
-- ---------------------------------------------------------------------------
UPDATE pages
SET content = REPLACE(
    content,
    'A fiók adatait a fiók törléséig tároljuk.',
    CONCAT(
        'A fiók adatait a fiók törléséig tároljuk. ',
        '<span data-privacy-note="retention-after-deletion">',
        'A fiók törlése után a lezárult versenyek névsorában a nevező neve ',
        'megmarad &ndash; ez a versenyeredmény része &ndash;, az e-mail cím ',
        'és a telefonszám viszont törlődik.',
        '</span>'
    )
)
WHERE slug = 'adatkezeles'
  AND content NOT LIKE '%retention-after-deletion%';

-- ---------------------------------------------------------------------------
-- 2. Ugyanaz, HTML entitásokkal tárolt tartalomnál
-- ---------------------------------------------------------------------------
UPDATE pages
SET content = REPLACE(
    content,
    'A fi&oacute;k adatait a fi&oacute;k t&ouml;rl&eacute;s&eacute;ig t&aacute;roljuk.',
    CONCAT(
        'A fi&oacute;k adatait a fi&oacute;k t&ouml;rl&eacute;s&eacute;ig t&aacute;roljuk. ',
        '<span data-privacy-note="retention-after-deletion">',
        'A fiók törlése után a lezárult versenyek névsorában a nevező neve ',
        'megmarad &ndash; ez a versenyeredmény része &ndash;, az e-mail cím ',
        'és a telefonszám viszont törlődik.',
        '</span>'
    )
)
WHERE slug = 'adatkezeles'
  AND content NOT LIKE '%retention-after-deletion%';

-- ---------------------------------------------------------------------------
-- 3. A jogok szakasz kiegészítése - valódi UTF-8 karakteres címnél
-- ---------------------------------------------------------------------------
UPDATE pages
SET content = REPLACE(
    content,
    '<h2>7. Adatbiztonság</h2>',
    CONCAT(
        '<p data-privacy-note="account-deletion">A fiókodat ',
        '<strong>bármikor magad is törölheted</strong> a fiókod oldalán, a ',
        'jelszavad megadásával. A törlés nem vonható vissza, és a ',
        'következőkkel jár:</p>',
        '<ul>',
        '<li>a fiókod és a hozzá tartozó belépés megszűnik;</li>',
        '<li>a még nyitott versenyekre leadott nevezéseidet visszavonjuk, ',
        'ezért nem fogsz szerepelni a névsorban;</li>',
        '<li>a lezárult versenyek névsorában és a ranglistán a neved és az ',
        'elért eredményed megmarad, mert ezek a klub versenytörténetének ',
        'részei; az e-mail címedet és a telefonszámodat eltávolítjuk.</li>',
        '</ul>',
        '<p>Ha a névsorban szereplő neved törlését is kéred, azt az ',
        'info@okanyibiliard.hu címen jelezheted, és egyénileg megvizsgáljuk.</p>',
        '<h2>7. Adatbiztonság</h2>'
    )
)
WHERE slug = 'adatkezeles'
  AND content NOT LIKE '%account-deletion%';

-- ---------------------------------------------------------------------------
-- 4. Ugyanaz, HTML entitásokkal tárolt címnél
-- ---------------------------------------------------------------------------
UPDATE pages
SET content = REPLACE(
    content,
    '<h2>7. Adatbiztons&aacute;g</h2>',
    CONCAT(
        '<p data-privacy-note="account-deletion">A fiókodat ',
        '<strong>bármikor magad is törölheted</strong> a fiókod oldalán, a ',
        'jelszavad megadásával. A törlés nem vonható vissza, és a ',
        'következőkkel jár:</p>',
        '<ul>',
        '<li>a fiókod és a hozzá tartozó belépés megszűnik;</li>',
        '<li>a még nyitott versenyekre leadott nevezéseidet visszavonjuk, ',
        'ezért nem fogsz szerepelni a névsorban;</li>',
        '<li>a lezárult versenyek névsorában és a ranglistán a neved és az ',
        'elért eredményed megmarad, mert ezek a klub versenytörténetének ',
        'részei; az e-mail címedet és a telefonszámodat eltávolítjuk.</li>',
        '</ul>',
        '<p>Ha a névsorban szereplő neved törlését is kéred, azt az ',
        'info@okanyibiliard.hu címen jelezheted, és egyénileg megvizsgáljuk.</p>',
        '<h2>7. Adatbiztons&aacute;g</h2>'
    )
)
WHERE slug = 'adatkezeles'
  AND content NOT LIKE '%account-deletion%';
