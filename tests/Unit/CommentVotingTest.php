<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\AppException;
use App\Services\CommentService;
use Tests\TestCase;

/**
 * A hozzászólás-értékelés (fel/leértékelés) tesztjei.
 *
 * A kapcsoló-viselkedés és a számlálók konzisztenciája a funkció két
 * legkönnyebben elromló pontja, ezért külön osztályban részletesen fedjük.
 */
class CommentVotingTest extends TestCase
{
    private CommentService $service;
    private string $commentId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createCommentService();

        $topic = $this->createTestTopic();
        $this->commentId = $this->service->addComment($topic, 'Értékelhető hozzászólás', 'Géza')['id'];
    }

    /** A hozzászólás aktuális szavazatszámai */
    private function counts(): array
    {
        $row = $this->db->query(
            "SELECT upvotes, downvotes FROM comments WHERE id = '{$this->commentId}'"
        )->fetch();

        return ['up' => (int) $row['upvotes'], 'down' => (int) $row['downvotes']];
    }

    public function testNewCommentStartsWithNoVotes(): void
    {
        $this->assertSame(['up' => 0, 'down' => 0], $this->counts());
    }

    public function testUpvoteIsCounted(): void
    {
        $result = $this->service->vote($this->commentId, 1, 'guest:aaa');

        $this->assertSame(CommentService::VOTE_ADDED, $result);
        $this->assertSame(['up' => 1, 'down' => 0], $this->counts());
    }

    public function testDownvoteIsCounted(): void
    {
        $result = $this->service->vote($this->commentId, -1, 'guest:aaa');

        $this->assertSame(CommentService::VOTE_ADDED, $result);
        $this->assertSame(['up' => 0, 'down' => 1], $this->counts());
    }

    public function testVotingSameDirectionTwiceRemovesTheVote(): void
    {
        $this->service->vote($this->commentId, 1, 'guest:aaa');
        $result = $this->service->vote($this->commentId, 1, 'guest:aaa');

        $this->assertSame(CommentService::VOTE_REMOVED, $result);
        $this->assertSame(['up' => 0, 'down' => 0], $this->counts());
    }

    public function testVotingOppositeDirectionFlipsTheVote(): void
    {
        $this->service->vote($this->commentId, 1, 'guest:aaa');
        $result = $this->service->vote($this->commentId, -1, 'guest:aaa');

        $this->assertSame(CommentService::VOTE_CHANGED, $result);
        // Nem összeadódik: egy szavazónak egy szavazata van
        $this->assertSame(['up' => 0, 'down' => 1], $this->counts());
    }

    public function testOneVotePerVoterEvenWithManyAttempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service->vote($this->commentId, 1, 'guest:aaa');
        }

        // Páratlan számú kattintás után szavazat van, párosnál nincs
        $counts = $this->counts();
        $this->assertLessThanOrEqual(1, $counts['up']);
    }

    public function testDifferentVotersAccumulate(): void
    {
        $this->service->vote($this->commentId, 1, 'guest:aaa');
        $this->service->vote($this->commentId, 1, 'guest:bbb');
        $this->service->vote($this->commentId, -1, 'guest:ccc');

        $this->assertSame(['up' => 2, 'down' => 1], $this->counts());
    }

    public function testVoteCountsAppearInVisibleComments(): void
    {
        $this->service->vote($this->commentId, 1, 'guest:aaa');
        $this->service->vote($this->commentId, 1, 'guest:bbb');

        $comments = $this->service->getCommentsForModeration();

        $this->assertSame(2, (int) $comments[0]['upvotes']);
        $this->assertSame(0, (int) $comments[0]['downvotes']);
    }

    public function testGetVoterVotesReportsOwnVoteOnly(): void
    {
        $this->service->vote($this->commentId, 1, 'guest:aaa');
        $this->service->vote($this->commentId, -1, 'guest:bbb');

        $comments = $this->service->getCommentsForModeration();

        $mine = $this->service->getVoterVotes($comments, 'guest:aaa');
        $theirs = $this->service->getVoterVotes($comments, 'guest:bbb');
        $none = $this->service->getVoterVotes($comments, 'guest:zzz');

        $this->assertSame(1, $mine[$this->commentId]);
        $this->assertSame(-1, $theirs[$this->commentId]);
        $this->assertArrayNotHasKey($this->commentId, $none);
    }

    public function testInvalidVoteValueIsRejected(): void
    {
        $this->expectException(AppException::class);

        $this->service->vote($this->commentId, 5, 'guest:aaa');
    }

    public function testVotingUnknownCommentIsRejected(): void
    {
        $this->expectException(AppException::class);

        $this->service->vote('nem-letezo-id', 1, 'guest:aaa');
    }

    public function testHiddenCommentCannotBeVotedOn(): void
    {
        $this->service->toggleHidden($this->commentId);

        $this->expectException(AppException::class);

        $this->service->vote($this->commentId, 1, 'guest:aaa');
    }

    public function testDeletingCommentRemovesItsVotes(): void
    {
        $this->service->vote($this->commentId, 1, 'guest:aaa');
        $this->service->deleteComment($this->commentId);

        $remaining = $this->db->query(
            "SELECT COUNT(*) AS c FROM comment_votes WHERE comment_id = '{$this->commentId}'"
        )->fetch();

        $this->assertSame(0, (int) $remaining['c']);
    }

    public function testCountersAreRecalculatedNotIncremented(): void
    {
        $this->service->vote($this->commentId, 1, 'guest:aaa');

        // Szándékos elcsúsztatás: a számláló hamis értékre állítása
        $this->db->exec("UPDATE comments SET upvotes = 99 WHERE id = '{$this->commentId}'");

        // A következő szavazás újraszámol a szavazatokból, tehát helyreáll
        $this->service->vote($this->commentId, 1, 'guest:bbb');

        $this->assertSame(['up' => 2, 'down' => 0], $this->counts());
    }
}
