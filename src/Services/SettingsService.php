<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use PDO;

/**
 * Oldalbeállítások olvasása és mentése.
 *
 * A beállításokra a kérés több pontján is szükség van (útvonalak
 * regisztrálása, menü, lábléc, oldaltérkép), ezért az értékek kérésen belül
 * egyszer töltődnek be és statikus gyorsítótárba kerülnek. A gyorsítótár
 * szándékosan statikus: a nézetekben nincs mód szolgáltatást átadni, ott a
 * forumEnabled() helper hívja ugyanezt az osztályt, és így nem keletkezik
 * lekérdezés minden hívásnál.
 */
class SettingsService
{
    /** A fórum modul kapcsolója */
    public const FORUM_ENABLED = 'forum_enabled';

    /** A ranglista modul kapcsolója */
    public const RANKING_ENABLED = 'ranking_enabled';

    /**
     * A közvetítés menüpont célja.
     *
     * Szöveges beállítás, nem kapcsoló: a menüpont akkor jelenik meg, ha van
     * megadva cím. Így egyetlen mező dönt a megjelenésről is - nem lehet
     * "bekapcsolva, de üres" állapotba jutni, ami törött hivatkozást adna.
     */
    public const BROADCAST_URL = 'broadcast_url';

    /** A közvetítés menüpont felirata */
    public const BROADCAST_LABEL = 'broadcast_label';

    /**
     * A beállítások alapértelmezései.
     *
     * Ha egy kulcs nincs az adatbázisban, ez az érték érvényes. A fórum
     * alapértelmezetten kikapcsolt, ezért egy friss telepítésen sem jelenik
     * meg, amíg a szervező nem engedélyezi. A ranglista viszont igen: a
     * szervező kérte a felületet, tehát legyen látható.
     *
     * @var array<string, string>
     */
    private const DEFAULTS = [
        self::FORUM_ENABLED => '0',
        self::RANKING_ENABLED => '1',
        self::BROADCAST_URL => '',
        self::BROADCAST_LABEL => 'Közvetítés',
    ];

    /** @var array<string, string|null>|null Kérésen belüli gyorsítótár */
    private static ?array $cache = null;

    private Setting $settingModel;

    public function __construct(private PDO $db)
    {
        $this->settingModel = new Setting($db);
    }

    /**
     * Az összes beállítás, az alapértelmezésekkel feltöltve.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        if (self::$cache === null) {
            self::$cache = array_merge(self::DEFAULTS, $this->settingModel->findAllAsMap());
        }

        return self::$cache;
    }

    /**
     * Egy beállítás szöveges értéke.
     */
    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Logikai beállítás: csak az '1' jelent igazat.
     *
     * Így egy elírt vagy üres érték kikapcsolt állapotot jelent, nem
     * véletlenül bekapcsoltat.
     */
    public function isEnabled(string $key): bool
    {
        return $this->get($key, self::DEFAULTS[$key] ?? '0') === '1';
    }

    /**
     * Aktív-e a fórum modul.
     */
    public function isForumEnabled(): bool
    {
        return $this->isEnabled(self::FORUM_ENABLED);
    }

    /**
     * Aktív-e a ranglista modul.
     */
    public function isRankingEnabled(): bool
    {
        return $this->isEnabled(self::RANKING_ENABLED);
    }

    /**
     * Logikai beállítás mentése.
     */
    public function setEnabled(string $key, bool $enabled): void
    {
        $this->settingModel->save($key, $enabled ? '1' : '0');
        self::clearCache();
    }

    /**
     * Szöveges beállítás mentése.
     *
     * A körülvágás itt történik, egy helyen: egy véletlen szóköz a beírt
     * URL végén törött hivatkozást adna, és ezt nem érdemes minden
     * hívóra hagyni.
     */
    public function setValue(string $key, ?string $value): void
    {
        $value = trim((string) $value);

        $this->settingModel->save($key, $value);
        self::clearCache();
    }

    /**
     * A közvetítés menüpont adatai, vagy null ha nincs beállítva.
     *
     * Csak http és https címet fogad el. Enélkül egy elírt vagy szándékosan
     * beírt "javascript:" séma kerülhetne a menübe, ami a látogató
     * böngészőjében futó kódot jelentene.
     *
     * @return array{url:string, label:string}|null
     */
    public function getBroadcastLink(): ?array
    {
        $url = trim((string) $this->get(self::BROADCAST_URL, ''));

        if ($url === '' || preg_match('#^https?://#i', $url) !== 1) {
            return null;
        }

        $label = trim((string) $this->get(self::BROADCAST_LABEL, ''));

        return [
            'url' => $url,
            'label' => $label !== '' ? $label : 'Közvetítés',
        ];
    }

    /**
     * A gyorsítótár eldobása.
     *
     * Mentés után automatikusan lefut. Tesztekben azért kell külön hívni,
     * mert a statikus gyorsítótár a tesztesetek között is fennmaradna.
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
