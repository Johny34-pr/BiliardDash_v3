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
        // A verseny NAPJÁIG listázunk, nem a nevezési határidőig: a határidő
        // lejárta után is látható marad a kiírás és a nevezői névsor, ami a
        // verseny napján a helyszínen a legfontosabb.
        $competitions = $this->competitionService->getUpcomingCompetitions();
        $pageTitle = 'Versenynevezés - Okányi Biliárd Klub';
        $metaDescription = 'Közelgő biliárdversenyek és online nevezés. '
            . 'Nézd meg a versenyek dátumát, helyszínét és a nevezési határidőt.';

        // Render view within layout
        ob_start();
        require __DIR__ . '/../Views/competitions/index.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Versenyeink > Közelgő versenyek.
     *
     * Ugyanaz az adatkör, mint a Nevezés oldalon, de versenynaptár
     * szemszögből. A kártyajelölés közös, ezért a kettő nem csúszhat el.
     */
    public function upcoming(): void
    {
        $competitions = $this->competitionService->getUpcomingCompetitions();
        $pageTitle = 'Közelgő versenyek - Okányi Biliárd Klub';
        $metaDescription = 'Az Okányi Biliárd Klub közelgő versenyei: dátum, '
            . 'helyszín, nevezési határidő és nevezői névsor.';

        ob_start();
        require __DIR__ . '/../Views/competitions/upcoming.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * Versenyeink > Korábbi versenyek.
     *
     * Évek szerint csoportosítva, mert szezononként több tucat verseny gyűlik
     * össze, és egy folyamatos lista néhány év után átláthatatlan lenne.
     */
    public function past(): void
    {
        $competitions = $this->competitionService->getPastCompetitions();

        $competitionsByYear = [];
        $protocolCount = 0;

        foreach ($competitions as $competition) {
            $year = (int) date('Y', strtotime($competition['date']));
            $competitionsByYear[$year][] = $competition;

            if (!empty($competition['protocol_path'])) {
                $protocolCount++;
            }
        }

        // A findPast() dátum szerint csökkenően ad vissza, tehát az évek már
        // helyes sorrendben keletkeztek - de a szándékot kiírjuk
        krsort($competitionsByYear);

        $totalCount = count($competitions);
        $pageTitle = 'Korábbi versenyek - Okányi Biliárd Klub';
        $metaDescription = 'Az Okányi Biliárd Klub lezárult versenyei: '
            . 'nevezői névsorok és letölthető jegyzőkönyvek.';

        ob_start();
        require __DIR__ . '/../Views/competitions/past.php';
        $content = ob_get_clean();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * A verseny jegyzőkönyvének letöltése.
     *
     * Nem közvetlen fájlhivatkozás, hanem letöltő végpont, két okból:
     *   - a látogató az EREDETI fájlnevet kapja, nem a tárolt UUID-t;
     *   - a tárolt útvonal nem szivárog ki a jelölésbe.
     *
     * A fájlt a feltöltési könyvtáron belülre korlátozzuk: egy hibás vagy
     * manipulált adatbázis-bejegyzés különben bármely fájlt kiadhatná.
     */
    public function protocol(string $versenyId): void
    {
        $competition = $this->competitionService->getCompetitionById($versenyId);

        if ($competition === null || empty($competition['protocol_path'])) {
            $this->renderNotFound();
            return;
        }

        $publicRoot = dirname(__DIR__, 2) . '/public';
        $uploadsRoot = realpath($publicRoot . '/uploads');
        $path = realpath($publicRoot . '/' . ltrim((string) $competition['protocol_path'], '/'));

        if ($uploadsRoot === false || $path === false
            || !str_starts_with($path, $uploadsRoot . DIRECTORY_SEPARATOR)
            || !is_file($path)
        ) {
            error_log('[CompetitionController] Jegyzőkönyv nem elérhető: ' . $competition['protocol_path']);
            $this->renderNotFound();
            return;
        }

        $filename = (string) ($competition['protocol_name'] ?? 'jegyzokonyv');

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($path));
        // Kétféle filename: az egyszerű a régi böngészőknek, a filename* az
        // ékezetes névhez (RFC 5987)
        header(
            'Content-Disposition: attachment; filename="' . $this->asciiFilename($filename) . '"; '
            . "filename*=UTF-8''" . rawurlencode($filename)
        );
        header('X-Content-Type-Options: nosniff');

        readfile($path);
    }

    /**
     * A nyilvános nevezői lista CSV exportja.
     *
     * Csak azt az adatkört tartalmazza, ami az oldalon is látszik (sorszám,
     * név, nevezés ideje). Az elérhetőségeket tartalmazó export a szervezői
     * felületen marad.
     */
    public function exportRegistrants(string $versenyId): void
    {
        $competition = $this->competitionService->getCompetitionById($versenyId);

        if ($competition === null) {
            $this->renderNotFound();
            return;
        }

        $csv = $this->competitionService->exportPublicRegistrantsCsv($versenyId);

        // Beszédes fájlnév: a dátum és a verseny neve alapján, hogy a letöltött
        // fájlok ne "nevezok.csv", "nevezok(1).csv" néven gyűljenek
        $slug = $this->filenameSlug(
            date('Y-m-d', strtotime($competition['date'])) . '-' . $competition['name']
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="nevezok-' . $slug . '.csv"');
        header('Content-Length: ' . strlen($csv));
        header('X-Content-Type-Options: nosniff');

        echo $csv;
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

    /**
     * Fájlnév ASCII változata a Content-Disposition fejléchez.
     *
     * A fejléc nem hordozhat ékezetet biztonsággal, ezért az egyszerű
     * filename= értékhez az ékezeteket alapbetűre cseréljük. A pontos nevet a
     * filename*= paraméter viszi.
     */
    private function asciiFilename(string $name): string
    {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ú' => 'U', 'Ü' => 'U', 'Ű' => 'U',
        ];

        $ascii = strtr($name, $map);
        $ascii = (string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $ascii);
        $ascii = trim($ascii, '_');

        return $ascii !== '' ? $ascii : 'letoltes';
    }

    /**
     * Fájlnévbe illeszthető azonosító egy szabad szövegből.
     */
    private function filenameSlug(string $text): string
    {
        $slug = strtolower($this->asciiFilename($text));
        $slug = (string) preg_replace('/_+/', '-', $slug);
        $slug = trim((string) preg_replace('/-+/', '-', $slug), '-');

        return $slug !== '' ? substr($slug, 0, 80) : 'lista';
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
    }
}
