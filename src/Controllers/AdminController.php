<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Database;
use App\Core\Session;
use App\Models\CompetitionNotification;
use App\Services\CommentService;
use App\Services\NewsService;
use App\Services\GalleryService;
use App\Services\CompetitionService;
use App\Services\AuthService;
use App\Services\EmailService;
use App\Services\ImageService;
use App\Services\PageService;
use App\Services\NotificationService;
use App\Services\RankingService;
use App\Services\RememberMeService;
use App\Services\SeasonService;
use App\Services\SettingsService;
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
    private SettingsService $settingsService;
    private PageService $pageService;
    private AuthService $authService;
    private RememberMeService $rememberMeService;
    private SeasonService $seasonService;
    private EmailService $emailService;
    private NotificationService $notificationService;
    private RankingService $rankingService;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->newsService = new NewsService($db);
        $this->galleryService = new GalleryService($db, new ImageService());
        $mailConfig = require __DIR__ . '/../../config/mail.php';
        $this->emailService = new EmailService($mailConfig);
        $this->competitionService = new CompetitionService($db, $this->emailService);
        $this->validationService = new ValidationService();
        $this->commentService = new CommentService($db);
        $this->topicService = new TopicService($db, $this->commentService);
        $this->settingsService = new SettingsService($db);
        $this->pageService = new PageService($db);
        $this->authService = new AuthService($db);
        $this->rememberMeService = new RememberMeService($db);
        $this->seasonService = new SeasonService($db);
        $this->notificationService = new NotificationService($db, $this->emailService);
        $this->rankingService = new RankingService($db);
    }

    // =========================================================================
    // Oldalbeállítások
    // =========================================================================

    /**
     * Kapcsolható funkciók áttekintése.
     */
    public function settings(): void
    {
        $this->requireAdmin();

        $forumEnabled = $this->settingsService->isForumEnabled();
        $rankingEnabled = $this->settingsService->isRankingEnabled();
        $broadcastUrl = (string) $this->settingsService->get(SettingsService::BROADCAST_URL, '');
        $broadcastLabel = (string) $this->settingsService->get(SettingsService::BROADCAST_LABEL, '');
        $pageTitle = 'Beállítások - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/settings.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Kapcsolható funkciók mentése.
     *
     * A jelölőmezők hiányzó értéke kikapcsolt állapotot jelent, ezért az
     * isset() vizsgálat elég: a böngésző a bejelöletlen mezőt nem küldi el.
     */
    public function settingsUpdate(): void
    {
        $this->requireAdmin();

        $forumEnabled = isset($_POST['forum_enabled']);
        $rankingEnabled = isset($_POST['ranking_enabled']);
        $forumWasEnabled = $this->settingsService->isForumEnabled();
        $rankingWasEnabled = $this->settingsService->isRankingEnabled();

        $this->settingsService->setEnabled(SettingsService::FORUM_ENABLED, $forumEnabled);
        $this->settingsService->setEnabled(SettingsService::RANKING_ENABLED, $rankingEnabled);

        // A közvetítés címe szöveges beállítás: az üres érték azt jelenti,
        // hogy a menüpont nem jelenik meg
        $broadcastUrl = trim($_POST['broadcast_url'] ?? '');
        $broadcastLabel = trim($_POST['broadcast_label'] ?? '');

        // Érvénytelen sémát nem mentünk el: a menüben törött vagy veszélyes
        // hivatkozás lenne belőle. A hibát jelezzük, a többi beállítás
        // viszont mentődik - a szervező munkája nem veszik el.
        $urlError = null;

        if ($broadcastUrl !== '' && preg_match('#^https?://#i', $broadcastUrl) !== 1) {
            $urlError = 'A közvetítés címe nem került mentésre: http:// vagy https:// kezdettel add meg.';
        } else {
            $this->settingsService->setValue(SettingsService::BROADCAST_URL, $broadcastUrl);
        }

        $this->settingsService->setValue(SettingsService::BROADCAST_LABEL, $broadcastLabel);

        // A modulok állapotváltozását külön kimondjuk: annak van a
        // legnagyobb következménye, mert menüpontok és útvonalak tűnnek el
        $changes = [];

        if ($forumEnabled !== $forumWasEnabled) {
            $changes[] = $forumEnabled
                ? 'A fórum bekapcsolva.'
                : 'A fórum kikapcsolva, a tartalma megmaradt.';
        }

        if ($rankingEnabled !== $rankingWasEnabled) {
            $changes[] = $rankingEnabled
                ? 'A ranglista bekapcsolva.'
                : 'A ranglista kikapcsolva, a pontszámok megmaradtak.';
        }

        Session::flash('success', $changes === []
            ? 'A beállítások mentve.'
            : implode(' ', $changes));

        if ($urlError !== null) {
            Session::flash('error', $urlError);
        }

        redirect('/admin/beallitasok');
    }

    // =========================================================================
    // Szezonok
    // =========================================================================

    /**
     * Szezonok kezelése: lista, létrehozás, szerkesztés egy oldalon.
     *
     * A szezon két helyen rendez - a galéria archívumában és a ranglistán -,
     * ezért önálló felületet kap. Egy oldal elég hozzá: kevés szezon van, és
     * a műveletek (aktuálisra jelölés, archiválás) egy kattintásosak.
     */
    public function seasonList(): void
    {
        $this->requireAdmin();

        $seasons = $this->seasonService->getAll();
        $errors = Session::getFlash('season_errors') ?: [];
        $editId = $_GET['szerkeszt'] ?? null;
        $pageTitle = 'Szezonok - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/seasons/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Új szezon létrehozása.
     */
    public function seasonStore(): void
    {
        $this->requireAdmin();

        $name = trim($_POST['name'] ?? '');
        $startsOn = trim($_POST['starts_on'] ?? '');

        if ($name === '') {
            Session::flash('season_errors', ['name' => 'A szezon nevének megadása kötelező.']);
            redirect('/admin/szezonok');
            return;
        }

        try {
            $this->seasonService->create($name, $startsOn, isset($_POST['is_current']));
            Session::flash('success', 'A(z) "' . $name . '" szezon létrehozva.');
        } catch (AppException $e) {
            Session::flash('season_errors', ['name' => $e->getMessage()]);
        } catch (\Throwable $e) {
            error_log('[AdminController] Szezon létrehozási hiba: ' . $e->getMessage());
            Session::flash('error', 'A szezon létrehozása nem sikerült.');
        }

        redirect('/admin/szezonok');
    }

    /**
     * Szezon adatainak módosítása.
     */
    public function seasonUpdate(string $id): void
    {
        $this->requireAdmin();

        $name = trim($_POST['name'] ?? '');
        $startsOn = trim($_POST['starts_on'] ?? '');

        if ($name === '') {
            Session::flash('season_errors', [$id => 'A szezon nevének megadása kötelező.']);
            redirect('/admin/szezonok?szerkeszt=' . urlencode($id));
            return;
        }

        try {
            $this->seasonService->update($id, $name, $startsOn);
            Session::flash('success', 'A szezon adatai mentve.');
            redirect('/admin/szezonok');
            return;
        } catch (AppException $e) {
            Session::flash('season_errors', [$id => $e->getMessage()]);
        } catch (\Throwable $e) {
            error_log('[AdminController] Szezon mentési hiba: ' . $e->getMessage());
            Session::flash('error', 'A szezon mentése nem sikerült.');
        }

        redirect('/admin/szezonok?szerkeszt=' . urlencode($id));
    }

    /**
     * Az aktuális szezon kijelölése.
     */
    public function seasonMakeCurrent(string $id): void
    {
        $this->requireAdmin();

        try {
            $this->seasonService->makeCurrent($id);
            $season = $this->seasonService->getById($id);
            Session::flash('success', 'A(z) "' . ($season['name'] ?? '') . '" szezon lett az aktuális.');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AdminController] Szezon kijelölési hiba: ' . $e->getMessage());
            Session::flash('error', 'A szezon kijelölése nem sikerült.');
        }

        redirect('/admin/szezonok');
    }

    /**
     * Szezon archiválása vagy visszahelyezése.
     *
     * Az archivált szezon ranglistája a nyilvános archívumba kerül. A rejtett
     * mező a kívánt ÁLLAPOTOT küldi, nem a váltás tényét, így egy kétszer
     * elküldött űrlap sem fordítja vissza.
     */
    public function seasonToggleArchived(string $id): void
    {
        $this->requireAdmin();

        $archived = ($_POST['archived'] ?? '') === '1';

        try {
            $this->seasonService->setArchived($id, $archived);
            Session::flash('success', $archived
                ? 'A szezon archiválva: a ranglistája az archívumba került.'
                : 'A szezon visszakerült az aktív szezonok közé.');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AdminController] Szezon archiválási hiba: ' . $e->getMessage());
            Session::flash('error', 'Az archiválás nem sikerült.');
        }

        redirect('/admin/szezonok');
    }

    /**
     * Szezon törlése.
     *
     * A hozzá tartozó versenyek és albumok megmaradnak, csak szezon
     * nélkülivé válnak - erre a nézet is figyelmeztet.
     */
    public function seasonDelete(string $id): void
    {
        $this->requireAdmin();

        try {
            $season = $this->seasonService->getById($id);
            $this->seasonService->delete($id);
            Session::flash(
                'success',
                'A(z) "' . ($season['name'] ?? '') . '" szezon törölve. A versenyek és albumok megmaradtak.'
            );
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AdminController] Szezon törlési hiba: ' . $e->getMessage());
            Session::flash('error', 'A szezon törlése nem sikerült.');
        }

        redirect('/admin/szezonok');
    }

    // =========================================================================
    // Ranglista pontszámok
    // =========================================================================

    /**
     * Versenyek listája a pontszámok szerkesztéséhez.
     *
     * A ranglista számolt eredmény, ezért itt nincs "újraszámolás" művelet:
     * a pontszám mentése után a nyilvános állás már a friss értéket mutatja.
     */
    public function rankingList(): void
    {
        $this->requireAdmin();

        $competitions = $this->competitionService->getAllCompetitions();

        // Versenyenként a felvitt pontszámok száma, hogy a lista mutassa,
        // hol van még dolga a szervezőnek
        $entryCounts = [];

        foreach ($competitions as $competition) {
            $entryCounts[$competition['id']] = count(
                $this->rankingService->getCompetitionEntries($competition['id'])
            );
        }

        $currentSeason = $this->seasonService->getCurrent();
        $pageTitle = 'Ranglista pontszámok - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/ranking/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Egy verseny pontszámainak szerkesztése.
     */
    public function rankingEdit(string $competitionId): void
    {
        $this->requireAdmin();

        $competition = $this->competitionService->getCompetitionById($competitionId);

        if ($competition === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $entries = $this->rankingService->getCompetitionEntries($competitionId);
        $errors = Session::getFlash('ranking_errors') ?: [];
        $data = Session::getFlash('ranking_data') ?: [];
        $editId = $_GET['szerkeszt'] ?? null;

        // Szerkesztésre csak ehhez a versenyhez tartozó pontszám nyitható
        $editing = $editId !== null ? $this->rankingService->getEntry($editId) : null;

        if ($editing !== null && $editing['competition_id'] !== $competitionId) {
            $editing = null;
        }

        // A nevezők neve segít a felvitelben: a versenyen indulók listája
        $registrants = array_column(
            $this->competitionService->getPublicRegistrants($competitionId),
            'full_name'
        );

        // Albumok az "átvétel a helyezettekből" művelethez. Csak azok
        // hasznosak, amelyekben van felvitt dobogó.
        $albums = array_values(array_filter(
            $this->galleryService->getAlbumsForListing(null),
            static fn(array $album): bool => $album['placements'] !== []
        ));

        $pageTitle = 'Pontszámok: ' . $competition['name'] . ' - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/ranking/edit.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Új pontszám felvitele egy versenyhez.
     */
    public function rankingEntryStore(string $competitionId): void
    {
        $this->requireAdmin();

        $target = '/admin/ranglista/' . $competitionId;

        $data = [
            'playerName' => trim($_POST['player_name'] ?? ''),
            'points' => trim($_POST['points'] ?? ''),
            'place' => trim($_POST['place'] ?? ''),
        ];

        $errors = $this->validateRankingEntry($data);

        if ($errors !== []) {
            Session::flash('ranking_errors', $errors);
            Session::flash('ranking_data', $data);
            redirect($target);
            return;
        }

        try {
            $this->rankingService->addEntry(
                $competitionId,
                $data['playerName'],
                (int) $data['points'],
                $data['place'] === '' ? null : (int) $data['place']
            );

            Session::flash('success', $data['playerName'] . ' pontszáma felvéve.');
        } catch (AppException $e) {
            Session::flash('ranking_errors', ['playerName' => $e->getMessage()]);
            Session::flash('ranking_data', $data);
        } catch (\Throwable $e) {
            error_log('[AdminController] Pontszám felvitel hiba: ' . $e->getMessage());
            Session::flash('error', 'A pontszám felvitele nem sikerült.');
        }

        redirect($target);
    }

    /**
     * Pontszám módosítása.
     */
    public function rankingEntryUpdate(string $id): void
    {
        $this->requireAdmin();

        $entry = $this->rankingService->getEntry($id);

        if ($entry === null) {
            Session::flash('error', 'A pontszám nem található.');
            redirect('/admin/ranglista');
            return;
        }

        $target = '/admin/ranglista/' . $entry['competition_id'];

        $data = [
            'playerName' => trim($_POST['player_name'] ?? ''),
            'points' => trim($_POST['points'] ?? ''),
            'place' => trim($_POST['place'] ?? ''),
        ];

        $errors = $this->validateRankingEntry($data);

        if ($errors !== []) {
            Session::flash('ranking_errors', $errors);
            Session::flash('ranking_data', $data);
            redirect($target . '?szerkeszt=' . urlencode($id));
            return;
        }

        try {
            $this->rankingService->updateEntry(
                $id,
                $data['playerName'],
                (int) $data['points'],
                $data['place'] === '' ? null : (int) $data['place']
            );

            Session::flash('success', 'A pontszám mentve. A ranglista azonnal követi a változást.');
            redirect($target);
            return;
        } catch (AppException $e) {
            Session::flash('ranking_errors', ['playerName' => $e->getMessage()]);
            Session::flash('ranking_data', $data);
        } catch (\Throwable $e) {
            error_log('[AdminController] Pontszám mentési hiba: ' . $e->getMessage());
            Session::flash('error', 'A pontszám mentése nem sikerült.');
        }

        redirect($target . '?szerkeszt=' . urlencode($id));
    }

    /**
     * Pontszám törlése.
     */
    public function rankingEntryDelete(string $id): void
    {
        $this->requireAdmin();

        try {
            $competitionId = $this->rankingService->deleteEntry($id);
            Session::flash('success', 'A pontszám törölve.');
            redirect('/admin/ranglista/' . $competitionId);
            return;
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AdminController] Pontszám törlési hiba: ' . $e->getMessage());
            Session::flash('error', 'A pontszám törlése nem sikerült.');
        }

        redirect('/admin/ranglista');
    }

    /**
     * Pontszámok átvétele a galéria helyezettjeiből.
     *
     * A szervező a galériában amúgy is felviszi a dobogót, ezért a nevek
     * kétszeri beírása felesleges munka. A pontokat a helyezésből
     * származtatjuk, és minden érték utólag átírható.
     *
     * A már felvitt pontszámokat NEM írja felül: egy kézi javítást a
     * másolás nem tehet tönkre.
     */
    public function rankingImport(string $competitionId): void
    {
        $this->requireAdmin();

        $albumId = trim($_POST['album_id'] ?? '');
        $target = '/admin/ranglista/' . $competitionId;

        if ($albumId === '') {
            Session::flash('error', 'Válaszd ki, melyik album helyezettjeit vesszük át.');
            redirect($target);
            return;
        }

        try {
            $album = $this->galleryService->getAlbumView($albumId);

            if ($album === null) {
                Session::flash('error', 'Az album nem található.');
                redirect($target);
                return;
            }

            $result = $this->rankingService->importFromPlacements($competitionId, $album['placements']);

            if ($result['added'] === 0 && $result['skipped'] === 0) {
                Session::flash('error', 'Ebben az albumban nincs felvitt helyezett.');
            } else {
                $message = $result['added'] . ' pontszám átvéve a helyezettekből.';

                if ($result['skipped'] > 0) {
                    $message .= ' ' . $result['skipped']
                        . ' játékos kimaradt, mert már szerepelt a listában.';
                }

                Session::flash('success', $message);
            }
        } catch (\Throwable $e) {
            error_log('[AdminController] Pontszám átvétel hiba: ' . $e->getMessage());
            Session::flash('error', 'Az átvétel nem sikerült.');
        }

        redirect($target);
    }

    /**
     * Pontszám űrlap validálása.
     *
     * A pont lehet negatív is (levonás), ezért csak a szám formátumát
     * követeljük meg, nem azt, hogy pozitív legyen.
     *
     * @param array{playerName:string, points:string, place:string} $data
     * @return array<string, string>
     */
    private function validateRankingEntry(array $data): array
    {
        $errors = [];

        if ($data['playerName'] === '') {
            $errors['playerName'] = 'A játékos nevének megadása kötelező.';
        } elseif (mb_strlen($data['playerName']) > 100) {
            $errors['playerName'] = 'A név legfeljebb 100 karakter lehet.';
        }

        if ($data['points'] === '') {
            $errors['points'] = 'A pontszám megadása kötelező.';
        } elseif (preg_match('/^-?\d{1,6}$/', $data['points']) !== 1) {
            $errors['points'] = 'A pontszám egész szám legyen.';
        }

        if ($data['place'] !== '' && preg_match('/^\d{1,3}$/', $data['place']) !== 1) {
            $errors['place'] = 'A helyezés pozitív egész szám legyen.';
        }

        return $errors;
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
        $pageTitle = 'Admin Belépés - Okányi Biliárd Klub';

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
     *
     * Itt fut le az esedékes nevezésindítási értesítések kiküldése is. Erre
     * azért van szükség, mert a nevezés megnyílása időpont, nem művelet:
     * nincs kérés, amihez hozzá lehetne kötni a levelet. Az ütemezett
     * futtatás (tools/send-notifications.php) a rendes út, de cron nélkül is
     * kimegy a levél - legkésőbb akkor, amikor a szervező belép.
     */
    public function dashboard(): void
    {
        $this->requireAdmin();

        try {
            // Csak a darabszám kell, ezért COUNT lekérdezéssel kérjük -
            // korábban a teljes tartalom betöltődött a megszámolásához
            $newsCount = $this->newsService->countNews();
            $albumCount = $this->galleryService->countAlbums();
            $competitionCount = $this->competitionService->countOpenCompetitions();
        } catch (\Throwable $e) {
            error_log('[AdminController] Dashboard hiba: ' . $e->getMessage());
            $newsCount = 0;
            $albumCount = 0;
            $competitionCount = 0;
        }

        // A kiküldés hibája nem akadályozhatja meg az áttekintő megjelenítését
        $sentNotices = [];

        try {
            $sentNotices = $this->notificationService->sendDueRegistrationOpenNotices();
        } catch (\Throwable $e) {
            error_log('[AdminController] Esedékes értesítés hiba: ' . $e->getMessage());
        }

        if ($sentNotices !== []) {
            $names = array_column($sentNotices, 'competition');
            Session::flash(
                'success',
                'Kiküldtük a nevezésindítási értesítést: ' . implode(', ', $names) . '.'
            );
        }

        $pageTitle = 'Admin Dashboard - Okányi Biliárd Klub';

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
    // Regisztrált felhasználók kezelése
    // =========================================================================

    /**
     * A regisztrált fiókok listája.
     */
    public function userList(): void
    {
        $this->requireAdmin();

        $users = $this->authService->getAllUsers();
        // A visszaállított jelszó egyszer, kiemelten jelenik meg
        $newPassword = Session::getFlash('new_password') ?: null;
        $pageTitle = 'Felhasználók - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/users/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Fiók szerkesztő űrlapja.
     */
    public function userEdit(string $id): void
    {
        $this->requireAdmin();

        $user = $this->authService->getUserById($id);

        if ($user === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $errors = [];
        $data = [
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'city' => $user['city'],
        ];
        $pageTitle = $user['name'] . ' szerkesztése - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/users/edit.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Fiók módosítása.
     *
     * A jelszó mező üresen hagyható: ilyenkor a meglévő jelszó marad
     * érvényben. Új jelszó megadása minden megjegyzett belépést
     * érvénytelenít, hogy egy korábbi süti ne léptessen be tovább.
     */
    public function userUpdate(string $id): void
    {
        $this->requireAdmin();

        $user = $this->authService->getUserById($id);

        if ($user === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'passwordConfirm' => $_POST['password_confirm'] ?? '',
        ];

        $validator = $this->validationService->validateUserUpdate($data);
        $errors = $validator->isValid() ? [] : $validator->getErrors();

        if ($errors === []) {
            try {
                $this->authService->updateUser($id, $data['name'], $data['email'], $data['phone'], $data['city']);

                if ($data['password'] !== '') {
                    $this->authService->updatePassword($id, $data['password']);
                    $this->rememberMeService->forgetAllForUser($id);
                }

                Session::flash('success', 'A fiók adatai mentve.');
                redirect('/admin/felhasznalok');
                return;
            } catch (AppException $e) {
                // Foglalt e-mail cím: a mező mellett jelezzük
                $errors = $e->getCode() === AppException::DUPLICATE_ENTRY
                    ? ['email' => $e->getMessage()]
                    : ['general' => 'A mentés nem sikerült. Kérjük, próbálja újra.'];
            } catch (\Throwable $e) {
                error_log('[AdminController] Fiók mentési hiba: ' . $e->getMessage());
                $errors = ['general' => 'A mentés nem sikerült. Kérjük, próbálja újra.'];
            }
        }

        $pageTitle = $user['name'] . ' szerkesztése - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/users/edit.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Jelszó visszaállítása: új, véletlen jelszó beállítása.
     *
     * Akkor kell, ha a tag nem tud belépni. A generált jelszót a szervezőnek
     * MEGMUTATJUK (flash üzenetben), mert neki kell átadnia a tagnak
     * telefonon vagy személyesen. A tag kap egy értesítő levelet a
     * változásról, de a jelszót SZÁNDÉKOSAN nem tartalmazza: az e-mail nem
     * biztonságos csatorna.
     *
     * A művelet minden megjegyzett belépést érvénytelenít, különben egy régi
     * süti az új jelszó mellett is beléptetne.
     */
    public function userResetPassword(string $id): void
    {
        $this->requireAdmin();

        $user = $this->authService->getUserById($id);

        if ($user === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        try {
            $password = $this->authService->resetPassword($id);
            $this->rememberMeService->forgetAllForUser($id);

            // Külön flash kulcs: a nézet kiemelten, másolható módon jeleníti
            // meg. A sima "success" üzenet között könnyen elsikkadna, pedig
            // ez az egyetlen alkalom, amikor a jelszó látható.
            Session::flash('new_password', [
                'name' => $user['name'],
                'password' => $password,
            ]);

            $this->emailService->sendPasswordResetNotice($user['email'], $user['name']);
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AdminController] Jelszó visszaállítási hiba: ' . $e->getMessage());
            Session::flash('error', 'A jelszó visszaállítása nem sikerült.');
        }

        redirect('/admin/felhasznalok');
    }

    /**
     * Fiók törlése.
     *
     * A felhasználó nevezései megmaradnak, vendégnevezéssé válnak, így a
     * szervező névsora nem csorbul. A megjegyzett belépéseit is eldobjuk.
     */
    public function userDelete(string $id): void
    {
        $this->requireAdmin();

        try {
            $user = $this->authService->getUserById($id);
            $name = $user['name'] ?? 'A fiók';

            $this->rememberMeService->forgetAllForUser($id);
            $this->authService->deleteUser($id);

            Session::flash('success', $name . ' fiókja törölve. A nevezései vendégnevezésként megmaradtak.');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AdminController] Fiók törlési hiba: ' . $e->getMessage());
            Session::flash('error', 'A fiók törlése nem sikerült.');
        }

        redirect('/admin/felhasznalok');
    }

    // =========================================================================
    // Tartalmi oldalak (Rólunk, Emlékoldal, Adatkezelési tájékoztató)
    // =========================================================================

    /**
     * A szerkeszthető oldalak listája.
     */
    public function pageList(): void
    {
        $this->requireAdmin();

        $pages = $this->pageService->getAllPages();
        $pageTitle = 'Oldalak kezelése - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/pages/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Oldal szerkesztő űrlapja.
     */
    public function pageEdit(string $id): void
    {
        $this->requireAdmin();

        $page = $this->pageService->getPageById($id);

        if ($page === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $errors = [];
        $data = [
            'title' => $page['title'],
            'content' => $page['content'],
            'metaDescription' => $page['meta_description'] ?? '',
        ];
        $pageTitle = $page['title'] . ' szerkesztése - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/pages/edit.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Oldal mentése.
     *
     * A slug nem módosítható: az oldalak fix útvonalon élnek, és a menü
     * közvetlenül ezekre hivatkozik.
     */
    public function pageUpdate(string $id): void
    {
        $this->requireAdmin();

        $page = $this->pageService->getPageById($id);

        if ($page === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $data = [
            'title' => trim($_POST['title'] ?? ''),
            // A tartalmat nem vágjuk körbe: a szerkesztő HTML-je szándékosan
            // változatlanul kerül tárolásra, ahogy a híreknél is
            'content' => $_POST['content'] ?? '',
            'metaDescription' => trim($_POST['metaDescription'] ?? ''),
        ];

        $validator = $this->validationService->validatePage($data);

        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            $pageTitle = $page['title'] . ' szerkesztése - Admin';

            ob_start();
            require __DIR__ . '/../Views/admin/pages/edit.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/admin.php';
            return;
        }

        $this->pageService->updatePage($id, $data['title'], $data['content'], $data['metaDescription']);
        Session::flash('success', 'Az oldal sikeresen mentve!');
        redirect('/admin/oldalak');
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
        // A szezon a galéria archívumának rendező elve
        $seasonOptions = $this->seasonService->getOptions();
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
     * Album adatainak módosítása: név és szezon.
     *
     * A hibát az albumhoz kötve flasheljük (rename_errors[albumId]), így a
     * lista több album közül is a megfelelő űrlapnál jelzi a problémát.
     *
     * A szezon a galéria archívumának rendező elve, ezért a névvel együtt
     * egyetlen űrlapon állítható - nem érdemes két külön mentésre bontani.
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
            $this->galleryService->setAlbumSeason($id, $_POST['season_id'] ?? null);
            Session::flash('success', 'Az album adatai mentve.');
        } catch (\Throwable $e) {
            error_log('[AdminController] Album mentési hiba: ' . $e->getMessage());
            Session::flash('error', 'Hiba történt az album mentése során.');
        }

        redirect('/admin/galeria');
    }

    /**
     * Album archiválása vagy visszahelyezése.
     *
     * Az archiválás nem törlés: az album oldala változatlanul elérhető
     * marad, csak az aktuális galériából kerül át az archívumba
     * (/galeria/archiv), hogy ott a friss versenyek legyenek elöl.
     */
    public function albumToggleArchived(string $id): void
    {
        $this->requireAdmin();

        // A rejtett mező a kívánt ÁLLAPOTOT küldi, nem a váltás tényét: így
        // egy kétszer elküldött űrlap sem fordítja vissza a beállítást
        $archived = ($_POST['archived'] ?? '') === '1';

        try {
            $name = $this->galleryService->setAlbumArchived($id, $archived);

            Session::flash('success', $archived
                ? '"' . $name . '" az archívumba került. Az album oldala továbbra is elérhető.'
                : '"' . $name . '" visszakerült az aktuális albumok közé.');
        } catch (\Throwable $e) {
            error_log('[AdminController] Album archiválás hiba: ' . $e->getMessage());
            Session::flash('error', 'Az archiválás nem sikerült.');
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

    // =========================================================================
    // Album helyezettek
    // =========================================================================

    /**
     * Egy album helyezettjeinek kezelése: lista, felvitel, szerkesztés.
     *
     * Egyetlen oldalon van a névsor és az űrlapok, mert az eredmény
     * felvitele jellemzően egy munkamenetben történik, és így nem kell
     * oldalak között ugrálni.
     */
    public function placementList(string $id): void
    {
        $this->requireAdmin();

        $album = $this->galleryService->getAlbumView($id);

        if ($album === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $images = $this->galleryService->getAlbumImages($id);
        $placements = $album['placements'];
        $cover = $album['cover'];
        $albumData = $album['album'];

        $errors = Session::getFlash('placement_errors') ?: [];
        $editId = $_GET['szerkeszt'] ?? null;
        $editing = $editId !== null ? $this->galleryService->getPlacementById($editId) : null;

        // Csak ehhez az albumhoz tartozó helyezés szerkeszthető innen
        if ($editing !== null && $editing['album_id'] !== $id) {
            $editing = null;
        }

        $pageTitle = 'Helyezettek: ' . $albumData['name'] . ' - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/gallery/placements.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Új helyezett felvitele.
     */
    public function placementStore(string $id): void
    {
        $this->requireAdmin();

        $data = [
            'position' => trim($_POST['position'] ?? ''),
            'playerName' => trim($_POST['playerName'] ?? ''),
            'note' => trim($_POST['note'] ?? ''),
        ];

        $validator = $this->validationService->validatePlacement($data);

        if (!$validator->isValid()) {
            Session::flash('placement_errors', $validator->getErrors());
            Session::flash('error', 'A helyezett rögzítése nem sikerült, ellenőrizd a mezőket.');
            redirect("/admin/galeria/{$id}/helyezettek");
            return;
        }

        try {
            $this->galleryService->addPlacement(
                $id,
                (int) $data['position'],
                $data['playerName'],
                $data['note']
            );
            Session::flash('success', $data['playerName'] . ' felvéve a ' . (int) $data['position'] . '. helyre.');
        } catch (\Throwable $e) {
            error_log('[AdminController] Helyezett felvitel hiba: ' . $e->getMessage());
            Session::flash('error', 'Hiba történt a helyezett rögzítése során.');
        }

        redirect("/admin/galeria/{$id}/helyezettek");
    }

    /**
     * Helyezett módosítása.
     */
    public function placementUpdate(string $id): void
    {
        $this->requireAdmin();

        $placement = $this->galleryService->getPlacementById($id);

        if ($placement === null) {
            Session::flash('error', 'A helyezett nem található.');
            redirect('/admin/galeria');
            return;
        }

        $albumId = $placement['album_id'];

        $data = [
            'position' => trim($_POST['position'] ?? ''),
            'playerName' => trim($_POST['playerName'] ?? ''),
            'note' => trim($_POST['note'] ?? ''),
        ];

        $validator = $this->validationService->validatePlacement($data);

        if (!$validator->isValid()) {
            Session::flash('placement_errors', $validator->getErrors());
            Session::flash('error', 'A módosítás nem sikerült, ellenőrizd a mezőket.');
            redirect("/admin/galeria/{$albumId}/helyezettek?szerkeszt={$id}");
            return;
        }

        try {
            $this->galleryService->updatePlacement(
                $id,
                (int) $data['position'],
                $data['playerName'],
                $data['note']
            );
            Session::flash('success', 'A helyezett módosítva.');
        } catch (\Throwable $e) {
            error_log('[AdminController] Helyezett módosítás hiba: ' . $e->getMessage());
            Session::flash('error', 'Hiba történt a módosítás során.');
        }

        redirect("/admin/galeria/{$albumId}/helyezettek");
    }

    /**
     * Helyezett törlése.
     */
    public function placementDelete(string $id): void
    {
        $this->requireAdmin();

        try {
            $albumId = $this->galleryService->deletePlacement($id);
            Session::flash('success', 'A helyezett törölve.');
            redirect("/admin/galeria/{$albumId}/helyezettek");
        } catch (\Throwable $e) {
            error_log('[AdminController] Helyezett törlés hiba: ' . $e->getMessage());
            Session::flash('error', 'Hiba történt a törlés során.');
            redirect('/admin/galeria');
        }
    }

    /**
     * Album borítóképének kijelölése.
     *
     * A galéria az albumokat a borítóképükkel jelöli, ezért a szervezőnek
     * választania kell tudnia, melyik kép kerüljön a lista élére.
     */
    public function albumSetCover(string $id): void
    {
        $this->requireAdmin();

        $imageId = $_POST['image_id'] ?? '';

        try {
            $this->galleryService->setCoverImage($id, $imageId);
            Session::flash('success', 'A borítókép beállítva.');
        } catch (\Throwable $e) {
            error_log('[AdminController] Borítókép beállítás hiba: ' . $e->getMessage());
            Session::flash('error', 'A borítókép beállítása nem sikerült.');
        }

        redirect("/admin/galeria/{$id}/helyezettek");
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

        // Melyik versenyről milyen értesítés ment már ki - egy lekérdezésből,
        // hogy a lista ne fusson N+1-be
        $sentNotifications = $this->notificationService->getSentKinds(
            array_column($competitions, 'id')
        );
        $recipientCount = $this->notificationService->countRecipients();
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
        // Az értesítés jelölőmező csak a létrehozó űrlapon jelenik meg
        $showNotifyOption = true;
        $recipientCount = $this->notificationService->countRecipients();
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
            $showNotifyOption = true;
            $recipientCount = $this->notificationService->countRecipients();
            $pageTitle = 'Új verseny - Admin';

            ob_start();
            require __DIR__ . '/../Views/admin/competitions/create.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/admin.php';
            return;
        }

        $competition = $this->competitionService->createCompetition($data);

        // Értesítés a tagoknak a versenykiírásról. A jelölőmező alapból be van
        // jelölve, de a szervező kiveheti - például ha csak elmenti a
        // vázlatot, és később hirdeti meg. A lista oldalon bármikor
        // utólag is kiküldhető.
        $message = 'Verseny sikeresen létrehozva!';

        if (isset($_POST['notify'])) {
            $result = $this->notificationService->notifyAnnounced($competition);
            $message .= ' ' . $this->describeNotification($result);
        }

        Session::flash('success', $message);
        redirect('/admin/versenyek');
    }

    /**
     * Értesítés kézi kiküldése egy versenyről.
     *
     * Akkor kell, ha a szervező a létrehozásnál nem küldte ki, vagy a
     * nevezésindítási levelet szeretné azonnal elindítani, nem megvárva az
     * ütemezett futtatást.
     *
     * A kétszeres kiküldést az adatbázis zárja ki, ezért itt elég a
     * visszajelzést közvetíteni.
     */
    public function competitionNotify(string $id): void
    {
        $this->requireAdmin();

        $competition = $this->competitionService->getCompetitionById($id);

        if ($competition === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $kind = ($_POST['kind'] ?? '') === CompetitionNotification::KIND_REGISTRATION_OPEN
            ? CompetitionNotification::KIND_REGISTRATION_OPEN
            : CompetitionNotification::KIND_ANNOUNCED;

        try {
            $result = $kind === CompetitionNotification::KIND_REGISTRATION_OPEN
                ? $this->notificationService->notifyRegistrationOpen($competition)
                : $this->notificationService->notifyAnnounced($competition);

            // Kihagyás és teljes kudarc esetén is HIBA-jelzés kell: siker
            // színben megjelenő "nem ment ki" üzenetet a szervező átfutná
            if ($result['skipped'] || $result['sent'] === 0) {
                Session::flash('error', $this->describeNotification($result));
            } else {
                Session::flash('success', $this->describeNotification($result));
            }
        } catch (\Throwable $e) {
            error_log('[AdminController] Értesítés kiküldési hiba: ' . $e->getMessage());
            Session::flash('error', 'Az értesítés kiküldése nem sikerült.');
        }

        redirect('/admin/versenyek');
    }

    /**
     * Emberi nyelvű összegzés egy kiküldésről.
     *
     * A sikertelen címeket is kiírjuk, mert a szervezőnek tudnia kell, ha
     * valakihez nem ért el a levél - így utána tud járni telefonon.
     *
     * @param array{sent:int, failed:int, skipped:bool, reason:?string} $result
     */
    private function describeNotification(array $result): string
    {
        if ($result['skipped']) {
            return (string) $result['reason'];
        }

        // Nulla kiküldött levélnél a "kiküldve 0 tagnak" félrevezető lenne:
        // a szervező azt hihetné, nincs kinek küldeni, holott a levelezés
        // beállítása hibás. Ezért itt a konkrét okot mutatjuk.
        if ($result['sent'] === 0) {
            return 'Az értesítés NEM ment ki. ' . ($result['reason'] ?? 'Ismeretlen hiba.');
        }

        $message = 'Értesítés kiküldve ' . $result['sent'] . ' tagnak.';

        if ($result['failed'] > 0) {
            $message .= ' ' . ($result['reason'] ?? $result['failed'] . ' címre nem sikerült.');
        }

        return $message;
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

        // A tagok listája a "nevezés hozzáadása" űrlap választójához. Azok a
        // fiókok, amelyek már neveztek, nem jelennek meg: versenyenként egy
        // fiók egyszer nevezhet, tehát felkínálni sem érdemes őket.
        $registeredUserIds = array_filter(array_column($registrations, 'created_by_user_id'));
        $users = array_values(array_filter(
            $this->authService->getAllUsers(),
            static fn(array $user): bool => !in_array($user['id'], $registeredUserIds, true)
        ));

        $errors = Session::getFlash('registration_errors') ?: [];
        $data = Session::getFlash('registration_data') ?: [];
        $pageTitle = 'Nevezések: ' . $competition['name'] . ' - Admin';

        ob_start();
        require __DIR__ . '/../Views/admin/competitions/registrations.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/admin.php';
    }

    /**
     * Nevezés felvitele szervezői jogkörben, bárki nevében.
     *
     * Két mód van, a "mode" mező választja ki:
     *
     *   - "member": a klub egy tagja. Ilyenkor a nevezés a FIÓKJÁHOZ kötődik,
     *     tehát a tag a saját fiókjában is látja, és a határidő lejártáig
     *     maga visszavonhatja. Az adatok a fiókból jönnek.
     *
     *   - "guest": fiók nélküli játékos. Az adatokat a szervező írja be, a
     *     nevezés vendégnevezés marad.
     *
     * A hibákat flash üzenetben adjuk vissza, mert a lista GET oldalára
     * irányítunk - így az F5 nem küldi el újra a nevezést.
     */
    public function registrationStore(string $id): void
    {
        $this->requireAdmin();

        $competition = $this->competitionService->getCompetitionById($id);

        if ($competition === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $mode = ($_POST['mode'] ?? '') === 'guest' ? 'guest' : 'member';
        $target = "/admin/versenyek/{$id}/nevezesek";

        try {
            if ($mode === 'member') {
                $userId = trim($_POST['user_id'] ?? '');
                $user = $userId !== '' ? $this->authService->getUserById($userId) : null;

                if ($user === null) {
                    Session::flash('registration_errors', ['user_id' => 'Válassz ki egy tagot.']);
                    redirect($target);
                    return;
                }

                $this->competitionService->registerAsAdmin(
                    $id,
                    [
                        'fullName' => $user['name'],
                        'email' => $user['email'],
                        'phone' => $user['phone'],
                    ],
                    $user['id']
                );

                Session::flash('success', $user['name'] . ' nevezése rögzítve.');
                redirect($target);
                return;
            }

            $data = [
                'fullName' => trim($_POST['full_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
            ];

            $validator = $this->validationService->validateRegistration($data);

            if (!$validator->isValid()) {
                Session::flash('registration_errors', $validator->getErrors());
                Session::flash('registration_data', $data);
                redirect($target);
                return;
            }

            $this->competitionService->registerAsAdmin($id, $data, null);

            Session::flash('success', $data['fullName'] . ' nevezése rögzítve (fiók nélkül).');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AdminController] Nevezés felvitel hiba: ' . $e->getMessage());
            Session::flash('error', 'A nevezés felvitele nem sikerült.');
        }

        redirect($target);
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
