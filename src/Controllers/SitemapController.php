<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Services\CommentService;
use App\Services\CompetitionService;
use App\Services\EmailService;
use App\Services\GalleryService;
use App\Services\ImageService;
use App\Services\NewsService;
use App\Services\TopicService;

/**
 * Keresőknek szóló, gépi olvasásra szánt válaszok: oldaltérkép és robots.txt.
 *
 * Mindkettő futásidőben áll össze, nem statikus fájlból. Így a frissen
 * felvitt hír, album, verseny és topik azonnal bekerül az oldaltérképbe,
 * kézi újragenerálás nélkül.
 *
 * Fontos: ezek nem lehetnek fizikai fájlok a public/ könyvtárban, mert a
 * gyökér .htaccess a létező fájlt előbb szolgálná ki, mint a front
 * controllert, és a tartalom megfagyna.
 */
class SitemapController
{
    /**
     * Egy tartalomtípusból legfeljebb ennyi elem kerül az oldaltérképbe.
     *
     * A protokoll 50 000 URL-t engedélyez fájlonként. Ez a korlát bőven
     * alatta van, viszont megvédi a lekérdezéseket egy elszabadult
     * adatmennyiségtől. Ha egy típus ezt eléri, oldaltérkép-indexre
     * lesz szükség.
     */
    private const MAX_PER_TYPE = 2000;

    private NewsService $newsService;
    private GalleryService $galleryService;
    private CompetitionService $competitionService;
    private TopicService $topicService;

    public function __construct()
    {
        $db = Database::getConnection();

        $this->newsService = new NewsService($db);
        $this->galleryService = new GalleryService($db, new ImageService());

        // A nevezési e-maileket itt nem küldünk, de a service konstruktora
        // megkívánja a levelezőt, ezért a valós konfigurációval adjuk át.
        $mailConfig = require __DIR__ . '/../../config/mail.php';
        $this->competitionService = new CompetitionService($db, new EmailService($mailConfig));

        $this->topicService = new TopicService($db, new CommentService($db));
    }

    /**
     * XML oldaltérkép - GET /sitemap.xml
     *
     * A publikus tartalmat sorolja fel: főoldal, hírek, galéria és albumok,
     * versenyek és nevezői listák, fórum és topikok. A belépés mögötti,
     * illetve a felhasználó-specifikus oldalak (fiók, admin, be- és
     * kilépés) szándékosan kimaradnak - ezeket a robots.txt is tiltja.
     */
    public function index(): void
    {
        $urls = array_merge(
            $this->staticUrls(),
            $this->newsUrls(),
            $this->galleryUrls(),
            $this->competitionUrls(),
            $this->forumUrls()
        );

        header('Content-Type: application/xml; charset=UTF-8');
        // Egy órán át gyorsítótárazható: a keresők amúgy sem kérik gyakrabban
        header('Cache-Control: public, max-age=3600');

        echo $this->renderXml($urls);
    }

    /**
     * robots.txt - GET /robots.txt
     *
     * A privát felületeket kizárja, és megadja az oldaltérkép helyét.
     * A Disallow nem biztonsági eszköz (az admin védelmét a session adja),
     * hanem azt akadályozza meg, hogy értéktelen vagy duplikált oldalak
     * kerüljenek az indexbe.
     */
    public function robots(): void
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Belépés mögötti és felhasználó-specifikus felületek',
            'Disallow: /admin',
            'Disallow: /fiok',
            'Disallow: /belepes',
            'Disallow: /regisztracio',
            'Disallow: /kilepes',
            '',
            '# A feltöltött fájlok közvetlen listázása nem tartalom',
            'Disallow: /uploads/',
            '',
            'Sitemap: ' . siteUrl('/sitemap.xml'),
        ];

        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: public, max-age=86400');

        echo implode("\n", $lines) . "\n";
    }

    // =========================================================================
    // URL gyűjtés tartalomtípusonként
    // =========================================================================

    /**
     * Állandó oldalak.
     *
     * A priority és a changefreq csak jelzés a keresőnek, nem garancia.
     * A gyűjtőoldalak kapják a magasabb értéket, mert onnan érhető el
     * a többi tartalom.
     *
     * @return array<array{loc:string, lastmod:?string, changefreq:string, priority:string}>
     */
    private function staticUrls(): array
    {
        return [
            $this->url('/', null, 'daily', '1.0'),
            $this->url('/galeria', null, 'weekly', '0.8'),
            $this->url('/nevezes', null, 'daily', '0.9'),
            $this->url('/forum', null, 'daily', '0.7'),
        ];
    }

    /**
     * Hírek. A hír módosítási ideje (updated_at) a lastmod alapja,
     * mert a kereső ez alapján dönt az újralátogatásról.
     *
     * @return array<array{loc:string, lastmod:?string, changefreq:string, priority:string}>
     */
    private function newsUrls(): array
    {
        $urls = [];

        foreach ($this->newsService->getLatestNews(self::MAX_PER_TYPE) as $news) {
            $urls[] = $this->url(
                '/hirek/' . $news['id'],
                $news['updated_at'] ?? $news['published_at'],
                'monthly',
                '0.7'
            );
        }

        return $urls;
    }

    /**
     * Galéria albumok.
     *
     * Az albumoknak nincs updated_at oszlopa, ezért a létrehozás dátumát
     * használjuk. Új kép feltöltése nem mozdítja a lastmod értéket, ami itt
     * elfogadható: a keresők az albumot amúgy is ritkán járják újra.
     *
     * @return array<array{loc:string, lastmod:?string, changefreq:string, priority:string}>
     */
    private function galleryUrls(): array
    {
        $urls = [];

        foreach ($this->galleryService->getAlbums() as $album) {
            // Az üres albumnak nincs mit mutatnia, ezért kimarad
            if ((int) $album['image_count'] === 0) {
                continue;
            }

            $urls[] = $this->url('/galeria/' . $album['id'], $album['created_at'], 'monthly', '0.6');
        }

        return $urls;
    }

    /**
     * Versenyek: a nevezési oldal és a nyilvános nevezői lista.
     *
     * A lezárult versenyek is bent maradnak, mert a nevezői listájuk
     * továbbra is érvényes, hivatkozható tartalom.
     *
     * @return array<array{loc:string, lastmod:?string, changefreq:string, priority:string}>
     */
    private function competitionUrls(): array
    {
        $urls = [];
        $today = date('Y-m-d');

        foreach ($this->competitionService->getAllCompetitions() as $competition) {
            // A jövőbeli verseny aktívabb tartalom, ezért gyakoribb
            // bejárást jelzünk és nagyobb súlyt adunk neki
            $isUpcoming = $competition['date'] >= $today;

            $urls[] = $this->url(
                '/nevezes/' . $competition['id'],
                $competition['updated_at'] ?? null,
                $isUpcoming ? 'daily' : 'yearly',
                $isUpcoming ? '0.8' : '0.4'
            );

            $urls[] = $this->url(
                '/nevezes/' . $competition['id'] . '/nevezok',
                $competition['updated_at'] ?? null,
                $isUpcoming ? 'daily' : 'yearly',
                $isUpcoming ? '0.6' : '0.3'
            );
        }

        return $urls;
    }

    /**
     * Fórum topikok. Az elrejtett topikokat a service már kiszűri.
     *
     * A lastmod a legutóbbi hozzászólás ideje, így egy felélesztett téma
     * újra bekerül a keresők látókörébe.
     *
     * @return array<array{loc:string, lastmod:?string, changefreq:string, priority:string}>
     */
    private function forumUrls(): array
    {
        $urls = [];

        foreach ($this->topicService->getVisibleTopics(self::MAX_PER_TYPE) as $topic) {
            $urls[] = $this->url(
                '/forum/' . $topic['id'],
                $topic['last_activity_at'] ?? $topic['created_at'],
                'weekly',
                '0.5'
            );
        }

        return $urls;
    }

    // =========================================================================
    // Segédmetódusok
    // =========================================================================

    /**
     * Egy oldaltérkép-bejegyzés összeállítása.
     *
     * @param string      $path      Alkalmazáson belüli útvonal
     * @param string|null $timestamp Bármilyen strtotime-mal értelmezhető dátum
     *
     * @return array{loc:string, lastmod:?string, changefreq:string, priority:string}
     */
    private function url(string $path, ?string $timestamp, string $changefreq, string $priority): array
    {
        return [
            'loc' => siteUrl($path),
            'lastmod' => $this->formatDate($timestamp),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    /**
     * Dátum W3C formátumra alakítása az oldaltérkép számára.
     *
     * Érvénytelen vagy hiányzó dátumnál null a válasz, és a lastmod elem
     * egyszerűen kimarad - ez megengedett, szemben egy hibás dátummal,
     * amitől a kereső az egész fájlt elutasíthatja.
     */
    private function formatDate(?string $timestamp): ?string
    {
        if ($timestamp === null || trim($timestamp) === '') {
            return null;
        }

        $parsed = strtotime($timestamp);

        if ($parsed === false || $parsed <= 0) {
            return null;
        }

        return date(DATE_W3C, $parsed);
    }

    /**
     * Az oldaltérkép XML összeállítása.
     *
     * Az URL-eket htmlspecialchars-szal escape-eljük: a sitemap protokoll
     * megköveteli az &, ', ", < és > karakterek entitásként való írását.
     *
     * @param array<array{loc:string, lastmod:?string, changefreq:string, priority:string}> $urls
     */
    private function renderXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "    <url>\n";
            $xml .= '        <loc>' . $this->escapeXml($url['loc']) . "</loc>\n";

            if ($url['lastmod'] !== null) {
                $xml .= '        <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }

            $xml .= '        <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            $xml .= '        <priority>' . $url['priority'] . "</priority>\n";
            $xml .= "    </url>\n";
        }

        $xml .= '</urlset>' . "\n";

        return $xml;
    }

    /**
     * XML szövegcsomó escape-elése.
     */
    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
