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

    /**
     * Szerkeszthető tartalmi oldal validálása.
     *
     * A meta leírás nem kötelező: üresen hagyva a PageService a tartalomból
     * készít egyet. A 300 karakteres korlát az adatbázis oszlopmérete.
     */
    public function validatePage(array $data): Validator
    {
        $v = new Validator();
        $v->required('title', $data['title'] ?? null, 'A cím megadása kötelező')
          ->maxLength('title', $data['title'] ?? null, 200, 'A cím maximum 200 karakter lehet')
          ->required('content', $data['content'] ?? null, 'A tartalom megadása kötelező')
          ->maxLength('metaDescription', $data['metaDescription'] ?? null, 300,
              'A leírás maximum 300 karakter lehet');

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

    /**
     * Album helyezett validálása.
     *
     * A helyezés 1 és 999 közötti egész szám. Az alsó korlát azért 1, mert
     * nincs nulladik helyezett; a felső korlát csak az elírás ellen védi az
     * adatbázist, nem valós versenyméret.
     *
     * A megjegyzés (pl. egyesület) nem kötelező.
     */
    public function validatePlacement(array $data): Validator
    {
        $v = new Validator();
        $position = trim((string) ($data['position'] ?? ''));

        $v->required('playerName', $data['playerName'] ?? null, 'A név megadása kötelező')
          ->maxLength('playerName', $data['playerName'] ?? null, 100, 'A név maximum 100 karakter lehet')
          ->required('position', $position ?: null, 'A helyezés megadása kötelező')
          ->maxLength('note', $data['note'] ?? null, 150, 'A megjegyzés maximum 150 karakter lehet');

        // Számformátum csak akkor vizsgálandó, ha egyáltalán van érték
        if ($position !== '' && (!ctype_digit($position) || (int) $position < 1 || (int) $position > 999)) {
            $v->addError('position', 'A helyezés 1 és 999 közötti szám legyen');
        }

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

    /**
     * Verseny validálása.
     *
     * A nevezés nyitódátuma (registrationOpensAt) nem kötelező: üresen
     * hagyva a nevezés a kiírástól a határidőig nyitott. Ha meg van adva,
     * a határidő előtt kell lennie, különben a nevezés soha nem nyílna meg.
     */
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
          ->date('registrationDeadline', $data['registrationDeadline'] ?? null, 'Érvénytelen határidő formátum')
          ->date('registrationOpensAt', $data['registrationOpensAt'] ?? null, 'Érvénytelen nyitódátum formátum');

        $opensAt = trim((string) ($data['registrationOpensAt'] ?? ''));
        $deadline = trim((string) ($data['registrationDeadline'] ?? ''));

        // Sorrend ellenőrzése - csak ha mindkét időpont önmagában érvényes
        if ($opensAt !== '' && $deadline !== ''
            && $v->getError('registrationOpensAt') === null
            && $v->getError('registrationDeadline') === null
            && strtotime($opensAt) >= strtotime($deadline)
        ) {
            $v->addError('registrationOpensAt', 'A nyitódátumnak a nevezési határidő előtt kell lennie');
        }

        return $v;
    }

    /**
     * Felhasználói fiók regisztráció validálása.
     *
     * A jelszó minimális hosszát az AuthService::MIN_PASSWORD_LENGTH adja meg,
     * és a megerősítő mezővel is egyeznie kell.
     */
    public function validateUserRegistration(array $data): Validator
    {
        $v = new Validator();
        $minLength = AuthService::MIN_PASSWORD_LENGTH;

        $v->required('name', $data['name'] ?? null, 'A név megadása kötelező')
          ->maxLength('name', $data['name'] ?? null, 100, 'A név maximum 100 karakter lehet')
          ->required('email', $data['email'] ?? null, 'Az e-mail cím megadása kötelező')
          ->email('email', $data['email'] ?? null, 'Érvénytelen e-mail formátum')
          ->maxLength('email', $data['email'] ?? null, 255, 'Az e-mail cím maximum 255 karakter lehet')
          ->required('phone', $data['phone'] ?? null, 'A telefonszám megadása kötelező')
          ->maxLength('phone', $data['phone'] ?? null, 50, 'A telefonszám maximum 50 karakter lehet')
          ->required('city', $data['city'] ?? null, 'A település megadása kötelező')
          ->maxLength('city', $data['city'] ?? null, 100, 'A település maximum 100 karakter lehet')
          ->required('password', $data['password'] ?? null, 'A jelszó megadása kötelező')
          ->minLength('password', $data['password'] ?? null, $minLength, "A jelszó legalább {$minLength} karakter legyen");

        // Jelszó megerősítés egyezése - csak ha van megadott jelszó
        if (!empty($data['password']) && ($data['passwordConfirm'] ?? '') !== $data['password']) {
            $v->addError('passwordConfirm', 'A két jelszó nem egyezik');
        }

        return $v;
    }

    /**
     * Fiók módosítása szervezői felületen.
     *
     * A jelszó itt nem kötelező: üresen hagyva a meglévő marad érvényben.
     * Ha meg van adva, ugyanaz a hosszkorlát él, mint regisztrációnál.
     */
    public function validateUserUpdate(array $data): Validator
    {
        $v = new Validator();
        $minLength = AuthService::MIN_PASSWORD_LENGTH;

        $v->required('name', $data['name'] ?? null, 'A név megadása kötelező')
          ->maxLength('name', $data['name'] ?? null, 100, 'A név maximum 100 karakter lehet')
          ->required('email', $data['email'] ?? null, 'Az e-mail cím megadása kötelező')
          ->email('email', $data['email'] ?? null, 'Érvénytelen e-mail formátum')
          ->maxLength('email', $data['email'] ?? null, 255, 'Az e-mail cím maximum 255 karakter lehet')
          ->required('phone', $data['phone'] ?? null, 'A telefonszám megadása kötelező')
          ->maxLength('phone', $data['phone'] ?? null, 50, 'A telefonszám maximum 50 karakter lehet')
          ->required('city', $data['city'] ?? null, 'A település megadása kötelező')
          ->maxLength('city', $data['city'] ?? null, 100, 'A település maximum 100 karakter lehet');

        // Jelszó csak akkor vizsgálandó, ha a szervező meg is adott újat
        $password = (string) ($data['password'] ?? '');

        if ($password !== '') {
            $v->minLength('password', $password, $minLength,
                "A jelszó legalább {$minLength} karakter legyen");

            if (($data['passwordConfirm'] ?? '') !== $password) {
                $v->addError('passwordConfirm', 'A két jelszó nem egyezik');
            }
        }

        return $v;
    }

    /**
     * Fórum topik validálása.
     *
     * A név csak vendégként kötelező; bejelentkezve a fiók nevét használjuk.
     *
     * @param bool $isGuest Vendégként nyitja-e a topikot
     */
    public function validateTopic(array $data, bool $isGuest): Validator
    {
        $v = new Validator();

        if ($isGuest) {
            $v->required('authorName', $data['authorName'] ?? null, 'A név megadása kötelező')
              ->maxLength('authorName', $data['authorName'] ?? null, CommentService::MAX_NAME_LENGTH,
                  'A név maximum ' . CommentService::MAX_NAME_LENGTH . ' karakter lehet');
        }

        $v->required('title', $data['title'] ?? null, 'A cím megadása kötelező')
          ->minLength('title', $data['title'] ?? null, TopicService::MIN_TITLE_LENGTH,
              'A cím legalább ' . TopicService::MIN_TITLE_LENGTH . ' karakter legyen')
          ->maxLength('title', $data['title'] ?? null, TopicService::MAX_TITLE_LENGTH,
              'A cím maximum ' . TopicService::MAX_TITLE_LENGTH . ' karakter lehet')
          ->required('body', $data['body'] ?? null, 'A nyitó bejegyzés nem lehet üres')
          ->minLength('body', $data['body'] ?? null, TopicService::MIN_BODY_LENGTH,
              'A nyitó bejegyzés túl rövid')
          ->maxLength('body', $data['body'] ?? null, TopicService::MAX_BODY_LENGTH,
              'A nyitó bejegyzés maximum ' . TopicService::MAX_BODY_LENGTH . ' karakter lehet');

        return $v;
    }

    /**
     * Fórum hozzászólás validálása.
     *
     * A név csak vendégként kötelező; bejelentkezve a fiók nevét használjuk.
     * A hosszkorlátokat a CommentService konstansai adják meg.
     *
     * @param bool $isGuest Vendégként küldi-e be a hozzászólást
     */
    public function validateComment(array $data, bool $isGuest): Validator
    {
        $v = new Validator();

        if ($isGuest) {
            $v->required('authorName', $data['authorName'] ?? null, 'A név megadása kötelező')
              ->maxLength('authorName', $data['authorName'] ?? null, CommentService::MAX_NAME_LENGTH,
                  'A név maximum ' . CommentService::MAX_NAME_LENGTH . ' karakter lehet');
        }

        $v->required('body', $data['body'] ?? null, 'A hozzászólás nem lehet üres')
          ->minLength('body', $data['body'] ?? null, CommentService::MIN_LENGTH,
              'A hozzászólás túl rövid')
          ->maxLength('body', $data['body'] ?? null, CommentService::MAX_LENGTH,
              'A hozzászólás maximum ' . CommentService::MAX_LENGTH . ' karakter lehet');

        return $v;
    }

    /**
     * Bejelentkezési adatok validálása.
     *
     * Csak a mezők jelenlétét ellenőrzi; a hitelesítés az AuthService dolga.
     */
    public function validateUserLogin(array $data): Validator
    {
        $v = new Validator();
        $v->required('email', $data['email'] ?? null, 'Az e-mail cím megadása kötelező')
          ->required('password', $data['password'] ?? null, 'A jelszó megadása kötelező');
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
