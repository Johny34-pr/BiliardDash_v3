# Okányi Biliárd Klub weboldal

Az Okányi Biliárd Klub közösségi weboldala: hírek, fotógaléria és online versenynevezés. Keretrendszer nélküli PHP MVC alkalmazás, amely bármely standard LAMP/WAMP környezetben futtatható.

## Funkciók

**Publikus felület**

- Hírek listázása a főoldalon (10 legfrissebb, fordított időrendben, 200 karakteres összefoglalóval)
- Hír részletes nézet
- Fotógaléria albumokba rendezve, borítóképpel és képszámmal
- Archív galéria: a korábbi szezonok albumai szezon szerint csoportosítva
- Lightbox képnézegető: nyíl- és billentyűzet-navigáció, mobilon swipe gesztus
- Nyitott versenyek listája és online nevezési űrlap
- Visszaigazoló e-mail sikeres nevezés után
- Ranglista: a szezon pontverseny-állása versenyenkénti bontásban, archívummal
- Közvetítés menüpont: külső élő adásra mutató hivatkozás
- Reszponzív elrendezés három töréspontra (mobil / tablet / asztali)
- Nyilvános nevezői lista versenyenként, belépés nélkül is

**Felhasználói fiókok**

- Regisztráció és belépés e-mail címmel
- Nevezés **csak belépve, kizárólag a saját nevében** — a név és az e-mail cím a fiókból származik
- Saját nevezések áttekintése és visszavonása a nevezési határidőig
- Saját jelszó megváltoztatása a fiók oldalán

**Fórum** (kapcsolható modul, alapértelmezetten kikapcsolva)

- Topikok nyitása és hozzászólás vendégként és belépve is
- Csak egyszerű szöveg, korlátozott emojikészlettel
- Hozzászólások fel- és leértékelése
- Moderálás az admin felületen: lezárás, elrejtés (mindkettő visszavonható) és végleges törlés

**Admin felület**

- Session-alapú bejelentkezés
- Hírek létrehozása, szerkesztése, törlése TinyMCE rich text szerkesztővel
- Albumok létrehozása, képfeltöltés bélyegkép (200×200 px) és éles közepes méret (max. 1200 px) generálásával
- Albumok archiválása és szezonhoz rendelése
- Versenyek kezelése és a nevezői lista megtekintése
- Nevezés felvitele bárki nevében: meglévő taghoz kötve vagy vendégnevezésként
- Nevezői lista exportálása CSV formátumban (UTF-8 BOM)
- Ranglista pontszámainak szerkesztése; az állás azonnal újraszámolódik
- Szezonok kezelése: létrehozás, aktuális kijelölése, archiválás
- Körlevél a tagoknak a versenykiírásról és a nevezés megnyílásáról
- Tagok jelszavának visszaállítása generált jelszóval
- Kapcsolható modulok (fórum, ranglista) és a Közvetítés hivatkozása a beállítások oldalon

## Technológiai stack

| Terület | Választás |
| --- | --- |
| Backend | PHP 8.1+ (egyszerű MVC, keretrendszer nélkül) |
| Adatbázis | MySQL 8.0+ |
| DB hozzáférés | PDO prepared statements |
| Stílus | Előre generált segédosztály-készlet (PHP generátor) |
| Képkezelés | PHP GD Library |
| E-mail | PHPMailer (SMTP) |
| Rich text | TinyMCE 6 (CDN), képfeltöltéssel és belső hivatkozás-választóval |
| Frontend JS | Vanilla JavaScript |
| Tesztelés | PHPUnit 10 + Eris (property-based testing) + Mockery |
| Webszerver | Apache + mod_rewrite |

Nincs Node és nincs npm. A stíluslapot egy PHP szkript állítja elő
(`php tools/build-css.php`), a TinyMCE pedig CDN-ről töltődik.

## Követelmények

- PHP 8.1 vagy újabb, engedélyezett `pdo_mysql`, `gd` és `mbstring` kiterjesztésekkel
- MySQL 8.0 vagy újabb
- Apache engedélyezett `mod_rewrite` modullal és `AllowOverride All` beállítással
- Composer

## Telepítés

### 1. Függőségek telepítése és a stíluslap előállítása

```bash
composer install
php tools/build-css.php
php tools/generate-icons.php
```

Mindkét generátor kimenete verziókövetett fájl, tehát friss klón után már kész
van — a parancsokat csak akkor kell lefuttatni, ha módosítottál a bemeneten:

- `tools/build-css.php` → `public/assets/css/tailwind.css`, ha sablont vagy
  design tokent módosítottál
- `tools/generate-icons.php` → ikonok és a megosztási kép, ha a logót
  (`public/assets/images/logo.png`) cserélted

Node és npm egyikhez sem kell, csak PHP a `gd` kiterjesztéssel. Részletek a
[Megjelenés és design rendszer](#megjelenés-és-design-rendszer) szakaszban.

### 2. Adatbázis létrehozása és séma betöltése

```bash
mysql -u root -e "CREATE DATABASE billiard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php tools/migrate.php
```

A `tools/migrate.php` sorszám szerint futtatja a `database/migrations/` alatti
fájlokat, és a `schema_migrations` táblában nyilvántartja, mi futott már le —
így egy meglévő adatbázison is biztonságosan újrafuttatható.

```bash
php tools/migrate.php --status              # mi futott le, mi van hátra
php tools/migrate.php                       # a hátralévők alkalmazása
php tools/migrate.php --baseline 006_...    # a megadott fájlig lefutottnak jelöl, futtatás nélkül
```

A `--baseline` arra kell, ha a séma már kézzel be van töltve: felveszi a
nyilvántartásba a korábbi migrációkat, hogy ne próbálja újra alkalmazni őket.

| Migráció | Tartalom |
| --- | --- |
| `001` | Alap táblák: hírek, albumok, képek, versenyek, nevezések |
| `002` | Felhasználói fiókok és a nevezés rögzítője |
| `003` | Fórum hozzászólások |
| `004` | Hozzászólások értékelése (fel/leértékelés) |
| `005` | Fórum topikok; a meglévő hozzászólásokat egy alap topikba menti |
| `006` | Nevezés nyitódátuma (`competitions.registration_opens_at`) |
| `007` | Beállítások tábla és szerkeszthető tartalmi oldalak (Rólunk, Emlékoldal, Adatkezelés) |
| `008` | Galéria helyezettek (`album_placements`) |
| `009` | Település a fiókokban és a „belépés megjegyzése" tokenek |
| `010` | Szezonok, ranglista pontszámok, körlevél-napló, album-archiválás, közepes képméret |
| `011` | Az adatkezelési tájékoztató kép- és videófelvételekről szóló szakasza |

XAMPP alatt Windows-on a PHP a `C:\xampp\php\php.exe`, a MySQL kliens a
`C:\xampp\mysql\bin\mysql.exe` útvonalon található. Alternatívaként a
migrációkat a phpMyAdmin felületén is be lehet importálni, sorszám szerinti
sorrendben.

**Meglévő galéria frissítése a `010` migráció után.** A migráció felveszi az
`images.medium_path` oszlopot, de a korábban feltöltött képekhez még nincs
közepes méret. A pótlást ez az eszköz végzi:

```bash
php tools/backfill-images.php --check    # mennyi a hátralévő, írás nélkül
php tools/backfill-images.php            # a közepes méretek előállítása
```

Idempotens: csak ott dolgozik, ahol a `medium_path` üres, tehát bármikor
újrafuttatható. Amíg nem fut le, a galéria a bélyegképet mutatja — nem törik
el, csak lágyabb.

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

APP_NAME="Okányi Biliárd Klub"
APP_URL=http://localhost
APP_DEBUG=false
ADMIN_PASSWORD=valasz-egy-eros-jelszot

TINYMCE_API_KEY=

MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=info@okanyibiliard.hu
MAIL_FROM_NAME="Okányi Biliárd Klub"
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

### 6. Időzített feladat az értesítésekhez (nem kötelező)

A nevezés megnyílása **időpont, nem művelet**: nincs kérés, amihez a körlevél
kiküldését hozzá lehetne kötni. Ezért két helyről fut:

```bash
php tools/send-notifications.php --check    # mi esedékes, kiküldés nélkül
php tools/send-notifications.php            # az esedékes körlevelek kiküldése
```

Az eszközt érdemes napi gyakorisággal időzíteni. Linuxon crontabbal:

```cron
0 8 * * * /usr/bin/php /var/www/okanyibiliard/tools/send-notifications.php >> /var/log/okanyi-notifications.log 2>&1
```

Windowson a Feladatütemezőben, `C:\xampp\php\php.exe` programmal és
`C:\xampp\htdocs\tools\send-notifications.php` argumentummal.

Időzítés **nélkül sem marad ki** a kiküldés: az esedékes körleveleket a
szervezői áttekintő (`/admin`) betöltése is elindítja, tehát legkésőbb akkor
kimennek, amikor a szervező belép. Az időzítés csak azt javítja, hogy a levél
közelebb essen a tényleges nyitási időponthoz.

A kétszeres kiküldést a `competition_notifications` tábla
`UNIQUE (competition_id, kind)` megkötése akadályozza meg, nem alkalmazásbeli
ellenőrzés — így két párhuzamos futás sem tud ugyanarról két levelet küldeni.

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
| GET | `/galeria/archiv` | `GalleryController@archive` |
| GET | `/galeria/{albumId}` | `GalleryController@show` |
| GET | `/nevezes` | `CompetitionController@index` |
| GET | `/nevezes/{versenyId}` | `CompetitionController@showForm` |
| GET | `/nevezes/{versenyId}/nevezok` | `CompetitionController@registrants` |
| POST | `/nevezes/{versenyId}` | `CompetitionController@submitRegistration` |
| GET | `/rolunk`, `/emlekoldal`, `/tarshonlapok`, `/adatkezeles` | `PageController@about`, `@memorial`, `@partners`, `@privacy` |
| GET | `/ranglista` | `RankingController@index` (kapcsolható) |
| GET | `/ranglista/archiv` | `RankingController@archive` (kapcsolható) |
| GET | `/ranglista/{seasonId}` | `RankingController@show` (kapcsolható) |
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
| POST | `/fiok/jelszo` | `AuthController@changePassword` |

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
| GET, POST | `/admin/galeria/{id}/helyezettek` | `AdminController@placementList`, `@placementStore` |
| POST | `/admin/galeria/{id}/boritokep` | `AdminController@albumSetCover` |
| POST | `/admin/galeria/{id}/archivalas` | `AdminController@albumToggleArchived` |
| POST | `/admin/galeria/{id}/szerkeszt` | `AdminController@albumUpdate` (név és szezon) |
| GET | `/admin/versenyek` | `AdminController@competitionList` |
| GET, POST | `/admin/versenyek/uj` | `AdminController@competitionCreate`, `@competitionStore` |
| GET, POST | `/admin/versenyek/{id}/szerkeszt` | `AdminController@competitionEdit`, `@competitionUpdate` |
| POST | `/admin/versenyek/{id}/torol` | `AdminController@competitionDelete` |
| POST | `/admin/versenyek/{id}/ertesites` | `AdminController@competitionNotify` (körlevél) |
| GET, POST | `/admin/versenyek/{id}/nevezesek` | `AdminController@registrationList`, `@registrationStore` |
| GET | `/admin/versenyek/{id}/export` | `AdminController@exportCsv` |
| POST | `/admin/versenyek/nevezes/{id}/torol` | `AdminController@registrationDelete` |
| GET | `/admin/felhasznalok` | `AdminController@userList` |
| GET, POST | `/admin/felhasznalok/{id}/szerkeszt` | `AdminController@userEdit`, `@userUpdate` |
| POST | `/admin/felhasznalok/{id}/jelszo` | `AdminController@userResetPassword` |
| POST | `/admin/felhasznalok/{id}/torol` | `AdminController@userDelete` |
| GET | `/admin/oldalak` | `AdminController@pageList` |
| GET, POST | `/admin/oldalak/{id}/szerkeszt` | `AdminController@pageEdit`, `@pageUpdate` |
| GET, POST | `/admin/beallitasok` | `AdminController@settings`, `@settingsUpdate` |
| GET | `/admin/szezonok` | `AdminController@seasonList` |
| POST | `/admin/szezonok/uj` | `AdminController@seasonStore` |
| POST | `/admin/szezonok/{id}/szerkeszt` | `AdminController@seasonUpdate` |
| POST | `/admin/szezonok/{id}/aktualis` | `AdminController@seasonMakeCurrent` |
| POST | `/admin/szezonok/{id}/archivalas` | `AdminController@seasonToggleArchived` |
| POST | `/admin/szezonok/{id}/torol` | `AdminController@seasonDelete` |
| GET | `/admin/ranglista` | `AdminController@rankingList` (kapcsolható) |
| GET, POST | `/admin/ranglista/{competitionId}` | `AdminController@rankingEdit`, `@rankingEntryStore` |
| POST | `/admin/ranglista/{competitionId}/atvetel` | `AdminController@rankingImport` |
| POST | `/admin/ranglista/pont/{id}/szerkeszt` | `AdminController@rankingEntryUpdate` |
| POST | `/admin/ranglista/pont/{id}/torol` | `AdminController@rankingEntryDelete` |
| GET | `/admin/forum` | `AdminController@topicList` |
| POST | `/admin/forum/{id}/lezar` | `AdminController@topicToggleLocked` |
| POST | `/admin/forum/{id}/elrejt` | `AdminController@topicToggleHidden` |
| POST | `/admin/forum/{id}/torol` | `AdminController@topicDelete` |
| GET | `/admin/forum/hozzaszolasok` | `AdminController@commentList` |
| POST | `/admin/forum/hozzaszolas/{id}/elrejt` | `AdminController@commentToggleHidden` |
| POST | `/admin/forum/hozzaszolas/{id}/torol` | `AdminController@commentDelete` |

Az útvonalak a `config/routes.php` fájlban vannak definiálva.

**Kapcsolható modulok.** A fórum és a ranglista útvonalai csak akkor kerülnek
regisztrálásra, ha a modul be van kapcsolva (`/admin/beallitasok`). Kikapcsolva
a router `404`-et ad rájuk — ez erősebb védelem, mint a menüpont elrejtése,
mert a mentett hivatkozáson keresztül sem érhető el a tartalom.

> A `/galeria/archiv` és a `/ranglista/archiv` szándékosan a paraméteres minta
> **előtt** van regisztrálva, különben az „archiv" szó album-, illetve
> szezonazonosítóként értelmeződne. Ugyanez az oka, hogy a ranglista
> pontszámainak útvonala `/admin/ranglista/pont/{id}/...`.

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
│       ├── albums/{id}/        → full/ (eredeti), medium/ (max 1200px),
│       │                         thumb/ (200x200px)
│       └── media/{év}/{hónap}/ → Szerkesztőbe feltöltött kép és dokumentum
├── src/
│   ├── Controllers/            → Home, News, Gallery, Competition, Auth,
│   │                             Forum, Ranking, Page, Sitemap, Admin,
│   │                             AdminMedia
│   ├── Models/                 → News, Album, Image, Competition, Registration,
│   │                             User, Topic, Comment, CommentVote, Season,
│   │                             RankingEntry, CompetitionNotification,
│   │                             Setting, Page, AlbumPlacement
│   ├── Services/               → News, Gallery, Competition, Image, Email,
│   │                             Validation, Auth, Topic, Comment,
│   │                             Media, LinkTarget, Season, Ranking,
│   │                             Notification, Settings, RememberMe
│   ├── Views/
│   │   ├── layouts/            → main.php (publikus), admin.php
│   │   ├── partials/           → head.php (design tokenek), header.php,
│   │   │                         navigation.php, account-menu.php,
│   │   │                         admin-mode-bar.php, brand-mark.php,
│   │   │                         footer.php, tinymce.php, editor-help.php
│   │   ├── home|news|competitions/  → publikus nézetek
│   │   ├── gallery/            → index.php, show.php, archive.php,
│   │   │                         _album-card.php (közös kártya)
│   │   ├── ranking/            → index.php (szezon táblázat), archive.php
│   │   ├── forum/              → index.php (topiklista), create.php, show.php
│   │   ├── pages/              → about.php, memorial.php, partners.php,
│   │   │                         privacy.php
│   │   ├── auth/               → login.php, register.php
│   │   ├── account/            → index.php (saját nevezések + jelszó)
│   │   ├── admin/              → admin nézetek (news, gallery, competitions,
│   │   │                         users, pages, seasons, ranking, forum)
│   │   └── errors/             → 404.php, 500.php
│   └── Core/                   → Env, Database, Router, Session, Validator,
│                                 AppException, helpers
├── config/                     → database.php, app.php, mail.php, routes.php,
│                                 contact.php
├── database/migrations/        → 001 … 011 (sorszámozott SQL)
├── tools/                      → migrate.php, build-css.php,
│                                 generate-icons.php, backfill-images.php,
│                                 send-notifications.php, css/
└── tests/{Unit,Properties,Integration}/
```

## Eszközök

Mind CLI szkript, a projekt gyökeréből futtatva. Egyik sem igényel Node-ot.

| Eszköz | Mit tesz | `--check` |
| --- | --- | --- |
| `tools/migrate.php` | a hátralévő migrációk alkalmazása | `--status` |
| `tools/build-css.php` | a stíluslap előállítása a nézetekből | igen |
| `tools/generate-icons.php` | ikonok és megosztási kép a `logo.png`-ből | nem |
| `tools/backfill-images.php` | közepes képméret pótlása a régi képekhez | igen |
| `tools/send-notifications.php` | az esedékes körlevelek kiküldése | igen |

A `--check` mód mindenhol ugyanazt jelenti: felméri és kiírja, mi lenne a
teendő, de **nem ír** — tehát élesben is biztonságosan futtatható. A
`build-css.php` ezen felül 1-es kilépési kóddal áll le, ha feloldatlan
osztálynevet talál, ezért folyamatos integrációba is beköthető.

## Nevezés és felhasználói fiókok

Kétféle azonosítás létezik, egymástól függetlenül. A `Session` osztály mindkettőt kezeli, és külön-külön léptethetők ki.

| | Látogatói fiók | Szervezői hozzáférés |
| --- | --- | --- |
| Mire szolgál | nevezés, fórum, saját nevezések | hírek, galéria, versenyek, ranglista, szezonok kezelése |
| Belépés | `/belepes`, e-mail + jelszó | `/admin/login`, közös jelszó (e-mail nélkül) |
| Kilépés | `/kilepes` | `/admin/logout` |
| Session kulcs | `user` | `is_admin` |
| Kötelező-e | **a nevezéshez igen**, a böngészéshez nem | csak szervezőknek |

A kettő nem zárja ki egymást: valaki lehet csak látogató, csak szervező, mindkettő, vagy egyik sem. Ezért a felület mindenhol **megnevezve** mutatja a két szerepet, nem általános „fiók" címke alatt:

- **Fejléc:** egyetlen fiókmenü (`partials/account-menu.php`), amelyben a két szerep külön, feliratozott szakaszban van, mindkettőhöz rövid magyarázattal. Korábban két párhuzamos sáv volt, két különböző szóval a kilépésre — abból nem derült ki, melyik gomb melyik szerepre hat.
- **Jelzősáv:** ha szervezői mód aktív, a publikus oldalak tetején arany sáv jelenik meg (`partials/admin-mode-bar.php`), hogy a hozzáférés ne maradhasson észrevétlenül bekapcsolva.
- **Egységes szóhasználat:** a látogatói fióknál „Belépés" / „Kilépés a fiókból", a szervezőinél „Szervezői belépés" / „Kilépés a szervezői módból". Az admin felület kilépés gombja is „Szervezői kilépés", és jelzi, hogy a látogatói fiókot nem érinti.
- **Kereszthivatkozások:** mindkét belépő oldal elmondja, mire szolgál, és hova kell menni a másikért.

**A nevezés regisztrációhoz kötött, és mindenki csak a saját nevében nevezhet.** Fiók nélkül a nevezési űrlap helyett a belépésre és a regisztrációra hívó kártya jelenik meg.

| Mód | Ki indítja | `created_by_user_id` | Visszavonható |
| --- | --- | --- | --- |
| Saját nevezés | belépett tag a `/nevezes/{id}` űrlapon | a fiók azonosítója | igen, a határidőig |
| Tag nevében | szervező az admin felületen | a *tag* azonosítója | igen, a tag is visszavonhatja |
| Vendégnevezés | szervező az admin felületen | `NULL` | csak szervező |

**A nevező neve és e-mail címe a FIÓKBÓL jön, nem az űrlapról.** A `registerSelf()`
csak a telefonszámot olvassa a beküldött adatokból, mert az eltérhet a fiókban
tárolttól. A név és az e-mail cím mezője meg sem jelenik az űrlapon, csak
olvasható formában — és ha valaki mégis beküld ilyen mezőt, az nem érvényesül.
Így nem lehet más nevében nevezni.

A kétszeres nevezést két megkötés együtt akadályozza:
`UNIQUE (competition_id, email)` és `UNIQUE (competition_id, created_by_user_id)`.
Az első nélkül ugyanaz a fiók egy másik e-mail címmel kétszer nevezhetne; a
másodikban a MySQL a `NULL`-okat egyedinek tekinti, ezért a szervezői
vendégnevezésből több is lehet egy versenyen.

**A szervező sem lépi át a határidőt.** A `registerAsAdmin()` ugyanúgy ellenőrzi
a nyitódátumot és a nevezési határidőt. Ha a szervezőnek a határidő után kell
felvinnie valakit, előbb a határidőt módosítja — így a névsor és a meghirdetett
határidő nem mond ellent egymásnak.

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

**Jelszó-visszaállítás.** Nincs önkiszolgáló „elfelejtett jelszó" folyamat; a
visszaállítást a szervező végzi a `/admin/felhasznalok` oldalon. A rendszer
12 karakteres jelszót generál, amelyből kimaradnak az összekeverhető karakterek
(`0`/`O`, `1`/`l`/`I`), mert a jelszót jellemzően telefonon diktálják.

A generált jelszó **kizárólag a szervezőnek jelenik meg**, egyetlen alkalommal,
közvetlenül a visszaállítás után — az értesítő e-mail szándékosan nem
tartalmazza, mert az e-mail nem biztonságos csatorna. A tag ezután a `/fiok`
oldalon lecserélheti megjegyezhetőre; ehhez a jelenlegi jelszót is meg kell
adnia, hogy egy eltulajdonított munkamenettel ne lehessen kizárni a tulajdonost.

Mindkét művelet érvényteleníti a „belépés megjegyzése" tokeneket, így a régi
süti nem léptet be.

**Publikus útvonalak:** `/regisztracio`, `/belepes`, `/kilepes`, `/fiok`, valamint `POST /fiok/nevezes/{id}/visszavonas` és `POST /fiok/jelszo`.

Az admin nevezői listája jelvénnyel mutatja, hogy egy nevezés vendégként vagy fiókkal érkezett.

**Nyilvános nevezői lista.** A `/nevezes/{id}/nevezok` útvonal belépés nélkül is elérhető, és szándékosan **csak a nevezők nevét és a nevezés idejét** adja vissza. Az e-mail cím és a telefonszám személyes adat, ezért az kizárólag a szervező admin felületén látható. A két adatkört két külön szolgáltatásmetódus választja el: a nyilvános `getPublicRegistrants()` és az admin `getRegistrations()`.

## Szezonok

A szezon (`seasons`) két helyen rendez: a galéria archívumában és a ranglistán.
Ezért önálló felülete van (`/admin/szezonok`), nem egy album vagy verseny
mellékes beállítása.

| Jelző | Jelentés |
| --- | --- |
| `is_current` | a futó évad. Ennek a ranglistája jelenik meg a `/ranglista` címen |
| `is_archived` | lezárt évad. A `/ranglista/archiv` és a galéria archívuma listázza |
| `starts_on` | ez adja a sorrendet — a név szerinti rendezés csak véletlenül helyes |

**Az „aktuális" kizárólagos.** A `makeCurrent()` először minden szezonról leveszi
a jelölést, és csak utána teszi rá a kiválasztottra. A két lépés szándékosan nem
egy tranzakció: ha félbeszakad, a legrosszabb eset az, hogy egyik szezon sem
aktuális — ez a felületen látszik és egy kattintással javítható. A „két aktuális
szezon" viszont kétértelmű állapot lenne, amit semmi nem jelez.

Az aktuális szezon **nem archiválható és nem törölhető** (`VALIDATION_ERROR`):
előbb ki kell jelölni az utódját.

**Album archiválása külön oszlop** (`albums.is_archived`), nem a szezonból
számolt érték. Így a szervező egy régi szezon albumát szándékosan előtérben
tarthatja, például egy jubileumi versenyét.

## Galéria és képméretek

Minden feltöltött képből három változat készül:

| Változat | Méret | Hol jelenik meg |
| --- | --- | --- |
| `full/` | az eredeti, bájtra változatlan | a lightbox nagyítása |
| `medium/` | leghosszabb oldal max. 1200 px, arányos | a galéria kártyái és az album oldala |
| `thumb/` | 200×200 px, középre vágva | tartalék, ha a közepes méret hiányzik |

A közepes méret azért kell, mert a 200 px-es bélyegkép egy 4:3-as, több száz
pixel széles kártyára kifeszítve láthatóan lágy. A `createResized()`
**nem vág** (megőrzi a képarányt) és **nem nagyít** (a korlát alatti képet
változatlanul másolja, mert a felskálázás nem élesít).

Ha az átméretezett fájl nagyobb lett az eredetinél — ez tömör, kevés színt
használó PNG-nél előfordul —, akkor az eredetit másolja: így egyszerre lesz
kevesebb a letöltés és több a képpont.

A közepes méret előállításának hibája **nem hiúsítja meg a feltöltést**: a
`medium_path` üresen marad, és a megjelenítés a bélyegképre esik vissza.

## Ranglista

A szezon pontversenyének állása, versenyenkénti bontásban. Minden sor egy
játékos, minden oszlop egy verseny, a jobb szélen az összesítés. A táblázat
mobilon vízszintesen görgethető, a játékos neve és a helyezése görgetés közben
is látszik.

**Az állás számolt, nem tárolt érték.** Nincs összeg-oszlop az adatbázisban: a
`ranking_entries` sorai az egyetlen igazságforrás, és minden megjelenítés
belőlük áll össze. Ezért a szervezői javítás **azonnal** érvényesül, külön
újraszámoló művelet nélkül. A denormalizált összeg elcsúszhatna a részletektől,
és egy kimaradt frissítés után csendben hibás lenne.

| Művelet | Útvonal |
| --- | --- |
| Pontszám felvitele egy versenyen | `POST /admin/ranglista/{competitionId}` |
| Helyezésekből átvétel | `POST /admin/ranglista/{competitionId}/atvetel` |
| Pontszám módosítása | `POST /admin/ranglista/pont/{id}/szerkeszt` |
| Pontszám törlése | `POST /admin/ranglista/pont/{id}/torol` |

Egy játékos egy versenyen csak egyszer szerepelhet:
`UNIQUE (competition_id, player_name)`.

**Átvétel a galéria helyezettjeiből.** Ha egy verseny helyezettjei már fel vannak
vezetve a galériában, az `importFromPlacements()` pontszámmá alakítja őket a
következő kulcs szerint:

| Helyezés | 1. | 2. | 3. | 4. | 5. | 6. | továbbiak |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Pont | 12 | 9 | 7 | 5 | 3 | 2 | 1 |

Az átvétel csak kiindulópont: minden pontszám utólag szerkeszthető.

**Holtverseny.** Azonos összpontszám esetén a játékosok ugyanazt a helyezést
kapják, és a következő helyezés annyival ugrik, ahányan holtversenyben vannak.

Az archívum a `/ranglista/archiv` címen listázza a lezárt szezonokat, a
`/ranglista/{seasonId}` pedig egy konkrét szezon táblázatát adja „Archív szezon"
jelvénnyel.

## Körlevél a tagoknak

A rendszer két alkalommal küld körlevelet minden regisztrált tagnak:

| Fajta | Mikor | Indítás |
| --- | --- | --- |
| `announced` | új versenykiírás | a létrehozó űrlap jelölőmezője (alapból bejelölve), vagy a lista „Értesítés" gombja |
| `registration_open` | a nevezés megnyílt | időzített feladat, vagy a szervezői áttekintő betöltése |

**A kétszeres kiküldés ellen adatbázis szintű védelem van**, nem alkalmazásbeli
ellenőrzés: a `competition_notifications` táblán `UNIQUE (competition_id, kind)`,
és a kiküldés **jogának lefoglalása megelőzi magát a küldést**. Aki be tudja
szúrni a sort, az küld; aki ütközik, az kihagyja. Egy „már kiment?" típusú
alkalmazásbeli ellenőrzés két párhuzamos kérésnél elbukna.

**Minden címzett külön levelet kap**, hogy senki ne lássa a többiek e-mail
címét, és egy hibás cím ne szakítsa meg a sort. A sikertelen címek a PHP
hibanaplóba kerülnek, a kiküldés eredménye (`recipient_count`, `failed_count`)
pedig a naplótáblába.

A versenyek listája oszlopban mutatja, melyik körlevél ment már ki, és melyik
indítható még.

## Kapcsolható modulok és a Közvetítés hivatkozás

A `/admin/beallitasok` oldalon állítható:

| Beállítás | Hatás |
| --- | --- |
| `forum_enabled` | a fórum menüpontja és **útvonalai**; kikapcsolva `404` |
| `ranking_enabled` | a ranglista menüpontja és **útvonalai**; kikapcsolva `404` |
| `broadcast_url` | a Közvetítés menüpont célja (külső cím) |
| `broadcast_label` | a menüpont felirata, alapértelmezés: „Közvetítés" |

**A Közvetítés szöveges beállítás, nem kapcsoló.** A menüpont akkor jelenik meg,
ha van megadott cím — így nem állhat elő „bekapcsolva, de üres" állapot, ami
törött menüpontot adna. A hivatkozás új lapon nyílik
(`target="_blank" rel="noopener noreferrer"`).

A cím sémáját a `getBroadcastLink()` ellenőrzi: csak `http` és `https` fogadható
el, tehát egy `javascript:` kezdetű érték nem mentődik el.

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

A design tokenek egyetlen helyen, a `tools/css/tokens.php` fájlban vannak definiálva. Ide tartoznak a színskálák, a tipográfia, az árnyékok és a sarokkerekítések. A stíluslapot ebből egy PHP szkript állítja elő, a nézeteket beolvasva:

```bash
php tools/build-css.php            # public/assets/css/tailwind.css előállítása
php tools/build-css.php --check    # csak ellenőrzés, írás nélkül
```

A generátor három részből áll:

| Fájl | Feladat |
| --- | --- |
| `tools/css/tokens.php` | színek, méretlépcsők, árnyékok, töréspontok |
| `tools/css/UtilityResolver.php` | osztálynév → CSS deklarációk |
| `resources/css/base.css` | alapréteg: normalizálás és a CSS változók |

A szkript végigolvassa a `src/Views/` alatti sablonokat és a `public/assets/js/` szkripteket, és csak a tényleg használt osztályokhoz készít szabályt. Ha egy `class` attribútumban olyan nevet talál, amelyet nem tud feloldani, kiírja a nevét és a fájlt, majd 1-es kilépési kóddal áll le — így egy elgépelt osztálynév nem marad csendben stílus nélkül. Új segédosztály felvételéhez a `UtilityResolver.php` feloldóit kell bővíteni.

Nincs Node, npm vagy más külső eszközlánc: a generáláshoz ugyanaz a PHP kell, ami a kiszolgálón amúgy is fut.

**Paletta.** Három skála: `billiard-green` (mély biliárdposztó zöld, 50–950), `billiard-gold` (meleg sárgaréz akcentus) és `sand` (meleg semleges alapszínek a hideg szürke helyett). Betűtípus: Inter.

**Márkajel és ikonok.** Minden megjelenés egyetlen forrásfájlból származik: `public/assets/images/logo.png`, a klub címere. Ebből készül a böngészőfül ikonja, az iOS kezdőképernyő ikonja, a webmanifest ikonjai és a közösségi megosztás előnézeti képe:

```bash
php tools/generate-icons.php
```

| Előállított fájl | Rendeltetés |
| --- | --- |
| `public/favicon.ico` | 16 + 32 + 48 px, régebbi böngészők és a Google Search |
| `public/icon-192.png`, `public/icon-512.png` | modern böngészők és a webmanifest |
| `public/apple-touch-icon.png` | 180 px, iOS kezdőképernyő |
| `public/assets/images/og-default.png` | 1200×630, Facebook és Twitter/X kártya |

Az oldalon látható márkajelet a `src/Views/partials/brand-mark.php` adja — ugyanezt a képet mutatja a fejlécben, a láblécben, a szervezői felületen és a hibaoldalakon is. Méretezése a `$brandMarkSize` változóval állítható (pl. `'w-9 h-9'`).

**Kétféle logót kezel, felismerés alapján.** A generátor a kép sarkainak átlátszóságából dönti el, melyikről van szó, mert a kettőnek ellentétes kezelés kell:

| Logófajta | Kezelés |
| --- | --- |
| Lebegő jelkép, átlátszó háttérrel (címer, embléma) | 80%-os kitöltés, körülötte levegő, alatta világos tábla |
| Teljes vásznat kitöltő kép, saját opak háttérrel | 100%-os kitöltés, a lekerekített forma maszkként vágja a sarkokat |

Ha egy teljes vásznat kitöltő logót 80%-on helyeznénk el, látható **kettős keret** lenne belőle: egy világos négyzet egy másik világos, lekerekített négyzet közepén. A felismerés miatt ez beállítás nélkül megoldódik, tehát a logó cseréje továbbra is egyetlen fájl felülírása.

Az ikonok háttere törtfehér (`sand-50`), nem a márka sötétzöldje: sötét alapon egy világos hátterű logó 16 pixelen összemosódna. A mostani logó a saját opak hátterét hozza, ezért ez a szín csak tartalék — akkor lép életbe, ha a logót átlátszó hátterű változatra cserélik.

> **A logó cseréje két lépés.** A forrásfájl (`public/assets/images/logo.png`)
> felülírása után **le kell futtatni a `php tools/generate-icons.php`
> parancsot**. Az oldalon látható márkajel azonnal frissül, mert a
> `publicAsset()` a fájl módosítási idejét fűzi a címhez; a böngészőfül
> ikonja, az iOS kezdőképernyő ikonja és a közösségi megosztás képe viszont
> **generált fájl**, nem erre a képre hivatkozik, tehát magától nem változik.
> Ez a leggyakoribb oka annak, ha a logó „nem mindenhol" cserélődik.

**Komponensosztályok.** A `public/assets/css/app.css` egy kis komponenskészletet definiál, hogy a nézetek olvashatóak maradjanak hosszú segédosztály-listák helyett. Ezt a fájlt a generátor nem írja felül, közvetlenül szerkeszthető:

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

Az adatbázis minden táblája UUID (`CHAR(36)`) elsődleges kulcsot használ, `utf8mb4_unicode_ci` collationnel, InnoDB motorral. A `registrations` táblán `UNIQUE KEY (competition_id, email)` és `UNIQUE KEY (competition_id, created_by_user_id)` akadályozza meg a dupla nevezést, a `images` és `registrations` idegen kulcsai `ON DELETE CASCADE` beállítással törlődnek.

**Egyedi megkötések mint üzleti szabály.** Három helyen az adatbázis megkötése
hordozza a szabályt, nem alkalmazásbeli ellenőrzés — mert az utóbbi két
párhuzamos kérésnél elbukik:

| Megkötés | Mit véd |
| --- | --- |
| `registrations UNIQUE (competition_id, email/created_by_user_id)` | kétszeres nevezés |
| `ranking_entries UNIQUE (competition_id, player_name)` | ugyanaz a játékos kétszer egy versenyen |
| `competition_notifications UNIQUE (competition_id, kind)` | ugyanaz a körlevél kétszer |

## Biztonság

- **SQL injection**: minden adatbázis-művelet PDO prepared statementet használ, emulált prepare kikapcsolva
- **XSS**: a nézetek az `e()` helperen keresztül escape-elnek minden kimenetet
- **Forráskód védelem**: a `src/`, `config/`, `vendor/`, `tests/` könyvtárak és a `.env` nem érhetők el HTTP-n
- **Feltöltés**: MIME típus (JPEG/PNG) és méret (max 10 MB) validáció, a feltöltési könyvtárban a PHP futtatás tiltva
- **Admin hozzáférés**: session-alapú, `password_verify()` támogatással
- **Nevezés**: a nevező neve és e-mail címe a fiókból származik, nem a beküldött adatokból — más nevében nem lehet nevezni
- **Külső hivatkozás**: a Közvetítés címénél csak `http`/`https` séma fogadható el, és a link `rel="noopener noreferrer"` attribútummal nyílik
- **Generált jelszó**: `random_int()` alapú, csak a szervezőnek jelenik meg egyszer, e-mailben nem megy ki
- **Kapcsolható modulok**: kikapcsolt állapotban az útvonalak nincsenek is regisztrálva, tehát a mentett hivatkozás sem ér el tartalmat

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

**A visszaigazoló e-mail vagy a körlevél nem érkezik meg**

Ellenőrizd a `.env` SMTP beállításait: a `MAIL_USERNAME` és a `MAIL_PASSWORD`
üresen hagyva nincs mivel hitelesíteni a kapcsolatot. A `MAIL_FROM_ADDRESS`
legyen valódi tartományú cím — a `@localhost` végűt a PHPMailer érvénytelenként
elutasítja. Fejlesztés közben a Mailtrap vagy hasonló szolgáltatás használata
ajánlott. A hibák a PHP error logba kerülnek, az `EmailService` legfeljebb 3
kísérletet tesz.

A `php tools/send-notifications.php --check` megmutatja, mennyi címzett van és
mi esedékes — küldés nélkül, tehát biztonságosan futtatható.

> Ha egy körlevél hibás SMTP beállítás mellett „ment el", a naplósor akkor is
> létrejön, és a `UNIQUE (competition_id, kind)` miatt másodszor nem küldhető
> újra. Ilyenkor a `competition_notifications` megfelelő sorát kell törölni.

**A galéria borítóképei lágyak**

A `010` migráció utáni pótlás nem futott le. Ellenőrizd
`php tools/backfill-images.php --check` paranccsal, majd futtasd le pótlás
nélküli argumentummal.

**A ranglista vagy a fórum menüpont nem látszik**

A modul ki van kapcsolva a `/admin/beallitasok` oldalon. Kikapcsolt állapotban
az útvonalai sem léteznek, ezért a közvetlen cím is `404`-et ad.

## Licenc

MIT — lásd a [LICENSE](LICENSE) fájlt.
