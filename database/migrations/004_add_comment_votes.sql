-- ============================================================================
-- Magyar Biliárd Weboldal - Hozzászólások értékelése
-- Migráció: 004_add_comment_votes.sql
--
-- Leírás:
--   Fel- és leértékelés a fórum hozzászólásaira, vendégként és belépve is.
--
-- Azonosítás (voter_key):
--   Bejelentkezve  -> "user:{felhasználó azonosítója}"
--   Vendégként     -> "guest:{sessionben tárolt véletlen token}"
--   A UNIQUE (comment_id, voter_key) biztosítja, hogy egy szavazó egy
--   hozzászólásra csak egy szavazatot adhasson.
--
-- A comments.upvotes / downvotes csak gyorsított összesítés: minden
-- szavazás után a comment_votes táblából számoljuk újra, így nem csúszhat el.
-- ============================================================================

-- Gyorsított szavazatszámlálók a hozzászólásokon
ALTER TABLE comments
    ADD COLUMN upvotes INT UNSIGNED NOT NULL DEFAULT 0 AFTER body,
    ADD COLUMN downvotes INT UNSIGNED NOT NULL DEFAULT 0 AFTER upvotes;

-- Egyedi szavazatok
CREATE TABLE comment_votes (
    id CHAR(36) PRIMARY KEY,
    comment_id CHAR(36) NOT NULL,

    -- A szavazó azonosítója: "user:{id}" vagy "guest:{token}"
    voter_key VARCHAR(80) NOT NULL,

    -- Bejelentkezett szavazó fiókja, vendég esetén NULL
    user_id CHAR(36) NULL DEFAULT NULL,

    -- +1 = felértékelés, -1 = leértékelés
    value TINYINT NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_comment_voter (comment_id, voter_key),
    INDEX idx_comment_id (comment_id),

    CONSTRAINT fk_votes_comment
        FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_vote_value CHECK (value IN (-1, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
