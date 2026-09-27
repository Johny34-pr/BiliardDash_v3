<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Album;
use App\Models\AlbumPlacement;
use App\Models\Image;
use PDO;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class GalleryService
{
    /**
     * Hány helyezett jelenjen meg az album listában.
     *
     * A lista csak a dobogót mutatja, hogy a kártyák egy magasságúak
     * maradjanak; a teljes névsor az album oldalán olvasható.
     */
    public const LIST_PLACEMENT_LIMIT = 3;

    private Album $albumModel;
    private Image $imageModel;
    private AlbumPlacement $placementModel;

    public function __construct(private PDO $db, private ImageService $imageService)
    {
        $this->albumModel = new Album($db);
        $this->imageModel = new Image($db);
        $this->placementModel = new AlbumPlacement($db);
    }

    // =========================================================================
    // Helyezettek
    // =========================================================================

    /**
     * Egy album helyezettjei, helyezés szerint.
     *
     * @return array<array{id:string, album_id:string, position:int, player_name:string, note:?string, created_at:string, updated_at:string}>
     */
    public function getPlacements(string $albumId): array
    {
        return $this->placementModel->findByAlbumId($albumId);
    }

    /**
     * Több album helyezettjei egyetlen lekérdezéssel, albumonként csoportosítva.
     *
     * @param array<string> $albumIds
     * @return array<string, array<array{position:int, player_name:string, note:?string}>>
     */
    public function getPlacementsForAlbums(array $albumIds): array
    {
        return $this->placementModel->findByAlbumIds($albumIds);
    }

    /**
     * Egy helyezés lekérdezése.
     */
    public function getPlacementById(string $id): ?array
    {
        return $this->placementModel->findById($id);
    }

    /**
     * Új helyezett felvitele egy albumhoz.
     *
     * @throws RuntimeException Ha az album nem létezik.
     */
    public function addPlacement(string $albumId, int $position, string $playerName, ?string $note = null): array
    {
        if ($this->albumModel->findById($albumId) === null) {
            throw new RuntimeException('Az album nem található.');
        }

        $id = Uuid::uuid4()->toString();
        $this->placementModel->create($id, $albumId, $position, $playerName, $this->normalizeNote($note));

        $placement = $this->placementModel->findById($id);

        if ($placement === null) {
            throw new RuntimeException('A helyezett rögzítése nem sikerült.');
        }

        return $placement;
    }

    /**
     * Helyezett módosítása.
     *
     * @throws RuntimeException Ha a helyezés nem létezik.
     */
    public function updatePlacement(string $id, int $position, string $playerName, ?string $note = null): array
    {
        if ($this->placementModel->findById($id) === null) {
            throw new RuntimeException('A helyezett nem található.');
        }

        $this->placementModel->update($id, $position, $playerName, $this->normalizeNote($note));

        return $this->placementModel->findById($id);
    }

    /**
     * Helyezett törlése.
     *
     * @return string Az album azonosítója, ahová tartozott
     * @throws RuntimeException Ha a helyezés nem létezik.
     */
    public function deletePlacement(string $id): string
    {
        $placement = $this->placementModel->findById($id);

        if ($placement === null) {
            throw new RuntimeException('A helyezett nem található.');
        }

        $this->placementModel->delete($id);

        return $placement['album_id'];
    }

    /**
     * Az album borítóképének kijelölése.
     *
     * A galéria az albumokat a borítóképükkel jelöli, ezért a szervezőnek
     * tudnia kell választani, melyik kép legyen az. Csak az albumhoz tartozó
     * kép fogadható el, különben egy másik album képe kerülhetne a borítóra.
     *
     * @throws RuntimeException Ha a kép nem az albumhoz tartozik.
     */
    public function setCoverImage(string $albumId, string $imageId): void
    {
        $image = $this->imageModel->findById($imageId);

        if ($image === null || $image['album_id'] !== $albumId) {
            throw new RuntimeException('A kép nem ehhez az albumhoz tartozik.');
        }

        $this->albumModel->updateCoverImageId($albumId, $imageId);
    }

    /**
     * Üres megjegyzés null-ra alakítása, hogy az adatbázisban ne
     * keletkezzen üres string a hiányzó érték helyén.
     */
    private function normalizeNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }

    /**
     * Összes album lekérdezése fordított időrendi sorrendben.
     *
     * @return array<array{id:string, name:string, cover_image_id:?string, image_count:int, created_at:string}>
     */
    public function getAlbums(): array
    {
        return $this->albumModel->findAll();
    }

    /**
     * Az albumok száma (az áttekintő statisztikához).
     */
    public function countAlbums(): int
    {
        return $this->albumModel->countAll();
    }

    /**
     * Az archivált albumok száma.
     *
     * A galéria fejléce ebből tudja, érdemes-e kiírni az archívumra vezető
     * hivatkozást: nulla archív albumnál a link üres oldalra vinne.
     */
    public function countArchivedAlbums(): int
    {
        return $this->albumModel->countArchived();
    }

    /**
     * Archivált albumok szezononként csoportosítva.
     *
     * Az archívum a régebbi évadok versenyeit gyűjti, ezért a szezon a
     * rendező elv, nem a feltöltés dátuma. A szezon nélküli albumok egy
     * külön, névtelen csoportba kerülnek a lista végén - így nem tűnnek el
     * azok sem, amelyekhez a szervező még nem adott meg évadot.
     *
     * @return array<array{season:?string, albums:array<array<string, mixed>>}>
     */
    public function getArchivedAlbumsBySeason(): array
    {
        $albums = $this->getAlbumsForListing(true);

        if ($albums === []) {
            return [];
        }

        /** @var array<string, array{season:?string, albums:array}> $groups */
        $groups = [];

        foreach ($albums as $album) {
            // A csoportkulcs nem lehet null, ezért a szezon nélküli albumok
            // egy üres kulcsú csoportba kerülnek
            $key = $album['season_name'] ?? '';

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'season' => $album['season_name'],
                    'albums' => [],
                ];
            }

            $groups[$key]['albums'][] = $album;
        }

        // A szezon nélküli csoport a lista végére kerül
        $withoutSeason = $groups[''] ?? null;
        unset($groups['']);

        $result = array_values($groups);

        if ($withoutSeason !== null) {
            $result[] = $withoutSeason;
        }

        return $result;
    }

    /**
     * Album archiválása vagy visszahelyezése az aktuális galériába.
     *
     * @throws RuntimeException Ha az album nem létezik.
     */
    public function setAlbumArchived(string $albumId, bool $archived): string
    {
        $album = $this->albumModel->findById($albumId);

        if ($album === null) {
            throw new RuntimeException('Az album nem található.');
        }

        $this->albumModel->updateArchived($albumId, $archived);

        return $album['name'];
    }

    /**
     * Album szezonjának beállítása.
     *
     * Az üres érték a "nincs megadva" állapotot jelenti, ezért null-ra
     * alakul: az idegen kulcs üres stringet nem fogadna el.
     *
     * @throws RuntimeException Ha az album nem létezik.
     */
    public function setAlbumSeason(string $albumId, ?string $seasonId): void
    {
        if ($this->albumModel->findById($albumId) === null) {
            throw new RuntimeException('Az album nem található.');
        }

        $seasonId = trim((string) $seasonId);

        $this->albumModel->updateSeason($albumId, $seasonId === '' ? null : $seasonId);
    }

    /**
     * Albumok a galéria listájához: borítókép és a dobogósok.
     *
     * A galéria minden albumot a borítóképével jelöl, alatta a dobogóval.
     * Az összeállítás legfeljebb három lekérdezésből áll (albumok a
     * borítóval, pótlás a borító nélkülieknek, helyezettek), nem
     * albumonként egyből.
     *
     * Ha egy albumnak van képe, de nincs beállított borítója - vagy a
     * beállított borító már nem létezik -, a legutóbb feltöltött képe
     * szolgál borítóként. Így egy hiányzó beállítás nem üres kártyát
     * eredményez.
     *
     * A kártya képe a KÖZEPES méret, nem a bélyegkép: a 200x200-as bélyeg a
     * 4:3-as kártyaterületre kifeszítve lágy és levágott volt. A bélyegkép
     * ettől nem lesz feleslegessé - kis méretű változatként bekerül a
     * srcset-be, és a közepes méret hiányában (régi kép) tartalékként áll.
     *
     * @param bool|null $archived null = mindegyik, false = csak az aktuális,
     *                            true = csak az archivált albumok
     * @return array<array{id:string, name:string, image_count:int, created_at:string, season_name:?string, is_archived:bool, cover_url:?string, cover_thumb_url:?string, cover_full_url:?string, cover_alt:string, placements:array}>
     */
    public function getAlbumsForListing(?bool $archived = false): array
    {
        $albums = $this->albumModel->findAllWithCover($archived);

        if ($albums === []) {
            return [];
        }

        // Amelyik albumnak van képe, de a borítója nem oldható fel,
        // ott a legfrissebb képet használjuk
        $needsFallback = [];
        foreach ($albums as $album) {
            if ($album['cover_full_path'] === null && (int) $album['image_count'] > 0) {
                $needsFallback[] = $album['id'];
            }
        }

        $fallbackImages = $needsFallback !== []
            ? $this->imageModel->findLatestByAlbumIds($needsFallback)
            : [];

        $placements = $this->placementModel->findByAlbumIds(array_column($albums, 'id'));

        $listing = [];
        foreach ($albums as $album) {
            $thumb = $album['cover_thumbnail_path'];
            $medium = $album['cover_medium_path'];
            $full = $album['cover_full_path'];
            $alt = $album['cover_alt_text'];

            if ($full === null && isset($fallbackImages[$album['id']])) {
                $fallback = $fallbackImages[$album['id']];
                $thumb = $fallback['thumbnail_path'];
                $medium = $fallback['medium_path'];
                $full = $fallback['full_path'];
                $alt = $fallback['alt_text'];
            }

            // A megjelenítendő kép a közepes méret; ha még nincs (a
            // medium_path oszlop utólag került a táblába), a bélyegkép áll be
            $display = $medium ?? $thumb;

            $listing[] = [
                'id' => $album['id'],
                'name' => $album['name'],
                'image_count' => (int) $album['image_count'],
                'created_at' => $album['created_at'],
                'season_name' => $album['season_name'],
                'is_archived' => (int) $album['is_archived'] === 1,
                'cover_url' => $display !== null ? '/' . $display : null,
                'cover_thumb_url' => $thumb !== null ? '/' . $thumb : null,
                'cover_full_url' => $full !== null ? '/' . $full : null,
                'cover_alt' => $alt ?? $album['name'],
                'placements' => $placements[$album['id']] ?? [],
            ];
        }

        return $listing;
    }

    /**
     * Egy album megjelenítéséhez szükséges adatok: borítókép és helyezettek.
     *
     * A galéria album oldala a borítóképet mutatja nagyban, mellette a
     * verseny helyezettjeit. A további feltöltött képek az albumban
     * megmaradnak, de a publikus oldalon nem jelennek meg.
     *
     * @return array{album:array, cover:?array, placements:array}|null
     */
    public function getAlbumView(string $albumId): ?array
    {
        $album = $this->albumModel->findById($albumId);

        if ($album === null) {
            return null;
        }

        $images = $this->imageModel->findByAlbumId($albumId);
        $cover = null;

        // Elsőként a beállított borítót keressük
        foreach ($images as $image) {
            if ($image['id'] === $album['cover_image_id']) {
                $cover = $image;
                break;
            }
        }

        // Beállítás vagy érvényes borító nélkül a legfrissebb kép áll be
        if ($cover === null && $images !== []) {
            $cover = $images[0];
        }

        return [
            'album' => $album,
            'cover' => $cover,
            'placements' => $this->placementModel->findByAlbumId($albumId),
        ];
    }

    /**
     * Egy album képeinek lekérdezése.
     *
     * @return array<array{id:string, album_id:string, filename:string, thumbnail_path:string, full_path:string, alt_text:?string, uploaded_at:string}>
     */
    public function getAlbumImages(string $albumId): array
    {
        return $this->imageModel->findByAlbumId($albumId);
    }

    /**
     * Új album létrehozása.
     *
     * @return array{id:string, name:string, cover_image_id:?string, image_count:int, created_at:string}
     */
    public function createAlbum(string $name): array
    {
        $id = Uuid::uuid4()->toString();

        $this->albumModel->create($id, $name);

        $album = $this->albumModel->findById($id);

        if ($album === null) {
            throw new RuntimeException('Album létrehozás sikertelen.');
        }

        return $album;
    }

    /**
     * Album átnevezése.
     *
     * A név érvényességét (kötelező, max. 100 karakter) a hívó
     * ValidationService::validateAlbum() ellenőrzi.
     *
     * @return array{id:string, name:string, cover_image_id:?string, image_count:int, created_at:string}
     * @throws RuntimeException Ha az album nem létezik.
     */
    public function renameAlbum(string $albumId, string $name): array
    {
        if ($this->albumModel->findById($albumId) === null) {
            throw new RuntimeException('Az album nem található.');
        }

        $this->albumModel->updateName($albumId, $name);

        $album = $this->albumModel->findById($albumId);

        if ($album === null) {
            throw new RuntimeException('Album átnevezés sikertelen.');
        }

        return $album;
    }

    /**
     * Album törlése a benne lévő képekkel együtt.
     *
     * A képrekordokat az idegen kulcs kaszkádja is törölné, a feltöltött
     * fájlokat viszont nem, ezért azokat itt takarítjuk el - még az album
     * rekord törlése előtt, amíg az útvonalak kiolvashatók.
     *
     * Lépések:
     * 1. Album keresése (nem létező albumra hiba)
     * 2. Képfájlok törlése (full + thumb)
     * 3. Az album feltöltési könyvtárának eltávolítása
     * 4. Album rekord törlése (a képrekordok kaszkádban követik)
     *
     * @return string A törölt album neve, visszajelzéshez
     * @throws RuntimeException Ha az album nem található.
     */
    public function deleteAlbum(string $albumId): string
    {
        $album = $this->albumModel->findById($albumId);

        if ($album === null) {
            throw new RuntimeException('Az album nem található.');
        }

        $publicDir = dirname(__DIR__, 2) . '/public/';

        // Képfájlok törlése egyenként, az adatbázisban tárolt útvonalak alapján
        foreach ($this->imageModel->findByAlbumId($albumId) as $image) {
            $this->imageService->deleteImageFiles(
                $publicDir . $image['full_path'],
                $publicDir . $image['thumbnail_path']
            );
        }

        // Az album könyvtárának eltávolítása (full/, thumb/ és maga a könyvtár)
        $this->removeAlbumDirectory($albumId);

        // Album rekord törlése - a képrekordok FK cascade révén törlődnek
        $this->albumModel->delete($albumId);

        return $album['name'];
    }

    /**
     * Egy album feltöltési könyvtárának eltávolítása.
     *
     * Csak a public/uploads/albums/ alatti könyvtárat törli: a realpath
     * ellenőrzés megakadályozza, hogy egy manipulált azonosító a könyvtáron
     * kívülre mutasson. Nem rekurzív a végtelenségig, mert a szerkezet
     * ismert és fix: {album}/full, {album}/medium és {album}/thumb.
     */
    private function removeAlbumDirectory(string $albumId): void
    {
        $albumsRoot = realpath(dirname(__DIR__, 2) . '/public/uploads/albums');

        if ($albumsRoot === false) {
            return;
        }

        $albumDir = realpath($albumsRoot . DIRECTORY_SEPARATOR . $albumId);

        // Nincs könyvtár (üres album), vagy kilépne az albumok gyökeréből
        if ($albumDir === false || !str_starts_with($albumDir, $albumsRoot . DIRECTORY_SEPARATOR)) {
            return;
        }

        foreach (['full', 'medium', 'thumb'] as $subDir) {
            $path = $albumDir . DIRECTORY_SEPARATOR . $subDir;

            if (!is_dir($path)) {
                continue;
            }

            // Maradék fájlok (pl. sikertelen feltöltés töredékei) eltávolítása
            foreach (glob($path . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            @rmdir($path);
        }

        @rmdir($albumDir);
    }

    /**
     * Kép feltöltése egy albumba.
     *
     * Lépések:
     * 1. UUID generálás
     * 2. Egyedi fájlnév: uuid + eredeti kiterjesztés
     * 3. Könyvtárak létrehozása ha szükséges
     * 4. Fájl áthelyezés a full/ könyvtárba
     * 5. Bélyegkép generálás a thumb/ könyvtárba
     * 6. DB rekord mentés
     * 7. Album image_count növelés
     * 8. Ha ez az első kép, beállítás borítóképnek
     *
     * @param string $albumId Az album azonosítója
     * @param array $uploadedFile A $_FILES tömb egy eleme (name, tmp_name, type, size, error)
     * @return array{id:string, album_id:string, filename:string, thumbnail_path:string, full_path:string, alt_text:?string, uploaded_at:string}
     */
    public function uploadImage(string $albumId, array $uploadedFile): array
    {
        $imageId = Uuid::uuid4()->toString();

        // Eredeti fájl kiterjesztés megállapítása
        $originalName = $uploadedFile['name'] ?? '';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Egyedi fájlnév generálás
        $filename = $imageId . '.' . $extension;

        // Útvonalak meghatározása (relatív a public könyvtárhoz)
        $relativeFull = 'uploads/albums/' . $albumId . '/full/' . $filename;
        $relativeMedium = 'uploads/albums/' . $albumId . '/medium/' . $filename;
        $relativeThumb = 'uploads/albums/' . $albumId . '/thumb/' . $filename;

        // Abszolút útvonalak a fájlrendszeren
        $publicDir = dirname(__DIR__, 2) . '/public/';
        $fullPath = $publicDir . $relativeFull;
        $mediumPath = $publicDir . $relativeMedium;
        $thumbPath = $publicDir . $relativeThumb;

        // Könyvtárak létrehozása ha szükséges
        foreach ([dirname($fullPath), dirname($mediumPath), dirname($thumbPath)] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Fájl áthelyezés
        $tmpName = $uploadedFile['tmp_name'] ?? '';
        if (!move_uploaded_file($tmpName, $fullPath)) {
            throw new RuntimeException('Fájl feltöltés sikertelen.');
        }

        // Bélyegkép generálás
        $thumbnailCreated = $this->imageService->createThumbnail($fullPath, $thumbPath);
        if (!$thumbnailCreated) {
            // Ha a bélyegkép generálás sikertelen, töröljük a feltöltött fájlt
            @unlink($fullPath);
            throw new RuntimeException('Bélyegkép generálás sikertelen.');
        }

        // Közepes méret a megjelenítéshez. A galéria kártyái és az album
        // oldala ezt mutatják: a bélyegkép ide túl kicsi (kifeszítve lágy),
        // az eredeti pedig feleslegesen nagy letöltés.
        //
        // Ha nem sikerül, a feltöltés nem hiúsul meg: a medium_path marad
        // null, és a megjelenítés a bélyegképre esik vissza. Egy nem
        // létrehozható származtatott méret miatt nem érdemes elveszíteni a
        // már feltöltött fényképet.
        $mediumCreated = $this->imageService->createResized($fullPath, $mediumPath);

        if (!$mediumCreated) {
            error_log('[GalleryService] A közepes méret nem készült el: ' . $relativeMedium);
        }

        // DB rekord mentés
        $this->imageModel->create(
            $imageId,
            $albumId,
            $filename,
            $relativeThumb,
            $relativeFull,
            null,
            $mediumCreated ? $relativeMedium : null
        );

        // Album image_count növelés
        $this->albumModel->incrementImageCount($albumId);

        // Ha ez az első kép az albumban, beállítás borítóképnek
        $album = $this->albumModel->findById($albumId);
        if ($album !== null && $album['cover_image_id'] === null) {
            $this->albumModel->updateCoverImageId($albumId, $imageId);
        }

        // Visszatérés a mentett kép adataival
        $image = $this->imageModel->findById($imageId);

        if ($image === null) {
            throw new RuntimeException('Kép rekord lekérdezés sikertelen.');
        }

        return $image;
    }

    /**
     * Kép törlése (fájlok + DB rekord).
     *
     * Lépések:
     * 1. Kép keresése ID alapján
     * 2. Fájlok törlése (full + thumb)
     * 3. DB rekord törlése
     * 4. Album image_count csökkentése
     */
    public function deleteImage(string $imageId): void
    {
        // Kép keresése
        $image = $this->imageModel->findById($imageId);

        if ($image === null) {
            throw new RuntimeException('A kép nem található.');
        }

        // Abszolút útvonalak a fájlrendszeren
        $publicDir = dirname(__DIR__, 2) . '/public/';
        $fullPath = $publicDir . $image['full_path'];
        $thumbPath = $publicDir . $image['thumbnail_path'];
        $mediumPath = ($image['medium_path'] ?? null) !== null
            ? $publicDir . $image['medium_path']
            : null;

        // Fájlok törlése
        $this->imageService->deleteImageFiles($fullPath, $thumbPath, $mediumPath);

        // DB rekord törlése
        $this->imageModel->delete($imageId);

        // Album image_count csökkentése
        $this->albumModel->decrementImageCount($image['album_id']);

        // Ha a borítókép tűnt el, új borítót választunk a maradék képekből.
        // Nélküle az albums.cover_image_id egy már nem létező képre mutatna,
        // és a galéria listája helyőrzőt jelenítene meg a fotó helyett.
        $album = $this->albumModel->findById($image['album_id']);

        if ($album !== null && $album['cover_image_id'] === $imageId) {
            $remaining = $this->imageModel->findByAlbumId($image['album_id']);
            $this->albumModel->updateCoverImageId(
                $image['album_id'],
                $remaining[0]['id'] ?? null
            );
        }
    }
}
