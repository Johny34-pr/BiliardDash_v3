-- ============================================================================
-- Okányi Biliárd Klub weboldal - Verseny jegyzőkönyv
-- Migráció: 013_add_competition_protocol.sql
--
-- Leírás:
--   A lezárult versenyekhez a szervező feltöltheti a jegyzőkönyvet (PDF vagy
--   dokumentum). A "Versenyeink > Korábbi versenyek" oldal innen ajánlja fel
--   letöltésre.
--
--   Miért a competitions táblán és nem külön dokumentumtáblában: versenyenként
--   EGY jegyzőkönyv van, amit a szervező felülírhat. Egy külön tábla a több
--   dokumentum lehetőségét vinné be, amire most nincs igény, viszont minden
--   lekérdezést joinnal terhelne.
--
--   Négy oszlop, mert a letöltéshez és a megjelenítéshez mind kell:
--     protocol_path         a tárolt fájl útvonala a public/ alatt
--     protocol_name         az ERE DETI fájlnév, ezt látja és ezt kapja a
--                           látogató letöltéskor (a tárolt név UUID)
--     protocol_size         a méret, hogy a letöltés előtt látszódjon
--     protocol_uploaded_at  mikor került fel
--
--   Idempotens: minden oszlop hozzáadása előtt megnézzük, létezik-e már.
--   A MariaDB nem támogatja az "ADD COLUMN IF NOT EXISTS"-t minden
--   verzióban, ezért az information_schema alapú feltételes DDL-t
--   használjuk, ahogy a 010-es migráció is.
-- ============================================================================

SET @schema := DATABASE();

-- --- protocol_path ---------------------------------------------------------
SET @exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'competitions'
      AND COLUMN_NAME = 'protocol_path'
);
SET @sql := IF(
    @exists = 0,
    'ALTER TABLE competitions ADD COLUMN protocol_path VARCHAR(500) NULL AFTER registrant_count',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --- protocol_name ---------------------------------------------------------
SET @exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'competitions'
      AND COLUMN_NAME = 'protocol_name'
);
SET @sql := IF(
    @exists = 0,
    'ALTER TABLE competitions ADD COLUMN protocol_name VARCHAR(255) NULL AFTER protocol_path',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --- protocol_size ---------------------------------------------------------
SET @exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'competitions'
      AND COLUMN_NAME = 'protocol_size'
);
SET @sql := IF(
    @exists = 0,
    'ALTER TABLE competitions ADD COLUMN protocol_size INT UNSIGNED NULL AFTER protocol_name',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --- protocol_uploaded_at --------------------------------------------------
SET @exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'competitions'
      AND COLUMN_NAME = 'protocol_uploaded_at'
);
SET @sql := IF(
    @exists = 0,
    'ALTER TABLE competitions ADD COLUMN protocol_uploaded_at DATETIME NULL AFTER protocol_size',
    'DO 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --- Index: NEM kell -------------------------------------------------------
-- A "Korábbi versenyek" oldal a date oszlop szerint szűr és rendez, de erre
-- a 001-es migráció már létrehozta az idx_date (date) indexet. Egy második,
-- ugyanarra az oszlopra mutató index nem gyorsítana semmit, viszont minden
-- beszúrást és módosítást terhelne, és a tárhelyet is fogyasztaná.
--
-- (A migráció első változata tévesen felvett egy idx_competitions_date
-- indexet; ez a blokk azért maradt itt megjegyzésként, hogy a döntés indoka
-- ne tűnjön el.)
