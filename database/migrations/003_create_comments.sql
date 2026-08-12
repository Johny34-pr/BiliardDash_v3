-- ============================================================================
-- Magyar Biliárd Weboldal - Fórum
-- Migráció: 003_create_comments.sql
--
-- Leírás:
--   comments tábla a közösségi fórumhoz. Kommentelni vendégként és
--   bejelentkezve is lehet, a tartalom kizárólag egyszerű szöveg néhány
--   engedélyezett emojival.
--
-- Moderálás: az is_hidden mező elrejti a hozzászólást a publikus listáról
-- anélkül, hogy törölné, így a döntés visszavonható. A végleges törlés is
-- elérhető az admin felületen.
-- ============================================================================

CREATE TABLE comments (
    id CHAR(36) PRIMARY KEY,

    -- A hozzászóló fiókja, vagy NULL vendég hozzászólás esetén.
    -- ON DELETE SET NULL: fiók törlésekor a hozzászólás megmarad, a szerző
    -- neve a author_name mezőben pillanatképként rögzített.
    user_id CHAR(36) NULL DEFAULT NULL,

    -- A megjelenített szerzőnév a beküldés pillanatában.
    -- Bejelentkezve a fiók nevéből, vendégként a megadott névből.
    author_name VARCHAR(60) NOT NULL,

    -- Egyszerű szöveges tartalom (HTML nélkül), utf8mb4 az emojik miatt.
    body VARCHAR(1000) NOT NULL,

    -- Moderálás: 1 = elrejtve a publikus listáról
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,

    -- Visszaélések kezeléséhez: az IP címet csak hash formában tároljuk,
    -- így nem kezelünk azonosításra közvetlenül alkalmas adatot.
    ip_hash CHAR(64) NULL DEFAULT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_created_at (created_at DESC),
    INDEX idx_visible (is_hidden, created_at DESC),
    INDEX idx_user_id (user_id),

    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
