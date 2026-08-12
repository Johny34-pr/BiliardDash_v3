<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\AppException;
use App\Services\CommentService;
use App\Services\TopicService;
use Tests\TestCase;

/**
 * Fórum topikok tesztjei.
 *
 * A topik a fórum belépési pontja, ezért a létrehozás validációja és a
 * moderálási állapotok (elrejtés, lezárás, törlés) hatása a fontos.
 */
class TopicServiceTest extends TestCase
{
    private TopicService $service;
    private CommentService $commentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commentService = $this->createCommentService();
        $this->service = new TopicService($this->db, $this->commentService);
    }

    // =================================================================
    // Létrehozás
    // =================================================================

    public function testCreateTopicAsGuest(): void
    {
        $created = $this->service->createTopic('Milyen dákót vegyek?', 'Kezdő vagyok, segítsetek.', 'Géza');

        $topics = $this->service->getVisibleTopics();

        $this->assertCount(1, $topics);
        $this->assertSame('Milyen dákót vegyek?', $topics[0]['title']);
        $this->assertSame('Géza', $topics[0]['author_name']);
        $this->assertNull($topics[0]['user_id']);
        $this->assertSame($created['id'], $topics[0]['id']);
    }

    public function testCreateTopicAsUserUsesAccountName(): void
    {
        $user = ['id' => 'user-1', 'name' => 'Fiók Béla', 'email' => 'b@example.hu', 'phone' => '1'];
        $this->db->prepare('INSERT INTO users (id,name,email,phone,password_hash) VALUES (?,?,?,?,?)')
            ->execute([$user['id'], $user['name'], $user['email'], $user['phone'], 'hash']);

        // A beküldött név szándékosan más - a fiók neve kell érvényesüljön
        $this->service->createTopic('Belépve nyitom', 'Nyitó bejegyzés.', 'Hamis Név', $user);

        $topics = $this->service->getVisibleTopics();

        $this->assertSame('Fiók Béla', $topics[0]['author_name']);
        $this->assertSame('user-1', $topics[0]['user_id']);
    }

    public function testTitleIsStrippedOfMarkupAndNewlines(): void
    {
        $this->service->createTopic("<b>Cím</b>\n\nkét sorban", 'Nyitó bejegyzés.', 'Géza');

        $topics = $this->service->getVisibleTopics();

        $this->assertSame('Cím két sorban', $topics[0]['title']);
    }

    public function testTitleKeepsAllowedEmojiButDropsOthers(): void
    {
        $this->service->createTopic('Nagy meccs 🎱 és 💩', 'Nyitó bejegyzés.', 'Géza');

        $title = $this->service->getVisibleTopics()[0]['title'];

        $this->assertStringContainsString('🎱', $title);
        $this->assertStringNotContainsString('💩', $title);
    }

    public function testTooShortTitleIsRejected(): void
    {
        $this->expectException(AppException::class);

        $this->service->createTopic('ab', 'Rendes nyitó bejegyzés.', 'Géza');
    }

    public function testTooShortBodyIsRejected(): void
    {
        $this->expectException(AppException::class);

        $this->service->createTopic('Rendes cím', '', 'Géza');
    }

    public function testEmptyGuestNameIsRejected(): void
    {
        $this->expectException(AppException::class);

        $this->service->createTopic('Rendes cím', 'Rendes nyitó bejegyzés.', '  ');
    }

    public function testTitleIsLengthLimited(): void
    {
        $this->service->createTopic(str_repeat('a', 300), 'Nyitó bejegyzés.', 'Géza');

        $title = $this->service->getVisibleTopics()[0]['title'];

        $this->assertSame(TopicService::MAX_TITLE_LENGTH, mb_strlen($title));
    }

    // =================================================================
    // Hozzászólásszámláló és aktivitás
    // =================================================================

    public function testNewTopicHasNoComments(): void
    {
        $this->service->createTopic('Friss topik', 'Nyitó bejegyzés.', 'Géza');

        $this->assertSame(0, (int) $this->service->getVisibleTopics()[0]['comment_count']);
    }

    public function testCommentCountReflectsAddedComments(): void
    {
        $created = $this->service->createTopic('Beszélgetés', 'Nyitó bejegyzés.', 'Géza');
        $topic = $this->service->getTopicById($created['id']);

        $this->commentService->addComment($topic, 'Első válasz', 'Béla');
        $this->commentService->addComment($topic, 'Második válasz', 'Csaba');
        $this->service->refreshActivity($created['id']);

        $this->assertSame(2, (int) $this->service->getTopicById($created['id'])['comment_count']);
    }

    public function testHiddenCommentsDoNotCountTowardsTopicTotal(): void
    {
        $created = $this->service->createTopic('Beszélgetés', 'Nyitó bejegyzés.', 'Géza');
        $topic = $this->service->getTopicById($created['id']);

        $first = $this->commentService->addComment($topic, 'Látható válasz', 'Béla');
        $this->commentService->addComment($topic, 'Elrejtendő válasz', 'Csaba');

        $this->commentService->toggleHidden($first['id']);
        $this->service->refreshActivity($created['id']);

        $this->assertSame(1, (int) $this->service->getTopicById($created['id'])['comment_count']);
    }

    // =================================================================
    // Moderálás
    // =================================================================

    public function testHiddenTopicIsExcludedFromPublicList(): void
    {
        $created = $this->service->createTopic('Elrejtendő topik', 'Nyitó bejegyzés.', 'Géza');

        $this->service->toggleHidden($created['id']);

        $this->assertCount(0, $this->service->getVisibleTopics());
        $this->assertSame(0, $this->service->countVisibleTopics());
        // Moderáláshoz viszont továbbra is látszik
        $this->assertCount(1, $this->service->getTopicsForModeration());
    }

    public function testHiddenTopicIsNotPubliclyAccessible(): void
    {
        $created = $this->service->createTopic('Elrejtendő topik', 'Nyitó bejegyzés.', 'Géza');
        $this->service->toggleHidden($created['id']);

        $this->expectException(AppException::class);

        $this->service->getVisibleTopicOrFail($created['id']);
    }

    public function testToggleHiddenIsReversible(): void
    {
        $created = $this->service->createTopic('Ide-oda', 'Nyitó bejegyzés.', 'Géza');

        $this->assertTrue($this->service->toggleHidden($created['id']));
        $this->assertFalse($this->service->toggleHidden($created['id']));
        $this->assertCount(1, $this->service->getVisibleTopics());
    }

    public function testToggleLockedIsReversible(): void
    {
        $created = $this->service->createTopic('Lezárható', 'Nyitó bejegyzés.', 'Géza');

        $this->assertTrue($this->service->toggleLocked($created['id']));
        $this->assertSame(1, (int) $this->service->getTopicById($created['id'])['is_locked']);

        $this->assertFalse($this->service->toggleLocked($created['id']));
        $this->assertSame(0, (int) $this->service->getTopicById($created['id'])['is_locked']);
    }

    public function testLockedTopicRemainsReadable(): void
    {
        $created = $this->service->createTopic('Lezárt de olvasható', 'Nyitó bejegyzés.', 'Géza');
        $this->service->toggleLocked($created['id']);

        // Lezárva is elérhető a publikus felületen, csak nem fogad hozzászólást
        $topic = $this->service->getVisibleTopicOrFail($created['id']);

        $this->assertSame(1, (int) $topic['is_locked']);
    }

    public function testDeletingTopicAlsoRemovesItsComments(): void
    {
        $created = $this->service->createTopic('Törlendő topik', 'Nyitó bejegyzés.', 'Géza');
        $topic = $this->service->getTopicById($created['id']);

        $this->commentService->addComment($topic, 'Ez is törlődik', 'Béla');

        $this->service->deleteTopic($created['id']);

        $this->assertCount(0, $this->service->getTopicsForModeration());
        $this->assertCount(0, $this->commentService->getCommentsForModeration());
    }

    public function testModeratingUnknownTopicThrows(): void
    {
        $this->expectException(AppException::class);

        $this->service->toggleHidden('nem-letezo-id');
    }

    public function testUnknownTopicIsNotFound(): void
    {
        $this->expectException(AppException::class);

        $this->service->getVisibleTopicOrFail('nem-letezo-id');
    }
}
