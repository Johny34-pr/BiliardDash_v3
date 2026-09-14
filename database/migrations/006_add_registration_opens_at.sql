-- ============================================================================
-- Magyar Biliárd Weboldal - Nevezés nyitódátuma
-- Migráció: 006_add_registration_opens_at.sql
--
-- Leírás:
--   A versenyeknek eddig csak nevezési határidejük volt (meddig lehet
--   nevezni). Ez a migráció bevezeti a nyitódátumot is: mikortól lehet
--   nevezni. Így a szervező előre kiírhatja a versenyt anélkül, hogy a
--   nevezés azonnal megnyílna.
--
--   registration_opens_at:
--     NULL  = a nevezés azonnal nyitott (a határidőig)
--     érték = eddig az időpontig a verseny látszik, de a nevezés zárt
--
-- A meglévő versenyek NULL értéket kapnak, így a viselkedésük változatlan:
-- a nevezésük a határidő lejártáig nyitott marad.
-- ============================================================================

ALTER TABLE competitions
    ADD COLUMN registration_opens_at DATETIME NULL DEFAULT NULL AFTER date;

-- A nyitott versenyek listája a nyitódátum és a határidő szerint is szűr,
-- ezért a két oszlopra közös index kerül.
ALTER TABLE competitions
    ADD INDEX idx_registration_window (registration_opens_at, registration_deadline);
