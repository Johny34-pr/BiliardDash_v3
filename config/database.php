<?php

declare(strict_types=1);

/**
 * Adatbázis konfiguráció
 *
 * A .env betöltését az App\Core\Env osztály végzi. A hívás itt is szerepel
 * biztonsági tartalékként (a betöltés idempotens), hogy a konfiguráció akkor
 * is helyes legyen, ha ezt a fájlt a belépési ponton kívülről töltik be -
 * például egy karbantartó szkriptből.
 */

use App\Core\Env;

Env::load();

return [
    'host' => Env::get('DB_HOST', 'localhost'),
    'dbname' => Env::get('DB_NAME', 'billiard'),
    'username' => Env::get('DB_USERNAME', 'root'),
    // A jelszó lehet szándékosan üres, ezért itt üres string az alapérték
    'password' => Env::get('DB_PASSWORD', '') ?? '',
];
