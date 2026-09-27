<?php

declare(strict_types=1);

/**
 * Útvonal definíciók
 * 
 * @var \App\Core\Router $router
 */

// === Keresőknek szóló végpontok ===
// Futásidőben állnak össze, hogy az új tartalom azonnal megjelenjen bennük.
// Szándékosan nem fizikai fájlok: a gyökér .htaccess a létező public/ fájlt
// előbb szolgálná ki, mint a front controllert.
$router->get('/sitemap.xml', 'SitemapController@index');
$router->get('/robots.txt', 'SitemapController@robots');

// === Publikus útvonalak ===

$router->get('/', 'HomeController@index');
$router->get('/hirek/{id}', 'NewsController@show');
$router->get('/galeria', 'GalleryController@index');
// Az "archiv" a paraméteres minta ELŐTT szerepel, hogy ne album
// azonosítóként értelmeződjön
$router->get('/galeria/archiv', 'GalleryController@archive');
$router->get('/galeria/{albumId}', 'GalleryController@show');
$router->get('/nevezes', 'CompetitionController@index');

// Tartalmi oldalak. A Rólunk, az Emlékoldal és az adatkezelési tájékoztató
// szövege a szervezői felületen szerkeszthető; a Társhonlapok statikus.
$router->get('/rolunk', 'PageController@about');
$router->get('/emlekoldal', 'PageController@memorial');
$router->get('/tarshonlapok', 'PageController@partners');
$router->get('/csapataink', 'PageController@teams');
$router->get('/adatkezeles', 'PageController@privacy');
// A nevezői lista belépés nélkül is elérhető (csak nevek, elérhetőségek nélkül)
$router->get('/nevezes/{versenyId}/nevezok', 'CompetitionController@registrants');
$router->get('/nevezes/{versenyId}', 'CompetitionController@showForm');
$router->post('/nevezes/{versenyId}', 'CompetitionController@submitRegistration');

// === Ranglista (kapcsolható modul) ===
//
// A szezon pontversenye. Kikapcsolt állapotban az útvonalai nincsenek is
// regisztrálva, ezért a router 404-et ad rájuk - ugyanaz a minta, mint a
// fórumnál.
if (rankingEnabled()) {
    $router->get('/ranglista', 'RankingController@index');
    // Az "archiv" a paraméteres minta ELŐTT szerepel, hogy ne szezon
    // azonosítóként értelmeződjön
    $router->get('/ranglista/archiv', 'RankingController@archive');
    $router->get('/ranglista/{seasonId}', 'RankingController@show');
}

// === Fórum (kommentelés vendégként és belépve) ===
//
// A fórum kapcsolható modul, és alapértelmezetten inaktív. Kikapcsolt
// állapotban az útvonalai nincsenek is regisztrálva, ezért a router 404-et
// ad rájuk. Ez erősebb védelem, mint egy nézetbeli elrejtés: a mentett
// hivatkozáson keresztül sem érhető el a tartalom.
if (forumEnabled()) {
    $router->get('/forum', 'ForumController@index');
    // A konkrét útvonalak a paraméteres minta előtt szerepelnek, hogy a
    // "/forum/uj" ne a topik azonosítójaként értelmeződjön.
    $router->get('/forum/uj', 'ForumController@createForm');
    $router->post('/forum/uj', 'ForumController@store');
    $router->post('/forum/hozzaszolas/{id}/ertekeles', 'ForumController@vote');
    $router->get('/forum/{id}', 'ForumController@show');
    $router->post('/forum/{id}/hozzaszolas', 'ForumController@storeComment');
}

// === Felhasználói fiókok (publikus, az admin belépéstől független) ===

$router->get('/regisztracio', 'AuthController@registerForm');
$router->post('/regisztracio', 'AuthController@register');
$router->get('/belepes', 'AuthController@loginForm');
$router->post('/belepes', 'AuthController@login');
$router->get('/kilepes', 'AuthController@logout');

// Fiók - saját nevezések és jelszó kezelése
$router->get('/fiok', 'AuthController@account');
$router->post('/fiok/nevezes/{id}/visszavonas', 'AuthController@deleteRegistration');
// Saját jelszó megváltoztatása: a szervezői visszaállítás után a tag
// lecserélheti a generált jelszót megjegyezhetőre
$router->post('/fiok/jelszo', 'AuthController@changePassword');

// === Admin útvonalak (session-alapú autentikáció szükséges) ===

$router->get('/admin', 'AdminController@dashboard');
$router->get('/admin/login', 'AdminController@loginForm');
$router->post('/admin/login', 'AdminController@login');
$router->get('/admin/logout', 'AdminController@logout');

// Admin - Hírkezelés
$router->get('/admin/hirek', 'AdminController@newsList');
$router->get('/admin/hirek/uj', 'AdminController@newsCreate');
$router->post('/admin/hirek/uj', 'AdminController@newsStore');
$router->get('/admin/hirek/{id}/szerkeszt', 'AdminController@newsEdit');
$router->post('/admin/hirek/{id}/szerkeszt', 'AdminController@newsUpdate');
$router->post('/admin/hirek/{id}/torol', 'AdminController@newsDelete');

// Admin - Galéria kezelés
// A "kep/..." útvonal a paraméteres minták előtt szerepel, hogy a "kep"
// szó ne album azonosítóként értelmeződjön.
$router->get('/admin/galeria', 'AdminController@albumList');
$router->post('/admin/galeria/uj', 'AdminController@albumStore');
$router->post('/admin/galeria/kep/{id}/torol', 'AdminController@imageDelete');
// Egy helyezés műveletei az azonosítója alapján, album nélkül
$router->post('/admin/galeria/helyezett/{id}/szerkeszt', 'AdminController@placementUpdate');
$router->post('/admin/galeria/helyezett/{id}/torol', 'AdminController@placementDelete');
$router->get('/admin/galeria/{id}/feltolt', 'AdminController@imageUploadForm');
$router->post('/admin/galeria/{id}/feltolt', 'AdminController@imageUpload');
$router->get('/admin/galeria/{id}/helyezettek', 'AdminController@placementList');
$router->post('/admin/galeria/{id}/helyezettek', 'AdminController@placementStore');
$router->post('/admin/galeria/{id}/boritokep', 'AdminController@albumSetCover');
$router->post('/admin/galeria/{id}/archivalas', 'AdminController@albumToggleArchived');
$router->post('/admin/galeria/{id}/szerkeszt', 'AdminController@albumUpdate');
$router->post('/admin/galeria/{id}/torol', 'AdminController@albumDelete');

// Admin - Versenykezelés
$router->get('/admin/versenyek', 'AdminController@competitionList');
$router->get('/admin/versenyek/uj', 'AdminController@competitionCreate');
$router->post('/admin/versenyek/uj', 'AdminController@competitionStore');
$router->get('/admin/versenyek/{id}/szerkeszt', 'AdminController@competitionEdit');
$router->post('/admin/versenyek/{id}/szerkeszt', 'AdminController@competitionUpdate');
$router->post('/admin/versenyek/{id}/torol', 'AdminController@competitionDelete');
// Körlevél a tagoknak: versenykiírás vagy a nevezés megnyílása
$router->post('/admin/versenyek/{id}/ertesites', 'AdminController@competitionNotify');
$router->get('/admin/versenyek/{id}/nevezesek', 'AdminController@registrationList');
$router->post('/admin/versenyek/{id}/nevezesek', 'AdminController@registrationStore');
$router->get('/admin/versenyek/{id}/export', 'AdminController@exportCsv');
// Egy konkrét nevezés törlése szervezői jogkörben
$router->post('/admin/versenyek/nevezes/{id}/torol', 'AdminController@registrationDelete');

// Admin - Szerkesztő végpontjai (JSON, a TinyMCE hívja)
$router->post('/admin/media/kep', 'AdminMediaController@uploadImage');
$router->post('/admin/media/dokumentum', 'AdminMediaController@uploadDocument');
$router->get('/admin/media/lista', 'AdminMediaController@library');
$router->get('/admin/media/hivatkozasok', 'AdminMediaController@linkList');

// Admin - Regisztrált felhasználók
// Nincs létrehozás: fiókot a látogató hoz létre a nyilvános regisztráción.
$router->get('/admin/felhasznalok', 'AdminController@userList');
$router->get('/admin/felhasznalok/{id}/szerkeszt', 'AdminController@userEdit');
$router->post('/admin/felhasznalok/{id}/szerkeszt', 'AdminController@userUpdate');
// Jelszó visszaállítása: a rendszer új jelszót generál, a szervező adja át
$router->post('/admin/felhasznalok/{id}/jelszo', 'AdminController@userResetPassword');
$router->post('/admin/felhasznalok/{id}/torol', 'AdminController@userDelete');

// Admin - Tartalmi oldalak (Rólunk, Emlékoldal, Adatkezelési tájékoztató)
// Nincs létrehozás és törlés: az oldalak fix útvonalon élnek, csak a
// tartalmuk szerkeszthető.
$router->get('/admin/oldalak', 'AdminController@pageList');
$router->get('/admin/oldalak/{id}/szerkeszt', 'AdminController@pageEdit');
$router->post('/admin/oldalak/{id}/szerkeszt', 'AdminController@pageUpdate');

// Admin - Oldalbeállítások (a kapcsolható modulok itt állíthatók)
$router->get('/admin/beallitasok', 'AdminController@settings');
$router->post('/admin/beallitasok', 'AdminController@settingsUpdate');

// Admin - Szezonok. A szezon két helyen rendez: a galéria archívumában és a
// ranglistán, ezért a kezelése önálló felület.
$router->get('/admin/szezonok', 'AdminController@seasonList');
$router->post('/admin/szezonok/uj', 'AdminController@seasonStore');
$router->post('/admin/szezonok/{id}/szerkeszt', 'AdminController@seasonUpdate');
$router->post('/admin/szezonok/{id}/aktualis', 'AdminController@seasonMakeCurrent');
$router->post('/admin/szezonok/{id}/archivalas', 'AdminController@seasonToggleArchived');
$router->post('/admin/szezonok/{id}/torol', 'AdminController@seasonDelete');

// Admin - Ranglista pontszámok
// A "pont/..." útvonalak a paraméteres verseny-minta ELŐTT szerepelnek, hogy
// a "pont" szó ne versenyazonosítóként értelmeződjön.
if (rankingEnabled()) {
    $router->get('/admin/ranglista', 'AdminController@rankingList');
    $router->post('/admin/ranglista/pont/{id}/szerkeszt', 'AdminController@rankingEntryUpdate');
    $router->post('/admin/ranglista/pont/{id}/torol', 'AdminController@rankingEntryDelete');
    $router->get('/admin/ranglista/{competitionId}', 'AdminController@rankingEdit');
    $router->post('/admin/ranglista/{competitionId}', 'AdminController@rankingEntryStore');
    $router->post('/admin/ranglista/{competitionId}/atvetel', 'AdminController@rankingImport');
}

// Admin - Fórum moderálás
// Csak akkor elérhető, ha a fórum modul aktív: kikapcsolt fórumnál nincs
// mit moderálni, a beállítások oldalon viszont bármikor visszakapcsolható.
if (forumEnabled()) {
    // A konkrét útvonalak a paraméteres minták előtt szerepelnek.
    $router->get('/admin/forum', 'AdminController@topicList');
    $router->get('/admin/forum/hozzaszolasok', 'AdminController@commentList');
    $router->post('/admin/forum/hozzaszolas/{id}/elrejt', 'AdminController@commentToggleHidden');
    $router->post('/admin/forum/hozzaszolas/{id}/torol', 'AdminController@commentDelete');
    $router->post('/admin/forum/{id}/elrejt', 'AdminController@topicToggleHidden');
    $router->post('/admin/forum/{id}/lezar', 'AdminController@topicToggleLocked');
    $router->post('/admin/forum/{id}/torol', 'AdminController@topicDelete');
}
