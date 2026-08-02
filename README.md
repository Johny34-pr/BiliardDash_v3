# Magyar Biliárd Weboldal

Közösségi weboldal a magyar biliárd közösség számára: hírek, fotógaléria és online versenynevezés. Keretrendszer nélküli PHP MVC alkalmazás, amely bármely standard LAMP/WAMP környezetben futtatható.

## Funkciók

**Publikus felület**

- Hírek listázása a főoldalon (10 legfrissebb, fordított időrendben, 200 karakteres összefoglalóval)
- Hír részletes nézet
- Fotógaléria albumokba rendezve, borítóképpel és képszámmal
- Lightbox képnézegető: nyíl- és billentyűzet-navigáció, mobilon swipe gesztus
- Nyitott versenyek listája és online nevezési űrlap
- Visszaigazoló e-mail sikeres nevezés után
- Reszponzív elrendezés három töréspontra (mobil / tablet / asztali)

**Admin felület**

- Session-alapú bejelentkezés
- Hírek létrehozása, szerkesztése, törlése TinyMCE rich text szerkesztővel
- Albumok létrehozása, képfeltöltés automatikus 200x200px bélyegkép-generálással
- Versenyek kezelése és a nevezői lista megtekintése
- Nevezői lista exportálása CSV formátumban (UTF-8 BOM)

## Technológiai stack

| Terület | Választás |
| --- | --- |
| Backend | PHP 8.1+ (egyszerű MVC, keretrendszer nélkül) |
| Adatbázis | MySQL 8.0+ |
| DB hozzáférés | PDO prepared statements |
| Stílus | Tailwind CSS (CDN) |
| Képkezelés | PHP GD Library |
| E-mail | PHPMailer (SMTP) |
| Rich text | TinyMCE (CDN) |
| Frontend JS | Vanilla JavaScript |
| Tesztelés | PHPUnit 10 + Eris (property-based testing) + Mockery |
| Webszerver | Apache + mod_rewrite |

Nincs build lépés: a Tailwind és a TinyMCE CDN-ről töltődik.

## Követelmények

- PHP 8.1 vagy újabb, engedélyezett `pdo_mysql`, `gd` és `mbstring` kiterjesztésekkel
- MySQL 8.0 vagy újabb
- Apache engedélyezett `mod_rewrite` modullal és `AllowOverride All` beállítással
- Composer

## Telepítés

### 1. Függőségek telepítése

```bash
composer install
```

### 2. Adatbázis létrehozása és séma betöltése

```bash
mysql -u root -e "CREATE DATABASE billiard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root --default-character-set=utf8mb4 billiard < database/migrations/001_create_tables.sql
```

XAMPP alatt Windows-on a MySQL kliens a `C:\xampp\mysql\bin\mysql.exe` útvonalon található. Alternatívaként a migrációt a phpMyAdmin felületén is be lehet importálni.

### 3. Környezeti változók beállítása

```bash
cp .env.example .env
```

Ezután a `.env` fájlban állítsd be az értékeket:

```dotenv
DB_HOST=localhost
DB_NAME=billiard
DB_USERNAME=root
DB_PASSWORD=

APP_NAME="Magyar Biliárd"
APP_URL=http://localhost
APP_DEBUG=false
ADMIN_PASSWORD=valasz-egy-eros-jelszot

MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@magyarbilliard.hu
MAIL_FROM_NAME="Magyar Biliárd"
```

A `.env` fájlt UTF-8 kódolással mentsd, különben az ékezetes értékek hibásan jelennek meg. A fájl nem kerül verziókövetésbe, és a webszerver sem szolgálja ki.

**Fontos:** az `ADMIN_PASSWORD` alapértelmezett értéke `admin123`. Éles használat előtt cseréld le. A `Session::login()` először `password_verify()`-jal ellenőriz, így a `.env`-be bcrypt hash is írható:

```bash
php -r "echo password_hash('sajat-jelszo', PASSWORD_DEFAULT), PHP_EOL;"
```

### 4. Írási jogosultságok

A feltöltési könyvtárnak írhatónak kell lennie a webszerver felhasználója számára:

```bash
chmod -R 755 public/uploads
```

### 5. Webszerver konfiguráció

Az alkalmazás web rootja a `public/` könyvtár. Két beállítási mód létezik.

**A) Ajánlott: DocumentRoot a public/ könyvtárra**

```apache
<VirtualHost *:80>
    ServerName billiard.local
    DocumentRoot "C:/xampp/htdocs/public"

    <Directory "C:/xampp/htdocs/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**B) XAMPP alapértelmezés: a projekt a htdocs gyökerében**

Ebben az esetben a DocumentRoot a `htdocs`, ezért a gyökér szintű `.htaccess` irányítja a kéréseket a `public/` könyvtárba. Ez a fájl a repóban benne van, nincs további teendő. Ellenőrizd, hogy a `mod_rewrite` engedélyezve van a `httpd.conf`-ban:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

és hogy a `htdocs` könyvtárra `AllowOverride All` van beállítva.

Az alkalmazás ezután a `http://localhost/` címen érhető el.

## URL átírás (.htaccess)

A projekt három `.htaccess` fájlt használ.

**`.htaccess`** (projekt gyökér) — Áthidalja azt, hogy a XAMPP DocumentRootja a `htdocs`, az alkalmazás belépési pontja viszont a `public/index.php`:

1. A már `public/`-ba mutató kéréseket átengedi, elkerülve a végtelen ciklust
2. A `public/` könyvtárban létező statikus fájlokat közvetlenül kiszolgálja
3. Minden más kérést a `public/index.php` front controllerhez irányít

A `REQUEST_URI` változatlan marad, így a Router az eredeti útvonalat kapja meg. Mellékhatásként a `src/`, `config/`, `vendor/`, `tests/`, `database/` könyvtárak és a `.env` nem érhetők el böngészőből.

**`public/.htaccess`** — A klasszikus front controller minta: minden nem létező fájl és könyvtár kérése az `index.php`-hez kerül. Emellett letiltja a könyvtárlistázást és a rejtett fájlok elérését, biztonsági HTTP fejléceket állít be (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`), valamint gyorsítótárazást és gzip tömörítést konfigurál a statikus erőforrásokhoz.

**`public/uploads/.htaccess`** — A feltöltött fájlok soha nem futhatnak szkriptként: kikapcsolja a PHP motort, eltávolítja a szkript handlereket, és megtagadja a szkript kiterjesztésű fájlok elérését.

> A `<Directory>` direktíva `.htaccess` kontextusban nem használható, Apache 500-as hibát ad rá. A feltöltési könyvtár védelme ezért külön `.htaccess` fájlban van.

## Útvonalak

**Publikus**

| Metódus | Útvonal | Kezelő |
| --- | --- | --- |
| GET | `/` | `HomeController@index` |
| GET | `/hirek/{id}` | `NewsController@show` |
| GET | `/galeria` | `GalleryController@index` |
| GET | `/galeria/{albumId}` | `GalleryController@show` |
| GET | `/nevezes` | `CompetitionController@index` |
| GET | `/nevezes/{versenyId}` | `CompetitionController@showForm` |
| POST | `/nevezes/{versenyId}` | `CompetitionController@submitRegistration` |

**Admin** (bejelentkezés szükséges, egyébként átirányít a `/admin/login` oldalra)

| Metódus | Útvonal | Kezelő |
| --- | --- | --- |
| GET | `/admin` | `AdminController@dashboard` |
| GET, POST | `/admin/login` | `AdminController@loginForm`, `@login` |
| GET | `/admin/logout` | `AdminController@logout` |
| GET | `/admin/hirek` | `AdminController@newsList` |
| GET, POST | `/admin/hirek/uj` | `AdminController@newsCreate`, `@newsStore` |
| GET, POST | `/admin/hirek/{id}/szerkeszt` | `AdminController@newsEdit`, `@newsUpdate` |
| POST | `/admin/hirek/{id}/torol` | `AdminController@newsDelete` |
| GET | `/admin/galeria` | `AdminController@albumList` |
| POST | `/admin/galeria/uj` | `AdminController@albumStore` |
| GET, POST | `/admin/galeria/{id}/feltolt` | `AdminController@imageUploadForm`, `@imageUpload` |
| POST | `/admin/galeria/kep/{id}/torol` | `AdminController@imageDelete` |
| GET | `/admin/versenyek` | `AdminController@competitionList` |
| GET, POST | `/admin/versenyek/uj` | `AdminController@competitionCreate`, `@competitionStore` |
| GET, POST | `/admin/versenyek/{id}/szerkeszt` | `AdminController@competitionEdit`, `@competitionUpdate` |
| POST | `/admin/versenyek/{id}/torol` | `AdminController@competitionDelete` |
| GET | `/admin/versenyek/{id}/nevezesek` | `AdminController@registrationList` |
| GET | `/admin/versenyek/{id}/export` | `AdminController@exportCsv` |

Az útvonalak a `config/routes.php` fájlban vannak definiálva.

## Könyvtárstruktúra

```
.
├── .htaccess                   → Kérések irányítása a public/ könyvtárba
├── public/                     → Web root
│   ├── index.php               → Front controller
│   ├── .htaccess               → Front controller rewrite + biztonsági fejlécek
│   ├── assets/{css,js,images}/ → Statikus erőforrások
│   └── uploads/albums/{id}/    → full/ (eredeti) és thumb/ (200x200px)
├── src/
│   ├── Controllers/            → Home, News, Gallery, Competition, Admin
│   ├── Models/                 → News, Album, Image, Competition, Registration
│   ├── Services/               → News, Gallery, Competition, Image, Email, Validation
│   ├── Views/
│   │   ├── layouts/            → main.php (publikus), admin.php
│   │   ├── partials/           → head.php (design tokenek), header.php,
│   │   │                         navigation.php, footer.php, tinymce.php
│   │   ├── home|news|gallery|competitions/  → publikus nézetek
│   │   ├── admin/              → admin nézetek (news, gallery, competitions)
│   │   └── errors/             → 404.php, 500.php
│   └── Core/                   → Database, Router, Session, Validator, AppException, helpers
├── config/                     → database.php, app.php, mail.php, routes.php
├── database/migrations/         → 001_create_tables.sql
└── tests/{Unit,Properties,Integration}/
```

## Megjelenés és design rendszer

A design tokenek egyetlen helyen, a `src/Views/partials/head.php` fájlban vannak definiálva Tailwind konfigurációként. Ide tartoznak a színskálák, a tipográfia, az árnyékok és a sarokkerekítések. Ezt a partialt a publikus layout, az admin layout és a hibaoldalak is betöltik, így nem fordulhat elő, hogy a paletta több helyen szétcsúszik.

**Paletta.** Három skála: `billiard-green` (mély biliárdposztó zöld, 50–950), `billiard-gold` (meleg sárgaréz akcentus) és `sand` (meleg semleges alapszínek a hideg szürke helyett). Betűtípus: Inter.

**Komponensosztályok.** A `public/assets/css/app.css` egy kis komponenskészletet definiál, hogy a nézetek olvashatóak maradjanak hosszú Tailwind osztálylisták helyett:

| Osztály | Rendeltetés |
| --- | --- |
| `.btn` + `.btn-primary` / `.btn-gold` / `.btn-secondary` / `.btn-ghost` / `.btn-danger` / `.btn-sm` | Gombok |
| `.card`, `.card-interactive` | Kártyák, kattintható változat finom kiemelkedéssel |
| `.field`, `.label`, `.field-hint`, `.field-message`, `.field-error` | Űrlapelemek és mezőszintű hibajelzés |
| `.alert` + `.alert-success` / `-error` / `-warning` / `-info` | Visszajelzések |
| `.badge` + `.badge-green` / `-gold` / `-neutral` | Állapotjelvények |
| `.empty-state` | Üres állapotok ikonnal |
| `.nav-link`, `.nav-link-active` | Navigáció, aktív menüpont (Requirement 8.3) |
| `.article-body` | Az admin szerkesztőből érkező rich text tartalom tipográfiája |
| `.admin-table`, `.table-scroll` | Admin táblázatok, mobilon vízszintesen görgethetően |

**Elrendezés.** A fejléc ragadós (sticky), áttetsző hátterű, és egyetlen sávban tartalmazza a márkajelet, a navigációt és az admin hivatkozásokat. A `navigation.php` a menüpontokat definiálja, a `header.php` illeszti be és rendereli belőlük a mobil panelt is, így a menüpontok egy helyen szerkeszthetők.

**Akadálymentesség.** Az érintési célterületekre vonatkozó 44×44 pixeles minimum (Requirement 7.5) a `@media (pointer: coarse)` blokkban érvényesül, a folyó szövegben lévő hivatkozások kivételével — nélkülük a bekezdések sortávolsága széttolódna. A fókuszjelzés `:focus-visible` alapú, tehát csak billentyűzetes navigációnál látszik. Minden animáció és átmenet kikapcsol `prefers-reduced-motion: reduce` esetén. Mindkét layout tetején van „Ugrás a tartalomra" ugrólink.

## Architektúra

Négy réteg, felülről lefelé irányuló függőségekkel:

1. **Megjelenítési réteg** — PHP nézet sablonok, statikus erőforrások
2. **Alkalmazási réteg** — `Router` és kontrollerek. Minden kérés a `public/index.php`-be fut be, amely betölti az autoloadert, indítja a sessiont, betölti az útvonalakat, majd dispatchel. A központi hibakezelés `AppException`, `PDOException` és `Throwable` esetén a megfelelő hibaoldalt jeleníti meg.
3. **Domain réteg** — szolgáltatások, amelyek az üzleti logikát tartalmazzák
4. **Infrastruktúra réteg** — MySQL PDO kapcsolaton, fájlrendszer, PHPMailer

Az adatbázis minden táblája UUID (`CHAR(36)`) elsődleges kulcsot használ, `utf8mb4_unicode_ci` collationnel, InnoDB motorral. A `registrations` táblán `UNIQUE KEY (competition_id, email)` akadályozza meg a dupla nevezést, a `images` és `registrations` idegen kulcsai `ON DELETE CASCADE` beállítással törlődnek.

## Biztonság

- **SQL injection**: minden adatbázis-művelet PDO prepared statementet használ, emulált prepare kikapcsolva
- **XSS**: a nézetek az `e()` helperen keresztül escape-elnek minden kimenetet
- **Forráskód védelem**: a `src/`, `config/`, `vendor/`, `tests/` könyvtárak és a `.env` nem érhetők el HTTP-n
- **Feltöltés**: MIME típus (JPEG/PNG) és méret (max 10 MB) validáció, a feltöltési könyvtárban a PHP futtatás tiltva
- **Admin hozzáférés**: session-alapú, `password_verify()` támogatással

Éles üzembe helyezés előtt érdemes átgondolni: az `ADMIN_PASSWORD` lecserélése, HTTPS kikényszerítése, `APP_DEBUG=false`, valamint CSRF token bevezetése az admin űrlapokhoz (jelenleg nincs implementálva).

## Tesztelés

A tesztek külön `billiard_test` adatbázist használnak. A property tesztek és a unit tesztek SQLite in-memory adatbázison futnak a `tests/TestCase.php` alaposztályon keresztül, így nem igényelnek külön adatbázis-beállítást.

```bash
# Összes teszt
vendor/bin/phpunit

# Testsuite szerint
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Properties
vendor/bin/phpunit --testsuite Integration
```

Windows alatt: `vendor\bin\phpunit`.

Három tesztszint van:

- **Unit** (`tests/Unit/`) — konkrét példák és peremesetek
- **Properties** (`tests/Properties/`) — property-based tesztek Eris generátorokkal, univerzális helyességi tulajdonságokra (rendezés, round-trip, validáció, kaszkád törlés)
- **Integration** (`tests/Integration/`) — e-mail küldés mock SMTP-vel

## Hibakeresés

**A főoldal helyett könyvtárlistát vagy 404-et látok**

A gyökér `.htaccess` nem érvényesül. Ellenőrizd, hogy a `mod_rewrite` be van töltve a `httpd.conf`-ban, és hogy a `htdocs` könyvtárra `AllowOverride All` van beállítva (nem `None`). Módosítás után indítsd újra az Apache-ot.

**500 Internal Server Error minden oldalon**

Nézd meg az Apache hibanaplót: `C:\xampp\apache\logs\error.log`. A leggyakoribb ok egy `.htaccess` fájlban nem engedélyezett direktíva (például `<Directory>`), vagy hiányzó adatbázis.

**„A tartalom átmenetileg nem elérhető" üzenet**

Az adatbázis-kapcsolat nem áll fel. Ellenőrizd, hogy a MySQL fut, a `.env`-ben megadott `DB_NAME` adatbázis létezik, és a migráció lefutott.

**A bélyegképek nem generálódnak**

A GD kiterjesztés nincs engedélyezve. Ellenőrizd `php -m | findstr gd` paranccsal, és szükség esetén vedd ki a kommentet az `extension=gd` sor elől a `php.ini`-ben.

**A visszaigazoló e-mail nem érkezik meg**

Ellenőrizd a `.env` SMTP beállításait. Fejlesztés közben a Mailtrap vagy hasonló szolgáltatás használata ajánlott. A hibák a PHP error logba kerülnek, az `EmailService` legfeljebb 3 kísérletet tesz.

## Licenc

MIT — lásd a [LICENSE](LICENSE) fájlt.
