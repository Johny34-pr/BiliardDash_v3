<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Database;
use App\Core\Session;
use App\Models\CommentVote;
use App\Services\CommentService;
use App\Services\TopicService;
use App\Services\ValidationService;

/**
 * Közösségi fórum: topikok és hozzászólások.
 *
 * Topikot nyitni és hozzászólni vendégként és bejelentkezve is lehet.
 * A tartalom kizárólag egyszerű szöveg a megengedett emojikkal; a szűrést
 * a CommentService, illetve a TopicService végzi.
 */
class ForumController
{
    /** Topikok száma egy oldalon */
    private const TOPICS_PER_PAGE = 20;

    /** Hozzászólások száma egy oldalon a topikban */
    private const COMMENTS_PER_PAGE = 30;

    private CommentService $commentService;
    private TopicService $topicService;
    private ValidationService $validationService;

    public function __construct()
    {
        $db = Database::getConnection();
        $this->commentService = new CommentService($db);
        $this->topicService = new TopicService($db, $this->commentService);
        $this->validationService = new ValidationService();
    }

    // =====================================================================
    // Topiklista
    // =====================================================================

    /**
     * Fórum főoldal: topikok listája a legutóbbi aktivitás szerint.
     */
    public function index(): void
    {
        $total = $this->topicService->countVisibleTopics();
        $totalPages = max(1, (int) ceil($total / self::TOPICS_PER_PAGE));
        $page = min(max(1, (int) ($_GET['oldal'] ?? 1)), $totalPages);
        $offset = ($page - 1) * self::TOPICS_PER_PAGE;

        $topics = $this->topicService->getVisibleTopics(self::TOPICS_PER_PAGE, $offset);
        $currentUser = Session::user();

        $pageTitle = 'Fórum - Magyar Biliárd';

        ob_start();
        require __DIR__ . '/../Views/forum/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    // =====================================================================
    // Topik nyitása
    // =====================================================================

    public function createForm(): void
    {
        $this->renderTopicForm([], []);
    }

    public function store(): void
    {
        $user = Session::user();
        $isGuest = $user === null;

        $data = [
            'authorName' => trim($_POST['author_name'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'body' => trim($_POST['body'] ?? ''),
        ];

        $errors = [];

        // Visszaélés-védelem: topiknyitás között hosszabb várakozás
        $waitLeft = $this->throttleSecondsLeft('last_topic_at', TopicService::THROTTLE_SECONDS);
        if ($waitLeft > 0) {
            $errors['general'] = "Kérünk, várj még {$waitLeft} másodpercet a következő topik nyitásáig.";
        }

        if ($errors === []) {
            $validator = $this->validationService->validateTopic($data, $isGuest);

            if (!$validator->isValid()) {
                $errors = $validator->getErrors();
            }
        }

        if ($errors === []) {
            try {
                $topic = $this->topicService->createTopic(
                    $data['title'],
                    $data['body'],
                    $data['authorName'],
                    $user,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );

                $_SESSION['last_topic_at'] = time();
                Session::flash('success', 'A topik elkészült.');
                redirect('/forum/' . $topic['id']);
            } catch (AppException $e) {
                $errors['general'] = $e->getMessage();
            } catch (\Throwable $e) {
                error_log('[ForumController] Topik létrehozási hiba: ' . $e->getMessage());
                $errors['general'] = 'A topik létrehozása nem sikerült. Kérjük, próbáld újra.';
            }
        }

        $this->renderTopicForm($errors, $data);
    }

    // =====================================================================
    // Topik megtekintése és hozzászólás
    // =====================================================================

    /**
     * Egy topik nyitó bejegyzése és hozzászólásai.
     */
    public function show(string $id): void
    {
        try {
            $topic = $this->topicService->getVisibleTopicOrFail($id);
        } catch (AppException) {
            $this->renderNotFound();
            return;
        }

        $total = $this->commentService->countTopicComments($id);
        $totalPages = max(1, (int) ceil($total / self::COMMENTS_PER_PAGE));
        $page = min(max(1, (int) ($_GET['oldal'] ?? 1)), $totalPages);

        $this->renderTopic($topic, [
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'errors' => [],
            'data' => [],
        ]);
    }

    /**
     * Hozzászólás beküldése egy topikba.
     */
    public function storeComment(string $id): void
    {
        try {
            $topic = $this->topicService->getVisibleTopicOrFail($id);
        } catch (AppException) {
            $this->renderNotFound();
            return;
        }

        $user = Session::user();
        $isGuest = $user === null;

        $data = [
            'authorName' => trim($_POST['author_name'] ?? ''),
            'body' => trim($_POST['body'] ?? ''),
        ];

        $errors = [];

        $waitLeft = $this->throttleSecondsLeft('last_comment_at', CommentService::THROTTLE_SECONDS);
        if ($waitLeft > 0) {
            $errors['general'] = "Kérünk, várj még {$waitLeft} másodpercet a következő hozzászólásig.";
        }

        if ($errors === []) {
            $validator = $this->validationService->validateComment($data, $isGuest);

            if (!$validator->isValid()) {
                $errors = $validator->getErrors();
            }
        }

        if ($errors === []) {
            try {
                $this->commentService->addComment(
                    $topic,
                    $data['body'],
                    $data['authorName'],
                    $user,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );

                // A topik hozzászólásszáma és aktivitása frissül
                $this->topicService->refreshActivity($topic['id']);

                $_SESSION['last_comment_at'] = time();
                Session::flash('success', 'Hozzászólásod megjelent.');

                // Az utolsó oldalra ugrunk, ahol az új hozzászólás látszik
                $total = $this->commentService->countTopicComments($topic['id']);
                $lastPage = max(1, (int) ceil($total / self::COMMENTS_PER_PAGE));
                $query = $lastPage > 1 ? '?oldal=' . $lastPage : '';

                redirect('/forum/' . $topic['id'] . $query . '#hozzaszolasok');
            } catch (AppException $e) {
                $errors['general'] = $e->getMessage();
            } catch (\Throwable $e) {
                error_log('[ForumController] Hozzászólás hiba: ' . $e->getMessage());
                $errors['general'] = 'A hozzászólás mentése nem sikerült. Kérjük, próbáld újra.';
            }
        }

        $total = $this->commentService->countTopicComments($topic['id']);
        $totalPages = max(1, (int) ceil($total / self::COMMENTS_PER_PAGE));

        $this->renderTopic($topic, [
            'page' => $totalPages,
            'totalPages' => $totalPages,
            'total' => $total,
            'errors' => $errors,
            'data' => $data,
        ]);
    }

    // =====================================================================
    // Értékelés
    // =====================================================================

    /**
     * Hozzászólás fel- vagy leértékelése.
     *
     * Vendégként és belépve is működik; a szavazót a Session::voterKey()
     * azonosítja. Ugyanarra a gombra újra kattintva a szavazat visszavonható.
     */
    public function vote(string $id): void
    {
        $value = match ($_POST['ertekeles'] ?? '') {
            'fel' => CommentVote::UP,
            'le' => CommentVote::DOWN,
            default => 0,
        };

        if ($value === 0) {
            Session::flash('error', 'Érvénytelen értékelés.');
            redirect($this->voteReturnUrl($id));
        }

        try {
            $this->commentService->vote($id, $value, Session::voterKey(), Session::userId());
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[ForumController] Értékelési hiba: ' . $e->getMessage());
            Session::flash('error', 'Az értékelés mentése nem sikerült.');
        }

        redirect($this->voteReturnUrl($id));
    }

    // =====================================================================
    // Segédmetódusok
    // =====================================================================

    /**
     * Visszatérési cím szavazás után: a topik megfelelő oldalára, a
     * hozzászóláshoz ugró horgonnyal, hogy a látogató ne veszítse el a helyét.
     */
    private function voteReturnUrl(string $commentId): string
    {
        $topicId = $_POST['topik'] ?? '';
        $page = max(1, (int) ($_POST['oldal'] ?? 1));

        if ($topicId === '') {
            return '/forum';
        }

        $query = $page > 1 ? '?oldal=' . $page : '';

        return '/forum/' . $topicId . $query . '#hozzaszolas-' . $commentId;
    }

    /**
     * Mennyi másodpercet kell még várni a következő beküldésig.
     */
    private function throttleSecondsLeft(string $sessionKey, int $seconds): int
    {
        $last = $_SESSION[$sessionKey] ?? null;

        if ($last === null) {
            return 0;
        }

        return max(0, $seconds - (time() - (int) $last));
    }

    /**
     * Topiknyitó űrlap renderelése.
     */
    private function renderTopicForm(array $errors, array $data): void
    {
        $currentUser = Session::user();
        $allowedEmojis = CommentService::ALLOWED_EMOJIS;
        $maxTitleLength = TopicService::MAX_TITLE_LENGTH;
        $maxBodyLength = TopicService::MAX_BODY_LENGTH;

        $pageTitle = 'Új topik - Fórum - Magyar Biliárd';

        ob_start();
        require __DIR__ . '/../Views/forum/create.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Topik nézet renderelése a hozzászólásokkal.
     *
     * @param array{page:int, totalPages:int, total:int, errors:array, data:array} $state
     */
    private function renderTopic(array $topic, array $state): void
    {
        $page = $state['page'];
        $totalPages = $state['totalPages'];
        $total = $state['total'];
        $errors = $state['errors'];
        $data = $state['data'];

        $offset = ($page - 1) * self::COMMENTS_PER_PAGE;
        $comments = $this->commentService->getTopicComments($topic['id'], self::COMMENTS_PER_PAGE, $offset);

        $currentUser = Session::user();
        $allowedEmojis = CommentService::ALLOWED_EMOJIS;
        $maxLength = CommentService::MAX_LENGTH;

        // A látogató eddigi szavazatai, hogy a gombok kiemelhetők legyenek
        $myVotes = $comments !== []
            ? $this->commentService->getVoterVotes($comments, Session::voterKey())
            : [];

        $pageTitle = $topic['title'] . ' - Fórum - Magyar Biliárd';

        ob_start();
        require __DIR__ . '/../Views/forum/show.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
    }
}
