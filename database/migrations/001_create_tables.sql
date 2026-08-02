-- ============================================================================
-- Magyar Biliárd Weboldal - Adatbázis séma
-- Migráció: 001_create_tables.sql
-- Leírás: Alap táblák létrehozása (news, albums, images, competitions, registrations)
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================================

-- Hírek tábla
CREATE TABLE news (
    id CHAR(36) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    summary VARCHAR(200),
    published_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_published_at (published_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Albumok tábla
CREATE TABLE albums (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    cover_image_id CHAR(36) NULL,
    image_count INT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Képek tábla
CREATE TABLE images (
    id CHAR(36) PRIMARY KEY,
    album_id CHAR(36) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(500) NOT NULL,
    full_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (album_id) REFERENCES albums(id) ON DELETE CASCADE,
    INDEX idx_album_id (album_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Versenyek tábla
CREATE TABLE competitions (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    venue VARCHAR(200) NOT NULL,
    registration_deadline DATETIME NOT NULL,
    registrant_count INT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_deadline (registration_deadline),
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nevezések tábla
CREATE TABLE registrations (
    id CHAR(36) PRIMARY KEY,
    competition_id CHAR(36) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    UNIQUE KEY uk_competition_email (competition_id, email),
    INDEX idx_competition_id (competition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
