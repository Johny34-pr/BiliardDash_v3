<?php

declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $errors = [];

    public function required(string $field, ?string $value, string $message): self
    {
        if ($value === null || trim($value) === '') {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function maxLength(string $field, ?string $value, int $max, string $message): self
    {
        if ($value !== null && mb_strlen($value) > $max) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function minLength(string $field, ?string $value, int $min, string $message): self
    {
        if ($value !== null && mb_strlen($value) < $min) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function email(string $field, ?string $value, string $message): self
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function date(string $field, ?string $value, string $message): self
    {
        if ($value !== null && $value !== '') {
            $parsed = date_parse($value);
            if ($parsed['error_count'] > 0 || $parsed['year'] === false) {
                $this->errors[$field] = $message;
            }
        }
        return $this;
    }

    public function fileType(string $field, array $allowed, string $actualType, string $message): self
    {
        if (!in_array($actualType, $allowed, true)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function fileSize(string $field, int $maxBytes, int $actualSize, string $message): self
    {
        if ($actualSize > $maxBytes) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /**
     * Egyedi hiba hozzáadása.
     *
     * Olyan szabályokhoz, amelyek nem fejezhetők ki a fenti validátorokkal,
     * például két mező egyezésének ellenőrzéséhez. A többi validátorhoz
     * hasonlóan mezőnként egy hibát tart nyilván, a legutóbb beállítottat.
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getError(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }
}
