<?php

declare(strict_types=1);

/**
 * SMTP e-mail konfiguráció (PHPMailer)
 */

return [
    'host' => $_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST') ?: 'smtp.mailtrap.io',
    'port' => (int)($_ENV['MAIL_PORT'] ?? getenv('MAIL_PORT') ?: 587),
    'username' => $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME') ?: '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD') ?: '',
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? getenv('MAIL_ENCRYPTION') ?: 'tls',
    'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?: 'info@magyarbilliard.hu',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: 'Magyar Biliárd',
];
