<?php

declare(strict_types=1);

/**
 * Útvonal definíciók
 * 
 * @var \App\Core\Router $router
 */

// === Publikus útvonalak ===

$router->get('/', 'HomeController@index');
$router->get('/hirek/{id}', 'NewsController@show');
$router->get('/galeria', 'GalleryController@index');
$router->get('/galeria/{albumId}', 'GalleryController@show');
$router->get('/nevezes', 'CompetitionController@index');
// A nevezői lista belépés nélkül is elérhető (csak nevek, elérhetőségek nélkül)
$router->get('/nevezes/{versenyId}/nevezok', 'CompetitionController@registrants');
$router->get('/nevezes/{versenyId}', 'CompetitionController@showForm');
$router->post('/nevezes/{versenyId}', 'CompetitionController@submitRegistration');

// === Fórum (kommentelés vendégként és belépve) ===

$router->get('/forum', 'ForumController@index');
// A konkrét útvonalak a paraméteres minta előtt szerepelnek, hogy a
// "/forum/uj" ne a topik azonosítójaként értelmeződjön.
$router->get('/forum/uj', 'ForumController@createForm');
$router->post('/forum/uj', 'ForumController@store');
$router->post('/forum/hozzaszolas/{id}/ertekeles', 'ForumController@vote');
$router->get('/forum/{id}', 'ForumController@show');
$router->post('/forum/{id}/hozzaszolas', 'ForumController@storeComment');

// === Felhasználói fiókok (publikus, az admin belépéstől független) ===

$router->get('/regisztracio', 'AuthController@registerForm');
$router->post('/regisztracio', 'AuthController@register');
$router->get('/belepes', 'AuthController@loginForm');
$router->post('/belepes', 'AuthController@login');
$router->get('/kilepes', 'AuthController@logout');

// Fiók - saját nevezések kezelése
$router->get('/fiok', 'AuthController@account');
$router->post('/fiok/nevezes/{id}/visszavonas', 'AuthController@deleteRegistration');

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
$router->get('/admin/galeria', 'AdminController@albumList');
$router->post('/admin/galeria/uj', 'AdminController@albumStore');
$router->get('/admin/galeria/{id}/feltolt', 'AdminController@imageUploadForm');
$router->post('/admin/galeria/{id}/feltolt', 'AdminController@imageUpload');
$router->post('/admin/galeria/kep/{id}/torol', 'AdminController@imageDelete');

// Admin - Versenykezelés
$router->get('/admin/versenyek', 'AdminController@competitionList');
$router->get('/admin/versenyek/uj', 'AdminController@competitionCreate');
$router->post('/admin/versenyek/uj', 'AdminController@competitionStore');
$router->get('/admin/versenyek/{id}/szerkeszt', 'AdminController@competitionEdit');
$router->post('/admin/versenyek/{id}/szerkeszt', 'AdminController@competitionUpdate');
$router->post('/admin/versenyek/{id}/torol', 'AdminController@competitionDelete');
$router->get('/admin/versenyek/{id}/nevezesek', 'AdminController@registrationList');
$router->get('/admin/versenyek/{id}/export', 'AdminController@exportCsv');
// Egy konkrét nevezés törlése szervezői jogkörben
$router->post('/admin/versenyek/nevezes/{id}/torol', 'AdminController@registrationDelete');

// Admin - Fórum moderálás
// A konkrét útvonalak a paraméteres minták előtt szerepelnek.
$router->get('/admin/forum', 'AdminController@topicList');
$router->get('/admin/forum/hozzaszolasok', 'AdminController@commentList');
$router->post('/admin/forum/hozzaszolas/{id}/elrejt', 'AdminController@commentToggleHidden');
$router->post('/admin/forum/hozzaszolas/{id}/torol', 'AdminController@commentDelete');
$router->post('/admin/forum/{id}/elrejt', 'AdminController@topicToggleHidden');
$router->post('/admin/forum/{id}/lezar', 'AdminController@topicToggleLocked');
$router->post('/admin/forum/{id}/torol', 'AdminController@topicDelete');
