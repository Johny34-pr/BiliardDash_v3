-- ============================================================================
-- Okányi Biliárd Klub weboldal - Felhasználói fiókok
-- Migráció: 002_add_users_and_registration_owner.sql
--
-- Leírás:
--   1. users tábla: publikus felhasználói fiókok (az admin belépéstől független)
--   2. registrations.created_by_user_id: ki vitte fel a nevezést
--      NULL  = vendégnevezés (belépés nélkül)
--      érték = a nevezést rögzítő felhasználó fiókja
--
-- A nevező adatai (full_name, email, phone) továbbra is a nevezésen vannak,
-- így egy fiók magának és másnak is rögzíthet nevezést.
-- ============================================================================

-- Felhasználói fiókok
CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A nevezés rögzítőjének kapcsolása
-- ON DELETE SET NULL: fiók törlésekor a nevezés megmarad, vendégnevezéssé válik,
-- így a szervező névsora nem csorbul.
ALTER TABLE registrations
    ADD COLUMN created_by_user_id CHAR(36) NULL DEFAULT NULL AFTER competition_id,
    ADD INDEX idx_created_by_user_id (created_by_user_id),
    ADD CONSTRAINT fk_registrations_user
        FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL;
