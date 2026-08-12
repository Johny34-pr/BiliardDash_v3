<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\AppException;
use App\Services\CompetitionService;
use Tests\TestCase;

/**
 * Nevezés törlésének tesztjei.
 *
 * Két, szándékosan különböző szabályrendszer létezik:
 *
 *   - Felhasználói visszavonás (deleteOwnRegistration): csak a saját felvitt
 *     nevezés, és csak a nevezési határidő lejártáig.
 *   - Szervezői törlés (deleteRegistrationAsAdmin): bármelyik nevezés,
 *     a határidő után is.
 *
 * Mindkettőnek csökkentenie kell a nevezőszámot.
 */
class RegistrationDeletionTest extends TestCase
{
    private CompetitionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->createCompetitionService();
    }

    /**
     * Verseny létrehozása adott nevezési határidővel.
     */
    private function createCompetition(string $deadline): string
    {
        $id = 'comp-' . bin2hex(random_bytes(4));

        $this->db->prepare(
            'INSERT INTO competitions (id, name, date, venue, registration_deadline)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$id, 'Teszt Kupa', '2030-06-01', 'Budapest', $deadline]);

        return $id;
    }

    /** Felhasználói fiók létrehozása */
    private function createUser(string $id, string $name = 'Teszt User'): array
    {
        $this->db->prepare(
            'INSERT INTO users (id, name, email, phone, password_hash) VALUES (?,?,?,?,?)'
        )->execute([$id, $name, $id . '@example.hu', '+3630', 'hash']);

        return ['id' => $id, 'name' => $name, 'email' => $id . '@example.hu', 'phone' => '+3630'];
    }

    /** A verseny aktuális nevezőszáma */
    private function registrantCount(string $competitionId): int
    {
        $stmt = $this->db->prepare('SELECT registrant_count FROM competitions WHERE id = ?');
        $stmt->execute([$competitionId]);

        return (int) $stmt->fetch()['registrant_count'];
    }

    /** A határidő átállítása a múltba, a nevezés rögzítése után */
    private function expireDeadline(string $competitionId): void
    {
        $this->db->prepare('UPDATE competitions SET registration_deadline = ? WHERE id = ?')
            ->execute([date('Y-m-d H:i:s', strtotime('-1 day')), $competitionId]);
    }

    private const FUTURE = '+30 days';

    private function futureDeadline(): string
    {
        return date('Y-m-d H:i:s', strtotime(self::FUTURE));
    }

    // =================================================================
    // Felhasználói visszavonás
    // =================================================================

    public function testUserCanCancelOwnRegistrationBeforeDeadline(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());
        $user = $this->createUser('user-1');

        $reg = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Teszt User', 'email' => 'u1@example.hu', 'phone' => '+3630',
        ], $user['id']);

        $this->assertSame(1, $this->registrantCount($competitionId));

        $this->service->deleteOwnRegistration($reg['id'], $user['id']);

        $this->assertSame(0, $this->registrantCount($competitionId));
        $this->assertCount(0, $this->service->getRegistrations($competitionId));
    }

    public function testUserCannotCancelGuestRegistration(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());
        $user = $this->createUser('user-1');

        // Vendégnevezés: created_by_user_id NULL
        $reg = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Vendég Géza', 'email' => 'guest@example.hu', 'phone' => '+3630',
        ]);

        $this->expectException(AppException::class);

        $this->service->deleteOwnRegistration($reg['id'], $user['id']);
    }

    public function testUserCannotCancelAnotherUsersRegistration(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());
        $owner = $this->createUser('user-owner', 'Tulaj');
        $other = $this->createUser('user-other', 'Idegen');

        $reg = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Tulaj', 'email' => 'owner@example.hu', 'phone' => '+3630',
        ], $owner['id']);

        $this->expectException(AppException::class);

        $this->service->deleteOwnRegistration($reg['id'], $other['id']);
    }

    public function testUserCannotCancelAfterDeadline(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());
        $user = $this->createUser('user-1');

        $reg = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Teszt User', 'email' => 'u1@example.hu', 'phone' => '+3630',
        ], $user['id']);

        $this->expireDeadline($competitionId);

        $this->expectException(AppException::class);

        $this->service->deleteOwnRegistration($reg['id'], $user['id']);
    }

    public function testFailedUserCancellationLeavesRegistrationIntact(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());
        $user = $this->createUser('user-1');

        $reg = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Teszt User', 'email' => 'u1@example.hu', 'phone' => '+3630',
        ], $user['id']);

        $this->expireDeadline($competitionId);

        try {
            $this->service->deleteOwnRegistration($reg['id'], $user['id']);
        } catch (AppException) {
            // Várt hiba
        }

        // A nevezés és a számláló érintetlen marad
        $this->assertCount(1, $this->service->getRegistrations($competitionId));
        $this->assertSame(1, $this->registrantCount($competitionId));
    }

    // =================================================================
    // Szervezői törlés
    // =================================================================

    public function testAdminCanDeleteAnyRegistration(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());

        $reg = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Vendég Géza', 'email' => 'guest@example.hu', 'phone' => '+3630',
        ]);

        $returned = $this->service->deleteRegistrationAsAdmin($reg['id']);

        $this->assertSame($competitionId, $returned);
        $this->assertCount(0, $this->service->getRegistrations($competitionId));
        $this->assertSame(0, $this->registrantCount($competitionId));
    }

    public function testAdminCanDeleteAfterDeadline(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());

        $reg = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Lemondó Béla', 'email' => 'b@example.hu', 'phone' => '+3630',
        ]);

        // A határidő lejárta a szervezőt nem akadályozza
        $this->expireDeadline($competitionId);

        $this->service->deleteRegistrationAsAdmin($reg['id']);

        $this->assertCount(0, $this->service->getRegistrations($competitionId));
        $this->assertSame(0, $this->registrantCount($competitionId));
    }

    public function testAdminCanDeleteRegistrationOwnedByAUser(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());
        $user = $this->createUser('user-1');

        $reg = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Teszt User', 'email' => 'u1@example.hu', 'phone' => '+3630',
        ], $user['id']);

        $this->service->deleteRegistrationAsAdmin($reg['id']);

        $this->assertCount(0, $this->service->getRegistrations($competitionId));
    }

    public function testAdminDeletionOnlyRemovesTheTargetedRegistration(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());

        $keep = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Marad Anna', 'email' => 'a@example.hu', 'phone' => '+3630',
        ]);
        $drop = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Törlendő Béla', 'email' => 'b@example.hu', 'phone' => '+3630',
        ]);

        $this->service->deleteRegistrationAsAdmin($drop['id']);

        $remaining = $this->service->getRegistrations($competitionId);

        $this->assertCount(1, $remaining);
        $this->assertSame('Marad Anna', $remaining[0]['full_name']);
        $this->assertSame(1, $this->registrantCount($competitionId));
    }

    public function testDeletingUnknownRegistrationThrows(): void
    {
        $this->expectException(AppException::class);

        $this->service->deleteRegistrationAsAdmin('nem-letezo-id');
    }

    public function testDeletedEmailCanRegisterAgain(): void
    {
        $competitionId = $this->createCompetition($this->futureDeadline());

        $reg = $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Géza', 'email' => 'geza@example.hu', 'phone' => '+3630',
        ]);

        $this->service->deleteRegistrationAsAdmin($reg['id']);

        // A törlés után ugyanaz az e-mail cím újra nevezhet
        $this->service->registerForCompetition($competitionId, [
            'fullName' => 'Géza', 'email' => 'geza@example.hu', 'phone' => '+3630',
        ]);

        $this->assertCount(1, $this->service->getRegistrations($competitionId));
        $this->assertSame(1, $this->registrantCount($competitionId));
    }
}
