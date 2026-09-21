-- ============================================================================
-- Okányi Biliárd Klub weboldal - Album helyezettek
-- Migráció: 008_add_album_placements.sql
--
-- Leírás:
--   A galéria átalakul: egy album a verseny egy eredményhirdetését jelenti.
--   Az album a borítóképét mutatja, mellette pedig a verseny helyezettjei
--   olvashatók. A helyezetteket a szervező viszi fel és szerkeszti.
--
--   position: a helyezés száma (1 = első helyezett). Szándékosan nem egyedi
--   albumon belül, mert kieséses rendszerben két játékos is oszthat egy
--   helyet (jellemzően a harmadikat), és ilyenkor mindkettőt fel kell tudni
--   vinni ugyanazzal a számmal.
--
--   A rendezés position szerint történik, holtversenyben pedig player_name
--   szerint. A created_at nem alkalmas másodlagos rendezésre, mert csak
--   másodpontosságú: az egyazon másodpercben felvitt holtversenyzők sorrendje
--   futásonként változna.
-- ============================================================================

CREATE TABLE album_placements (
    id CHAR(36) PRIMARY KEY,
    album_id CHAR(36) NOT NULL,

    -- A helyezés száma: 1, 2, 3, ...
    position INT UNSIGNED NOT NULL,

    player_name VARCHAR(100) NOT NULL,

    -- Opcionális kiegészítés, pl. egyesület vagy település
    note VARCHAR(150) NULL DEFAULT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_album_position (album_id, position, created_at),

    CONSTRAINT fk_placements_album
        FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
