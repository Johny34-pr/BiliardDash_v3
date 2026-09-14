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

- Nyilvános nevezői lista versenyenként, belépés nélkül is

**Felhasználói fiókok**

- Regisztráció és belépés e-mail címmel
- Nevezés magának előtöltött űrlappal, vagy más nevében
- Saját nevezések áttekintése és visszavonása a nevezési határidőig
- A vendégnevezés (belépés nélküli) továbbra is működik

**Fórum**

- Topikok nyitása és hozzászólás vendégként és belépve is
- Csak egyszerű szöveg, korlátozott emojikészlettel
- Hozzászólások fel- és leértékelése
- Moderálás az admin felületen: lezárás, elrejtés (mindkettő visszavonható) és végleges törlés

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
| Rich text | TinyMCE 6 (CDN), képfeltöltéssel és belső hivatkozás-választóval |
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
mysql -u root --default-character-set=utf8mb4 billiard < database/migrations/002_add_users_and_registration_owner.sql
mysql -u root --default-character-set=utf8mb4 billiard < database/migrations/003_create_comments.sql
mysql -u root --default-character-set=utf8mb4 billiard < database/migrations/004_add_comment_votes.sql
mysql -u root --default-character-set=utf8mb4 billiard < database/migrations/005_add_forum_topics.sql
```

A migrációkat sorszám szerinti sorrendben kell futtatni:

| Migráció | Tartalom |
| --- | --- |
| `001` | Alap táblák: hírek, albumok, képek, versenyek, nevezések |
| `002` | Felhasználói fiókok és a nevezés rögzítője |
| `003` | Fórum hozzászólások |
| `004` | Hozzászólások értékelése (fel/leértékelés) |
| `005` | Fórum topikok; a meglévő hozzászólásokat egy alap topikba menti |

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

TINYMCE_API_KEY=

MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@magyarbilliard.hu
MAIL_FROM_NAME="Magyar Biliárd"
```

A `TINYMCE_API_KEY` a szerkesztő CDN kulcsa, a [tiny.cloud](https://www.tiny.cloud/) oldalon igényelhető. Üresen hagyva a szerkesztő működik, csak figyelmeztetést jelenít meg — ilyenkor az admin felület a szerkesztő alatt jelzi, mit kell beállítani.

**A környezeti változók betöltése.** A `.env` beolvasását az `App\Core\Env` osztály végzi, amit a `public/index.php` hív meg legelőször. A betöltés idempotens, és a **már beállított értékeket soha nem írja felül** — így az Apache `SetEnv` és a `phpunit.xml` beállításai elsőbbséget kapnak a `.env` fájllal szemben. A konfigurációs fájlok (`app.php`, `database.php`, `mail.php`) is meghívják, tehát önmagukban is helyes értéket adnak, nem csak akkor, ha előtte véletlenül betöltődött egy másik konfiguráció.

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
| GET | `/nevezes/{versenyId}/nevezok` | `CompetitionController@registrants` |
| POST | `/nevezes/{versenyId}` | `CompetitionController@submitRegistration` |
| GET | `/forum` | `ForumController@index` (topiklista) |
| GET, POST | `/forum/uj` | `ForumController@createForm`, `@store` |
| GET | `/forum/{id}` | `ForumController@show` |
| POST | `/forum/{id}/hozzaszolas` | `ForumController@storeComment` |
| POST | `/forum/hozzaszolas/{id}/ertekeles` | `ForumController@vote` |

**Felhasználói fiók** (a `/fiok` belépést igényel, egyébként a `/belepes` oldalra irányít)

| Metódus | Útvonal | Kezelő |
| --- | --- | --- |
| GET, POST | `/regisztracio` | `AuthController@registerForm`, `@register` |
| GET, POST | `/belepes` | `AuthController@loginForm`, `@login` |
| GET | `/kilepes` | `AuthController@logout` |
| GET | `/fiok` | `AuthController@account` |
| POST | `/fiok/nevezes/{id}/visszavonas` | `AuthController@deleteRegistration` |

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
| POST | `/admin/versenyek/nevezes/{id}/torol` | `AdminController@registrationDelete` |
| GET | `/admin/forum` | `AdminController@topicList` |
| POST | `/admin/forum/{id}/lezar` | `AdminController@topicToggleLocked` |
| POST | `/admin/forum/{id}/elrejt` | `AdminController@topicToggleHidden` |
| POST | `/admin/forum/{id}/torol` | `AdminController@topicDelete` |
| GET | `/admin/forum/hozzaszolasok` | `AdminController@commentList` |
| POST | `/admin/forum/hozzaszolas/{id}/elrejt` | `AdminController@commentToggleHidden` |
| POST | `/admin/forum/hozzaszolas/{id}/torol` | `AdminController@commentDelete` |

Az útvonalak a `config/routes.php` fájlban vannak definiálva.

## Könyvtárstruktúra

```
.
├── .htaccess                   → Kérések irányítása a public/ könyvtárba
├── public/                     → Web root
│   ├── index.php               → Front controller
│   ├── .htaccess               → Front controller rewrite + biztonsági fejlécek
│   ├── assets/css/app.css      → Design rendszer
│   ├── assets/js/              → app.js, gallery.js, editor.js
│   └── uploads/
│       ├── .htaccess           → Szkriptfuttatás tiltása (alkönyvtárakra is)
│       ├── albums/{id}/        → full/ (eredeti) és thumb/ (200x200px)
│       └── media/{év}/{hónap}/ → Szerkesztőbe feltöltött kép és dokumentum
├── src/
│   ├── Controllers/            → Home, News, Gallery, Competition, Auth,
│   │                             Forum, Admin, AdminMedia
│   ├── Models/                 → News, Album, Image, Competition, Registration,
│   │                             User, Topic, Comment, CommentVote
│   ├── Services/               → News, Gallery, Competition, Image, Email,
│   │                             Validation, Auth, Topic, Comment,
│   │                             Media, LinkTarget
│   ├── Views/
│   │   ├── layouts/            → main.php (publikus), admin.php
│   │   ├── partials/           → head.php (design tokenek), header.php,
│   │   │                         navigation.php, account-menu.php,
│   │   │                         admin-mode-bar.php, footer.php, tinymce.php,
│   │   │                         editor-help.php
│   │   ├── home|news|gallery|competitions/  → publikus nézetek
│   │   ├── forum/              → index.php (topiklista), create.php, show.php
│   │   ├── auth/               → login.php, register.php
│   │   ├── account/            → index.php (saját nevezések)
│   │   ├── admin/              → admin nézetek (news, gallery, competitions)
│   │   └── errors/             → 404.php, 500.php
│   └── Core/                   → Env, Database, Router, Session, Validator,
│                                 AppException, helpers
├── config/                     → database.php, app.php, mail.php, routes.php
├── database/migrations/         → 001_create_tables.sql
└── tests/{Unit,Properties,Integration}/
```

## Nevezés és felhasználói fiókok

Kétféle azonosítás létezik, egymástól függetlenül. A `Session` osztály mindkettőt kezeli, és külön-külön léptethetők ki.

| | Látogatói fiók | Szervezői hozzáférés |
| --- | --- | --- |
| Mire szolgál | nevezés, fórum, saját nevezések | hírek, galéria, versenyek, fórum kezelése |
| Belépés | `/belepes`, e-mail + jelszó | `/admin/login`, közös jelszó (e-mail nélkül) |
| Kilépés | `/kilepes` | `/admin/logout` |
| Session kulcs | `user` | `is_admin` |
| Kötelező-e | nem, vendégként is működik minden | csak szervezőknek |

A kettő nem zárja ki egymást: valaki lehet csak látogató, csak szervező, mindkettő, vagy egyik sem. Ezért a felület mindenhol **megnevezve** mutatja a két szerepet, nem általános „fiók" címke alatt:

- **Fejléc:** egyetlen fiókmenü (`partials/account-menu.php`), amelyben a két szerep külön, feliratozott szakaszban van, mindkettőhöz rövid magyarázattal. Korábban két párhuzamos sáv volt, két különböző szóval a kilépésre — abból nem derült ki, melyik gomb melyik szerepre hat.
- **Jelzősáv:** ha szervezői mód aktív, a publikus oldalak tetején arany sáv jelenik meg (`partials/admin-mode-bar.php`), hogy a hozzáférés ne maradhasson észrevétlenül bekapcsolva.
- **Egységes szóhasználat:** a látogatói fióknál „Belépés" / „Kilépés a fiókból", a szervezőinél „Szervezői belépés" / „Kilépés a szervezői módból". Az admin felület kilépés gombja is „Szervezői kilépés", és jelzi, hogy a látogatói fiókot nem érinti.
- **Kereszthivatkozások:** mindkét belépő oldal elmondja, mire szolgál, és hova kell menni a másikért.

**Nevezés három módon.** A nevező adatai (`full_name`, `email`, `phone`) mindig magán a nevezésen vannak, ezért egy fiók több személynek is rögzíthet nevezést:

| Mód | Belépés | `created_by_user_id` | Visszavonható |
| --- | --- | --- | --- |
| Vendégnevezés | nem kell | `NULL` | nem (csak admin) |
| Magamnak | igen | a fiók azonosítója | igen, a határidőig |
| Másnak | igen | a *rögzítő* fiók azonosítója | igen, a határidőig |

A „magamnak" mód a fiók adataival tölti elő az űrlapot, a „másnak" üresen hagyja. A választás csak kényelmi előtöltés: a nevezés tulajdonosát mindig a `created_by_user_id` határozza meg.

**Kinek az e-mail-címe kerül a nevezésre.** Mindig a *nevezőé*, azaz aki játszani fog. Két okból: így a `UNIQUE (competition_id, email)` megkötés továbbra is helyesen szűri a kétszeres nevezést, és a visszaigazoló e-mail is ahhoz jut el, akit érint.

**Visszavonás szabályai.** Kétféle törlés létezik, szándékosan különböző szabályokkal:

| | `deleteOwnRegistration()` (felhasználó) | `deleteRegistrationAsAdmin()` (szervező) |
| --- | --- | --- |
| Kinek a nevezését | csak amit ő vitt fel | bármelyiket |
| Határidő után | nem (`410`) | igen |
| Vendégnevezés | nem (`403`) | igen |

A felhasználói visszavonás két feltételt ellenőriz: a nevezést ő vitte-e fel, és lejárt-e már a határidő. A szervezői törlésnél egyik sem korlátoz, mert lemondást vagy hibás nevezést a határidő után is rendezni kell.

Mindkét út csökkenti a `registrant_count`-ot, hogy konzisztens maradjon a valós nevezésszámmal. A törlés után ugyanaz az e-mail cím újra nevezhet, mert a `UNIQUE (competition_id, email)` megkötést a törölt sor már nem foglalja.

**Fiók törlésekor** a nevezés nem törlődik: a `created_by_user_id` idegen kulcs `ON DELETE SET NULL`, tehát a nevezés vendégnevezéssé válik, és a szervező névsora nem csorbul.

**Jelszavak.** `password_hash()` (bcrypt) tárolás, a hash soha nem kerül sessionbe vagy naplóba. A bejelentkezés nem árulja el, hogy az e-mail cím vagy a jelszó volt hibás, így nem lehet vele létező fiókokat felderíteni. Nincs e-mail-cím megerősítés.

**Publikus útvonalak:** `/regisztracio`, `/belepes`, `/kilepes`, `/fiok`, valamint `POST /fiok/nevezes/{id}/visszavonas`.

Az admin nevezői listája jelvénnyel mutatja, hogy egy nevezés vendégként vagy fiókkal érkezett.

**Nyilvános nevezői lista.** A `/nevezes/{id}/nevezok` útvonal belépés nélkül is elérhető, és szándékosan **csak a nevezők nevét és a nevezés idejét** adja vissza. Az e-mail cím és a telefonszám személyes adat, ezért az kizárólag a szervező admin felületén látható. A két adatkört két külön szolgáltatásmetódus választja el: a nyilvános `getPublicRegistrants()` és az admin `getRegistrations()`.

## Fórum

A fórum **topikokból** (témákból) áll: egy topik címből és nyitó bejegyzésből, a hozzászólások pedig egy topikhoz tartoznak. Topikot nyitni és hozzászólni vendégként és belépve is lehet. Belépve a szerzőnév a fiókból származik, ezért a beküldött érték nem érvényesül — így nem lehet más nevében írni.

**Rendezés.** A topiklista a legutóbbi aktivitás szerint rendez (`last_activity_at`), így a mozgásban lévő beszélgetések kerülnek előre. Egy topikon belül viszont a hozzászólások időrendben, a legkorábbival kezdve jelennek meg, hogy a beszélgetés felülről lefelé követhető legyen.

**Tartalmi szabály: csak egyszerű szöveg.** A `CommentService::sanitizeBody()` minden HTML jelölést eltávolít, feloldja az entitásokat, majd **újra** eltávolítja a jelölést (így az entitásként bújtatott tag sem éled újra), kiszűri a vezérlőkaraktereket, normalizálja a whitespace-t, és érvényesíti az 1000 karakteres korlátot. A kimenet `e()`-vel escape-elve jelenik meg, a sortöréseket CSS `white-space: pre-wrap` tartja meg — így nincs `nl2br`, és nem nyílik HTML injektálási lehetőség.

**Emojik.** Csak a `CommentService::ALLOWED_EMOJIS` listán szereplő emojik használhatók, minden más piktogram kiszűrődik. A szűrés a megengedett emojikat előbb helyőrzőre cseréli, így a több kódpontból álló emojik (például a variációs jelölőt használó ❤️) sem sérülnek. A felületen ugyanez a lista jelenik meg választhatóként.

**Értékelés.** Minden hozzászólás fel- és leértékelhető. Ugyanarra a gombra újra kattintva a szavazat visszavonható, az ellenkezőre kattintva átfordul — egy szavazónak hozzászólásonként mindig legfeljebb egy szavazata van, amit a `UNIQUE (comment_id, voter_key)` megkötés garantál.

A szavazót a `Session::voterKey()` azonosítja: belépve `user:{id}` (eszközfüggetlen), vendégként `guest:{sessionben tárolt token}`. A vendég azonosítás megakadályozza az ismételt szavazást ugyanabból a böngészőmenetből, de a session törlése után újra lehet szavazni. Ez szándékos kompromisszum, hogy ne kelljen azonosításra alkalmas adatot tárolni.

A `comments.upvotes` / `downvotes` csak gyorsított összesítés: minden szavazás után a `comment_votes` táblából **újraszámoljuk**, nem növeljük vagy csökkentjük, így a számlálók nem tudnak elcsúszni a valós szavazatoktól.

**Moderálás.** Három eszköz áll rendelkezésre, növekvő súlyú sorrendben:

| Eszköz | Hatás | Visszavonható |
| --- | --- | --- |
| Lezárás (`topics.is_locked`) | A topik olvasható marad, de nem fogad új hozzászólást | igen |
| Elrejtés (`is_hidden`) | Eltűnik a publikus listáról, de megmarad az adatbázisban | igen |
| Törlés | Véglegesen eltávolítja; topik esetén a hozzászólásait is | nem |

Elrejtett hozzászólásra nem lehet szavazni, és elrejtett topik publikusan `404`-et ad. A topik törlésekor a hozzászólásai, azokkal együtt a szavazatok is kaszkádban törlődnek.

A `topics.comment_count` szintén gyorsított összesítés, amit a hozzászólásokból számolunk újra — az elrejtett hozzászólások nem számítanak bele, így a moderálás után is helyes marad.

**Visszaélés-védelem.** Két hozzászólás között 15, két topiknyitás között 60 másodpercet kell várni (sessionben tárolt időbélyeg alapján), és az IP cím csak SHA-256 hash formában tárolódik.

**Útvonalak.** Publikus: `GET /forum` (topiklista), `GET|POST /forum/uj` (topik nyitása), `GET /forum/{id}` (topik), `POST /forum/{id}/hozzaszolas`, `POST /forum/hozzaszolas/{id}/ertekeles`. Admin: `/admin/forum` (topikok) és `/admin/forum/hozzaszolasok` (hozzászólások) a hozzájuk tartozó műveletekkel.

> A `/forum/uj` útvonal szándékosan a `/forum/{id}` minta **előtt** van regisztrálva, különben a router az „uj" szót topik azonosítóként értelmezné. Ugyanez az oka, hogy a szavazás útvonala `/forum/hozzaszolas/{id}/ertekeles`.

## Blogszerkesztő

A hírszerkesztő TinyMCE 6 alapú. A beállítás a `public/assets/js/editor.js` fájlban van, a kiszolgálóoldali adatokat (végpontok, méretkorlátok) a `partials/tinymce.php` adja át a szerkesztő `data-*` attribútumain — így a JavaScript gyorsítótárazható marad, a korlátok pedig PHP oldalon, egy helyen módosíthatók.

A CDN kulcs a `.env` `TINYMCE_API_KEY` beállításából jön, nem a forráskódból.

**Kép beszúrása.** Húzd-és-vidd, vágólapról beillesztés és fájlválasztó is működik; a kép azonnal feltöltődik (`automatic_uploads`). Formátum: JPEG, PNG, GIF, WebP, legfeljebb 10 MB.

**Dokumentum csatolása.** A *Csatolmány* gomb PDF, DOC(X), XLS(X), ODT, ODS, TXT és CSV fájlt tölt fel (max. 20 MB), és kiemelt, letölthető hivatkozásként szúrja be (`a.attachment`). A publikus oldalon önálló blokként jelenik meg, hogy ne olvadjon bele a bekezdésbe.

**Médiakönyvtár.** A korábbi feltöltések listából újra beszúrhatók, így nem kell ugyanazt kétszer feltölteni.

**Belső hivatkozás-választó.** A hivatkozás párbeszédpanel legördülőjét a kiszolgáló tölti fel (`link_list`), a `LinkTargetService` állítja össze: aloldalak, minden verseny **nevezési űrlapja** és nevezői listája, hírek, galéria albumok és fórum topikok. Így nem kell URL-t kézzel beírni, és nem keletkezik törött hivatkozás. Az elrejtett fórum topikok nem jelennek meg a listában.

### Feltöltés biztonsága

A `MediaService` négy egymást erősítő szabályt alkalmaz:

1. **A típus a tartalomból derül ki** (`finfo`), nem a kliens által küldött MIME-ből, ami hamisítható.
2. **A fájlnév mindig újonnan generált UUID**, a kiterjesztés a felismert típushoz tartozó engedélyezett érték. Így a kettős kiterjesztés (`kep.php.jpg`) nem használható szkript becsempészésére.
3. **Képeknél `getimagesize()` ellenőrzés** is fut, hogy a fájl valóban kép legyen.
4. **Az SVG szándékosan nincs az engedélyezett formátumok között**, mert szkriptet tartalmazhat. Tömörített állomány (zip) sem, mert a tartalma feltöltéskor nem ellenőrizhető.

A tárolás `public/uploads/media/{év}/{hónap}/` alatt történik, ahol a `public/uploads/.htaccess` (az alkönyvtárakra is érvényes) letiltja a szkriptfuttatást. Minden végpont szervezői hozzáférést igényel, és hiba esetén JSON választ ad a megfelelő állapotkóddal — nem HTML átirányítást, amit a szerkesztő nem tudna értelmezni.

**Végpontok:** `POST /admin/media/kep`, `POST /admin/media/dokumentum`, `GET /admin/media/lista`, `GET /admin/media/hivatkozasok`.

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
