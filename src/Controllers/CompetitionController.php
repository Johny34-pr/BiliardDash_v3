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
        $pageTitle = 'Versenynevezés - Okányi Biliárd Klub';
        $metaDescription = 'Nyitott biliárdversenyek és online nevezés. '
            . 'Nézd meg a versenyek dátumát, helyszínét és a nevezési határidőt.';

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
        $deadlinePassed = $this->competitionService->isDeadlinePassed($competition);
        $registrationOpened = $this->competitionService->hasRegistrationOpened($competition);
        $pageTitle = 'Nevezők: ' . $competition['name'] . ' - Okányi Biliárd Klub';
        $metaDescription = sprintf(
            'A(z) %s nevezői listája. %d nevező, a verseny %s, helyszín: %s.',
            $competition['name'],
            count($registrants),
            date('Y. m. d.', strtotime($competition['date'])),
            $competition['venue']
        );

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

        // Nevezési időablak ellenőrzése - a lejárt és a még meg sem nyílt
        // állapotot is a nézet jelzi, ezért itt csak megszakítjuk a mentést
        if (!$this->competitionService->isRegistrationOpen($competition)) {
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

    /**
     * Nevezési űrlap renderelése a fő layoutban.
     *
     * A nevezés két okból lehet zárt, és a kettő más üzenetet kíván:
     * a határidő lejárt ($deadlinePassed), vagy még nem nyílt meg
     * ($registrationOpened === false).
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
        $deadlinePassed = $this->competitionService->isDeadlinePassed($competition);
        $registrationOpened = $this->competitionService->hasRegistrationOpened($competition);
        $currentUser = Session::user();

        $pageTitle = 'Nevezés: ' . $competition['name'] . ' - Okányi Biliárd Klub';
        $metaDescription = sprintf(
            'Online nevezés a(z) %s versenyre. Időpont: %s, helyszín: %s. Nevezési határidő: %s.',
            $competition['name'],
            date('Y. m. d.', strtotime($competition['date'])),
            $competition['venue'],
            date('Y. m. d. H:i', strtotime($competition['registration_deadline']))
        );

        // Strukturált adat: a verseny sportesemény, dátummal és helyszínnel
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'SportsEvent',
            'name' => $competition['name'],
            'startDate' => date('Y-m-d', strtotime($competition['date'])),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'location' => [
                '@type' => 'Place',
                'name' => $competition['venue'],
            ],
            'url' => siteUrl('/nevezes/' . $competition['id']),
            'organizer' => [
                '@type' => 'Organization',
                'name' => 'Okányi Biliárd Klub',
                'url' => siteUrl('/'),
            ],
        ];

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
