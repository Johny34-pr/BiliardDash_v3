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
$router->get('/nevezes/{versenyId}', 'CompetitionController@showForm');
$router->post('/nevezes/{versenyId}', 'CompetitionController@submitRegistration');

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
