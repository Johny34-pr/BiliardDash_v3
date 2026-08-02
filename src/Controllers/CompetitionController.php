<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Services\CompetitionService;
use App\Services\EmailService;
use App\Services\ValidationService;

class CompetitionController
{
    private CompetitionService $competitionService;
    private ValidationService $validationService;

    public function __construct()
    {
        $mailConfig = require __DIR__ . '/../../config/mail.php';
        $emailService = new EmailService($mailConfig);
        $this->competitionService = new CompetitionService(Database::getConnection(), $emailService);
        $this->validationService = new ValidationService();
    }

    /**
     * Nyitott versenyek listája - dátum szerinti növekvő sorrendben.
     */
    public function index(): void
    {
        $competitions = $this->competitionService->getOpenCompetitions();
        $pageTitle = 'Nevezés - Magyar Biliárd';

        // Render view within layout
        ob_start();
        require __DIR__ . '/../Views/competitions/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Nevezési űrlap megjelenítése egy adott versenyhez.
     */
    public function showForm(string $versenyId): void
    {
        $competition = $this->competitionService->getCompetitionById($versenyId);

        if ($competition === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $errors = [];
        $data = [];
        $deadlinePassed = false;
        $duplicateError = false;
        $success = isset($_GET['success']) && $_GET['success'] === '1';
        $pageTitle = 'Nevezés: ' . e($competition['name']);

        // Check if deadline has passed
        if (new \DateTime($competition['registration_deadline']) < new \DateTime()) {
            $deadlinePassed = true;
        }

        // Render view within layout
        ob_start();
        require __DIR__ . '/../Views/competitions/register.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Nevezési űrlap feldolgozása.
     */
    public function submitRegistration(string $versenyId): void
    {
        $competition = $this->competitionService->getCompetitionById($versenyId);

        if ($competition === null) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            return;
        }

        $errors = [];
        $duplicateError = false;
        $success = false;
        $pageTitle = 'Nevezés: ' . e($competition['name']);

        // Határidő ellenőrzés
        if (new \DateTime($competition['registration_deadline']) < new \DateTime()) {
            $deadlinePassed = true;
            $data = [];

            ob_start();
            require __DIR__ . '/../Views/competitions/register.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/main.php';
            return;
        }

        $deadlinePassed = false;

        $data = [
            'fullName' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];

        // Validáció
        $validator = $this->validationService->validateRegistration($data);
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();

            ob_start();
            require __DIR__ . '/../Views/competitions/register.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/main.php';
            return;
        }

        // Duplikáció ellenőrzés
        if ($this->competitionService->checkDuplicateRegistration($versenyId, $data['email'])) {
            $duplicateError = true;

            ob_start();
            require __DIR__ . '/../Views/competitions/register.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/main.php';
            return;
        }

        // Nevezés rögzítése
        try {
            $this->competitionService->registerForCompetition($versenyId, $data);
            Session::flash('success', 'Sikeres nevezés! Visszaigazoló e-mailt küldtünk.');
            redirect("/nevezes/{$versenyId}?success=1");
        } catch (\Throwable $e) {
            error_log('[CompetitionController] Nevezés hiba: ' . $e->getMessage());
            $errors = ['general' => 'Hiba történt a nevezés során. Kérjük, próbálja újra.'];

            ob_start();
            require __DIR__ . '/../Views/competitions/register.php';
            $content = ob_get_clean();

            require __DIR__ . '/../Views/layouts/main.php';
        }
    }
}
