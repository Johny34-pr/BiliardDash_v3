-- ============================================================================
-- Okányi Biliárd Klub weboldal - Szezonok, ranglista, értesítések
-- Migráció: 010_add_seasons_ranking_and_notifications.sql
--
-- Hat egymásra épülő rész:
--
--   1. seasons: a szezon mint önálló fogalom. Eddig nem létezett, pedig
--      két funkció is erre épül: az archív galéria és az archív ranglista.
--      A "melyik szezonhoz tartozik" kérdést egyetlen helyen kell tudni
--      megválaszolni, ezért tábla, nem pedig szöveges címke a versenyen.
--
--   2. competitions.season_id és albums.season_id: a hozzárendelés. Mindkettő
--      NULL-t is felvehet, mert a régi tartalomnál nem tudjuk biztosan, melyik
--      szezonhoz tartozott - a szervező utólag beállíthatja.
--
--   3. albums.is_archived: az archív galéria kapcsolója. Szándékosan külön
--      oszlop, nem a szezonból számolt érték: egy régi szezon albumát a
--      szervező szándékosan előtérben tarthatja, és fordítva.
--
--   4. images.medium_path: a galéria listakártyáinak éles képe. Eddig csak
--      200x200-as bélyegkép és a változatlan eredeti létezett, a kártya
--      pedig a bélyegképet feszítette ki négyszeres méretre - ettől volt
--      lágy. A közepes méret 1200 px-ig arányosan méretezett változat.
--      NULL a régi képeknél, amíg a tools/backfill-images.php le nem fut;
--      addig a megjelenítés a bélyegképre esik vissza.
--
--   5. ranking_entries: versenyenkénti pontszámok játékosonként. A ranglista
--      NEM tárolt összeg, hanem ezekből számolt eredmény - így nem tud
--      elcsúszni a pontoktól, és minden szerkesztés után azonnal helyes.
--
--   6. competition_notifications: mely versenyről, milyen értesítés ment ki.
--      Az (competition_id, kind) páron UNIQUE kulcs áll, ezért ugyanaz az
--      értesítés kétszer nem küldhető ki - sem kézi indításból, sem a
--      cronból, sem párhuzamos kérésekből.
--
-- Továbbá: a registrations táblára felkerül a (competition_id,
-- created_by_user_id) UNIQUE kulcs, hogy egy fiók versenyenként egyszer
-- nevezhessen. A vendégnevezéseknél a created_by_user_id NULL, és a MySQL a
-- NULL értékeket egyedinek tekinti, ezért a szervezői felvitel nem ütközik.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Szezonok
-- ---------------------------------------------------------------------------
CREATE TABLE seasons (
    id CHAR(36) PRIMARY KEY,

    -- Megjelenő név, pl. "2026/2027"
    name VARCHAR(60) NOT NULL,

    -- A szezon kezdete. Ez adja a rendezést, mert a név szerinti rendezés
    -- ("2026/2027" vs "2027/2028") csak véletlenül helyes.
    starts_on DATE NULL DEFAULT NULL,

    -- Pontosan egy szezon aktuális. A kizárólagosságot a SeasonService
    -- érvényesíti: új aktuális beállításakor a többiről leveszi a jelzőt.
    is_current TINYINT(1) NOT NULL DEFAULT 0,

    -- Az archivált szezon ranglistája már véglegesnek tekintett: a
    -- publikus felületen az archívumban jelenik meg, nem az élen.
    is_archived TINYINT(1) NOT NULL DEFAULT 0,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_seasons_name (name),
    INDEX idx_seasons_order (is_archived, starts_on, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A jelenlegi szezon, hogy a felület első indításkor se legyen üres.
INSERT INTO seasons (id, name, starts_on, is_current, is_archived) VALUES
('00000000-0000-4000-8000-000000000020', '2026/2027', '2026-07-01', 1, 0);

-- ---------------------------------------------------------------------------
-- 2. Szezon hozzárendelése a versenyekhez
-- ---------------------------------------------------------------------------
ALTER TABLE competitions
    ADD COLUMN season_id CHAR(36) NULL DEFAULT NULL AFTER id,
    ADD INDEX idx_competitions_season (season_id, date),
    ADD CONSTRAINT fk_competitions_season
        FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL;

-- A meglévő versenyek az aktuális szezonhoz kerülnek: ez a legvalószínűbb,
-- és a szervező bármikor átállíthatja.
UPDATE competitions
SET season_id = '00000000-0000-4000-8000-000000000020'
WHERE season_id IS NULL;

-- ---------------------------------------------------------------------------
-- 3. Szezon és archiválás az albumoknál
-- ---------------------------------------------------------------------------
ALTER TABLE albums
    ADD COLUMN season_id CHAR(36) NULL DEFAULT NULL AFTER name,
    ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER season_id,
    ADD INDEX idx_albums_archived (is_archived, created_at),
    ADD CONSTRAINT fk_albums_season
        FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL;

UPDATE albums
SET season_id = '00000000-0000-4000-8000-000000000020'
WHERE season_id IS NULL;

-- ---------------------------------------------------------------------------
-- 4. Közepes méretű kép a galéria kártyáihoz
-- ---------------------------------------------------------------------------
ALTER TABLE images
    ADD COLUMN medium_path VARCHAR(500) NULL DEFAULT NULL AFTER thumbnail_path;

-- ---------------------------------------------------------------------------
-- 5. Ranglista pontszámok
-- ---------------------------------------------------------------------------
CREATE TABLE ranking_entries (
    id CHAR(36) PRIMARY KEY,
    competition_id CHAR(36) NOT NULL,

    -- Ha a játékosnak van fiókja, ide kerül. A ranglista ettől függetlenül
    -- a néven alapul: a klub versenyein fiók nélküli játékos is indulhat,
    -- és a pontjai ugyanúgy beleszámítanak.
    user_id CHAR(36) NULL DEFAULT NULL,

    player_name VARCHAR(100) NOT NULL,
    points INT NOT NULL DEFAULT 0,

    -- A versenyen elért helyezés. Csak tájékoztató adat a táblázatban,
    -- a ranglista a pontokból áll össze.
    place INT UNSIGNED NULL DEFAULT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Egy versenyen egy játékos egyszer szerepel. Enélkül egy kétszer
    -- felvitt név duplán számítana bele az összesítésbe.
    UNIQUE KEY uk_ranking_competition_player (competition_id, player_name),
    INDEX idx_ranking_competition (competition_id, points),
    INDEX idx_ranking_player (player_name),

    CONSTRAINT fk_ranking_competition
        FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    CONSTRAINT fk_ranking_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 6. Kiküldött értesítések nyilvántartása
-- ---------------------------------------------------------------------------
CREATE TABLE competition_notifications (
    id CHAR(36) PRIMARY KEY,
    competition_id CHAR(36) NOT NULL,

    -- 'announced'         = új versenykiírás
    -- 'registration_open' = megnyílt a nevezés
    kind VARCHAR(30) NOT NULL,

    recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Ez a kulcs a kétszeres kiküldés elleni védelem: a beszúrás ütközik,
    -- ha ugyanarról a versenyről ugyanaz az értesítés már kiment.
    UNIQUE KEY uk_notification_kind (competition_id, kind),
    INDEX idx_notification_sent (sent_at),

    CONSTRAINT fk_notification_competition
        FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 7. Fiókonként egy nevezés versenyenként
-- ---------------------------------------------------------------------------
-- A nevezés mostantól belépéshez kötött, és mindenki a saját nevében nevez,
-- ezért a fiók-verseny pár egyedisége érvényesíthető adatbázis szinten is.
-- A vendégnevezés (created_by_user_id IS NULL) a szervezői felvitel útja
-- marad; a MySQL a NULL értékeket egyedinek tekinti, ezért abból több is
-- lehet ugyanazon a versenyen.
ALTER TABLE registrations
    ADD UNIQUE KEY uk_competition_user (competition_id, created_by_user_id);

-- ---------------------------------------------------------------------------
-- 8. Új beállítások
-- ---------------------------------------------------------------------------
-- A közvetítés menüpont üres URL-lel nem jelenik meg, ezért a kezdőérték
-- üres string. A ranglista kapcsolható, alapértelmezetten bekapcsolva:
-- a szervező kérte a felületet, tehát legyen látható.
INSERT INTO site_settings (setting_key, setting_value) VALUES
('broadcast_url', ''),
('broadcast_label', 'Közvetítés'),
('ranking_enabled', '1');
