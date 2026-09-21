<?php

declare(strict_types=1);

/**
 * Alkalmazás konfiguráció
 *
 * A környezeti változókat az App\Core\Env osztály olvassa be a .env fájlból.
 * A betöltés idempotens, ezért itt is meghívjuk: így ez a fájl önmagában is
 * helyes értékeket ad, nem csak akkor, ha előtte más konfiguráció betöltődött.
 */

use App\Core\Env;

Env::load();

return [
    'name' => Env::get('APP_NAME', 'Okányi Biliárd Klub'),
    'base_url' => Env::get('APP_URL', 'http://localhost'),
    'upload_max_size' => 10 * 1024 * 1024, // 10 MB
    'upload_allowed_types' => ['image/jpeg', 'image/png'],
    'admin_password' => Env::get('ADMIN_PASSWORD', 'admin123'),
    'debug' => Env::bool('APP_DEBUG', false),

    /*
     * A TinyMCE szerkesztő CDN kulcsa.
     *
     * Kulcs nélkül a szerkesztő betölthető a 'no-api-key' azonosítóval, de
     * a felületen figyelmeztetés jelenik meg. Saját kulcs a tiny.cloud
     * oldalon igényelhető, és a .env fájlba kerül - nem a forráskódba.
     */
    'tinymce_api_key' => Env::get('TINYMCE_API_KEY', ''),
];
