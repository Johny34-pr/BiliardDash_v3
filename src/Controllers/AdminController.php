<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Database;
use App\Core\Session;
use App\Services\CommentService;
use App\Services\NewsService;
use App\Services\GalleryService;
use App\Services\CompetitionService;
use App\Services\ImageService;
use App\Services\TopicService;
use App\Services\ValidationService;

class AdminController
{
    private NewsService $newsService;
    private GalleryService $galleryService;
    private CompetitionService $competitionService;
    private ValidationService $validationService;
    private CommentService $commentService;
    private TopicService $topicService;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->newsService = new NewsService($db);
        $this->galleryService = new GalleryService($db, new ImageService());
        $mailConfig = require __DIR__ . '/../../config/mail.php';
        $emailService = new \App\Services\EmailService($mailConfig);
        $this->competitionService = new CompetitionService($db, $emailService);
        $this->validationService = new ValidationService();
        $this->commentService = new CommentService($db);
        $this->topicService = new TopicService($db, $this->commentService);
    }

    /**
     * Admin hozzáférés ellenőrzése - ha nincs bejelentkezve, átirányít a login oldalra
     */
    private function requireAdmin(): void
    {
        if (!Session::isAdmin()) {
            redirect('/admin/login');
        }
    }

    /**
     * Bejelentkező űrlap megjelenítése
     */
    public function loginForm(): void
    {
        $error = Session::getFlash('login_error');
        $pageTitle = 'Admin Belépés - Magyar Biliárd';

        require __DIR__ . '/../Views/admin/login.php';
    }

    /**
     * Bejelentkezés feldolgozása
     */
    public function login(): void
    {
        $password = $_POST['password'] ?? '';

        if (Session::login($password)) {
            redirect('/admin');
        }

        Session::flash('login_error', 'Hibás jelszó!');
        redirect('/admin/login');
    }

    /**
     * Kijelentkezés
     */
    public function logout(): void
    {
        Session::logout();
        redirect('/admin/login');
    }

    /**
     * Admin dashboard - áttekintő oldal
     */
    public function dashboard(): void
    {
        $this->requireAdmin();

        try {
            $newsCount = count($this->newsService->getLatestNews(1000));
            $albumCount = count($this->galleryService->getAlbums());
            $competitionCount = count($this->competitionService->getOpenCompetitions());
        } catch (\Throwable $e) {
            error_log('[AdminController] Dashboard hiba: ' . $e->getMessage());
            $newsCount = 0;
            $albumCount = 0;
            $competitionCount = 0;
        }

        $pageTitle = 'Admin Dashboard - Magyar Biliárd';

        ob_start();
        require __DIR__ . '/../Views/admin/dashboard.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    // =========================================================================
    // Hírkezelés
    // =========================================================================

    /**
     * Hírek listázása az admin felületen
     */
    public function newsList(): void
    {
        $this->requireAdmin();

        $news = $this->newsService->getLatestNews(1000);
        $pageTitle = 'Hírek kezelése - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/news/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Új hír létrehozás űrlap megjelenítése
     */
    public function newsCreate(): void
    {
        $this->requireAdmin();

        $errors = [];
        $data = ['title' => '', 'content' => ''];
        $pageTitle = 'Új hír - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/news/create.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Új hír mentése
     */
    public function newsStore(): void
    {
        $this->requireAdmin();

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'content' => $_POST['content'] ?? '',
        ];

        $validator = $this->validationService->validateNews($data);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $pageTitle = 'Új hír - Admin';

            ob_start();
            require __DIR__ . '/../Views/admin/news/create.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/admin.php';
            return;
        }

        $this->newsService->createNews($data['title'], $data['content']);
        Session::flash('success', 'Hír sikeresen létrehozva!');
        redirect('/admin/hirek');
    }

    /**
     * Hír szerkesztés űrlap megjelenítése
     */
    public function newsEdit(string $id): void
    {
        $this->requireAdmin();

        $news = $this->newsService->getNewsById($id);

        if ($news === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $errors = [];
        $data = [
            'title' => $news['title'],
            'content' => $news['content'],
        ];
        $pageTitle = 'Hír szerkesztése - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/news/edit.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Hír frissítése
     */
    public function newsUpdate(string $id): void
    {
        $this->requireAdmin();

        $news = $this->newsService->getNewsById($id);

        if ($news === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'content' => $_POST['content'] ?? '',
        ];

        $validator = $this->validationService->validateNews($data);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $pageTitle = 'Hír szerkesztése - Admin';

            ob_start();
            require __DIR__ . '/../Views/admin/news/edit.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/admin.php';
            return;
        }

        $this->newsService->updateNews($id, $data['title'], $data['content']);
        Session::flash('success', 'Hír sikeresen frissítve!');
        redirect('/admin/hirek');
    }

    /**
     * Hír törlése
     */
    public function newsDelete(string $id): void
    {
        $this->requireAdmin();

        $this->newsService->deleteNews($id);
        Session::flash('success', 'Hír sikeresen törölve!');
        redirect('/admin/hirek');
    }

    // =========================================================================
    // Galéria kezelés
    // =========================================================================

    /**
     * Albumok listázása az admin felületen (képekkel együtt)
     */
    public function albumList(): void
    {
        $this->requireAdmin();

        $albums = $this->galleryService->getAlbums();

        // Minden albumhoz betöltjük a képeket
        $albumImages = [];
        foreach ($albums as $album) {
            $albumImages[$album['id']] = $this->galleryService->getAlbumImages($album['id']);
        }

        $errors = Session::getFlash('album_errors') ?: [];
        // Átnevezési hibák albumonként: [albumId => hibaüzenet]
        $renameErrors = Session::getFlash('rename_errors') ?: [];
        $pageTitle = 'Galéria kezelése - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/gallery/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Új album létrehozása
     */
    public function albumStore(): void
    {
        $this->requireAdmin();

        $data = [
            'name' => $_POST['name'] ?? '',
        ];

        $validator = $this->validationService->validateAlbum($data);

        if (!$validator->isValid()) {
            Session::flash('album_errors', $validator->getErrors());
            Session::flash('error', $validator->getErrors()['name'] ?? 'Érvénytelen album név.');
            redirect('/admin/galeria');
            return;
        }

        $name = trim($data['name']);
        $this->galleryService->createAlbum($name);
        Session::flash('success', 'Album sikeresen létrehozva!');
        redirect('/admin/galeria');
    }

    /**
     * Album átnevezése
     *
     * A hibát az albumhoz kötve flasheljük (album_errors[albumId]), így a
     * lista több album közül is a megfelelő űrlapnál jelzi a problémát.
     */
    public function albumUpdate(string $id): void
    {
        $this->requireAdmin();

        $data = ['name' => $_POST['name'] ?? ''];
        $validator = $this->validationService->validateAlbum($data);

        if (!$validator->isValid()) {
            Session::flash('rename_errors', [$id => $validator->getErrors()['name'] ?? 'Érvénytelen album név.']);
            Session::flash('error', $validator->getErrors()['name'] ?? 'Érvénytelen album név.');
            redirect('/admin/galeria');
            return;
        }

        try {
            $this->galleryService->renameAlbum($id, trim($data['name']));
            Session::flash('success', 'Album sikeresen átnevezve!');
        } catch (\Throwable $e) {
            error_log('[AdminController] Album átnevezés hiba: ' . $e->getMessage());
            Session::flash('error', 'Hiba történt az album átnevezése során.');
        }

        redirect('/admin/galeria');
    }

    /**
     * Album törlése a benne lévő képekkel együtt
     */
    public function albumDelete(string $id): void
    {
        $this->requireAdmin();

        try {
            $name = $this->galleryService->deleteAlbum($id);
            Session::flash('success', 'A(z) "' . $name . '" album és a benne lévő képek törölve!');
        } catch (\Throwable $e) {
            error_log('[AdminController] Album törlés hiba: ' . $e->getMessage());
            Session::flash('error', 'Hiba történt az album törlése során.');
        }

        redirect('/admin/galeria');
    }

    /**
     * Képfeltöltő űrlap megjelenítése
     */
    public function imageUploadForm(string $id): void
    {
        $this->requireAdmin();

        $db = Database::getConnection();
        $albumModel = new \App\Models\Album($db);
        $album = $albumModel->findById($id);

        if ($album === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $errors = Session::getFlash('upload_errors') ?: [];
        // A címet nyersen adjuk át: az escape-elés a head partial dolga
        $pageTitle = 'Kép feltöltés: ' . $album['name'] . ' - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/gallery/upload.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Kép feltöltése egy albumba
     */
    public function imageUpload(string $id): void
    {
        $this->requireAdmin();

        $file = $_FILES['image'] ?? null;

        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Nincs feltöltött fájl vagy hiba történt a feltöltés során.');
            redirect("/admin/galeria/{$id}/feltolt");
            return;
        }

        $validator = $this->validationService->validateImageUpload($file);

        if (!$validator->isValid()) {
            Session::flash('upload_errors', $validator->getErrors());
            Session::flash('error', implode(' ', array_values($validator->getErrors())));
            redirect("/admin/galeria/{$id}/feltolt");
            return;
        }

        try {
            $this->galleryService->uploadImage($id, $file);
            Session::flash('success', 'Kép sikeresen feltöltve!');
            redirect('/admin/galeria');
        } catch (\Throwable $e) {
            error_log('[AdminController] Kép feltöltés hiba: ' . $e->getMessage());
            Session::flash('error', 'Hiba történt a kép feltöltése során.');
            redirect("/admin/galeria/{$id}/feltolt");
        }
    }

    /**
     * Kép törlése (fájlok + DB rekord)
     */
    public function imageDelete(string $id): void
    {
        $this->requireAdmin();

        try {
            $this->galleryService->deleteImage($id);
            Session::flash('success', 'Kép sikeresen törölve!');
        } catch (\Throwable $e) {
            error_log('[AdminController] Kép törlés hiba: ' . $e->getMessage());
            Session::flash('error', 'Hiba történt a kép törlése során.');
        }

        redirect('/admin/galeria');
    }

    // =========================================================================
    // Versenykezelés
    // =========================================================================

    /**
     * Versenyek listázása az admin felületen
     */
    public function competitionList(): void
    {
        $this->requireAdmin();

        $competitions = $this->competitionService->getAllCompetitions();
        $pageTitle = 'Versenyek kezelése - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/competitions/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Új verseny létrehozás űrlap megjelenítése
     */
    public function competitionCreate(): void
    {
        $this->requireAdmin();

        $errors = [];
        $data = [
            'name' => '',
            'date' => '',
            'venue' => '',
            'registrationOpensAt' => '',
            'registrationDeadline' => '',
        ];
        $pageTitle = 'Új verseny - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/competitions/create.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Új verseny mentése
     */
    public function competitionStore(): void
    {
        $this->requireAdmin();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'date' => trim($_POST['date'] ?? ''),
            'venue' => trim($_POST['venue'] ?? ''),
            // Üresen hagyható: ilyenkor a nevezés azonnal nyitott
            'registrationOpensAt' => trim($_POST['registrationOpensAt'] ?? ''),
            'registrationDeadline' => trim($_POST['registrationDeadline'] ?? ''),
        ];

        $validator = $this->validationService->validateCompetition($data);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $pageTitle = 'Új verseny - Admin';

            ob_start();
            require __DIR__ . '/../Views/admin/competitions/create.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/admin.php';
            return;
        }

        $this->competitionService->createCompetition($data);
        Session::flash('success', 'Verseny sikeresen létrehozva!');
        redirect('/admin/versenyek');
    }

    /**
     * Verseny szerkesztés űrlap megjelenítése
     */
    public function competitionEdit(string $id): void
    {
        $this->requireAdmin();

        $competition = $this->competitionService->getCompetitionById($id);

        if ($competition === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $errors = [];
        $data = [
            'name' => $competition['name'],
            'date' => $competition['date'],
            'venue' => $competition['venue'],
            // A nyitódátum elhagyható, ezért csak akkor töltjük elő, ha van
            'registrationOpensAt' => $competition['registration_opens_at'] !== null
                ? date('Y-m-d\TH:i', strtotime($competition['registration_opens_at']))
                : '',
            'registrationDeadline' => date('Y-m-d\TH:i', strtotime($competition['registration_deadline'])),
        ];
        $pageTitle = 'Verseny szerkesztése - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/competitions/edit.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Verseny frissítése
     */
    public function competitionUpdate(string $id): void
    {
        $this->requireAdmin();

        $competition = $this->competitionService->getCompetitionById($id);

        if ($competition === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'date' => trim($_POST['date'] ?? ''),
            'venue' => trim($_POST['venue'] ?? ''),
            // Üresen hagyható: ilyenkor a nevezés azonnal nyitott
            'registrationOpensAt' => trim($_POST['registrationOpensAt'] ?? ''),
            'registrationDeadline' => trim($_POST['registrationDeadline'] ?? ''),
        ];

        $validator = $this->validationService->validateCompetition($data);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $pageTitle = 'Verseny szerkesztése - Admin';

            ob_start();
            require __DIR__ . '/../Views/admin/competitions/edit.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/admin.php';
            return;
        }

        $this->competitionService->updateCompetition($id, $data);
        Session::flash('success', 'Verseny sikeresen frissítve!');
        redirect('/admin/versenyek');
    }

    /**
     * Verseny törlése (kaszkád: nevezésekkel együtt)
     */
    public function competitionDelete(string $id): void
    {
        $this->requireAdmin();

        $this->competitionService->deleteCompetition($id);
        Session::flash('success', 'Verseny és a hozzá tartozó nevezések sikeresen törölve!');
        redirect('/admin/versenyek');
    }

    /**
     * Egy verseny nevezéseinek listázása
     */
    public function registrationList(string $id): void
    {
        $this->requireAdmin();

        $competition = $this->competitionService->getCompetitionById($id);

        if ($competition === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $registrations = $this->competitionService->getRegistrations($id);
        $pageTitle = 'Nevezések: ' . $competition['name'] . ' - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/competitions/registrations.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Nevezések CSV exportja
     */
    public function exportCsv(string $id): void
    {
        $this->requireAdmin();

        $competition = $this->competitionService->getCompetitionById($id);

        if ($competition === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $csv = $this->competitionService->exportRegistrationsCsv($id);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="nevezesek_' . $id . '.csv"');
        echo $csv;
    }

    /**
     * Nevezés törlése szervezői jogkörben.
     *
     * A felhasználói visszavonással szemben itt a határidő lejárta után is
     * lehetséges a törlés, mert lemondást vagy hibás nevezést utólag is
     * rendezni kell.
     */
    public function registrationDelete(string $id): void
    {
        $this->requireAdmin();

        try {
            $competitionId = $this->competitionService->deleteRegistrationAsAdmin($id);
            Session::flash('success', 'A nevezés törölve.');
            redirect('/admin/versenyek/' . $competitionId . '/nevezesek');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
            redirect('/admin/versenyek');
        }
    }

    // =====================================================================
    // Fórum moderálás
    // =====================================================================

    /**
     * Topikok listája moderáláshoz (az elrejtettekkel együtt).
     */
    public function topicList(): void
    {
        $this->requireAdmin();

        $topics = $this->topicService->getTopicsForModeration();
        $pageTitle = 'Fórum topikok - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/forum/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Topik elrejtése vagy visszaállítása.
     */
    public function topicToggleHidden(string $id): void
    {
        $this->requireAdmin();

        try {
            $hidden = $this->topicService->toggleHidden($id);
            Session::flash('success', $hidden
                ? 'A topik elrejtve a fórumról.'
                : 'A topik újra látható a fórumon.');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('/admin/forum');
    }

    /**
     * Topik lezárása vagy újranyitása.
     *
     * A lezárt topik olvasható marad, de nem fogad új hozzászólást.
     */
    public function topicToggleLocked(string $id): void
    {
        $this->requireAdmin();

        try {
            $locked = $this->topicService->toggleLocked($id);
            Session::flash('success', $locked
                ? 'A topik lezárva, nem fogad új hozzászólást.'
                : 'A topik újra megnyitva.');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('/admin/forum');
    }

    /**
     * Topik végleges törlése a hozzászólásaival együtt.
     */
    public function topicDelete(string $id): void
    {
        $this->requireAdmin();

        try {
            $this->topicService->deleteTopic($id);
            Session::flash('success', 'A topik és a hozzászólásai véglegesen törölve.');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('/admin/forum');
    }

    /**
     * Hozzászólások listája moderáláshoz (az elrejtettekkel együtt).
     */
    public function commentList(): void
    {
        $this->requireAdmin();

        $comments = $this->commentService->getCommentsForModeration();
        $pageTitle = 'Fórum hozzászólások - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/forum/comments.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Hozzászólás elrejtése vagy visszaállítása.
     *
     * Az elrejtés visszavonható, ezért ez az elsődleges moderálási eszköz.
     */
    public function commentToggleHidden(string $id): void
    {
        $this->requireAdmin();

        try {
            // A topik azonosítója a törlés/elrejtés utáni számlálófrissítéshez
            $comment = $this->commentService->getCommentById($id);

            $hidden = $this->commentService->toggleHidden($id);

            if ($comment !== null) {
                $this->topicService->refreshActivity($comment['topic_id']);
            }

            Session::flash('success', $hidden
                ? 'A hozzászólás elrejtve a fórumról.'
                : 'A hozzászólás újra látható a fórumon.');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('/admin/forum/hozzaszolasok');
    }

    /**
     * Hozzászólás végleges törlése.
     */
    public function commentDelete(string $id): void
    {
        $this->requireAdmin();

        try {
            $comment = $this->commentService->getCommentById($id);

            $this->commentService->deleteComment($id);

            if ($comment !== null) {
                $this->topicService->refreshActivity($comment['topic_id']);
            }

            Session::flash('success', 'A hozzászólás véglegesen törölve.');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        }

        redirect('/admin/forum/hozzaszolasok');
    }
}
