-- ============================================================================
-- Okányi Biliárd Klub weboldal - Kép- és videófelvételek az adatkezelésben
-- Migráció: 011_add_privacy_media_section.sql
--
-- Leírás:
--   Az adatkezelési tájékoztató (pages.slug = 'adatkezeles') eddig nem szólt
--   a versenyeken készülő fotó- és videófelvételekről, pedig a galéria, a
--   közösségi oldalak és az élő közvetítés is ezekre épül. A hiányzó szakasz
--   bekerül a 3. helyre, mert tartalmilag az adatkörhöz tartozik: közvetlenül
--   a "Milyen adatokat kezelünk" után a helye, a megőrzési idő és a
--   továbbítás elé.
--
--   A beszúrás miatt a korábbi 3-6. szakasz 4-7. lesz. Az átszámozás
--   FORDÍTOTT sorrendben fut (6->7, 5->6, 4->5, 3->4), különben az éppen
--   átírt szám ütközne a következő cserével.
--
--   Idempotens: minden UPDATE-nek feltétele, hogy a tartalom még ne
--   tartalmazza az új szakasz címét. Így a migráció újrafuttatása nem
--   számoz tovább, és ha a szervező időközben maga írta meg a szakaszt,
--   azt sem írjuk felül.
--
--   A szöveg HTML entitásokkal íródik, ahogy a 007-es migráció seedje is -
--   így az SQL fájl kódolása nem befolyásolja az eredményt.
--
--   FIGYELEM: a szöveg jogi felülvizsgálatot igényel. A tömegfelvétel és a
--   hozzájárulás megfogalmazása a Ptk. 2:48. §-ára és a GDPR önkéntes
--   hozzájárulás követelményére épül, de nem helyettesíti a jogi ellenőrzést.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. A meglévő szakaszok átszámozása (fordított sorrendben)
-- ---------------------------------------------------------------------------
UPDATE pages
SET content = REPLACE(content, '<h2>6. Adatbiztons&aacute;g</h2>', '<h2>7. Adatbiztons&aacute;g</h2>')
WHERE slug = 'adatkezeles'
  AND content NOT LIKE '%K&eacute;p- &eacute;s vide&oacute;felv&eacute;telek%';

UPDATE pages
SET content = REPLACE(content, '<h2>5. Milyen jogaid vannak</h2>', '<h2>6. Milyen jogaid vannak</h2>')
WHERE slug = 'adatkezeles'
  AND content NOT LIKE '%K&eacute;p- &eacute;s vide&oacute;felv&eacute;telek%';

UPDATE pages
SET content = REPLACE(content, '<h2>4. Kinek adjuk &aacute;t</h2>', '<h2>5. Kinek adjuk &aacute;t</h2>')
WHERE slug = 'adatkezeles'
  AND content NOT LIKE '%K&eacute;p- &eacute;s vide&oacute;felv&eacute;telek%';

UPDATE pages
SET content = REPLACE(content, '<h2>3. Meddig t&aacute;roljuk</h2>', '<h2>4. Meddig t&aacute;roljuk</h2>')
WHERE slug = 'adatkezeles'
  AND content NOT LIKE '%K&eacute;p- &eacute;s vide&oacute;felv&eacute;telek%';

-- ---------------------------------------------------------------------------
-- 2. Az új 3. szakasz beszúrása a "Meddig tároljuk" elé
-- ---------------------------------------------------------------------------
-- Ez az utasítás viszi be a jelölőszöveget is, ezért a fenti feltételek
-- mindegyike még a beszúrás előtti állapotot látja.
UPDATE pages
SET content = REPLACE(
    content,
    '<h2>4. Meddig t&aacute;roljuk</h2>',
    CONCAT(
        '<h2>3. K&eacute;p- &eacute;s vide&oacute;felv&eacute;telek a versenyeken</h2>',
        '<p>A versenyeinken f&eacute;nyk&eacute;p- &eacute;s vide&oacute;felv&eacute;telek k&eacute;sz&uuml;lnek. ',
        'Ezeket a verseny dokument&aacute;l&aacute;s&aacute;ra, az eredm&eacute;nyek k&ouml;zz&eacute;t&eacute;tel&eacute;re &eacute;s a klub ',
        'bemutat&aacute;s&aacute;ra haszn&aacute;ljuk fel. A felv&eacute;telek a weboldal gal&eacute;ri&aacute;j&aacute;ban, ',
        'a klub Facebook oldal&aacute;n &eacute;s csoportjaiban jelenhetnek meg, illetve egyes versenyekr&#337;l ',
        '&eacute;l&#337; k&ouml;zvet&iacute;t&eacute;s is k&eacute;sz&uuml;l.</p>',
        '<p>A verseny eg&eacute;sz&eacute;t bemutat&oacute;, t&ouml;bb r&eacute;sztvev&#337;t &aacute;br&aacute;zol&oacute; felv&eacute;teleket ',
        't&ouml;megfelv&eacute;telk&eacute;nt kezelj&uuml;k, ezekhez a Polg&aacute;ri T&ouml;rv&eacute;nyk&ouml;nyv szerint nem ',
        'sz&uuml;ks&eacute;ges egyedi hozz&aacute;j&aacute;rul&aacute;s. Ha valakir&#337;l &ouml;n&aacute;ll&oacute;an, egy&eacute;rtelm&#369;en ',
        'felismerhet&#337; m&oacute;don k&eacute;sz&uuml;l felv&eacute;tel &ndash; p&eacute;ld&aacute;ul j&aacute;t&eacute;k k&ouml;zben az asztaln&aacute;l ',
        'vagy az eredm&eacute;nyhirdet&eacute;sen &ndash;, annak k&ouml;zz&eacute;t&eacute;tel&eacute;hez a hozz&aacute;j&aacute;rul&aacute;s&aacute;t ',
        'k&eacute;rj&uuml;k. A hozz&aacute;j&aacute;rul&aacute;s megad&aacute;sa &ouml;nk&eacute;ntes, a nevez&eacute;snek nem felt&eacute;tele, ',
        '&eacute;s b&aacute;rmikor visszavonhat&oacute;.</p>',
        '<p>Ha nem szeretn&eacute;d, hogy felv&eacute;tel k&eacute;sz&uuml;lj&ouml;n r&oacute;lad, vagy egy m&aacute;r k&ouml;zz&eacute;tett ',
        'k&eacute;p elt&aacute;vol&iacute;t&aacute;s&aacute;t k&eacute;red, jelezd a helysz&iacute;nen a versenyszervez&#337;nek, vagy ',
        '&iacute;rj az info@okanyibiliard.hu c&iacute;mre. A jelzett felv&eacute;telt elt&aacute;vol&iacute;tjuk a weboldalr&oacute;l ',
        '&eacute;s a k&ouml;z&ouml;ss&eacute;gi oldalainkr&oacute;l. A hozz&aacute;j&aacute;rul&aacute;s visszavon&aacute;sa a kor&aacute;bbi ',
        'k&ouml;zz&eacute;t&eacute;telt nem &eacute;rinti visszamen&#337;leg.</p>',
        '<p>A felv&eacute;teleket a gal&eacute;ria r&eacute;szek&eacute;nt, a klub t&ouml;rt&eacute;net&eacute;nek dokument&aacute;l&aacute;sa ',
        'c&eacute;lj&aacute;b&oacute;l tart&oacute;san meg&#337;rizz&uuml;k, kiv&eacute;ve, ha a t&ouml;rl&eacute;s&uuml;ket k&eacute;red.</p>',
        '<h2>4. Meddig t&aacute;roljuk</h2>'
    )
)
WHERE slug = 'adatkezeles'
  AND content NOT LIKE '%K&eacute;p- &eacute;s vide&oacute;felv&eacute;telek%';
