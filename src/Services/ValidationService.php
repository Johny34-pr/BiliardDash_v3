<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;

class ValidationService
{
    public function validateNews(array $data): Validator
    {
        $v = new Validator();
        $v->required('title', $data['title'] ?? null, 'A cím megadása kötelező')
          ->maxLength('title', $data['title'] ?? null, 200, 'A cím maximum 200 karakter lehet')
          ->required('content', $data['content'] ?? null, 'A tartalom megadása kötelező');
        return $v;
    }

    public function validateAlbum(array $data): Validator
    {
        $v = new Validator();
        $name = trim($data['name'] ?? '');
        $v->required('name', $name ?: null, 'Az album neve kötelező')
          ->maxLength('name', $name, 100, 'Az album neve maximum 100 karakter lehet');
        return $v;
    }

    public function validateRegistration(array $data): Validator
    {
        $v = new Validator();
        $v->required('fullName', $data['fullName'] ?? null, 'A név megadása kötelező')
          ->maxLength('fullName', $data['fullName'] ?? null, 100, 'A név maximum 100 karakter lehet')
          ->required('email', $data['email'] ?? null, 'Az e-mail cím megadása kötelező')
          ->email('email', $data['email'] ?? null, 'Érvénytelen e-mail formátum')
          ->required('phone', $data['phone'] ?? null, 'A telefonszám megadása kötelező');
        return $v;
    }

    public function validateCompetition(array $data): Validator
    {
        $v = new Validator();
        $v->required('name', $data['name'] ?? null, 'A verseny neve kötelező')
          ->maxLength('name', $data['name'] ?? null, 100, 'A név maximum 100 karakter lehet')
          ->required('date', $data['date'] ?? null, 'A dátum megadása kötelező')
          ->date('date', $data['date'] ?? null, 'Érvénytelen dátum formátum')
          ->required('venue', $data['venue'] ?? null, 'A helyszín megadása kötelező')
          ->maxLength('venue', $data['venue'] ?? null, 200, 'A helyszín maximum 200 karakter')
          ->required('registrationDeadline', $data['registrationDeadline'] ?? null, 'A nevezési határidő megadása kötelező')
          ->date('registrationDeadline', $data['registrationDeadline'] ?? null, 'Érvénytelen határidő formátum');
        return $v;
    }

    public function validateImageUpload(array $file): Validator
    {
        $v = new Validator();
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 10 * 1024 * 1024; // 10 MB

        $v->fileType('image', $allowedTypes, $file['type'] ?? '', 'Csak JPEG és PNG formátum támogatott')
          ->fileSize('image', $maxSize, $file['size'] ?? 0, 'A fájl mérete nem haladhatja meg a 10 MB-ot');
        return $v;
    }
}
