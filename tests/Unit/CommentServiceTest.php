<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\AppException;
use App\Services\CommentService;
use Tests\TestCase;

/**
 * A fórum hozzászólások tisztítási és moderálási logikájának tesztjei.
 *
 * A tisztítás a funkció biztonsági szempontból legérzékenyebb pontja:
 * a hozzászólás csak egyszerű szöveg lehet, a megengedett emojikkal.
 */
class CommentServiceTest extends TestCase
{
    private CommentService $service;

    /** @var array<string,mixed> A hozzászólásokat befogadó teszt topik */
    private array $topic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createCommentService();
        $this->topic = $this->createTestTopic();
    }

    // =================================================================
    // Tisztítás: HTML eltávolítása
    // =================================================================

    public function testStripsHtmlTags(): void
    {
        $result = $this->service->sanitizeBody('<b>Szia</b> mindenkinek');

        $this->assertSame('Szia mindenkinek', $result);
    }

    public function testRemovesScriptContentAndTags(): void
    {
        $result = $this->service->sanitizeBody('Ártalmatlan<script>alert("xss")</script> szöveg');

        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('</script>', $result);
    }

    public function testDecodedEntitiesCannotReintroduceMarkup(): void
    {
        // Entitásként bújtatott tag a feloldás után sem maradhat jelölés
        $result = $this->service->sanitizeBody('&lt;img src=x onerror=alert(1)&gt; hello');

        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringNotContainsString('onerror', $result);
    }

    public function testDecodesAccentedEntities(): void
    {
        $result = $this->service->sanitizeBody('Sportt&aacute;rsak &uuml;dv');

        $this->assertSame('Sporttársak üdv', $result);
    }

    public function testPreservesHungarianAccents(): void
    {
        $text = 'Árvíztűrő tükörfúrógép őszülő ÖÜÓ';
        $this->assertSame($text, $this->service->sanitizeBody($text));
    }

    // =================================================================
    // Tisztítás: emojik
    // =================================================================

    public function testKeepsAllowedEmojis(): void
    {
        $result = $this->service->sanitizeBody('Nagy meccs volt 🎱👍');

        $this->assertStringContainsString('🎱', $result);
        $this->assertStringContainsString('👍', $result);
    }

    public function testKeepsMultiCodepointAllowedEmoji(): void
    {
        // A ❤️ két kódpontból áll (U+2764 + U+FE0F), nem sérülhet a szűréskor
        $result = $this->service->sanitizeBody('Imádom ❤️');

        $this->assertStringContainsString('❤️', $result);
    }

    public function testRemovesDisallowedEmoji(): void
    {
        // A 💩 nincs a megengedett listán
        $result = $this->service->sanitizeBody('Na ezt nem 💩 kérem');

        $this->assertStringNotContainsString('💩', $result);
        $this->assertStringContainsString('Na ezt nem', $result);
    }

    public function testKeepsAllowedWhileRemovingDisallowedEmoji(): void
    {
        $result = $this->service->sanitizeBody('mix 👍💩🔥');

        $this->assertStringContainsString('👍', $result);
        $this->assertStringContainsString('🔥', $result);
        $this->assertStringNotContainsString('💩', $result);
    }

    public function testEveryAllowedEmojiSurvivesSanitization(): void
    {
        foreach (CommentService::ALLOWED_EMOJIS as $emoji) {
            $result = $this->service->sanitizeBody("teszt {$emoji}");
            $this->assertStringContainsString(
                $emoji,
                $result,
                "A megengedett emoji eltűnt a tisztítás során: {$emoji}"
            );
        }
    }

    // =================================================================
    // Tisztítás: whitespace és hossz
    // =================================================================

    public function testCollapsesExcessiveBlankLines(): void
    {
        $result = $this->service->sanitizeBody("első\n\n\n\n\nmásodik");

        $this->assertSame("első\n\nmásodik", $result);
    }

    public function testCollapsesRepeatedSpaces(): void
    {
        $result = $this->service->sanitizeBody('sok     szóköz');

        $this->assertSame('sok szóköz', $result);
    }

    public function testRemovesControlCharacters(): void
    {
        $result = $this->service->sanitizeBody("szoveg\x00\x07vege");

        $this->assertSame('szovegvege', $result);
    }

    public function testEnforcesMaxLength(): void
    {
        $long = str_repeat('a', CommentService::MAX_LENGTH + 500);
        $result = $this->service->sanitizeBody($long);

        $this->assertSame(CommentService::MAX_LENGTH, mb_strlen($result));
    }

    // =================================================================
    // Név tisztítása
    // =================================================================

    public function testNameStripsMarkupAndEmoji(): void
    {
        $result = $this->service->sanitizeName('<b>Péter</b> 🎱');

        $this->assertSame('Péter', $result);
    }

    public function testNameIsLengthLimited(): void
    {
        $result = $this->service->sanitizeName(str_repeat('x', 200));

        $this->assertSame(CommentService::MAX_NAME_LENGTH, mb_strlen($result));
    }

    // =================================================================
    // Hozzászólás rögzítése
    // =================================================================

    public function testAddCommentAsGuestStoresNullUserId(): void
    {
        $this->service->addComment($this->topic, 'Vendég vagyok', 'Géza');

        $comments = $this->service->getTopicComments($this->topic['id']);

        $this->assertCount(1, $comments);
        $this->assertNull($comments[0]['user_id']);
        $this->assertSame('Géza', $comments[0]['author_name']);
    }

    public function testAddCommentLinksTheCommentToTheTopic(): void
    {
        $this->service->addComment($this->topic, 'Ebbe a topikba írok', 'Géza');

        $comments = $this->service->getTopicComments($this->topic['id']);

        $this->assertSame($this->topic['id'], $comments[0]['topic_id']);
    }

    public function testCommentsOfOtherTopicsAreNotReturned(): void
    {
        $other = $this->createTestTopic('Másik topik');

        $this->service->addComment($this->topic, 'Első topikban', 'Géza');
        $this->service->addComment($other, 'Másik topikban', 'Béla');

        $this->assertCount(1, $this->service->getTopicComments($this->topic['id']));
        $this->assertCount(1, $this->service->getTopicComments($other['id']));
        $this->assertSame('Másik topikban', $this->service->getTopicComments($other['id'])[0]['body']);
    }

    public function testAddCommentAsUserUsesAccountName(): void
    {
        $user = ['id' => 'user-1', 'name' => 'Fiók Béla', 'email' => 'b@example.hu', 'phone' => '1'];
        $this->db->prepare('INSERT INTO users (id,name,email,phone,password_hash) VALUES (?,?,?,?,?)')
            ->execute([$user['id'], $user['name'], $user['email'], $user['phone'], 'hash']);

        // A beküldött név szándékosan más - a fiók neve kell érvényesüljön
        $this->service->addComment($this->topic, 'Belépve írok', 'Hamis Név', $user);

        $comments = $this->service->getTopicComments($this->topic['id']);

        $this->assertSame('Fiók Béla', $comments[0]['author_name']);
        $this->assertSame('user-1', $comments[0]['user_id']);
    }

    public function testAddCommentRejectsBodyThatBecomesEmptyAfterSanitization(): void
    {
        $this->expectException(AppException::class);

        // Csak jelölés és nem engedett emoji: tisztítás után nem marad tartalom
        $this->service->addComment($this->topic, '<p></p>💩', 'Géza');
    }

    public function testAddCommentRejectsEmptyGuestName(): void
    {
        $this->expectException(AppException::class);

        $this->service->addComment($this->topic, 'Rendes hozzászólás', '   ');
    }

    public function testLockedTopicRejectsNewComments(): void
    {
        $locked = $this->createTestTopic('Lezárt topik', true);

        $this->expectException(AppException::class);

        $this->service->addComment($locked, 'Ez nem mehet át', 'Géza');
    }

    // =================================================================
    // Moderálás
    // =================================================================

    public function testHiddenCommentIsExcludedFromPublicList(): void
    {
        $created = $this->service->addComment($this->topic, 'Elrejtendő', 'Géza');

        $this->service->toggleHidden($created['id']);

        $this->assertCount(0, $this->service->getTopicComments($this->topic['id']));
        $this->assertSame(0, $this->service->countTopicComments($this->topic['id']));
        // Moderáláshoz viszont továbbra is látszik
        $this->assertCount(1, $this->service->getCommentsForModeration());
    }

    public function testToggleHiddenIsReversible(): void
    {
        $created = $this->service->addComment($this->topic, 'Ide-oda', 'Géza');

        $this->assertTrue($this->service->toggleHidden($created['id']));
        $this->assertFalse($this->service->toggleHidden($created['id']));
        $this->assertCount(1, $this->service->getTopicComments($this->topic['id']));
    }

    public function testDeleteCommentRemovesItPermanently(): void
    {
        $created = $this->service->addComment($this->topic, 'Törlendő', 'Géza');

        $this->service->deleteComment($created['id']);

        $this->assertCount(0, $this->service->getCommentsForModeration());
    }

    public function testModeratingUnknownCommentThrows(): void
    {
        $this->expectException(AppException::class);

        $this->service->toggleHidden('nem-letezo-id');
    }
}

