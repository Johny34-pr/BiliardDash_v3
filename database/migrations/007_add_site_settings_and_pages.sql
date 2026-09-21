-- ============================================================================
-- Okányi Biliárd Klub weboldal - Oldalbeállítások és szerkeszthető tartalmi oldalak
-- Migráció: 007_add_site_settings_and_pages.sql
--
-- Leírás:
--   1. site_settings: kulcs-érték tábla a futásidőben kapcsolható
--      funkciókhoz. Az első ilyen a fórum modul, amely alapértelmezetten
--      inaktív: ilyenkor a menüből is eltűnik, és az útvonalai sem érhetők el.
--      Ez szándékosan adatbázisban van és nem .env-ben, mert a szervezőnek
--      a felületről kell tudnia kapcsolni, szerverhozzáférés nélkül.
--
--   2. pages: szerkeszthető tartalmi oldalak (Rólunk, Emlékoldal,
--      Adatkezelési tájékoztató). A slug adja a publikus útvonalat, ezért
--      egyedi. A tartalom a hírekkel egyező módon rich text HTML.
--
--      A slug nem szerkeszthető a felületről: az oldalak fix útvonalon
--      élnek, és a menü is ezekre hivatkozik. Új oldal felvétele
--      migrációval történik, hogy a hivatkozások ne törhessenek el.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Oldalbeállítások (kulcs-érték)
-- ---------------------------------------------------------------------------
CREATE TABLE site_settings (
    setting_key VARCHAR(60) PRIMARY KEY,

    -- Szövegként tárolt érték. A típusát a hívó dönti el: a logikai
    -- kapcsolók '0' vagy '1' értéket kapnak.
    setting_value TEXT NULL DEFAULT NULL,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A fórum alapértelmezetten kikapcsolt állapotban indul
INSERT INTO site_settings (setting_key, setting_value) VALUES ('forum_enabled', '0');

-- ---------------------------------------------------------------------------
-- 2. Szerkeszthető tartalmi oldalak
-- ---------------------------------------------------------------------------
CREATE TABLE pages (
    id CHAR(36) PRIMARY KEY,

    -- A publikus útvonal utolsó szegmense, pl. 'rolunk' → /rolunk
    slug VARCHAR(60) NOT NULL,

    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,

    -- Keresőoptimalizálási leírás. Üresen hagyva a tartalom első
    -- mondataiból készül, ezért nem kötelező.
    meta_description VARCHAR(300) NULL DEFAULT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_pages_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 3. Kezdő tartalom
-- ---------------------------------------------------------------------------
-- A szövegek kiindulási vázlatok, a szervező a felületen átírja őket.
-- Az adatkezelési tájékoztató az alkalmazás valódi működését írja le
-- (mely mezőket kéri, meddig tárolja), de jogi ellenőrzést igényel.

INSERT INTO pages (id, slug, title, content, meta_description) VALUES
(
    '00000000-0000-4000-8000-000000000010',
    'rolunk',
    'Rólunk',
    '<h2>Kik vagyunk</h2><p>Az Ok&aacute;nyi Bili&aacute;rdterem k&ouml;z&ouml;ss&eacute;g&eacute;nek oldala. C&eacute;lunk, hogy a bili&aacute;rdot j&aacute;tsz&oacute;k egy helyen tal&aacute;lj&aacute;k meg a versenyki&iacute;r&aacute;sokat, a nevez&eacute;st &eacute;s az esem&eacute;nyek k&eacute;peit.</p><h2>Mit tal&aacute;lsz itt</h2><ul><li>Friss h&iacute;rek &eacute;s versenybesz&aacute;mol&oacute;k</li><li>Online nevez&eacute;s a meghirdetett versenyekre</li><li>Fot&oacute;gal&eacute;ria a helyezettekkel</li></ul><p><em>Ez a sz&ouml;veg a szervez&#337;i fel&uuml;leten szerkeszthet&#337;.</em></p>',
    'Az Okányi Biliárdterem közösségének bemutatkozása: versenyek, nevezés és galéria egy helyen.'
),
(
    '00000000-0000-4000-8000-000000000011',
    'emlekoldal',
    'Emlékoldal',
    '<p>Ezen az oldalon azokra eml&eacute;kez&uuml;nk, akik sokat tettek a k&ouml;z&ouml;ss&eacute;g&eacute;rt, &eacute;s m&aacute;r nem lehetnek k&ouml;z&ouml;tt&uuml;nk.</p><p><em>Ez a sz&ouml;veg a szervez&#337;i fel&uuml;leten szerkeszthet&#337;: ide ker&uuml;lhetnek nevek, k&eacute;pek &eacute;s megeml&eacute;kez&eacute;sek.</em></p>',
    'Megemlékezés azokról, akik sokat tettek a biliárd közösségért.'
),
(
    '00000000-0000-4000-8000-000000000012',
    'adatkezeles',
    'Adatkezelési tájékoztató',
    '<p><strong>Ez a t&aacute;j&eacute;koztat&oacute; kiindul&aacute;si v&aacute;zlat, amelyet k&ouml;zz&eacute;t&eacute;tel el&#337;tt jogi szakember&nbsp;ellen&#337;rz&eacute;s&eacute;re javasolt bocs&aacute;tani. Az adatkezel&#337; adatait ki kell eg&eacute;sz&iacute;teni.</strong></p><h2>1. Az adatkezel&#337;</h2><p>Adatkezel&#337; neve, sz&eacute;khelye &eacute;s el&eacute;rhet&#337;s&eacute;ge: <em>kit&ouml;lt&eacute;sre v&aacute;r</em>. Kapcsolat: info@okanyibiliard.hu</p><h2>2. Milyen adatokat kezel&uuml;nk</h2><h3>2.1 Versenynevez&eacute;s</h3><p>Nevez&eacute;skor a nevez&#337; nev&eacute;t, e-mail c&iacute;m&eacute;t &eacute;s telefonsz&aacute;m&aacute;t k&eacute;rj&uuml;k. Ezekre a nevez&eacute;s nyilv&aacute;ntart&aacute;s&aacute;hoz, a visszaigazol&oacute; e-mail k&uuml;ld&eacute;s&eacute;hez &eacute;s a versennyel kapcsolatos kapcsolattart&aacute;shoz van sz&uuml;ks&eacute;g. Jogalap: a szolg&aacute;ltat&aacute;s ny&uacute;jt&aacute;s&aacute;hoz sz&uuml;ks&eacute;ges szerz&#337;d&eacute;s teljes&iacute;t&eacute;se.</p><p>A nyilv&aacute;nos nevez&#337;i list&aacute;n <strong>kiz&aacute;r&oacute;lag a nevez&#337; neve &eacute;s a nevez&eacute;s ideje</strong> jelenik meg. Az e-mail c&iacute;met &eacute;s a telefonsz&aacute;mot csak a szervez&#337; l&aacute;tja.</p><h3>2.2 L&aacute;togat&oacute;i fi&oacute;k</h3><p>A fi&oacute;k l&eacute;trehoz&aacute;sa nem k&ouml;telez&#337;, nevezni n&eacute;lk&uuml;le is lehet. Regisztr&aacute;ci&oacute;n&aacute;l a nevet, e-mail c&iacute;met, telefonsz&aacute;mot, telep&uuml;l&eacute;st &eacute;s jelsz&oacute;t k&eacute;rj&uuml;k. A jelsz&oacute;t nem t&aacute;roljuk olvashat&oacute; form&aacute;ban, csak annak egyir&aacute;ny&uacute; matematikai lenyomat&aacute;t.</p><h3>2.3 F&oacute;rum</h3><p>Ha a f&oacute;rum akt&iacute;v, a hozz&aacute;sz&oacute;l&aacute;shoz megadott nevet &eacute;s a hozz&aacute;sz&oacute;l&aacute;s sz&ouml;veg&eacute;t t&aacute;roljuk. Az IP-c&iacute;met kiz&aacute;r&oacute;lag lenyomat form&aacute;j&aacute;ban, vissza&eacute;l&eacute;sek sz&#369;r&eacute;s&eacute;hez &#337;rizz&uuml;k meg.</p><h3>2.4 S&uuml;tik</h3><p>M&#369;k&ouml;d&eacute;shez sz&uuml;ks&eacute;ges s&uuml;tit haszn&aacute;lunk a bejelentkezett &aacute;llapot fenntart&aacute;s&aacute;hoz. Ha a bel&eacute;p&eacute;skor a bel&eacute;p&eacute;si adatok megjegyz&eacute;s&eacute;t k&eacute;red, egy tov&aacute;bbi, lej&aacute;rati id&#337;vel ell&aacute;tott s&uuml;tit is elhelyez&uuml;nk. Ez a s&uuml;ti a kil&eacute;p&eacute;skor &eacute;rv&eacute;nytelenn&eacute; v&aacute;lik. M&eacute;r&eacute;si &eacute;s hirdet&eacute;si c&eacute;l&uacute; s&uuml;tit nem haszn&aacute;lunk.</p><h2>3. Meddig t&aacute;roljuk</h2><p>A nevez&eacute;si adatokat a verseny lez&aacute;r&aacute;s&aacute;t k&ouml;vet&#337;en az elsz&aacute;mol&aacute;shoz &eacute;s az eredm&eacute;nyek nyilv&aacute;ntart&aacute;s&aacute;hoz sz&uuml;ks&eacute;ges ideig kezelj&uuml;k. A fi&oacute;k adatait a fi&oacute;k t&ouml;rl&eacute;s&eacute;ig t&aacute;roljuk.</p><h2>4. Kinek adjuk &aacute;t</h2><p>Az adatokat harmadik f&eacute;lnek nem adjuk el &eacute;s marketing c&eacute;lra nem haszn&aacute;ljuk fel. A visszaigazol&oacute; e-mailek k&uuml;ld&eacute;s&eacute;hez levelez&#337;szolg&aacute;ltat&oacute;t vesz&uuml;nk ig&eacute;nybe, amely az e-mail c&iacute;met a k&uuml;ld&eacute;s c&eacute;lj&aacute;b&oacute;l ismeri meg.</p><h2>5. Milyen jogaid vannak</h2><p>K&eacute;rheted a r&oacute;lad t&aacute;rolt adatok m&aacute;solat&aacute;t, azok jav&iacute;t&aacute;s&aacute;t vagy t&ouml;rl&eacute;s&eacute;t, illetve tiltakozhatsz a kezel&eacute;s ellen. A nevez&eacute;sedet a nevez&eacute;si hat&aacute;rid&#337; lej&aacute;rt&aacute;ig a fi&oacute;kodban magad is visszavonhatod. Megkeres&eacute;s&eacute;t az info@okanyibiliard.hu c&iacute;men v&aacute;rjuk.</p><h2>6. Adatbiztons&aacute;g</h2><p>A jelsz&oacute;t lenyomat form&aacute;j&aacute;ban t&aacute;roljuk, a szervez&#337;i fel&uuml;let k&uuml;l&ouml;n jelsz&oacute;val v&eacute;dett, &eacute;s a felt&ouml;lt&ouml;tt f&aacute;jlok nem futtathat&oacute;k a kiszolg&aacute;l&oacute;n.</p>',
    'Tájékoztató arról, milyen személyes adatokat kezelünk a versenynevezés, a látogatói fiók és a fórum során.'
);
