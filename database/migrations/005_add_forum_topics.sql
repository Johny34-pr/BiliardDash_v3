-- ============================================================================
-- Magyar Biliárd Weboldal - Fórum topikok
-- Migráció: 005_add_forum_topics.sql
--
-- Leírás:
--   A fórum eddig egyetlen összefüggő hozzászólás-folyam volt. Ez a migráció
--   bevezeti a topikokat (témákat): egy topik címből és nyitó bejegyzésből áll,
--   a hozzászólások pedig egy topikhoz tartoznak.
--
--   Topikot vendégként és belépve is lehet nyitni, a moderálás pedig
--   elrejtéssel (visszavonható), lezárással és végleges törléssel történik.
--
-- Adatmentés: a meglévő, topik nélküli hozzászólások egy automatikusan
-- létrehozott "Általános beszélgetés" topikba kerülnek, így nem tűnnek el.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Topikok
-- ---------------------------------------------------------------------------
CREATE TABLE topics (
    id CHAR(36) PRIMARY KEY,

    -- A topikot nyitó fiókja, vagy NULL vendég esetén.
    -- ON DELETE SET NULL: fiók törlésekor a topik megmarad, a szerző neve
    -- az author_name mezőben pillanatképként rögzített.
    user_id CHAR(36) NULL DEFAULT NULL,

    author_name VARCHAR(60) NOT NULL,
    title VARCHAR(120) NOT NULL,

    -- Nyitó bejegyzés: egyszerű szöveg, a hozzászólásokkal egyező szabályok
    -- szerint tisztítva (utf8mb4 az emojik miatt).
    body VARCHAR(2000) NOT NULL,

    -- Gyorsított összesítés, a hozzászólásokból újraszámolva
    comment_count INT UNSIGNED NOT NULL DEFAULT 0,

    -- Moderálás: lezárva = nem fogad új hozzászólást
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    -- Moderálás: elrejtve = nem látszik a publikus listán (visszavonható)
    is_hidden TINYINT(1) NOT NULL DEFAULT 0,

    ip_hash CHAR(64) NULL DEFAULT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    -- A legutóbbi hozzászólás ideje; a topiklista rendezési alapja
    last_activity_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_visible_activity (is_hidden, last_activity_at DESC),
    INDEX idx_user_id (user_id),

    CONSTRAINT fk_topics_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. A hozzászólások topikhoz kötése
-- ---------------------------------------------------------------------------
-- Először NULL-t engedő oszlopként, hogy a meglévő sorok is átmenthetők
-- legyenek, majd a feltöltés után kötelezővé tesszük.
ALTER TABLE comments
    ADD COLUMN topic_id CHAR(36) NULL DEFAULT NULL AFTER id;

-- ---------------------------------------------------------------------------
-- 3. Meglévő hozzászólások átmentése
-- ---------------------------------------------------------------------------
-- Alap topik létrehozása, de csak ha van mit átmenteni.
INSERT INTO topics (id, user_id, author_name, title, body, created_at, last_activity_at)
SELECT
    '00000000-0000-4000-8000-000000000001',
    NULL,
    'Magyar Biliárd',
    'Általános beszélgetés',
    'Ebben a topikban a fórum korábbi, téma nélküli hozzászólásai találhatók.',
    COALESCE(MIN(created_at), NOW()),
    COALESCE(MAX(created_at), NOW())
FROM comments
WHERE topic_id IS NULL
HAVING COUNT(*) > 0;

-- A topik nélküli hozzászólások hozzárendelése
UPDATE comments
SET topic_id = '00000000-0000-4000-8000-000000000001'
WHERE topic_id IS NULL;

-- ---------------------------------------------------------------------------
-- 4. A kapcsolat kötelezővé tétele
-- ---------------------------------------------------------------------------
ALTER TABLE comments
    MODIFY COLUMN topic_id CHAR(36) NOT NULL,
    ADD INDEX idx_topic_created (topic_id, created_at),
    ADD CONSTRAINT fk_comments_topic
        FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE;

-- ---------------------------------------------------------------------------
-- 5. A hozzászólásszámlálók feltöltése
-- ---------------------------------------------------------------------------
UPDATE topics t
SET comment_count = (SELECT COUNT(*) FROM comments c WHERE c.topic_id = t.id AND c.is_hidden = 0);
