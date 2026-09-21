-- ============================================================================
-- Okányi Biliárd Klub weboldal - Település és belépés megjegyzése
-- Migráció: 009_add_user_city_and_remember_tokens.sql
--
-- Leírás:
--   1. users.city: a regisztrációnál kötelezővé tett település. A meglévő
--      fiókok üres értéket kapnak, ezért az oszlop NOT NULL DEFAULT '' -
--      így a régi sorok érvényesek maradnak, a kötelezőséget pedig a
--      regisztrációs validáció érvényesíti. A hiányzó településeket a
--      szervező utólag kitöltheti a felhasználókezelő felületen.
--
--   2. remember_tokens: a "belépési adatok megjegyzése" tokenjei.
--
--      Osztott token (selector + validator) séma: a sütiben két rész van,
--      a keresésre szolgáló selector és a titkos validator. Az adatbázis
--      csak a validator SHA-256 lenyomatát tárolja, így egy adatbázis-
--      kiszivárgás nem elég a bejelentkezéshez. A selector külön oszlop,
--      mert így a keresés indexelt, és nem kell minden sort végigpróbálni
--      (ami időzítéses támadásra adna felületet).
--
--      Eszközönként külön sor keletkezik, ezért a felhasználó több gépen is
--      bejelentkezve maradhat, és kilépéskor csak az adott eszköz tokenje
--      törlődik.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Település a felhasználói fiókokhoz
-- ---------------------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN city VARCHAR(100) NOT NULL DEFAULT '' AFTER phone;

-- ---------------------------------------------------------------------------
-- 2. Bejelentkezés megjegyzésének tokenjei
-- ---------------------------------------------------------------------------
CREATE TABLE remember_tokens (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,

    -- Nyilvános azonosító a süti első feléből: ez alapján keresünk
    selector CHAR(32) NOT NULL,

    -- A süti titkos felének SHA-256 lenyomata (64 hex karakter)
    token_hash CHAR(64) NOT NULL,

    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL DEFAULT NULL,

    UNIQUE KEY uk_remember_selector (selector),
    INDEX idx_remember_user (user_id),
    INDEX idx_remember_expires (expires_at),

    CONSTRAINT fk_remember_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
