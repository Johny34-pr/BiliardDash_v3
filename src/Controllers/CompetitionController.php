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
     * Nyilvános nevezői lista egy versenyhez.
     *
     * Belépés nélkül is elérhető, ezért csak a nevezők nevét és a nevezés
     * idejét jeleníti meg - e-mail címet és telefonszámot nem.
     */
    public function registrants(string $versenyId): void
    {
        $competition = $this->competitionService->getCompetitionById($versenyId);

        if ($competition === null) {
            $this->renderNotFound();
            return;
        }

        $registrants = $this->competitionService->getPublicRegistrants($versenyId);
        $deadlinePassed = $this->isDeadlinePassed($competition);
        $pageTitle = 'Nevezők: ' . $competition['name'] . ' - Magyar Biliárd';

        ob_start();
        require __DIR__ . '/../Views/competitions/registrants.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Nevezési űrlap megjelenítése egy adott versenyhez.
     *
     * Bejelentkezett felhasználónál a "magamnak" mód a fiók adataival tölti
     * elő az űrlapot. Vendégként az űrlap üresen jelenik meg, a nevezés
     * belépés nélkül is működik.
     */
    public function showForm(string $versenyId): void
    {
        $competition = $this->competitionService->getCompetitionById($versenyId);

        if ($competition === null) {
            $this->renderNotFound();
            return;
        }

        $user = Session::user();

        // Alapértelmezés belépve: magamnak nevezek
        $registerFor = ($_GET['kinek'] ?? '') === 'masnak' ? 'other' : 'self';

        $data = ($user !== null && $registerFor === 'self')
            ? ['fullName' => $user['name'], 'email' => $user['email'], 'phone' => $user['phone']]
            : [];

        $this->renderForm($competition, [
            'errors' => [],
            'data' => $data,
            'registerFor' => $registerFor,
            'duplicateError' => false,
            'success' => isset($_GET['success']) && $_GET['success'] === '1',
        ]);
    }

    /**
     * Nevezési űrlap feldolgozása.
     *
     * Belépett felhasználó esetén a nevezés hozzá kötődik (created_by_user_id),
     * így később visszavonhatja. Vendégként a nevezés kötetlen marad.
     */
    public function submitRegistration(string $versenyId): void
    {
        $competition = $this->competitionService->getCompetitionById($versenyId);

        if ($competition === null) {
            $this->renderNotFound();
            return;
        }

        $registerFor = ($_POST['register_for'] ?? '') === 'other' ? 'other' : 'self';

        $data = [
            'fullName' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];

        $state = [
            'errors' => [],
            'data' => $data,
            'registerFor' => $registerFor,
            'duplicateError' => false,
            'success' => false,
        ];

        // Határidő ellenőrzés - a nézet a lejárt állapotot maga jelzi
        if ($this->isDeadlinePassed($competition)) {
            $this->renderForm($competition, $state);
            return;
        }

        // Validáció
        $validator = $this->validationService->validateRegistration($data);
        if (!$validator->isValid()) {
            $state['errors'] = $validator->getErrors();
            $this->renderForm($competition, $state);
            return;
        }

        // Duplikáció ellenőrzés
        if ($this->competitionService->checkDuplicateRegistration($versenyId, $data['email'])) {
            $state['duplicateError'] = true;
            $this->renderForm($competition, $state);
            return;
        }

        // Nevezés rögzítése - belépve a felhasználóhoz kötve
        try {
            $this->competitionService->registerForCompetition($versenyId, $data, Session::userId());

            // Szándékosan nincs flash üzenet: a visszaigazolást a ?success=1
            // paraméterre a nézet jeleníti meg, részletesebb tartalommal.
            // Flash-sel együtt két helyen jelenne meg ugyanaz.
            redirect("/nevezes/{$versenyId}?success=1");
        } catch (\Throwable $e) {
            error_log('[CompetitionController] Nevezés hiba: ' . $e->getMessage());
            $state['errors'] = ['general' => 'Hiba történt a nevezés során. Kérjük, próbálja újra.'];
            $this->renderForm($competition, $state);
        }
    }

    // =====================================================================
    // Segédmetódusok
    // =====================================================================

    private function isDeadlinePassed(array $competition): bool
    {
        return new \DateTime($competition['registration_deadline']) < new \DateTime();
    }

    /**
     * Nevezési űrlap renderelése a fő layoutban.
     *
     * @param array{errors:array, data:array, registerFor:string, duplicateError:bool, success:bool} $state
     */
    private function renderForm(array $competition, array $state): void
    {
        $errors = $state['errors'];
        $data = $state['data'];
        $registerFor = $state['registerFor'];
        $duplicateError = $state['duplicateError'];
        $success = $state['success'];
        $deadlinePassed = $this->isDeadlinePassed($competition);
        $currentUser = Session::user();

        $pageTitle = 'Nevezés: ' . $competition['name'] . ' - Magyar Biliárd';

        ob_start();
        require __DIR__ . '/../Views/competitions/register.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
    }
}
