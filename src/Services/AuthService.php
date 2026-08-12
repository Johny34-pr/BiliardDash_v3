<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\AppException;
use App\Models\User;
use PDO;
use Ramsey\Uuid\Uuid;

/**
 * Publikus felhasználói fiókok kezelése.
 *
 * A jelszavak a PHP beépített password_hash() függvényével készülnek
 * (bcrypt), és soha nem kerülnek naplóba vagy sessionbe.
 */
class AuthService
{
    /** A jelszó minimális hossza karakterben */
    public const MIN_PASSWORD_LENGTH = 8;

    private User $userModel;

    public function __construct(private PDO $db)
    {
        $this->userModel = new User($db);
    }

    /**
     * Új fiók létrehozása.
     *
     * Az e-mail címet kisbetűsítve tárolja, hogy a bejelentkezés ne legyen
     * kis-nagybetű érzékeny.
     *
     * @return array{id:string, name:string, email:string, phone:string, created_at:string}
     * @throws AppException Ha az e-mail cím már használatban van.
     */
    public function register(string $name, string $email, string $phone, string $password): array
    {
        $email = $this->normalizeEmail($email);

        if ($this->userModel->emailExists($email)) {
            throw AppException::duplicateEntry('Ezzel az e-mail címmel már létezik fiók');
        }

        $id = Uuid::uuid4()->toString();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $this->userModel->create($id, $name, $email, $phone, $passwordHash);

        $user = $this->userModel->findById($id);

        if ($user === null) {
            throw new AppException('A fiók létrehozása nem sikerült', AppException::SERVER_ERROR);
        }

        return $user;
    }

    /**
     * Bejelentkezési kísérlet.
     *
     * Szándékosan nem árulja el, hogy az e-mail cím vagy a jelszó volt hibás,
     * így nem lehet vele létező fiókokat felderíteni.
     *
     * @return array{id:string, name:string, email:string, phone:string}|null
     *         A felhasználó adatai, vagy null hibás adatok esetén.
     */
    public function attemptLogin(string $email, string $password): ?array
    {
        $user = $this->userModel->findByEmail($this->normalizeEmail($email));

        if ($user === null) {
            // Időzítéses támadás elleni védelem: akkor is végezzünk hash
            // ellenőrzést, ha a fiók nem létezik.
            password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG.');
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
        ];
    }

    /**
     * Felhasználó lekérdezése azonosító alapján.
     *
     * @return array{id:string, name:string, email:string, phone:string, created_at:string}|null
     */
    public function getUserById(string $id): ?array
    {
        return $this->userModel->findById($id);
    }

    /**
     * E-mail cím normalizálása: körülvágás és kisbetűsítés.
     */
    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
