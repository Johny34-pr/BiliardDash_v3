<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use App\Services\ValidationService;
use PHPUnit\Framework\TestCase;

class ValidationServiceTest extends TestCase
{
    private ValidationService $service;

    protected function setUp(): void
    {
        $this->service = new ValidationService();
    }

    // --- News Validation ---

    public function testValidateNewsWithValidData(): void
    {
        $validator = $this->service->validateNews([
            'title' => 'Teszt hír cím',
            'content' => '<p>Teszt tartalom</p>',
        ]);

        $this->assertTrue($validator->isValid());
        $this->assertEmpty($validator->getErrors());
    }

    public function testValidateNewsRequiresTitle(): void
    {
        $validator = $this->service->validateNews([
            'content' => 'Tartalom',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('title'));
    }

    public function testValidateNewsRequiresContent(): void
    {
        $validator = $this->service->validateNews([
            'title' => 'Cím',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('content'));
    }

    public function testValidateNewsTitleMaxLength(): void
    {
        $validator = $this->service->validateNews([
            'title' => str_repeat('a', 201),
            'content' => 'Tartalom',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('title'));
    }

    public function testValidateNewsTitleAtMaxLengthIsValid(): void
    {
        $validator = $this->service->validateNews([
            'title' => str_repeat('a', 200),
            'content' => 'Tartalom',
        ]);

        $this->assertTrue($validator->isValid());
    }

    // --- Album Validation ---

    public function testValidateAlbumWithValidData(): void
    {
        $validator = $this->service->validateAlbum([
            'name' => 'Teszt Album',
        ]);

        $this->assertTrue($validator->isValid());
        $this->assertEmpty($validator->getErrors());
    }

    public function testValidateAlbumRequiresName(): void
    {
        $validator = $this->service->validateAlbum([]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('name'));
    }

    public function testValidateAlbumRejectsWhitespaceOnlyName(): void
    {
        $validator = $this->service->validateAlbum([
            'name' => '   ',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('name'));
    }

    public function testValidateAlbumNameMaxLength(): void
    {
        $validator = $this->service->validateAlbum([
            'name' => str_repeat('a', 101),
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('name'));
    }

    public function testValidateAlbumNameAtMaxLengthIsValid(): void
    {
        $validator = $this->service->validateAlbum([
            'name' => str_repeat('a', 100),
        ]);

        $this->assertTrue($validator->isValid());
    }

    // --- Registration Validation ---

    public function testValidateRegistrationWithValidData(): void
    {
        $validator = $this->service->validateRegistration([
            'fullName' => 'Teszt Felhasználó',
            'email' => 'teszt@example.com',
            'phone' => '+36301234567',
        ]);

        $this->assertTrue($validator->isValid());
        $this->assertEmpty($validator->getErrors());
    }

    public function testValidateRegistrationRequiresFullName(): void
    {
        $validator = $this->service->validateRegistration([
            'email' => 'teszt@example.com',
            'phone' => '+36301234567',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('fullName'));
    }

    public function testValidateRegistrationRequiresEmail(): void
    {
        $validator = $this->service->validateRegistration([
            'fullName' => 'Teszt Név',
            'phone' => '+36301234567',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('email'));
    }

    public function testValidateRegistrationRequiresPhone(): void
    {
        $validator = $this->service->validateRegistration([
            'fullName' => 'Teszt Név',
            'email' => 'teszt@example.com',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('phone'));
    }

    public function testValidateRegistrationRejectsInvalidEmail(): void
    {
        $validator = $this->service->validateRegistration([
            'fullName' => 'Teszt Név',
            'email' => 'nem-valid-email',
            'phone' => '+36301234567',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('email'));
    }

    public function testValidateRegistrationFullNameMaxLength(): void
    {
        $validator = $this->service->validateRegistration([
            'fullName' => str_repeat('a', 101),
            'email' => 'teszt@example.com',
            'phone' => '+36301234567',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('fullName'));
    }

    // --- Competition Validation ---

    public function testValidateCompetitionWithValidData(): void
    {
        $validator = $this->service->validateCompetition([
            'name' => 'Tavaszi Verseny',
            'date' => '2025-03-15',
            'venue' => 'Budapest, Sport utca 1.',
            'registrationDeadline' => '2025-03-10',
        ]);

        $this->assertTrue($validator->isValid());
        $this->assertEmpty($validator->getErrors());
    }

    public function testValidateCompetitionRequiresName(): void
    {
        $validator = $this->service->validateCompetition([
            'date' => '2025-03-15',
            'venue' => 'Budapest',
            'registrationDeadline' => '2025-03-10',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('name'));
    }

    public function testValidateCompetitionRequiresDate(): void
    {
        $validator = $this->service->validateCompetition([
            'name' => 'Verseny',
            'venue' => 'Budapest',
            'registrationDeadline' => '2025-03-10',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('date'));
    }

    public function testValidateCompetitionRequiresVenue(): void
    {
        $validator = $this->service->validateCompetition([
            'name' => 'Verseny',
            'date' => '2025-03-15',
            'registrationDeadline' => '2025-03-10',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('venue'));
    }

    public function testValidateCompetitionRequiresRegistrationDeadline(): void
    {
        $validator = $this->service->validateCompetition([
            'name' => 'Verseny',
            'date' => '2025-03-15',
            'venue' => 'Budapest',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('registrationDeadline'));
    }

    public function testValidateCompetitionRejectsInvalidDate(): void
    {
        $validator = $this->service->validateCompetition([
            'name' => 'Verseny',
            'date' => 'nem-datum',
            'venue' => 'Budapest',
            'registrationDeadline' => '2025-03-10',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('date'));
    }

    public function testValidateCompetitionNameMaxLength(): void
    {
        $validator = $this->service->validateCompetition([
            'name' => str_repeat('a', 101),
            'date' => '2025-03-15',
            'venue' => 'Budapest',
            'registrationDeadline' => '2025-03-10',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('name'));
    }

    public function testValidateCompetitionVenueMaxLength(): void
    {
        $validator = $this->service->validateCompetition([
            'name' => 'Verseny',
            'date' => '2025-03-15',
            'venue' => str_repeat('a', 201),
            'registrationDeadline' => '2025-03-10',
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('venue'));
    }

    // --- Image Upload Validation ---

    public function testValidateImageUploadWithValidJpeg(): void
    {
        $validator = $this->service->validateImageUpload([
            'type' => 'image/jpeg',
            'size' => 5 * 1024 * 1024, // 5 MB
        ]);

        $this->assertTrue($validator->isValid());
        $this->assertEmpty($validator->getErrors());
    }

    public function testValidateImageUploadWithValidPng(): void
    {
        $validator = $this->service->validateImageUpload([
            'type' => 'image/png',
            'size' => 1024,
        ]);

        $this->assertTrue($validator->isValid());
    }

    public function testValidateImageUploadRejectsInvalidType(): void
    {
        $validator = $this->service->validateImageUpload([
            'type' => 'image/gif',
            'size' => 1024,
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('image'));
    }

    public function testValidateImageUploadRejectsOversizedFile(): void
    {
        $validator = $this->service->validateImageUpload([
            'type' => 'image/jpeg',
            'size' => 11 * 1024 * 1024, // 11 MB
        ]);

        $this->assertFalse($validator->isValid());
        $this->assertNotNull($validator->getError('image'));
    }

    public function testValidateImageUploadAtExactMaxSizeIsValid(): void
    {
        $validator = $this->service->validateImageUpload([
            'type' => 'image/jpeg',
            'size' => 10 * 1024 * 1024, // exactly 10 MB
        ]);

        $this->assertTrue($validator->isValid());
    }
}
