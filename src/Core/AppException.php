<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

class AppException extends Exception
{
    public const NOT_FOUND = 404;
    public const VALIDATION_ERROR = 422;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const SERVER_ERROR = 500;
    public const DUPLICATE_ENTRY = 409;
    public const DEADLINE_PASSED = 410;

    private array $context;

    public function __construct(
        string $message = '',
        int $code = self::SERVER_ERROR,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function notFound(string $message = 'Az oldal nem található'): self
    {
        return new self($message, self::NOT_FOUND);
    }

    public static function validationError(string $message = 'Érvénytelen adatok', array $errors = []): self
    {
        return new self($message, self::VALIDATION_ERROR, ['errors' => $errors]);
    }

    public static function unauthorized(string $message = 'Bejelentkezés szükséges'): self
    {
        return new self($message, self::UNAUTHORIZED);
    }

    public static function forbidden(string $message = 'Hozzáférés megtagadva'): self
    {
        return new self($message, self::FORBIDDEN);
    }

    public static function duplicateEntry(string $message = 'Már létezik ilyen bejegyzés'): self
    {
        return new self($message, self::DUPLICATE_ENTRY);
    }

    public static function deadlinePassed(string $message = 'A határidő lejárt'): self
    {
        return new self($message, self::DEADLINE_PASSED);
    }
}
