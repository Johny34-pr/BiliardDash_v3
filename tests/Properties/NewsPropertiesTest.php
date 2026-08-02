<?php

declare(strict_types=1);

namespace Tests\Properties;

use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * Property-based tesztek a hírmodulhoz.
 *
 * Validates: Requirements 1.1, 1.2, 2.1, 2.2, 2.5
 */
class NewsPropertiesTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: billiard-website, Property 1: Hírek listázása helyes sorrendben és limittel
     *
     * For any collection of news items with different published_at dates,
     * getLatestNews(limit) returns at most `limit` items in descending order by published_at.
     *
     * **Validates: Requirements 1.1, 1.2**
     */
    public function testNewsListingOrderAndLimit(): void
    {
        $newsService = $this->createNewsService();

        $this->forAll(
            Generators::choose(1, 15),
            Generators::choose(1, 10)
        )->then(function (int $count, int $limit) use ($newsService) {
            $this->truncateTable('news');

            // Create $count news items with distinct published_at timestamps
            for ($i = 0; $i < $count; $i++) {
                $title = 'Teszt hír ' . ($i + 1);
                $content = 'Tartalom ' . ($i + 1);
                // Insert directly with distinct timestamps to avoid timing issues
                $id = \Ramsey\Uuid\Uuid::uuid4()->toString();
                $publishedAt = date('Y-m-d H:i:s', strtotime("-{$i} hours"));
                $this->db->prepare(
                    'INSERT INTO news (id, title, content, summary, published_at) VALUES (:id, :title, :content, :summary, :published_at)'
                )->execute([
                    ':id' => $id,
                    ':title' => $title,
                    ':content' => $content,
                    ':summary' => mb_substr($content, 0, 200),
                    ':published_at' => $publishedAt,
                ]);
            }

            $result = $newsService->getLatestNews($limit);

            // Property: result count is at most the limit
            $this->assertLessThanOrEqual($limit, count($result));

            // Property: result count is at most the total number of items
            $this->assertLessThanOrEqual($count, count($result));

            // Property: results are in descending order by published_at
            for ($i = 1; $i < count($result); $i++) {
                $this->assertGreaterThanOrEqual(
                    $result[$i]['published_at'],
                    $result[$i - 1]['published_at'],
                    'News items should be in descending order by published_at'
                );
            }
        });
    }

    /**
     * Feature: billiard-website, Property 2: Hír létrehozás round-trip
     *
     * For any valid news data (title ≤200 chars, non-empty content),
     * creating a news item and fetching it back preserves title, content,
     * and generates a correct summary (strip HTML, first 200 chars).
     *
     * **Validates: Requirements 2.1**
     */
    public function testNewsCreationRoundTrip(): void
    {
        $newsService = $this->createNewsService();

        $this->forAll(
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0 && mb_strlen($s) <= 200;
                },
                Generators::string()
            ),
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0;
                },
                Generators::string()
            )
        )->then(function (string $title, string $content) use ($newsService) {
            $this->truncateTable('news');

            $created = $newsService->createNews($title, $content);
            $retrieved = $newsService->getNewsById($created['id']);

            // Property: retrieved news is not null
            $this->assertNotNull($retrieved);

            // Property: title is preserved exactly
            $this->assertSame($title, $retrieved['title']);

            // Property: content is preserved exactly
            $this->assertSame($content, $retrieved['content']);

            // Property: summary is generated correctly (strip_tags + first 200 chars)
            $expectedSummary = mb_substr(trim(strip_tags($content)), 0, 200);
            $this->assertSame($expectedSummary, $retrieved['summary']);

            // Property: published_at is set (not null/empty)
            $this->assertNotEmpty($retrieved['published_at']);
        });
    }

    /**
     * Feature: billiard-website, Property 3: Hír szerkesztés megőrzi a publikálási dátumot
     *
     * For any existing news item and any valid edit data,
     * editing the news preserves the original published_at date.
     *
     * **Validates: Requirements 2.2**
     */
    public function testNewsEditPreservesPublishedAt(): void
    {
        $newsService = $this->createNewsService();

        $this->forAll(
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0 && mb_strlen($s) <= 200;
                },
                Generators::string()
            ),
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0;
                },
                Generators::string()
            ),
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0 && mb_strlen($s) <= 200;
                },
                Generators::string()
            ),
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0;
                },
                Generators::string()
            )
        )->then(function (string $origTitle, string $origContent, string $newTitle, string $newContent) use ($newsService) {
            $this->truncateTable('news');

            // Create the original news
            $created = $newsService->createNews($origTitle, $origContent);
            $originalPublishedAt = $created['published_at'];

            // Edit the news with new title and content
            $updated = $newsService->updateNews($created['id'], $newTitle, $newContent);

            // Property: published_at is preserved after edit
            $this->assertSame(
                $originalPublishedAt,
                $updated['published_at'],
                'Editing a news item should preserve the original published_at date'
            );

            // Verify via fresh read as well
            $retrieved = $newsService->getNewsById($created['id']);
            $this->assertSame($originalPublishedAt, $retrieved['published_at']);
        });
    }

    /**
     * Feature: billiard-website, Property 4: Hír validáció elutasítja az üres mezőket
     *
     * For any news data where title or content is empty string or only whitespace,
     * validation rejects it and returns error messages.
     *
     * **Validates: Requirements 2.5**
     */
    public function testNewsValidationRejectsEmptyFields(): void
    {
        $validationService = $this->createValidationService();

        // Generator for empty/whitespace-only strings
        $emptyOrWhitespace = Generators::elements(['', ' ', '  ', "\t", "\n", "   \t\n"]);

        // Case 1: empty title with valid content
        $this->forAll(
            $emptyOrWhitespace,
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0 && mb_strlen($s) <= 200;
                },
                Generators::string()
            )
        )->then(function (string $emptyTitle, string $validContent) use ($validationService) {
            $validator = $validationService->validateNews([
                'title' => $emptyTitle,
                'content' => $validContent,
            ]);

            // Property: validation fails for empty title
            $this->assertFalse($validator->isValid(), 'Validation should reject empty title');
            $this->assertNotNull($validator->getError('title'), 'Title field should have an error');
        });

        // Case 2: valid title with empty content
        $this->forAll(
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0 && mb_strlen($s) <= 200;
                },
                Generators::string()
            ),
            $emptyOrWhitespace
        )->then(function (string $validTitle, string $emptyContent) use ($validationService) {
            $validator = $validationService->validateNews([
                'title' => $validTitle,
                'content' => $emptyContent,
            ]);

            // Property: validation fails for empty content
            $this->assertFalse($validator->isValid(), 'Validation should reject empty content');
            $this->assertNotNull($validator->getError('content'), 'Content field should have an error');
        });

        // Case 3: both title and content empty
        $this->forAll(
            $emptyOrWhitespace,
            $emptyOrWhitespace
        )->then(function (string $emptyTitle, string $emptyContent) use ($validationService) {
            $validator = $validationService->validateNews([
                'title' => $emptyTitle,
                'content' => $emptyContent,
            ]);

            // Property: validation fails when both fields are empty
            $this->assertFalse($validator->isValid(), 'Validation should reject when both fields are empty');
            $this->assertNotNull($validator->getError('title'), 'Title field should have an error');
            $this->assertNotNull($validator->getError('content'), 'Content field should have an error');
        });
    }
}
