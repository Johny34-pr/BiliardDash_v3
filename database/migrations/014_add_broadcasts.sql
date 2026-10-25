-- ============================================================================
-- Okányi Biliárd Klub weboldal - Közvetítések
-- Migráció: 014_add_broadcasts.sql
--
-- Leírás:
--   A közvetítés eddig EGYETLEN beállítás volt (broadcast_url): a szervező
--   beírta az élő adás címét, és az új cím mentésekor a régi felülíródott.
--   A korábbi közvetítések így nyomtalanul eltűntek.
--
--   Ez a migráció önálló táblát ad nekik, hogy a régebbi adások is
--   megmaradjanak és a verseny neve alapján visszakereshetők legyenek.
--
--   Miért külön tábla és nem oszlopok a competitions táblán (mint a
--   jegyzőkönyvnél): egy versenyhez több közvetítés is tartozhat (asztalok,
--   elődöntő és döntő külön adásban), és a közvetítés lehet verseny nélküli
--   is (klubest, bemutató). Mindkettő elesne, ha a verseny sorában élne.
--
--   Oszlopok:
--     competition_id  melyik versenyhez tartozik, vagy NULL ha egyikhez sem.
--                     A verseny törlése NEM viszi magával a közvetítést
--                     (ON DELETE SET NULL): a felvett hivatkozás önmagában
--                     is tartalom.
--     title           a közvetítés megjelenő neve. Versenyhez kötve
--                     alapértelmezésben a verseny neve, de felülírható
--                     (pl. "Okány Kupa - döntő").
--     url             a külső adás címe (YouTube, Facebook). Csak http és
--                     https sémát fogad el az alkalmazás.
--     broadcast_date  mikor volt (vagy mikor lesz) az adás. Ez rendezi a
--                     listát, és ez alapján látszik, melyik a régebbi.
--     is_live         fut-e most. A szervező kapcsolja, ettől jelenik meg
--                     az "Élő" jelzés a menüben és a lista élén.
-- ============================================================================

-- --- A közvetítések táblája ------------------------------------------------
-- IF NOT EXISTS: a migráció újrafuttatása ne akadjon el a "table already
-- exists" hibán, ahogy a 013-as migráció oszlopvizsgálata is ezt a célt
-- szolgálja.
CREATE TABLE IF NOT EXISTS broadcasts (
    id CHAR(36) PRIMARY KEY,
    competition_id CHAR(36) NULL,
    title VARCHAR(160) NOT NULL,
    url VARCHAR(500) NOT NULL,
    broadcast_date DATE NOT NULL,
    is_live TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- A lista dátum szerint csökkenően rendez, a keresés pedig ugyanezen a
    -- renden lapoz, ezért az index a dátumra megy.
    INDEX idx_broadcast_date (broadcast_date DESC),

    -- A menü minden kérésnél megkérdezi, van-e élő adás: ez a legkisebb,
    -- leggyakrabban futó lekérdezés.
    INDEX idx_broadcast_live (is_live),

    -- A versenyre mutató JOIN a keresés alapja (a verseny nevére szűrünk)
    INDEX idx_broadcast_competition (competition_id),

    CONSTRAINT fk_broadcast_competition
        FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- A meglévő élő hivatkozás átmentése ------------------------------------
-- A beállításban lévő cím a most futó (vagy legutóbbi) közvetítés, ezért
-- nem dobjuk el: felvesszük a táblába élő adásként, a mai dátummal.
-- Verseny nélkül kerül be, mert a beállítás nem tudta, melyikhez tartozik -
-- a szervező a felületen utólag hozzárendelheti.
--
-- A cím a menüpont feliratából lesz; ha az üres, az alapértelmezés
-- "Közvetítés". Ez nem beszédes név egy archív bejegyzéshez, de a valóban
-- ismert adatból indul ki, és a szervezői felületen egy lépésben átírható.
SET @liveUrl := (SELECT setting_value FROM site_settings WHERE setting_key = 'broadcast_url');
SET @liveTitle := COALESCE(
    (SELECT NULLIF(TRIM(setting_value), '') FROM site_settings WHERE setting_key = 'broadcast_label'),
    'Közvetítés'
);

-- Ha a migráció már egyszer lefutott (vagy a szervező kézzel felvette ezt a
-- címet), nem keletkezik duplikált bejegyzés.
SET @alreadySaved := (SELECT COUNT(*) FROM broadcasts WHERE url = @liveUrl);

INSERT INTO broadcasts (id, competition_id, title, url, broadcast_date, is_live)
SELECT UUID(), NULL, @liveTitle, @liveUrl, CURDATE(), 1
FROM DUAL
WHERE @liveUrl REGEXP '^https?://'
  AND @alreadySaved = 0;

-- --- A feleslegessé vált beállítás eltávolítása ----------------------------
-- A cím mostantól a broadcasts táblában él, versenyenként. A kulcs
-- meghagyása félrevezető lenne: a beállítások oldalon szerkeszthetőnek
-- tűnne, de semmire nem hatna.
--
-- A broadcast_label MEGMARAD: az továbbra is a menüpont felirata.
DELETE FROM site_settings WHERE setting_key = 'broadcast_url';
