<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Models\Broadcast;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Közvetítések kezelése.
 *
 * A közvetítés egy külső élő adás (YouTube, Facebook) hivatkozása. Korábban
 * egyetlen beállítás tárolta az aktuális címet, és az új adás felülírta a
 * régit; mostantól minden közvetítés megmarad, és a verseny neve alapján
 * visszakereshető.
 *
 * Itt dől el, mi mentődhet el: a cím sémája (csak http és https), a név és a
 * dátum kitöltése. A szabályok egy helyen élnek, mert a felvitel és a
 * módosítás ugyanazt az adatkört kezeli.
 */
class BroadcastService
{
    /** A közvetítés nevének felső korlátja (a tárolt oszlop mérete) */
    private const TITLE_MAX = 160;

    private Broadcast $broadcastModel;

    public function __construct(private PDO $db)
    {
        $this->broadcastModel = new Broadcast($db);
    }

    /**
     * Közvetítések keresése a verseny neve vagy a közvetítés címe alapján.
     *
     * Üres keresőszó esetén a teljes lista jön vissza, ugyanabban a
     * rendezésben - a nyilvános oldalnak így nem kell kétféle ágat kezelnie.
     *
     * @return array<array<string, mixed>>
     */
    public function search(?string $term, int $limit, int $offset): array
    {
        return $this->broadcastModel->search($term, $limit, $offset);
    }

    /**
     * A keresésre illeszkedő közvetítések száma (a lapozáshoz).
     */
    public function countSearch(?string $term): int
    {
        return $this->broadcastModel->countSearch($term);
    }

    /**
     * Minden közvetítés, élő adás elöl - a szervezői listához.
     *
     * @return array<array<string, mixed>>
     */
    public function getAll(): array
    {
        return $this->broadcastModel->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(string $id): ?array
    {
        return $this->broadcastModel->findById($id);
    }

    /**
     * Összesítés a menühöz: van-e egyáltalán közvetítés, és fut-e élő adás.
     *
     * @return array{total:int, live:int}
     */
    public function getMenuState(): array
    {
        return $this->broadcastModel->summary();
    }

    /**
     * Új közvetítés felvétele.
     *
     * @param array{competitionId?:?string, title?:?string, url?:?string, date?:?string, isLive?:bool} $data
     * @return string A létrejött közvetítés azonosítója
     * @throws AppException Ha a cím, a név vagy a dátum érvénytelen.
     */
    public function create(array $data): string
    {
        $values = $this->prepareValues($data);
        $id = Uuid::uuid4()->toString();

        $this->broadcastModel->create(
            $id,
            $values['competitionId'],
            $values['title'],
            $values['url'],
            $values['date'],
            !empty($data['isLive'])
        );

        return $id;
    }

    /**
     * Közvetítés módosítása.
     *
     * Az élő jelzést nem érinti: azt a setLive() kapcsolja. Így egy
     * elírás javítása nem állítja le a futó adás jelzését.
     *
     * @param array{competitionId?:?string, title?:?string, url?:?string, date?:?string} $data
     * @throws AppException Ha a közvetítés nem létezik, vagy az adatok érvénytelenek.
     */
    public function update(string $id, array $data): void
    {
        if ($this->broadcastModel->findById($id) === null) {
            throw AppException::notFound('A közvetítés nem található');
        }

        $values = $this->prepareValues($data);

        $this->broadcastModel->update(
            $id,
            $values['competitionId'],
            $values['title'],
            $values['url'],
            $values['date']
        );
    }

    /**
     * Az élő jelzés be- vagy kikapcsolása.
     *
     * @throws AppException Ha a közvetítés nem létezik.
     */
    public function setLive(string $id, bool $isLive): void
    {
        if ($this->broadcastModel->findById($id) === null) {
            throw AppException::notFound('A közvetítés nem található');
        }

        $this->broadcastModel->setLive($id, $isLive);
    }

    /**
     * Közvetítés törlése.
     *
     * @throws AppException Ha a közvetítés nem létezik.
     */
    public function delete(string $id): void
    {
        if ($this->broadcastModel->findById($id) === null) {
            throw AppException::notFound('A közvetítés nem található');
        }

        $this->broadcastModel->delete($id);
    }

    /**
     * Az űrlapról érkező adatok ellenőrzése és előkészítése tárolásra.
     *
     * @param array{competitionId?:?string, title?:?string, url?:?string, date?:?string} $data
     * @return array{competitionId:?string, title:string, url:string, date:string}
     * @throws AppException
     */
    private function prepareValues(array $data): array
    {
        $competition = $this->resolveCompetition($data['competitionId'] ?? null);

        return [
            'competitionId' => $competition['id'] ?? null,
            'title' => $this->resolveTitle($data['title'] ?? null, $competition),
            'url' => $this->resolveUrl($data['url'] ?? null),
            'date' => $this->resolveDate($data['date'] ?? null, $competition),
        ];
    }

    /**
     * A megadott verseny betöltése, vagy null ha nincs megadva.
     *
     * Az üres érték érvényes: a közvetítés lehet verseny nélküli (klubest,
     * bemutató). Nem létező azonosítóra viszont hibát dobunk, nem csendben
     * null-ozzuk - különben a szervező azt hinné, hozzárendelte a versenyt,
     * és a közvetítés utána a verseny nevére nem lenne megtalálható.
     *
     * @return array{id:string, name:string, date:string}|null
     * @throws AppException
     */
    private function resolveCompetition(?string $competitionId): ?array
    {
        $competitionId = trim((string) $competitionId);

        if ($competitionId === '') {
            return null;
        }

        $stmt = $this->db->prepare('SELECT id, name, date FROM competitions WHERE id = :id');
        $stmt->execute([':id' => $competitionId]);
        $competition = $stmt->fetch();

        if ($competition === false) {
            throw AppException::validationError('A kiválasztott verseny nem található');
        }

        return $competition;
    }

    /**
     * A közvetítés megjelenő neve.
     *
     * Üresen hagyva a verseny nevét veszi át - ez a tipikus eset, és így a
     * szervezőnek nem kell kétszer beírnia ugyanazt. Verseny nélkül viszont
     * kötelező: név nélkül a bejegyzés sem a listában, sem a keresésben nem
     * lenne azonosítható.
     *
     * @param array{id:string, name:string, date:string}|null $competition
     * @throws AppException
     */
    private function resolveTitle(?string $title, ?array $competition): string
    {
        $title = trim((string) $title);

        if ($title === '') {
            $title = (string) ($competition['name'] ?? '');
        }

        if ($title === '') {
            throw AppException::validationError(
                'Adj nevet a közvetítésnek, vagy válassz hozzá versenyt'
            );
        }

        return mb_substr($title, 0, self::TITLE_MAX);
    }

    /**
     * A külső adás címe.
     *
     * Csak http és https sémát fogadunk el. Enélkül egy elírt vagy
     * szándékosan beírt "javascript:" cím kerülhetne az oldalra, ami a
     * látogató böngészőjében futó kódot jelentene.
     *
     * @throws AppException
     */
    private function resolveUrl(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            throw AppException::validationError('A közvetítés címének megadása kötelező');
        }

        if (preg_match('#^https?://#i', $url) !== 1) {
            throw AppException::validationError(
                'A közvetítés címét http:// vagy https:// kezdettel add meg'
            );
        }

        if (mb_strlen($url) > 500) {
            throw AppException::validationError('A közvetítés címe túl hosszú (legfeljebb 500 karakter)');
        }

        return $url;
    }

    /**
     * A közvetítés dátuma.
     *
     * Üresen hagyva a verseny napját veszi át, enélkül a mai napot. A dátum
     * kötelező, mert ez rendezi a listát: nélküle nem lehetne megmondani,
     * melyik a régebbi közvetítés.
     *
     * @param array{id:string, name:string, date:string}|null $competition
     * @throws AppException Ha a megadott dátum nem értelmezhető.
     */
    private function resolveDate(?string $date, ?array $competition): string
    {
        $date = trim((string) $date);

        if ($date === '') {
            $date = (string) ($competition['date'] ?? '');
        }

        if ($date === '') {
            return date('Y-m-d');
        }

        // A dátummezőből éééé-hh-nn érkezik; a verseny dátuma is ilyen.
        // Kézi beküldésnél viszont bármi jöhet, ezért ellenőrizzük, hogy
        // valódi naptári nap legyen - a MySQL különben '0000-00-00'-t
        // tárolna, ami a listát rendezhetetlenné tenné.
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));

        if ($parsed === false || $parsed->format('Y-m-d') !== substr($date, 0, 10)) {
            throw AppException::validationError('A közvetítés dátuma érvénytelen');
        }

        return $parsed->format('Y-m-d');
    }
}
