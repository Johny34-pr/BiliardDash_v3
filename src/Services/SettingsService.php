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

    /**
     * A kapcsolható funkciók alapértelmezései.
     *
     * Ha egy kulcs nincs az adatbázisban, ez az érték érvényes. A fórum
     * alapértelmezetten kikapcsolt, ezért egy friss telepítésen sem jelenik
     * meg, amíg a szervező nem engedélyezi.
     *
     * @var array<string, string>
     */
    private const DEFAULTS = [
        self::FORUM_ENABLED => '0',
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
     * Logikai beállítás mentése.
     */
    public function setEnabled(string $key, bool $enabled): void
    {
        $this->settingModel->save($key, $enabled ? '1' : '0');
        self::clearCache();
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
