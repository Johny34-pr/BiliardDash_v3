# Implementation Plan: Magyar Biliárd Weboldal (PHP + MySQL)

## Overview

Az implementáció egy egyszerű MVC architektúrájú PHP 8.1+ alkalmazást épít fel keretrendszer nélkül, MySQL 8.0+ adatbázissal, Tailwind CSS (CDN) frontenddel, és Apache mod_rewrite alapú URL routinggal. A fejlesztés inkrementális lépésekben halad: projekt struktúra → core réteg → adatbázis → szolgáltatások → kontrollerek → nézetek → admin felület → tesztek.

## Tasks

- [x] 1. Projekt struktúra és core infrastruktúra
  - [x] 1.1 Projekt inicializálás és Composer konfiguráció
    - Hozd létre a `composer.json` fájlt a design dokumentumban meghatározott függőségekkel (phpmailer/phpmailer, ramsey/uuid, phpunit, eris, mockery)
    - Hozd létre a könyvtárstruktúrát: `public/`, `src/Controllers/`, `src/Models/`, `src/Services/`, `src/Views/`, `src/Core/`, `config/`, `database/migrations/`, `tests/Unit/`, `tests/Properties/`, `tests/Integration/`
    - Hozd létre a `public/.htaccess` fájlt az Apache mod_rewrite szabályokkal
    - Hozd létre a `public/uploads/albums/` könyvtárat `.gitkeep` fájlokkal
    - _Requirements: 8.4_

  - [x] 1.2 Core osztályok implementálása (Database, Router, Session, Validator, helpers)
    - Implementáld a `src/Core/Database.php` singleton PDO connection osztályt (utf8mb4, ERRMODE_EXCEPTION, FETCH_ASSOC)
    - Implementáld a `src/Core/Router.php` osztályt GET/POST route kezeléssel és paraméteres URL mintákkal (pl. `/hirek/{id}`)
    - Implementáld a `src/Core/Session.php` osztályt (start, isAdmin, login, logout, flash, getFlash)
    - Implementáld a `src/Core/Validator.php` osztályt (required, maxLength, minLength, email, date, fileType, fileSize, isValid, getErrors)
    - Implementáld a `src/Core/helpers.php` segédfüggvényeket (e(), redirect(), asset(), currentUrl(), isActive())
    - Implementáld a `src/Core/AppException.php` egyedi exception osztályt hibakódokkal
    - _Requirements: 1.5, 2.5, 4.5, 4.6, 4.7, 5.5, 6.5_

  - [x] 1.3 Konfiguráció és Front Controller
    - Hozd létre a `config/database.php` fájlt (host, dbname, username, password konfigurációval, .env-ből olvasva ha elérhető)
    - Hozd létre a `config/app.php` fájlt (alkalmazás név, base URL, upload limit beállítások)
    - Hozd létre a `config/mail.php` fájlt (SMTP host, port, username, password, from address)
    - Implementáld a `public/index.php` front controllert: autoload, session start, route definíciók betöltése, dispatch, központi hibakezelés (AppException, PDOException, Throwable)
    - Hozd létre a `config/routes.php` fájlt az összes publikus és admin útvonal definícióval a design dokumentum szerint
    - _Requirements: 8.1_

- [x] 2. Adatbázis és Model réteg
  - [x] 2.1 Adatbázis migráció létrehozása
    - Hozd létre a `database/migrations/001_create_tables.sql` fájlt a design dokumentumban meghatározott sémával (news, albums, images, competitions, registrations táblák)
    - Minden táblánál UUID (CHAR(36)) elsődleges kulcs, utf8mb4_unicode_ci collation, InnoDB engine
    - Indexek: news.published_at DESC, albums.created_at DESC, competitions.registration_deadline, competitions.date, registrations.competition_id
    - UNIQUE KEY: registrations(competition_id, email) a dupla nevezés megakadályozásához
    - Foreign key: images.album_id → albums.id ON DELETE CASCADE, registrations.competition_id → competitions.id ON DELETE CASCADE
    - _Requirements: 5.8, 6.7_

  - [x] 2.2 Model osztályok implementálása
    - Implementáld a `src/Models/News.php` osztályt (findLatest, findById, create, update, delete) PDO prepared statements-szel
    - Implementáld a `src/Models/Album.php` osztályt (findAll, findById, create, incrementImageCount, decrementImageCount)
    - Implementáld a `src/Models/Image.php` osztályt (findByAlbumId, findById, create, delete)
    - Implementáld a `src/Models/Competition.php` osztályt (findOpen, findAll, findById, create, update, delete, incrementRegistrantCount)
    - Implementáld a `src/Models/Registration.php` osztályt (findByCompetitionId, create, findByCompetitionAndEmail, countByCompetition)
    - Minden modell konstruktorban PDO injekció, prepared statements használata SQL injection ellen
    - _Requirements: 1.1, 1.2, 3.1, 5.1, 5.8, 6.2, 6.4_

- [x] 3. Checkpoint - Core réteg ellenőrzés
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Szolgáltatási réteg (Services)
  - [x] 4.1 NewsService implementálása
    - Implementáld a `src/Services/NewsService.php` osztályt: getLatestNews(limit=10), getNewsById, createNews, updateNews, deleteNews
    - A createNews automatikusan generálja a summary-t: HTML strip + első 200 karakter
    - Az updateNews megőrzi az eredeti published_at dátumot
    - UUID generálás a ramsey/uuid könyvtárral
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2, 2.3_

  - [x] 4.2 ValidationService implementálása
    - Implementáld a `src/Services/ValidationService.php` osztályt: validateNews, validateAlbum, validateRegistration, validateCompetition, validateImageUpload
    - News validáció: cím kötelező (max 200 karakter), tartalom kötelező
    - Album validáció: név kötelező (1-100 karakter, trim, nem csak whitespace)
    - Registration validáció: fullName kötelező (max 100), email kötelező + formátum, phone kötelező
    - Competition validáció: name (max 100), date, venue (max 200), registrationDeadline - mind kötelező, dátum formátum ellenőrzés
    - Image validáció: JPEG/PNG típus, max 10 MB méret
    - _Requirements: 2.5, 4.5, 4.6, 4.7, 5.5, 6.5_

  - [x] 4.3 Property tesztek: Hír validáció és round-trip (Properties 1-4)
    - **Property 1: Hírek listázása helyes sorrendben és limittel**
    - **Property 2: Hír létrehozás round-trip**
    - **Property 3: Hír szerkesztés megőrzi a publikálási dátumot**
    - **Property 4: Hír validáció elutasítja az üres mezőket**
    - **Validates: Requirements 1.1, 1.2, 2.1, 2.2, 2.5**

  - [x] 4.4 ImageService implementálása
    - Implementáld a `src/Services/ImageService.php` osztályt: createThumbnail, isValidImageType, isValidFileSize, deleteImageFiles
    - A createThumbnail PHP GD library-vel arányosan átméretez és középre vágja 200x200px-re
    - Támogatott formátumok: JPEG (imagecreatefromjpeg/imagejpeg quality 85) és PNG (imagecreatefrompng/imagepng compression 8)
    - _Requirements: 4.2, 4.3, 4.4_

  - [x] 4.5 GalleryService implementálása
    - Implementáld a `src/Services/GalleryService.php` osztályt: getAlbums, getAlbumImages, createAlbum, uploadImage, deleteImage
    - Az uploadImage: fájl áthelyezés a uploads/albums/{albumId}/full/ könyvtárba, bélyegkép generálás a thumb/ könyvtárba, DB rekord mentés, album image_count növelés
    - A deleteImage: fájl törlés (full + thumb), DB rekord törlés, album image_count csökkentés
    - getAlbums: albums listázás image_count-tal és cover_image adatokkal, fordított időrendi sorrendben
    - _Requirements: 3.1, 3.2, 3.6, 4.1, 4.2, 4.3, 4.4_

  - [ ] 4.6 Property tesztek: Galéria (Properties 5, 8-11)
    - **Property 5: Albumok fordított időrendi sorrendje**
    - **Property 8: Képfeltöltés validáció**
    - **Property 9: Bélyegkép generálás mérete**
    - **Property 10: Album létrehozás round-trip**
    - **Property 11: Album név validáció elutasítja az üres neveket**
    - **Validates: Requirements 3.1, 4.1, 4.2, 4.3, 4.5, 4.6, 4.7**

  - [x] 4.7 CompetitionService implementálása
    - Implementáld a `src/Services/CompetitionService.php` osztályt: getOpenCompetitions, getCompetitionById, createCompetition, updateCompetition, deleteCompetition, registerForCompetition, getRegistrations, exportRegistrationsCsv, checkDuplicateRegistration
    - getOpenCompetitions: csak jövőbeli határidejű versenyek, dátum szerinti növekvő sorrend, registrant_count-tal
    - registerForCompetition: határidő ellenőrzés, duplikáció ellenőrzés, mentés, registrant_count növelés, email küldés triggerelése
    - exportRegistrationsCsv: CSV string generálás (név, email, telefon oszlopok, UTF-8 BOM)
    - _Requirements: 5.1, 5.3, 5.6, 5.8, 6.1, 6.2, 6.3, 6.4, 6.6, 6.7_

  - [x] 4.8 EmailService implementálása
    - Implementáld a `src/Services/EmailService.php` osztályt PHPMailer-rel: sendRegistrationConfirmation
    - SMTP konfiguráció a config/mail.php-ből
    - HTML email sablon: verseny neve, dátuma, helyszíne, nevező adatai
    - Retry logika: max 3 kísérlet, error_log hibánál
    - _Requirements: 5.7_

  - [ ] 4.9 Property tesztek: Versenyek és nevezés (Properties 12-22)
    - **Property 12: Nyitott versenyek szűrése és rendezése**
    - **Property 13: Nevezés round-trip**
    - **Property 14: Nevezési validáció elutasítja az érvénytelen adatokat**
    - **Property 15: Lejárt határidejű versenyre nem lehet nevezni**
    - **Property 16: Dupla nevezés megakadályozása**
    - **Property 17: Verseny létrehozás round-trip**
    - **Property 18: CSV export round-trip**
    - **Property 19: Nevezők számának konzisztenciája**
    - **Property 20: Verseny validáció elutasítja a hiányos adatokat**
    - **Property 21: Verseny szerkesztés megőrzi a nevezéseket**
    - **Property 22: Verseny törlés kaszkád**
    - **Validates: Requirements 5.1, 5.3, 5.5, 5.6, 5.8, 6.1, 6.3, 6.4, 6.5, 6.6, 6.7**

- [x] 5. Checkpoint - Szolgáltatási réteg ellenőrzés
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Publikus kontrollerek és nézetek
  - [x] 6.1 Layout és navigáció nézetek
    - Hozd létre a `src/Views/layouts/main.php` fő layout-ot: HTML5, Tailwind CSS CDN, egyéni tailwind konfig (billiard-green, billiard-gold színek), meta viewport, header/nav/footer include
    - Hozd létre a `src/Views/partials/header.php` fejlécet
    - Hozd létre a `src/Views/partials/navigation.php` navigációt: hamburger menü (mobil), aktív menüpont jelzés (isActive helper), min 44x44px érintési célterületek
    - Hozd létre a `src/Views/partials/footer.php` láblécet
    - Hozd létre a `src/Views/errors/404.php` és `src/Views/errors/500.php` hibaoldalakat
    - Egységes sötétzöld + arany színvilág minden elemen
    - _Requirements: 7.1, 7.2, 7.4, 7.5, 8.1, 8.2, 8.3_

  - [x] 6.2 HomeController és Hírek nézetek
    - Implementáld a `src/Controllers/HomeController.php` osztályt: index() → 10 legfrissebb hír betöltése
    - Hozd létre a `src/Views/home/index.php` nézetet: hírlista kártyák (cím, dátum, 200 kar. összefoglaló), üres állapot üzenet ha nincs hír, hiba állapot kezelés
    - Implementáld a `src/Controllers/NewsController.php` show() metódust
    - Hozd létre a `src/Views/news/show.php` nézetet: teljes hír (cím, dátum, tartalom), 404 ha nem található
    - Reszponzív elrendezés: egyoszlopos mobil, szélesebb asztali
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [x] 6.3 GalleryController és Galéria nézetek
    - Implementáld a `src/Controllers/GalleryController.php` osztályt: index() → albumok listázása, show(albumId) → album képei
    - Hozd létre a `src/Views/gallery/index.php` nézetet: album kártyák rácsos elrendezésben (borítókép, név, képek száma)
    - Hozd létre a `src/Views/gallery/show.php` nézetet: bélyegkép rács, lightbox trigger
    - Hozd létre a `public/assets/js/gallery.js` fájlt: Lightbox osztály (open, close, next, prev, keyboard kezelés Escape/Arrow, swipe gesztus mobil)
    - Kép betöltési hiba: placeholder kép `onerror` eseménnyel
    - Lightbox: első képnél hátra nyíl letiltva, utolsó képnél előre nyíl letiltva
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7, 7.3_

  - [ ] 6.4 Property teszt: Lightbox navigáció (Property 6)
    - **Property 6: Lightbox navigáció határai**
    - **Validates: Requirements 3.5**

  - [x] 6.5 CompetitionController és Nevezés nézetek
    - Implementáld a `src/Controllers/CompetitionController.php` osztályt: index(), showForm(competitionId), submitRegistration(competitionId)
    - Hozd létre a `src/Views/competitions/index.php` nézetet: nyitott versenyek listája (név, dátum, határidő), dátum szerinti növekvő sorrend
    - Hozd létre a `src/Views/competitions/register.php` nézetet: nevezési űrlap (teljes név, email, telefonszám), validációs hibaüzenetek mező mellett, lejárt határidő jelzés, dupla nevezés jelzés, sikeres nevezés visszaigazolás
    - Űrlap elemek: min 44x44px érintési célterület, egyoszlopos elrendezés mobilon
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.8, 7.4_

- [x] 7. Checkpoint - Publikus felület ellenőrzés
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Admin felület
  - [x] 8.1 Admin autentikáció és layout
    - Implementáld a `src/Controllers/AdminController.php` loginForm(), login(), logout() metódusokat
    - Hozd létre a `src/Views/layouts/admin.php` admin layout-ot (egyszerűsített navigáció, dashboard linkek)
    - Hozd létre a `src/Views/admin/login.php` bejelentkező űrlapot
    - Hozd létre a `src/Views/admin/dashboard.php` áttekintő oldalt
    - Session-alapú autentikáció: jelszó ellenőrzés (config/app.php-ből), admin middleware logika a route-okhoz
    - _Requirements: 2.1, 2.2, 2.3_

  - [x] 8.2 Admin Hírkezelés
    - Implementáld az AdminController newsList(), newsCreate(), newsStore(), newsEdit(), newsUpdate(), newsDelete() metódusokat
    - Hozd létre a `src/Views/admin/news/index.php` nézetet (hírek listája szerkesztés/törlés gombokkal)
    - Hozd létre a `src/Views/admin/news/create.php` és `edit.php` nézeteket TinyMCE (CDN) rich text szerkesztővel
    - Törlés megerősítő dialógus (JavaScript confirm)
    - Validációs hibaüzenetek megjelenítése (cím kötelező max 200, tartalom kötelező)
    - Rich text formázás: félkövér, dőlt, felsorolás, hivatkozás
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 8.3 Admin Galéria kezelés
    - Implementáld az AdminController albumList(), albumStore(), imageUpload(), imageDelete() metódusokat
    - Hozd létre a `src/Views/admin/gallery/index.php` nézetet (albumok listája, új album form, feltöltés gombok)
    - Hozd létre a `src/Views/admin/gallery/upload.php` nézetet (képfeltöltő form)
    - Album létrehozás: név validáció (1-100 karakter, nem üres/whitespace)
    - Képfeltöltés: JPEG/PNG formátum validáció, max 10 MB méret ellenőrzés, hibaüzenetek
    - Kép törlés: full + thumbnail fájlok eltávolítása
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 8.4 Admin Versenykezelés
    - Implementáld az AdminController competitionList(), competitionCreate(), competitionStore(), competitionEdit(), competitionUpdate(), competitionDelete(), registrationList(), exportCsv() metódusokat
    - Hozd létre a `src/Views/admin/competitions/index.php` nézetet (versenyek listája nevezők számával)
    - Hozd létre a `src/Views/admin/competitions/create.php` és `edit.php` nézeteket (név, dátum, helyszín, határidő form)
    - Hozd létre a `src/Views/admin/competitions/registrations.php` nézetet (nevezők listája: név, email, telefon + CSV export gomb)
    - Verseny törlés: megerősítés, kaszkád törlés (versennyel együtt a nevezések is)
    - CSV export: UTF-8 BOM, név/email/telefon oszlopok, letöltésként (Content-Disposition)
    - Validáció: minden kötelező mező (név, dátum, helyszín, határidő), hossz limitek
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

- [x] 9. Checkpoint - Admin felület ellenőrzés
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Frontend kiegészítések és reszponzivitás
  - [x] 10.1 Általános JavaScript és stílusok
    - Hozd létre a `public/assets/js/app.js` fájlt: hamburger menü toggle, kliens-oldali form validáció (kiegészítő), törlés confirm dialógusok
    - Hozd létre a `public/assets/css/app.css` fájlt: egyéni stílusok (Tailwind kiegészítés), lightbox overlay, animációk, placeholder kép stílusok
    - _Requirements: 7.2, 8.2_

  - [x] 10.2 Reszponzív elrendezés finomhangolás
    - Ellenőrizd és finomhangold a három töréspontot: mobil (<768px), tablet (768-1024px), asztali (>1024px)
    - Mobil: egyoszlopos elrendezés, hamburger menü, 44x44px érintési célterületek
    - Tablet: kétoszlopos rács galériánál
    - Asztali: teljes szélesség kihasználás, többoszlopos rács
    - Lightbox: swipe gesztus mobil nézetben
    - Nincsen vízszintes görgetés és átfedés semmilyen nézetben
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [ ] 10.3 Property teszt: Navigáció (Property 23)
    - **Property 23: Aktív navigáció jelzése**
    - **Validates: Requirements 8.3**

- [x] 11. Tesztelési infrastruktúra és unit tesztek
  - [x] 11.1 Tesztelési környezet beállítása
    - Hozd létre a `phpunit.xml` konfigurációt a design dokumentum szerint (Unit, Properties, Integration testsuites, teszt DB env vars)
    - Hozd létre a `tests/bootstrap.php` fájlt: autoload, teszt adatbázis konfig betöltése
    - Hozd létre a `tests/TestCase.php` alap osztályt: setUp/tearDown, truncateTable helper, teszt DB connection, service factory metódusok
    - _Requirements: 1.1, 2.1, 4.1, 5.1, 6.1_

  - [ ] 11.2 Unit tesztek: NewsService és ValidationService
    - Hozd létre a `tests/Unit/NewsServiceTest.php` fájlt: üres hírlista állapot, hír betöltési hiba, summary generálás edge case-ek
    - Hozd létre a `tests/Unit/ValidationServiceTest.php` fájlt: érvényes/érvénytelen inputok mindegyik validátorhoz, boundary értékek (0, 100, 200, 201 karakter)
    - _Requirements: 1.4, 1.5, 2.5, 5.5, 6.5_

  - [ ] 11.3 Unit tesztek: GalleryService és ImageService
    - Hozd létre a `tests/Unit/GalleryServiceTest.php` fájlt: album CRUD, kép feltöltés/törlés logika
    - Hozd létre a `tests/Unit/ImageServiceTest.php` fájlt: bélyegkép generálás különböző méretű képekkel, érvénytelen fájl elutasítás, fájl törlés
    - _Requirements: 3.2, 4.2, 4.3, 4.4, 4.5, 4.6_

  - [ ] 11.4 Unit tesztek: CompetitionService
    - Hozd létre a `tests/Unit/CompetitionServiceTest.php` fájlt: verseny CRUD, nevezés logika, duplikáció ellenőrzés, lejárt határidő, CSV export formátum
    - _Requirements: 5.1, 5.3, 5.6, 5.8, 6.1, 6.2, 6.3, 6.4, 6.6, 6.7_

  - [ ] 11.5 Integrációs teszt: EmailService
    - Hozd létre a `tests/Integration/EmailServiceTest.php` fájlt: email küldés mock SMTP-vel, tartalom ellenőrzés (verseny név, dátum, nevező adatok), retry logika
    - _Requirements: 5.7_

- [x] 12. Final checkpoint - Teljes rendszer ellenőrzés
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties (23 property a design dokumentumban)
- Unit tests validate specific examples and edge cases
- Az implementáció PHP 8.1+ szintaxist használ (match, named arguments, union types)
- A Tailwind CSS CDN-ről töltődik, nincs build lépés szükséges
- A TinyMCE CDN-ről töltődik a rich text szerkesztéshez
- Az adatbázis migrációt manuálisan kell futtatni (MySQL CLI vagy phpMyAdmin)
- A teszteléshez külön `billiard_test` adatbázis szükséges

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["1.2", "1.3"] },
    { "id": 2, "tasks": ["2.1"] },
    { "id": 3, "tasks": ["2.2"] },
    { "id": 4, "tasks": ["4.1", "4.2", "4.4"] },
    { "id": 5, "tasks": ["4.3", "4.5", "4.7", "4.8"] },
    { "id": 6, "tasks": ["4.6", "4.9"] },
    { "id": 7, "tasks": ["6.1"] },
    { "id": 8, "tasks": ["6.2", "6.3", "6.5"] },
    { "id": 9, "tasks": ["6.4", "8.1"] },
    { "id": 10, "tasks": ["8.2", "8.3", "8.4"] },
    { "id": 11, "tasks": ["10.1", "10.2"] },
    { "id": 12, "tasks": ["10.3", "11.1"] },
    { "id": 13, "tasks": ["11.2", "11.3", "11.4", "11.5"] }
  ]
}
```
