<?php

declare(strict_types=1);

namespace Tests\Properties;

use App\Core\AppException;
use Eris\Generators;
use Eris\TestTrait;
use Tests\TestCase;

/**
 * Property-based tesztek a verseny és nevezési modulhoz.
 *
 * Validates: Requirements 5.1, 5.3, 5.5, 5.6, 5.8, 6.1, 6.3, 6.4, 6.5, 6.6, 6.7
 */
class CompetitionPropertiesTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: billiard-website, Property 12: Nyitott versenyek szűrése és rendezése
     *
     * For any collection of competitions with different registration deadlines,
     * getOpenCompetitions() returns only competitions with future deadlines,
     * sorted by date ASC.
     *
     * NOTE: The Competition model uses NOW() which doesn't work in SQLite.
     * We test by inserting competitions directly and using the service layer
     * which relies on the model's findOpen(). Since SQLite doesn't support NOW(),
     * we replace it with datetime('now') for testing via a custom SQLite function.
     *
     * **Validates: Requirements 5.1**
     */
    public function testOpenCompetitionsFilteringAndSorting(): void
    {
        // Register SQLite NOW() function for compatibility
        $this->db->sqliteCreateFunction('NOW', function () {
            return date('Y-m-d H:i:s');
        });

        $competitionService = $this->createCompetitionService();

        $this->forAll(
            Generators::choose(1, 5),
            Generators::choose(1, 5)
        )->then(function (int $futureCount, int $pastCount) use ($competitionService) {
            $this->truncateTable('registrations');
            $this->truncateTable('competitions');

            // Insert future deadline competitions (open)
            $futureDates = [];
            for ($i = 0; $i < $futureCount; $i++) {
                $daysAhead = ($i + 1) * 30;
                $competitionDate = date('Y-m-d', strtotime("+{$daysAhead} days"));
                $deadline = date('Y-m-d H:i:s', strtotime('+' . ($daysAhead - 5) . ' days'));
                $id = \Ramsey\Uuid\Uuid::uuid4()->toString();

                $this->db->prepare(
                    'INSERT INTO competitions (id, name, date, venue, registration_deadline, registrant_count)
                     VALUES (:id, :name, :date, :venue, :deadline, 0)'
                )->execute([
                    ':id' => $id,
                    ':name' => "Future Competition {$i}",
                    ':date' => $competitionDate,
                    ':venue' => "Venue {$i}",
                    ':deadline' => $deadline,
                ]);
                $futureDates[] = $competitionDate;
            }

            // Insert past deadline competitions (closed)
            for ($i = 0; $i < $pastCount; $i++) {
                $daysBehind = ($i + 1) * 30;
                $competitionDate = date('Y-m-d', strtotime("-{$daysBehind} days"));
                $deadline = date('Y-m-d H:i:s', strtotime('-' . ($daysBehind + 5) . ' days'));
                $id = \Ramsey\Uuid\Uuid::uuid4()->toString();

                $this->db->prepare(
                    'INSERT INTO competitions (id, name, date, venue, registration_deadline, registrant_count)
                     VALUES (:id, :name, :date, :venue, :deadline, 0)'
                )->execute([
                    ':id' => $id,
                    ':name' => "Past Competition {$i}",
                    ':date' => $competitionDate,
                    ':venue' => "Old Venue {$i}",
                    ':deadline' => $deadline,
                ]);
            }

            $openCompetitions = $competitionService->getOpenCompetitions();

            // Property: only future deadline competitions are returned
            $this->assertCount($futureCount, $openCompetitions);

            // Property: all returned competitions have future deadlines
            $now = new \DateTime();
            foreach ($openCompetitions as $comp) {
                $this->assertGreaterThan(
                    $now,
                    new \DateTime($comp['registration_deadline']),
                    'Open competitions should only have future deadlines'
                );
            }

            // Property: results are sorted by date ASC
            for ($i = 1; $i < count($openCompetitions); $i++) {
                $this->assertGreaterThanOrEqual(
                    $openCompetitions[$i - 1]['date'],
                    $openCompetitions[$i]['date'],
                    'Open competitions should be sorted by date ASC'
                );
            }
        });
    }

    /**
     * Feature: billiard-website, Property 13: Nevezés round-trip
     *
     * For any valid registration data (name ≤100 chars, valid email, non-empty phone)
     * and an open competition, registering and retrieving returns the same data.
     *
     * **Validates: Requirements 5.3**
     */
    public function testRegistrationRoundTrip(): void
    {
        $competitionService = $this->createCompetitionService();

        $this->forAll(
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0 && mb_strlen($s) <= 100;
                },
                Generators::string()
            ),
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0 && mb_strlen($s) <= 20;
                },
                Generators::string()
            )
        )->then(function (string $fullName, string $phone) use ($competitionService) {
            $this->truncateTable('registrations');
            $this->truncateTable('competitions');

            // Create a competition with future deadline
            $competitionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $futureDeadline = date('Y-m-d H:i:s', strtotime('+30 days'));
            $futureDate = date('Y-m-d', strtotime('+60 days'));
            $this->db->prepare(
                'INSERT INTO competitions (id, name, date, venue, registration_deadline, registrant_count)
                 VALUES (:id, :name, :date, :venue, :deadline, 0)'
            )->execute([
                ':id' => $competitionId,
                ':name' => 'Test Competition',
                ':date' => $futureDate,
                ':venue' => 'Test Venue',
                ':deadline' => $futureDeadline,
            ]);

            // Generate a unique email for each iteration
            $email = 'test_' . uniqid() . '@example.com';

            $registrationData = [
                'fullName' => $fullName,
                'email' => $email,
                'phone' => $phone,
            ];

            $result = $competitionService->registerForCompetition($competitionId, $registrationData);

            // Property: returned data matches input
            $this->assertSame($fullName, $result['full_name']);
            $this->assertSame($email, $result['email']);
            $this->assertSame($phone, $result['phone']);
            $this->assertSame($competitionId, $result['competition_id']);

            // Property: retrieving registrations also contains the data
            $registrations = $competitionService->getRegistrations($competitionId);
            $this->assertCount(1, $registrations);
            $this->assertSame($fullName, $registrations[0]['full_name']);
            $this->assertSame($email, $registrations[0]['email']);
            $this->assertSame($phone, $registrations[0]['phone']);
        });
    }

    /**
     * Feature: billiard-website, Property 14: Nevezési validáció elutasítja az érvénytelen adatokat
     *
     * For any registration data with missing name, invalid email, or missing phone,
     * validation rejects it with field-specific error messages.
     *
     * **Validates: Requirements 5.5**
     */
    public function testRegistrationValidationRejectsInvalidData(): void
    {
        $validationService = $this->createValidationService();

        $emptyOrWhitespace = Generators::elements(['', ' ', '  ', "\t", "\n"]);

        // Case 1: Missing fullName
        $this->forAll(
            $emptyOrWhitespace
        )->then(function (string $emptyName) use ($validationService) {
            $validator = $validationService->validateRegistration([
                'fullName' => $emptyName,
                'email' => 'valid@example.com',
                'phone' => '+36201234567',
            ]);

            $this->assertFalse($validator->isValid(), 'Should reject empty fullName');
            $this->assertNotNull($validator->getError('fullName'));
        });

        // Case 2: Invalid email format
        $invalidEmails = Generators::elements([
            'notanemail',
            'missing@',
            '@nodomain.com',
            'spaces in@email.com',
            'no.at.sign',
        ]);

        $this->forAll(
            $invalidEmails
        )->then(function (string $invalidEmail) use ($validationService) {
            $validator = $validationService->validateRegistration([
                'fullName' => 'Valid Name',
                'email' => $invalidEmail,
                'phone' => '+36201234567',
            ]);

            $this->assertFalse($validator->isValid(), 'Should reject invalid email: ' . $invalidEmail);
            $this->assertNotNull($validator->getError('email'));
        });

        // Case 3: Missing phone
        $this->forAll(
            $emptyOrWhitespace
        )->then(function (string $emptyPhone) use ($validationService) {
            $validator = $validationService->validateRegistration([
                'fullName' => 'Valid Name',
                'email' => 'valid@example.com',
                'phone' => $emptyPhone,
            ]);

            $this->assertFalse($validator->isValid(), 'Should reject empty phone');
            $this->assertNotNull($validator->getError('phone'));
        });
    }

    /**
     * Feature: billiard-website, Property 15: Lejárt határidejű versenyre nem lehet nevezni
     *
     * For any competition with an expired deadline, registration attempts
     * should throw AppException::deadlinePassed().
     *
     * **Validates: Requirements 5.6**
     */
    public function testCannotRegisterForExpiredCompetition(): void
    {
        $competitionService = $this->createCompetitionService();

        $this->forAll(
            Generators::choose(1, 365)
        )->then(function (int $daysInPast) use ($competitionService) {
            $this->truncateTable('registrations');
            $this->truncateTable('competitions');

            // Create competition with past deadline
            $competitionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $pastDeadline = date('Y-m-d H:i:s', strtotime("-{$daysInPast} days"));
            $pastDate = date('Y-m-d', strtotime("-{$daysInPast} days"));

            $this->db->prepare(
                'INSERT INTO competitions (id, name, date, venue, registration_deadline, registrant_count)
                 VALUES (:id, :name, :date, :venue, :deadline, 0)'
            )->execute([
                ':id' => $competitionId,
                ':name' => 'Expired Competition',
                ':date' => $pastDate,
                ':venue' => 'Test Venue',
                ':deadline' => $pastDeadline,
            ]);

            $this->expectException(AppException::class);

            $competitionService->registerForCompetition($competitionId, [
                'fullName' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '+36201234567',
            ]);
        });
    }

    /**
     * Feature: billiard-website, Property 16: Dupla nevezés megakadályozása
     *
     * For any competition and email, if a registration already exists with that email,
     * a second registration attempt with the same email should throw AppException::duplicateEntry().
     *
     * **Validates: Requirements 5.8**
     */
    public function testDuplicateRegistrationPrevented(): void
    {
        $competitionService = $this->createCompetitionService();

        $this->forAll(
            Generators::choose(1, 10)
        )->then(function (int $iteration) use ($competitionService) {
            $this->truncateTable('registrations');
            $this->truncateTable('competitions');

            // Create competition with future deadline
            $competitionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $futureDeadline = date('Y-m-d H:i:s', strtotime('+30 days'));
            $futureDate = date('Y-m-d', strtotime('+60 days'));

            $this->db->prepare(
                'INSERT INTO competitions (id, name, date, venue, registration_deadline, registrant_count)
                 VALUES (:id, :name, :date, :venue, :deadline, 0)'
            )->execute([
                ':id' => $competitionId,
                ':name' => 'Test Competition',
                ':date' => $futureDate,
                ':venue' => 'Test Venue',
                ':deadline' => $futureDeadline,
            ]);

            $email = "duplicate_{$iteration}@example.com";
            $registrationData = [
                'fullName' => 'Test User',
                'email' => $email,
                'phone' => '+36201234567',
            ];

            // First registration should succeed
            $competitionService->registerForCompetition($competitionId, $registrationData);

            // Second registration with same email should throw
            $this->expectException(AppException::class);
            $competitionService->registerForCompetition($competitionId, $registrationData);
        });
    }

    /**
     * Feature: billiard-website, Property 17: Verseny létrehozás round-trip
     *
     * For any valid competition data (name ≤100, date, venue ≤200, deadline),
     * creating and retrieving returns the same data.
     *
     * **Validates: Requirements 6.1**
     */
    public function testCompetitionCreationRoundTrip(): void
    {
        $competitionService = $this->createCompetitionService();

        $this->forAll(
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0 && mb_strlen($s) <= 100;
                },
                Generators::string()
            ),
            Generators::suchThat(
                function (string $s) {
                    return mb_strlen(trim($s)) > 0 && mb_strlen($s) <= 200;
                },
                Generators::string()
            )
        )->then(function (string $name, string $venue) use ($competitionService) {
            $this->truncateTable('registrations');
            $this->truncateTable('competitions');

            $date = date('Y-m-d', strtotime('+60 days'));
            $deadline = date('Y-m-d H:i:s', strtotime('+30 days'));

            $data = [
                'name' => $name,
                'date' => $date,
                'venue' => $venue,
                'registrationDeadline' => $deadline,
            ];

            $created = $competitionService->createCompetition($data);
            $retrieved = $competitionService->getCompetitionById($created['id']);

            // Property: all fields preserved
            $this->assertNotNull($retrieved);
            $this->assertSame($name, $retrieved['name']);
            $this->assertSame($date, $retrieved['date']);
            $this->assertSame($venue, $retrieved['venue']);
            $this->assertSame($deadline, $retrieved['registration_deadline']);
            $this->assertEquals(0, $retrieved['registrant_count']);
        });
    }

    /**
     * Feature: billiard-website, Property 18: CSV export round-trip
     *
     * For any competition with registrations, the CSV export contains UTF-8 BOM,
     * correct header, and all participant data rows.
     *
     * **Validates: Requirements 6.3**
     */
    public function testCsvExportRoundTrip(): void
    {
        $competitionService = $this->createCompetitionService();

        $this->forAll(
            Generators::choose(1, 5)
        )->then(function (int $registrantCount) use ($competitionService) {
            $this->truncateTable('registrations');
            $this->truncateTable('competitions');

            // Create competition with future deadline
            $competitionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $futureDeadline = date('Y-m-d H:i:s', strtotime('+30 days'));
            $futureDate = date('Y-m-d', strtotime('+60 days'));

            $this->db->prepare(
                'INSERT INTO competitions (id, name, date, venue, registration_deadline, registrant_count)
                 VALUES (:id, :name, :date, :venue, :deadline, 0)'
            )->execute([
                ':id' => $competitionId,
                ':name' => 'CSV Test Competition',
                ':date' => $futureDate,
                ':venue' => 'Test Venue',
                ':deadline' => $futureDeadline,
            ]);

            // Register participants
            $participants = [];
            for ($i = 0; $i < $registrantCount; $i++) {
                $participant = [
                    'fullName' => "Participant {$i}",
                    'email' => "participant{$i}@example.com",
                    'phone' => "+3620123456{$i}",
                ];
                $competitionService->registerForCompetition($competitionId, $participant);
                $participants[] = $participant;
            }

            $csv = $competitionService->exportRegistrationsCsv($competitionId);

            // Property: starts with UTF-8 BOM
            $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'CSV should start with UTF-8 BOM');

            // Property: has correct header after BOM
            $withoutBom = substr($csv, 3);
            $lines = explode("\n", $withoutBom);
            $this->assertSame("Név,Email,Telefon", $lines[0], 'CSV header should be Név,Email,Telefon');

            // Property: correct number of data rows (excluding empty trailing line)
            $dataLines = array_filter(array_slice($lines, 1), fn($line) => $line !== '');
            $this->assertCount($registrantCount, $dataLines);

            // Property: each participant's data appears in the CSV
            foreach ($participants as $participant) {
                $this->assertStringContainsString($participant['fullName'], $csv);
                $this->assertStringContainsString($participant['email'], $csv);
                $this->assertStringContainsString($participant['phone'], $csv);
            }
        });
    }

    /**
     * Feature: billiard-website, Property 19: Nevezők számának konzisztenciája
     *
     * For any competition, the registrant_count field matches the actual
     * number of registrations.
     *
     * **Validates: Requirements 6.4**
     */
    public function testRegistrantCountConsistency(): void
    {
        $competitionService = $this->createCompetitionService();

        $this->forAll(
            Generators::choose(0, 5)
        )->then(function (int $registrantCount) use ($competitionService) {
            $this->truncateTable('registrations');
            $this->truncateTable('competitions');

            // Create competition with future deadline
            $competitionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $futureDeadline = date('Y-m-d H:i:s', strtotime('+30 days'));
            $futureDate = date('Y-m-d', strtotime('+60 days'));

            $this->db->prepare(
                'INSERT INTO competitions (id, name, date, venue, registration_deadline, registrant_count)
                 VALUES (:id, :name, :date, :venue, :deadline, 0)'
            )->execute([
                ':id' => $competitionId,
                ':name' => 'Count Test Competition',
                ':date' => $futureDate,
                ':venue' => 'Test Venue',
                ':deadline' => $futureDeadline,
            ]);

            // Register participants
            for ($i = 0; $i < $registrantCount; $i++) {
                $competitionService->registerForCompetition($competitionId, [
                    'fullName' => "Participant {$i}",
                    'email' => "count_test_{$i}@example.com",
                    'phone' => '+36201234567',
                ]);
            }

            // Property: registrant_count matches actual registrations
            $competition = $competitionService->getCompetitionById($competitionId);
            $registrations = $competitionService->getRegistrations($competitionId);

            $this->assertEquals(
                count($registrations),
                (int) $competition['registrant_count'],
                'registrant_count should match actual number of registrations'
            );
            $this->assertEquals($registrantCount, (int) $competition['registrant_count']);
        });
    }

    /**
     * Feature: billiard-website, Property 20: Verseny validáció elutasítja a hiányos adatokat
     *
     * For any competition data where any required field (name, date, venue, deadline) is missing,
     * validation rejects and returns errors.
     *
     * **Validates: Requirements 6.5**
     */
    public function testCompetitionValidationRejectsMissingFields(): void
    {
        $validationService = $this->createValidationService();

        $emptyOrWhitespace = Generators::elements(['', ' ', '  ', "\t", "\n"]);

        // Case 1: Missing name
        $this->forAll(
            $emptyOrWhitespace
        )->then(function (string $emptyName) use ($validationService) {
            $validator = $validationService->validateCompetition([
                'name' => $emptyName,
                'date' => '2025-06-15',
                'venue' => 'Test Venue',
                'registrationDeadline' => '2025-06-10',
            ]);

            $this->assertFalse($validator->isValid(), 'Should reject empty name');
            $this->assertNotNull($validator->getError('name'));
        });

        // Case 2: Missing date
        $this->forAll(
            $emptyOrWhitespace
        )->then(function (string $emptyDate) use ($validationService) {
            $validator = $validationService->validateCompetition([
                'name' => 'Valid Name',
                'date' => $emptyDate,
                'venue' => 'Test Venue',
                'registrationDeadline' => '2025-06-10',
            ]);

            $this->assertFalse($validator->isValid(), 'Should reject empty date');
            $this->assertNotNull($validator->getError('date'));
        });

        // Case 3: Missing venue
        $this->forAll(
            $emptyOrWhitespace
        )->then(function (string $emptyVenue) use ($validationService) {
            $validator = $validationService->validateCompetition([
                'name' => 'Valid Name',
                'date' => '2025-06-15',
                'venue' => $emptyVenue,
                'registrationDeadline' => '2025-06-10',
            ]);

            $this->assertFalse($validator->isValid(), 'Should reject empty venue');
            $this->assertNotNull($validator->getError('venue'));
        });

        // Case 4: Missing registrationDeadline
        $this->forAll(
            $emptyOrWhitespace
        )->then(function (string $emptyDeadline) use ($validationService) {
            $validator = $validationService->validateCompetition([
                'name' => 'Valid Name',
                'date' => '2025-06-15',
                'venue' => 'Test Venue',
                'registrationDeadline' => $emptyDeadline,
            ]);

            $this->assertFalse($validator->isValid(), 'Should reject empty deadline');
            $this->assertNotNull($validator->getError('registrationDeadline'));
        });
    }

    /**
     * Feature: billiard-website, Property 21: Verseny szerkesztés megőrzi a nevezéseket
     *
     * For any competition with existing registrations, editing the competition
     * data preserves all registrations unchanged.
     *
     * **Validates: Requirements 6.6**
     */
    public function testEditingCompetitionPreservesRegistrations(): void
    {
        $competitionService = $this->createCompetitionService();

        $this->forAll(
            Generators::choose(1, 5)
        )->then(function (int $registrantCount) use ($competitionService) {
            $this->truncateTable('registrations');
            $this->truncateTable('competitions');

            // Create competition with future deadline
            $competitionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $futureDeadline = date('Y-m-d H:i:s', strtotime('+30 days'));
            $futureDate = date('Y-m-d', strtotime('+60 days'));

            $this->db->prepare(
                'INSERT INTO competitions (id, name, date, venue, registration_deadline, registrant_count)
                 VALUES (:id, :name, :date, :venue, :deadline, 0)'
            )->execute([
                ':id' => $competitionId,
                ':name' => 'Original Competition',
                ':date' => $futureDate,
                ':venue' => 'Original Venue',
                ':deadline' => $futureDeadline,
            ]);

            // Register participants
            for ($i = 0; $i < $registrantCount; $i++) {
                $competitionService->registerForCompetition($competitionId, [
                    'fullName' => "Registrant {$i}",
                    'email' => "edit_test_{$i}@example.com",
                    'phone' => "+3620000000{$i}",
                ]);
            }

            // Get registrations before edit
            $registrationsBefore = $competitionService->getRegistrations($competitionId);

            // Edit competition
            $competitionService->updateCompetition($competitionId, [
                'name' => 'Updated Competition Name',
                'date' => date('Y-m-d', strtotime('+90 days')),
                'venue' => 'Updated Venue',
                'registrationDeadline' => date('Y-m-d H:i:s', strtotime('+45 days')),
            ]);

            // Get registrations after edit
            $registrationsAfter = $competitionService->getRegistrations($competitionId);

            // Property: registrations count is preserved
            $this->assertCount(count($registrationsBefore), $registrationsAfter);

            // Property: each registration's data is preserved
            for ($i = 0; $i < count($registrationsBefore); $i++) {
                $this->assertSame($registrationsBefore[$i]['id'], $registrationsAfter[$i]['id']);
                $this->assertSame($registrationsBefore[$i]['full_name'], $registrationsAfter[$i]['full_name']);
                $this->assertSame($registrationsBefore[$i]['email'], $registrationsAfter[$i]['email']);
                $this->assertSame($registrationsBefore[$i]['phone'], $registrationsAfter[$i]['phone']);
                $this->assertSame($registrationsBefore[$i]['competition_id'], $registrationsAfter[$i]['competition_id']);
            }
        });
    }

    /**
     * Feature: billiard-website, Property 22: Verseny törlés kaszkád
     *
     * For any competition with registrations, deleting the competition
     * also removes all its registrations.
     *
     * **Validates: Requirements 6.7**
     */
    public function testCompetitionDeletionCascade(): void
    {
        $competitionService = $this->createCompetitionService();

        $this->forAll(
            Generators::choose(1, 5)
        )->then(function (int $registrantCount) use ($competitionService) {
            $this->truncateTable('registrations');
            $this->truncateTable('competitions');

            // Enable foreign keys for cascade to work
            $this->db->exec('PRAGMA foreign_keys = ON');

            // Create competition with future deadline
            $competitionId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $futureDeadline = date('Y-m-d H:i:s', strtotime('+30 days'));
            $futureDate = date('Y-m-d', strtotime('+60 days'));

            $this->db->prepare(
                'INSERT INTO competitions (id, name, date, venue, registration_deadline, registrant_count)
                 VALUES (:id, :name, :date, :venue, :deadline, 0)'
            )->execute([
                ':id' => $competitionId,
                ':name' => 'Competition to Delete',
                ':date' => $futureDate,
                ':venue' => 'Test Venue',
                ':deadline' => $futureDeadline,
            ]);

            // Register participants
            for ($i = 0; $i < $registrantCount; $i++) {
                $competitionService->registerForCompetition($competitionId, [
                    'fullName' => "To Delete {$i}",
                    'email' => "delete_test_{$i}@example.com",
                    'phone' => '+36201234567',
                ]);
            }

            // Verify registrations exist
            $registrationsBefore = $competitionService->getRegistrations($competitionId);
            $this->assertCount($registrantCount, $registrationsBefore);

            // Delete competition
            $competitionService->deleteCompetition($competitionId);

            // Property: competition no longer exists
            $this->assertNull($competitionService->getCompetitionById($competitionId));

            // Property: registrations are also deleted (cascade)
            $registrationsAfter = $competitionService->getRegistrations($competitionId);
            $this->assertCount(0, $registrationsAfter);
        });
    }
}
