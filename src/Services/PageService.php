<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Models\Page;
use PDO;

/**
 * Szerkeszthető tartalmi oldalak kezelése.
 *
 * A tartalom a hírekkel egyező módon a rich text szerkesztőből származó HTML,
 * és ugyanúgy nyersen tárolódik: a szerzője a szervező, akinek a tartalmát az
 * alkalmazás megbízhatónak tekinti. Ez tudatos döntés, nem kihagyott
 * tisztítás - a nézetek ezért escape nélkül írják ki.
 */
class PageService
{
    /** A Rólunk oldal azonosítója az útvonalban */
    public const SLUG_ABOUT = 'rolunk';

    /** Az Emlékoldal azonosítója az útvonalban */
    public const SLUG_MEMORIAL = 'emlekoldal';

    /** Az adatkezelési tájékoztató azonosítója az útvonalban */
    public const SLUG_PRIVACY = 'adatkezeles';

    private Page $pageModel;

    public function __construct(private PDO $db)
    {
        $this->pageModel = new Page($db);
    }

    /**
     * Összes oldal a szervezői listához és az oldaltérképhez.
     *
     * @return array<array{id:string, slug:string, title:string, content:string, meta_description:?string, created_at:string, updated_at:string}>
     */
    public function getAllPages(): array
    {
        return $this->pageModel->findAll();
    }

    /**
     * Egy oldal a publikus útvonal alapján.
     */
    public function getPageBySlug(string $slug): ?array
    {
        return $this->pageModel->findBySlug($slug);
    }

    /**
     * Egy oldal azonosító alapján.
     */
    public function getPageById(string $id): ?array
    {
        return $this->pageModel->findById($id);
    }

    /**
     * Oldal tartalmának mentése.
     *
     * Üres leírás esetén a tartalom első mondataiból készít egyet, hogy a
     * keresőtalálatban ne az oldal általános alapleírása jelenjen meg.
     *
     * @throws AppException Ha az oldal nem létezik.
     */
    public function updatePage(string $id, string $title, string $content, ?string $metaDescription = null): array
    {
        if ($this->pageModel->findById($id) === null) {
            throw AppException::notFound('A keresett oldal nem található');
        }

        $metaDescription = trim((string) $metaDescription);

        if ($metaDescription === '') {
            $metaDescription = $this->buildDescription($content);
        }

        $this->pageModel->update($id, $title, $content, $metaDescription !== '' ? $metaDescription : null);

        return $this->pageModel->findById($id);
    }

    /**
     * Oldalleírás készítése a tartalomból.
     *
     * A HTML tageket eltávolítja, az entitásokat valódi karakterré oldja
     * (a szerkesztő névvel megadott entitásokat is előállít), majd
     * szóhatáron vág. A 300 karakteres korlát az adatbázis oszlopmérete.
     */
    private function buildDescription(string $content): string
    {
        $text = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if ($text === '' || mb_strlen($text) <= 160) {
            return $text;
        }

        $cut = mb_substr($text, 0, 160);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > 100) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " ,.;:-") . '…';
    }
}
