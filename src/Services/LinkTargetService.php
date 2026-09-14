<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Belső hivatkozási célok összegyűjtése a szerkesztő hivatkozás-választójához.
 *
 * A TinyMCE `link_list` beállítása ezt a szerkezetet várja:
 *
 *   [
 *     { "title": "Főoldal", "value": "/" },
 *     { "title": "Versenyek", "menu": [ { "title": "...", "value": "..." } ] }
 *   ]
 *
 * Így a szerkesztő nem kényszerül kézzel beírt URL-ekre: a versenyek nevezési
 * űrlapja, a hírek, az albumok és a fórum topikjai listából választhatók,
 * és az elírás okozta törött hivatkozás sem fordulhat elő.
 */
class LinkTargetService
{
    /** Hány elem szerepeljen legfeljebb egy-egy csoportban */
    private const GROUP_LIMIT = 40;

    public function __construct(
        private NewsService $newsService,
        private CompetitionService $competitionService,
        private GalleryService $galleryService,
        private TopicService $topicService
    ) {
    }

    /**
     * A teljes hivatkozáslista a szerkesztőhöz.
     *
     * Az üres csoportok kimaradnak, hogy ne jelenjenek meg használhatatlan
     * menüpontok.
     *
     * @return array<array{title:string, value?:string, menu?:array}>
     */
    public function getLinkList(): array
    {
        $list = [
            ['title' => 'Főoldal (hírek)', 'value' => '/'],
            ['title' => 'Galéria', 'value' => '/galeria'],
            ['title' => 'Versenyek és nevezés', 'value' => '/nevezes'],
            ['title' => 'Fórum', 'value' => '/forum'],
        ];

        foreach (
            [
                'Versenyek' => $this->competitionLinks(),
                'Hírek' => $this->newsLinks(),
                'Galéria albumok' => $this->albumLinks(),
                'Fórum topikok' => $this->topicLinks(),
            ] as $groupTitle => $items
        ) {
            if ($items !== []) {
                $list[] = ['title' => $groupTitle, 'menu' => $items];
            }
        }

        return $list;
    }

    /**
     * Versenyek: minden versenyhez a nevezési űrlap és a nevezői lista.
     *
     * @return array<array{title:string, menu:array}>
     */
    private function competitionLinks(): array
    {
        $items = [];

        foreach (array_slice($this->competitionService->getAllCompetitions(), 0, self::GROUP_LIMIT) as $competition) {
            $label = $competition['name'] . ' (' . date('Y. m. d.', strtotime($competition['date'])) . ')';

            $items[] = [
                'title' => $label,
                'menu' => [
                    ['title' => 'Nevezési űrlap', 'value' => '/nevezes/' . $competition['id']],
                    ['title' => 'Nevezői lista', 'value' => '/nevezes/' . $competition['id'] . '/nevezok'],
                ],
            ];
        }

        return $items;
    }

    /**
     * @return array<array{title:string, value:string}>
     */
    private function newsLinks(): array
    {
        $items = [];

        foreach ($this->newsService->getLatestNews(self::GROUP_LIMIT) as $news) {
            $items[] = [
                'title' => $news['title'],
                'value' => '/hirek/' . $news['id'],
            ];
        }

        return $items;
    }

    /**
     * @return array<array{title:string, value:string}>
     */
    private function albumLinks(): array
    {
        $items = [];

        foreach (array_slice($this->galleryService->getAlbums(), 0, self::GROUP_LIMIT) as $album) {
            $items[] = [
                'title' => $album['name'] . ' (' . (int) $album['image_count'] . ' kép)',
                'value' => '/galeria/' . $album['id'],
            ];
        }

        return $items;
    }

    /**
     * @return array<array{title:string, value:string}>
     */
    private function topicLinks(): array
    {
        $items = [];

        foreach ($this->topicService->getVisibleTopics(self::GROUP_LIMIT) as $topic) {
            $items[] = [
                'title' => $topic['title'],
                'value' => '/forum/' . $topic['id'],
            ];
        }

        return $items;
    }
}
