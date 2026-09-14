<?php

declare(strict_types=1);

/**
 * SMTP e-mail konfiguráció (PHPMailer)
 *
 * A környezeti változókat az App\Core\Env osztály olvassa be a .env fájlból.
 */

use App\Core\Env;

Env::load();

return [
    'host' => Env::get('MAIL_HOST', 'smtp.mailtrap.io'),
    'port' => (int) (Env::get('MAIL_PORT', '587') ?? '587'),
    // A felhasználó és a jelszó lehet szándékosan üres (pl. helyi teszt SMTP)
    'username' => Env::get('MAIL_USERNAME', '') ?? '',
    'password' => Env::get('MAIL_PASSWORD', '') ?? '',
    'encryption' => Env::get('MAIL_ENCRYPTION', 'tls'),
    'from_address' => Env::get('MAIL_FROM_ADDRESS', 'info@magyarbilliard.hu'),
    'from_name' => Env::get('MAIL_FROM_NAME', 'Magyar Biliárd'),
];
