<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Models\Comment;
use App\Models\CommentVote;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Fórum hozzászólások kezelése.
 *
 * Tartalmi szabály: a hozzászólás kizárólag egyszerű szöveg lehet, a
 * megengedett emojikészlettel. Minden HTML jelölés eltávolításra kerül, és a
 * listán nem szereplő emojik, piktogramok is kiszűrődnek.
 */
class CommentService
{
    /** A hozzászólás minimális és maximális hossza karakterben */
    public const MIN_LENGTH = 2;
    public const MAX_LENGTH = 1000;

    /** A megjelenített szerzőnév maximális hossza */
    public const MAX_NAME_LENGTH = 60;

    /** Két hozzászólás között kötelezően eltelő idő másodpercben */
    public const THROTTLE_SECONDS = 15;

    /**
     * A használható emojik listája.
     *
     * A felületen ugyanez a lista jelenik meg választhatóként, és a
     * mentés előtt minden más piktogram kiszűrődik.
     */
    public const ALLOWED_EMOJIS = [
        '🎱', '🏆', '👍', '👏', '🔥', '💪',
        '😀', '😂', '😮', '🤔', '🙌', '❤️',
    ];

    /**
     * Piktogram jellegű Unicode tartományok, amelyeket a megengedett
     * emojikon kívül eltávolítunk.
     */
    private const PICTOGRAPH_PATTERN = '/[\x{1F000}-\x{1FAFF}\x{2190}-\x{21FF}\x{2300}-\x{23FF}'
        . '\x{2460}-\x{24FF}\x{25A0}-\x{27BF}\x{2900}-\x{297F}\x{2B00}-\x{2BFF}'
        . '\x{FE00}-\x{FE0F}\x{200D}\x{20E3}\x{E0020}-\x{E007F}]/u';

    /** Szavazás eredménye - a felület visszajelzéséhez */
    public const VOTE_ADDED = 'added';
    public const VOTE_CHANGED = 'changed';
    public const VOTE_REMOVED = 'removed';

    private Comment $commentModel;
    private CommentVote $voteModel;

    public function __construct(private PDO $db)
    {
        $this->commentModel = new Comment($db);
        $this->voteModel = new CommentVote($db);
    }

    /**
     * Egy topik látható hozzászólásai, időrendben.
     *
     * @return array<array{id:string, topic_id:string, user_id:?string, author_name:string, body:string, upvotes:int, downvotes:int, created_at:string}>
     */
    public function getTopicComments(string $topicId, int $limit = 100, int $offset = 0): array
    {
        return $this->commentModel->findVisibleByTopic($topicId, $limit, $offset);
    }

    public function countTopicComments(string $topicId): int
    {
        return $this->commentModel->countVisibleByTopic($topicId);
    }

    /**
     * Összes hozzászólás moderáláshoz, a topik címével együtt.
     *
     * @return array<array{id:string, topic_id:string, topic_title:string, user_id:?string, author_name:string, body:string, is_hidden:int, created_at:string}>
     */
    public function getCommentsForModeration(int $limit = 300): array
    {
        return $this->commentModel->findAllForModeration($limit);
    }

    /**
     * Egy hozzászólás lekérdezése azonosító alapján.
     *
     * Moderálás után a topik számlálóinak frissítéséhez kell tudni, melyik
     * topikhoz tartozott a hozzászólás.
     *
     * @return array{id:string, topic_id:string, user_id:?string, author_name:string, body:string, is_hidden:int, created_at:string}|null
     */
    public function getCommentById(string $id): ?array
    {
        return $this->commentModel->findById($id);
    }

    /**
     * Új hozzászólás rögzítése egy topikban.
     *
     * A szerzőnév bejelentkezve a fiók nevéből származik, ezért a beküldött
     * érték ilyenkor nem érvényesül - így nem lehet más nevében írni.
     *
     * A topikot a hívó tölti be és adja át, így itt ellenőrizhető, hogy
     * létezik-e és fogad-e még hozzászólást, anélkül hogy ez a szolgáltatás
     * a topikok tárolásától függene.
     *
     * @param array       $topic      A topik, amelyhez a hozzászólás tartozik
     * @param string      $body       A hozzászólás nyers szövege
     * @param string      $guestName  Vendégként megadott név (belépve figyelmen kívül marad)
     * @param array|null  $user       A bejelentkezett felhasználó, vagy null
     * @param string|null $ipAddress  Kliens IP címe (hash-elve tárolódik)
     *
     * @return array{id:string, author_name:string, body:string}
     * @throws AppException Ha a topik lezárt, vagy a tartalom érvénytelen.
     */
    public function addComment(
        array $topic,
        string $body,
        string $guestName,
        ?array $user = null,
        ?string $ipAddress = null
    ): array {
        // Lezárt vagy elrejtett topik nem fogad új hozzászólást
        if (((int) ($topic['is_locked'] ?? 0)) === 1) {
            throw AppException::forbidden('Ez a topik le van zárva, nem fogad új hozzászólást');
        }

        if (((int) ($topic['is_hidden'] ?? 0)) === 1) {
            throw AppException::notFound('A topik nem található');
        }

        $cleanBody = $this->sanitizeBody($body);

        if (mb_strlen($cleanBody) < self::MIN_LENGTH) {
            throw AppException::validationError('A hozzászólás túl rövid');
        }

        $authorName = $user !== null
            ? $user['name']
            : $this->sanitizeName($guestName);

        if ($authorName === '') {
            throw AppException::validationError('A név megadása kötelező');
        }

        $id = Uuid::uuid4()->toString();

        $this->commentModel->create(
            $id,
            $topic['id'],
            $user['id'] ?? null,
            $authorName,
            $cleanBody,
            $ipAddress !== null ? hash('sha256', $ipAddress) : null
        );

        return ['id' => $id, 'author_name' => $authorName, 'body' => $cleanBody];
    }

    // =====================================================================
    // Értékelés
    // =====================================================================

    /**
     * Szavazat rögzítése egy hozzászólásra.
     *
     * Viselkedés:
     *   - Ha még nem szavazott: a szavazat bekerül
     *   - Ha ugyanezt szavazta: a szavazat visszavonásra kerül (kapcsoló)
     *   - Ha az ellenkezőjét szavazta: a szavazat átfordul
     *
     * A számlálókat a művelet után a szavazatokból számoljuk újra.
     *
     * @param int         $value    CommentVote::UP vagy CommentVote::DOWN
     * @param string      $voterKey A szavazó azonosítója (Session::voterKey())
     * @param string|null $userId   Bejelentkezett szavazó fiókja, vagy null
     *
     * @return string VOTE_ADDED, VOTE_CHANGED vagy VOTE_REMOVED
     * @throws AppException Érvénytelen érték vagy nem létező hozzászólás esetén.
     */
    public function vote(string $commentId, int $value, string $voterKey, ?string $userId = null): string
    {
        if (!in_array($value, [CommentVote::UP, CommentVote::DOWN], true)) {
            throw AppException::validationError('Érvénytelen értékelés');
        }

        $comment = $this->commentModel->findById($commentId);

        if ($comment === null) {
            throw AppException::notFound('A hozzászólás nem található');
        }

        // Elrejtett hozzászólásra ne lehessen szavazni
        if (((int) $comment['is_hidden']) === 1) {
            throw AppException::forbidden('Ez a hozzászólás nem értékelhető');
        }

        $existing = $this->voteModel->findByCommentAndVoter($commentId, $voterKey);

        if ($existing === null) {
            $this->voteModel->create(
                Uuid::uuid4()->toString(),
                $commentId,
                $voterKey,
                $userId,
                $value
            );
            $result = self::VOTE_ADDED;
        } elseif ((int) $existing['value'] === $value) {
            // Ugyanarra kattintott: szavazat visszavonása
            $this->voteModel->delete($existing['id']);
            $result = self::VOTE_REMOVED;
        } else {
            $this->voteModel->updateValue($existing['id'], $value);
            $result = self::VOTE_CHANGED;
        }

        $this->commentModel->refreshVoteCounts($commentId);

        return $result;
    }

    /**
     * Egy szavazó szavazatai a megadott hozzászólásokra.
     *
     * A felület ebből tudja kiemelni, mire szavazott már a látogató.
     *
     * @param array<array{id:string}> $comments
     * @return array<string,int> hozzászólás azonosító => szavazat értéke
     */
    public function getVoterVotes(array $comments, string $voterKey): array
    {
        $ids = array_column($comments, 'id');

        return $this->voteModel->findVotesByVoter($ids, $voterKey);
    }

    /**
     * Hozzászólás elrejtése vagy visszaállítása (moderálás).
     *
     * @return bool Az új elrejtett állapot.
     * @throws AppException Ha a hozzászólás nem létezik.
     */
    public function toggleHidden(string $id): bool
    {
        $comment = $this->commentModel->findById($id);

        if ($comment === null) {
            throw AppException::notFound('A hozzászólás nem található');
        }

        $newState = ((int) $comment['is_hidden']) === 0;
        $this->commentModel->setHidden($id, $newState);

        return $newState;
    }

    /**
     * Hozzászólás végleges törlése (moderálás).
     *
     * @throws AppException Ha a hozzászólás nem található.
     */
    public function deleteComment(string $id): void
    {
        if ($this->commentModel->findById($id) === null) {
            throw AppException::notFound('A hozzászólás nem található');
        }

        $this->commentModel->delete($id);
    }

    // =====================================================================
    // Tisztítás
    // =====================================================================

    /**
     * A hozzászólás szövegének tisztítása.
     *
     * Lépések:
     *   1. HTML tagek eltávolítása és entitások feloldása (csak szöveg)
     *   2. Vezérlőkarakterek eltávolítása
     *   3. A megengedett emojik védése, a többi piktogram kiszűrése
     *   4. Túlzott üres sorok és szóközök normalizálása
     *   5. Hosszkorlát érvényesítése
     */
    public function sanitizeBody(string $body): string
    {
        // 1. Csak szöveg: jelölés eltávolítása
        $text = strip_tags($body);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // A feloldás után újra eltávolítjuk a jelölést, ha entitásból keletkezett
        $text = strip_tags($text);

        // 2. Vezérlőkarakterek (a sortörés és tabulátor kivételével)
        $text = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // 3. Emoji szűrés a megengedett listára
        $text = $this->filterEmojis($text);

        // 4. Whitespace normalizálás: max 2 egymást követő sortörés,
        //    a soron belüli többszörös szóköz egyre csökken
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = (string) preg_replace('/[ \t]+/u', ' ', $text);
        $text = (string) preg_replace('/\n{3,}/u', "\n\n", $text);
        $text = (string) preg_replace('/ *\n */u', "\n", $text);
        $text = trim($text);

        // 5. Hosszkorlát
        return mb_substr($text, 0, self::MAX_LENGTH);
    }

    /**
     * A szerzőnév tisztítása: csak szöveg, emoji és jelölés nélkül.
     */
    public function sanitizeName(string $name): string
    {
        $clean = strip_tags($name);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);
        // Névben nem engedünk piktogramot
        $clean = (string) preg_replace(self::PICTOGRAPH_PATTERN, '', $clean);
        $clean = trim((string) preg_replace('/\s+/u', ' ', $clean));

        return mb_substr($clean, 0, self::MAX_NAME_LENGTH);
    }

    /**
     * A megengedett emojik megtartása, minden más piktogram eltávolítása.
     *
     * A megengedett emojikat előbb helyőrzőre cseréljük, így a több
     * kódpontból álló emojik (például a variációs jelölőt használó ❤️)
     * sem sérülnek a szűrés során.
     */
    private function filterEmojis(string $text): string
    {
        $placeholders = [];

        foreach (self::ALLOWED_EMOJIS as $index => $emoji) {
            $placeholder = "\x02E{$index}\x02";
            $placeholders[$placeholder] = $emoji;
            $text = str_replace($emoji, $placeholder, $text);
        }

        // Minden nem engedélyezett piktogram eltávolítása
        $text = (string) preg_replace(self::PICTOGRAPH_PATTERN, '', $text);

        // Megengedett emojik visszaállítása
        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }
}
