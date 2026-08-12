<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Database;
use App\Core\Session;
use App\Services\AuthService;
use App\Services\CompetitionService;
use App\Services\EmailService;
use App\Services\ValidationService;

/**
 * Publikus felhasználói fiókok: regisztráció, belépés, kilépés,
 * valamint a felhasználó által rögzített nevezések kezelése.
 *
 * Az admin belépés ettől függetlenül működik (AdminController).
 */
class AuthController
{
    private AuthService $authService;
    private ValidationService $validationService;
    private CompetitionService $competitionService;

    public function __construct()
    {
        $db = Database::getConnection();
        $mailConfig = require __DIR__ . '/../../config/mail.php';

        $this->authService = new AuthService($db);
        $this->validationService = new ValidationService();
        $this->competitionService = new CompetitionService($db, new EmailService($mailConfig));
    }

    // =====================================================================
    // Regisztráció
    // =====================================================================

    public function registerForm(): void
    {
        if (Session::isUser()) {
            redirect('/fiok');
        }

        $errors = [];
        $data = [];
        $this->renderAuth('register', 'Regisztráció - Magyar Biliárd', $errors, $data);
    }

    public function register(): void
    {
        if (Session::isUser()) {
            redirect('/fiok');
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'passwordConfirm' => $_POST['password_confirm'] ?? '',
        ];

        $validator = $this->validationService->validateUserRegistration($data);

        if (!$validator->isValid()) {
            $this->renderAuth('register', 'Regisztráció - Magyar Biliárd', $validator->getErrors(), $data);
            return;
        }

        try {
            $user = $this->authService->register(
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['password']
            );
        } catch (AppException $e) {
            // Foglalt e-mail cím: a mező mellett jelezzük
            $errors = $e->getCode() === AppException::DUPLICATE_ENTRY
                ? ['email' => $e->getMessage()]
                : ['general' => 'A regisztráció nem sikerült. Kérjük, próbálja újra.'];

            $this->renderAuth('register', 'Regisztráció - Magyar Biliárd', $errors, $data);
            return;
        } catch (\Throwable $e) {
            error_log('[AuthController] Regisztrációs hiba: ' . $e->getMessage());
            $this->renderAuth(
                'register',
                'Regisztráció - Magyar Biliárd',
                ['general' => 'A regisztráció nem sikerült. Kérjük, próbálja újra.'],
                $data
            );
            return;
        }

        // Sikeres regisztráció után azonnali beléptetés
        Session::loginUser($user);
        Session::flash('success', 'Sikeres regisztráció. Üdvözlünk, ' . $user['name'] . '!');
        redirect($this->pullRedirectTarget());
    }

    // =====================================================================
    // Belépés
    // =====================================================================

    public function loginForm(): void
    {
        if (Session::isUser()) {
            redirect('/fiok');
        }

        // Honnan érkezett a felhasználó - belépés után ide térjen vissza
        if (!empty($_GET['tovabb'])) {
            $_SESSION['login_redirect'] = $this->sanitizeRedirect($_GET['tovabb']);
        }

        $errors = [];
        $data = [];
        $this->renderAuth('login', 'Belépés - Magyar Biliárd', $errors, $data);
    }

    public function login(): void
    {
        if (Session::isUser()) {
            redirect('/fiok');
        }

        $data = [
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
        ];

        $validator = $this->validationService->validateUserLogin($data);

        if (!$validator->isValid()) {
            $this->renderAuth('login', 'Belépés - Magyar Biliárd', $validator->getErrors(), $data);
            return;
        }

        $user = $this->authService->attemptLogin($data['email'], $data['password']);

        if ($user === null) {
            // Nem árulja el, hogy az e-mail vagy a jelszó volt hibás
            $this->renderAuth(
                'login',
                'Belépés - Magyar Biliárd',
                ['general' => 'Hibás e-mail cím vagy jelszó'],
                ['email' => $data['email']]
            );
            return;
        }

        Session::loginUser($user);
        Session::flash('success', 'Sikeres belépés. Üdv, ' . $user['name'] . '!');
        redirect($this->pullRedirectTarget());
    }

    public function logout(): void
    {
        Session::logoutUser();
        Session::flash('success', 'Kiléptél a fiókodból.');
        redirect('/');
    }

    // =====================================================================
    // Fiók: saját nevezések
    // =====================================================================

    /**
     * A felhasználó által rögzített nevezések listája.
     */
    public function account(): void
    {
        $this->requireUser();

        $user = Session::user();
        $registrations = $this->competitionService->getRegistrationsByUser($user['id']);

        $pageTitle = 'Fiókom - Magyar Biliárd';

        ob_start();
        require __DIR__ . '/../Views/account/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Saját nevezés visszavonása.
     */
    public function deleteRegistration(string $id): void
    {
        $this->requireUser();

        try {
            $this->competitionService->deleteOwnRegistration($id, Session::userId());
            Session::flash('success', 'A nevezést visszavontuk.');
        } catch (AppException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[AuthController] Nevezés törlési hiba: ' . $e->getMessage());
            Session::flash('error', 'A nevezés visszavonása nem sikerült. Kérjük, próbálja újra.');
        }

        redirect('/fiok');
    }

    // =====================================================================
    // Segédmetódusok
    // =====================================================================

    /**
     * Bejelentkezés megkövetelése; ha nincs, a belépő oldalra irányít,
     * megőrizve a szándékolt célt.
     */
    private function requireUser(): void
    {
        if (!Session::isUser()) {
            redirect('/belepes?tovabb=' . urlencode(currentUrl()));
        }
    }

    /**
     * A belépés után használandó cél kiolvasása és törlése a sessionből.
     */
    private function pullRedirectTarget(): string
    {
        $target = $_SESSION['login_redirect'] ?? '/fiok';
        unset($_SESSION['login_redirect']);

        return $target;
    }

    /**
     * Átirányítási cél szűrése: csak az oldalon belüli, egyszerű útvonalak
     * engedélyezettek, így nem lehet külső oldalra irányítani (open redirect).
     */
    private function sanitizeRedirect(string $target): string
    {
        if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return '/fiok';
        }

        return $target;
    }

    /**
     * Autentikációs nézet renderelése a fő layoutban.
     */
    private function renderAuth(string $view, string $pageTitle, array $errors, array $data): void
    {
        ob_start();
        require __DIR__ . '/../Views/auth/' . $view . '.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
