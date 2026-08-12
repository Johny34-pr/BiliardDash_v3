<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Models\Topic;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Fórum topikok kezelése.
 *
 * Topikot vendégként és belépve is lehet nyitni. A cím és a nyitó bejegyzés
 * ugyanazon a tisztításon megy át, mint a hozzászólások: csak egyszerű
 * szöveg a megengedett emojikkal.
 */
class TopicService
{
    /** A topik címének hosszkorlátai */
    public const MIN_TITLE_LENGTH = 3;
    public const MAX_TITLE_LENGTH = 120;

    /** A nyitó bejegyzés hosszkorlátai */
    public const MIN_BODY_LENGTH = 2;
    public const MAX_BODY_LENGTH = 2000;

    /** Két topiknyitás között kötelezően eltelő idő másodpercben */
    public const THROTTLE_SECONDS = 60;

    private Topic $topicModel;

    public function __construct(private PDO $db, private CommentService $commentService)
    {
        $this->topicModel = new Topic($db);
    }

    /**
     * Látható topikok, a legutóbbi aktivitás szerint.
     *
     * @return array<array<string,mixed>>
     */
    public function getVisibleTopics(int $limit = 20, int $offset = 0): array
    {
        return $this->topicModel->findVisible($limit, $offset);
    }

    public function countVisibleTopics(): int
    {
        return $this->topicModel->countVisible();
    }

    /**
     * Összes topik moderáláshoz, az elrejtettekkel együtt.
     *
     * @return array<array<string,mixed>>
     */
    public function getTopicsForModeration(int $limit = 200): array
    {
        return $this->topicModel->findAllForModeration($limit);
    }

    /**
     * Egy topik lekérdezése azonosító alapján.
     *
     * @return array<string,mixed>|null
     */
    public function getTopicById(string $id): ?array
    {
        return $this->topicModel->findById($id);
    }

    /**
     * Publikusan megtekinthető topik lekérdezése.
     *
     * Az elrejtett topik nem érhető el a publikus felületen.
     *
     * @return array<string,mixed>
     * @throws AppException Ha a topik nem létezik vagy el van rejtve.
     */
    public function getVisibleTopicOrFail(string $id): array
    {
        $topic = $this->topicModel->findById($id);

        if ($topic === null || ((int) $topic['is_hidden']) === 1) {
            throw AppException::notFound('A topik nem található');
        }

        return $topic;
    }

    /**
     * Új topik nyitása.
     *
     * A szerzőnév bejelentkezve a fiók nevéből származik, ezért a beküldött
     * érték ilyenkor nem érvényesül - így nem lehet más nevében topikot nyitni.
     *
     * @param array|null $user A bejelentkezett felhasználó, vagy null
     *
     * @return array{id:string, title:string}
     * @throws AppException Ha a tartalom a tisztítás után érvénytelen.
     */
    public function createTopic(
        string $title,
        string $body,
        string $guestName,
        ?array $user = null,
        ?string $ipAddress = null
    ): array {
        // A címben nem engedünk sortörést, egyébként ugyanaz a tisztítás
        $cleanTitle = $this->sanitizeTitle($title);
        $cleanBody = $this->commentService->sanitizeBody($body);

        if (mb_strlen($cleanTitle) < self::MIN_TITLE_LENGTH) {
            throw AppException::validationError('A cím túl rövid');
        }

        if (mb_strlen($cleanBody) < self::MIN_BODY_LENGTH) {
            throw AppException::validationError('A nyitó bejegyzés túl rövid');
        }

        $authorName = $user !== null
            ? $user['name']
            : $this->commentService->sanitizeName($guestName);

        if ($authorName === '') {
            throw AppException::validationError('A név megadása kötelező');
        }

        $id = Uuid::uuid4()->toString();

        $this->topicModel->create(
            $id,
            $user['id'] ?? null,
            $authorName,
            $cleanTitle,
            mb_substr($cleanBody, 0, self::MAX_BODY_LENGTH),
            $ipAddress !== null ? hash('sha256', $ipAddress) : null
        );

        return ['id' => $id, 'title' => $cleanTitle];
    }

    /**
     * A topik hozzászólásszámlálójának és aktivitásának frissítése.
     */
    public function refreshActivity(string $topicId): void
    {
        $this->topicModel->refreshActivity($topicId);
    }

    // =====================================================================
    // Moderálás
    // =====================================================================

    /**
     * Topik elrejtése vagy visszaállítása.
     *
     * @return bool Az új elrejtett állapot.
     * @throws AppException Ha a topik nem létezik.
     */
    public function toggleHidden(string $id): bool
    {
        $topic = $this->requireTopic($id);
        $newState = ((int) $topic['is_hidden']) === 0;

        $this->topicModel->setHidden($id, $newState);

        return $newState;
    }

    /**
     * Topik lezárása vagy újranyitása.
     *
     * A lezárt topik olvasható marad, de nem fogad új hozzászólást.
     *
     * @return bool Az új lezárt állapot.
     * @throws AppException Ha a topik nem létezik.
     */
    public function toggleLocked(string $id): bool
    {
        $topic = $this->requireTopic($id);
        $newState = ((int) $topic['is_locked']) === 0;

        $this->topicModel->setLocked($id, $newState);

        return $newState;
    }

    /**
     * Topik végleges törlése a hozzászólásaival együtt.
     *
     * @throws AppException Ha a topik nem létezik.
     */
    public function deleteTopic(string $id): void
    {
        $this->requireTopic($id);

        $this->topicModel->delete($id);
    }

    // =====================================================================
    // Segédmetódusok
    // =====================================================================

    /**
     * A cím tisztítása: csak egyszerű szöveg, egy sorban.
     */
    public function sanitizeTitle(string $title): string
    {
        // A hozzászólás-tisztítás a jelölést és a nem engedett emojikat is kezeli
        $clean = $this->commentService->sanitizeBody($title);

        // Címben nincs sortörés
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));

        return mb_substr($clean, 0, self::MAX_TITLE_LENGTH);
    }

    /**
     * @return array<string,mixed>
     * @throws AppException Ha a topik nem létezik.
     */
    private function requireTopic(string $id): array
    {
        $topic = $this->topicModel->findById($id);

        if ($topic === null) {
            throw AppException::notFound('A topik nem található');
        }

        return $topic;
    }
}
