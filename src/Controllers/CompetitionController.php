<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
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
     * A nevezés belépéshez kötött: a nevező személye így egyértelmű, és a
     * nevezés a fiókjában is megjelenik, ahol a határidő lejártáig
     * visszavonhatja. Vendégként az űrlap helyett a belépésre hívó
     * tájékoztatás látszik, a verseny adatai viszont láthatók maradnak - a
     * versenykiírás nyilvános információ.
     */
    public function showForm(string $versenyId): void
    {
        $competition = $this->competitionService->getCompetitionById($versenyId);

        if ($competition === null) {
            $this->renderNotFound();
            return;
        }

        $user = Session::user();

        // A telefonszám az egyetlen szerkeszthető mező, a fiókból előtöltve
        $data = $user !== null ? ['phone' => $user['phone']] : [];

        $alreadyRegistered = $user !== null
            && $this->competitionService->hasUserRegistered($versenyId, $user['id']);

        $this->renderForm($competition, [
            'errors' => [],
            'data' => $data,
            'duplicateError' => false,
            'alreadyRegistered' => $alreadyRegistered,
            'success' => isset($_GET['success']) && $_GET['success'] === '1',
        ]);
    }

    /**
     * Nevezési űrlap feldolgozása.
     *
     * A nevező NEVE és E-MAIL CÍME a fiókból származik, nem az űrlapról:
     * mindenki csak a saját nevében nevezhet. Aki más helyett szeretne
     * nevezni, a szervezőt kéri meg - a szervezői felületen bárki
     * felvihető.
     */
    public function submitRegistration(string $versenyId): void
    {
        $competition = $this->competitionService->getCompetitionById($versenyId);

        if ($competition === null) {
            $this->renderNotFound();
            return;
        }

        // Belépés nélkül nincs nevezés. A cél megőrzésével irányítunk, hogy
        // a belépés után ne kelljen újra megkeresni a versenyt.
        if (!Session::isUser()) {
            redirect('/belepes?tovabb=' . urlencode('/nevezes/' . $versenyId));
            return;
        }

        $user = Session::user();
        $phone = trim($_POST['phone'] ?? '');

        $state = [
            'errors' => [],
            'data' => ['phone' => $phone],
            'duplicateError' => false,
            'alreadyRegistered' => false,
            'success' => false,
        ];

        // Nevezési időablak ellenőrzése - a lejárt és a még meg sem nyílt
        // állapotot is a nézet jelzi, ezért itt csak megszakítjuk a mentést
        if (!$this->competitionService->isRegistrationOpen($competition)) {
            $this->renderForm($competition, $state);
            return;
        }

        // Csak a telefonszám jön az űrlapról, ezért csak azt validáljuk.
        // A név és az e-mail a fiókból származik, azt a regisztráció
        // validálta - itt újra ellenőrizni felesleges és félrevezető lenne.
        if ($phone === '') {
            $state['errors'] = ['phone' => 'A telefonszám megadása kötelező'];
            $this->renderForm($competition, $state);
            return;
        }

        try {
            $this->competitionService->registerSelf($versenyId, $user, $phone);

            // Szándékosan nincs flash üzenet: a visszaigazolást a ?success=1
            // paraméterre a nézet jeleníti meg, részletesebb tartalommal.
            // Flash-sel együtt két helyen jelenne meg ugyanaz.
            redirect("/nevezes/{$versenyId}?success=1");
        } catch (AppException $e) {
            if ($e->getCode() === AppException::DUPLICATE_ENTRY) {
                $state['duplicateError'] = true;
                $state['alreadyRegistered'] = true;
            } else {
                $state['errors'] = ['general' => $e->getMessage()];
            }

            $this->renderForm($competition, $state);
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
     * @param array{errors:array, data:array, duplicateError:bool, alreadyRegistered:bool, success:bool} $state
     */
    private function renderForm(array $competition, array $state): void
    {
        $errors = $state['errors'];
        $data = $state['data'];
        $duplicateError = $state['duplicateError'];
        $alreadyRegistered = $state['alreadyRegistered'];
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
