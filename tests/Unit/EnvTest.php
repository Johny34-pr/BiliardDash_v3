<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Env;
use PHPUnit\Framework\TestCase;

/**
 * A környezeti változó betöltő tesztjei.
 *
 * Minden konfigurációs fájl erre épül, ezért a két legfontosabb szabályt
 * külön ellenőrizzük:
 *   1. A már beállított értékeket soha nem írja felül (a kiszolgáló és a
 *      tesztkörnyezet beállításai elsőbbséget kapnak a .env fájllal szemben).
 *   2. A "false" szöveg logikai hamisként értelmeződik - egyébként igaz
 *      értékké alakulna, mert a .env fájlból minden szövegként érkezik.
 *
 * Nem a Tests\TestCase-ből származik, mert nem kell hozzá adatbázis.
 */
class EnvTest extends TestCase
{
    private string $tempFile;

    /** @var array<string,string|false> A módosított változók eredeti értéke */
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempFile = sys_get_temp_dir() . '/envtest_' . bin2hex(random_bytes(6)) . '.env';
        Env::reset();
    }

    protected function tearDown(): void
    {
        @unlink($this->tempFile);

        // A teszt által beállított változók visszaállítása
        foreach ($this->originalEnv as $key => $value) {
            if ($value === false) {
                unset($_ENV[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        Env::reset();

        parent::tearDown();
    }

    /** Változó megjegyzése, hogy a teszt után visszaállítható legyen */
    private function remember(string ...$keys): void
    {
        foreach ($keys as $key) {
            $this->originalEnv[$key] = $_ENV[$key] ?? false;
            unset($_ENV[$key]);
            putenv($key);
        }
    }

    private function writeEnv(string $contents): void
    {
        file_put_contents($this->tempFile, $contents);
    }

    // =================================================================
    // Betöltés
    // =================================================================

    public function testLoadsSimpleValues(): void
    {
        $this->remember('ENVTEST_HOST', 'ENVTEST_NAME');
        $this->writeEnv("ENVTEST_HOST=localhost\nENVTEST_NAME=billiard\n");

        Env::load($this->tempFile);

        $this->assertSame('localhost', Env::get('ENVTEST_HOST'));
        $this->assertSame('billiard', Env::get('ENVTEST_NAME'));
    }

    public function testStripsSurroundingQuotes(): void
    {
        $this->remember('ENVTEST_DOUBLE', 'ENVTEST_SINGLE');
        $this->writeEnv("ENVTEST_DOUBLE=\"Magyar Biliárd\"\nENVTEST_SINGLE='Másik érték'\n");

        Env::load($this->tempFile);

        $this->assertSame('Magyar Biliárd', Env::get('ENVTEST_DOUBLE'));
        $this->assertSame('Másik érték', Env::get('ENVTEST_SINGLE'));
    }

    public function testIgnoresCommentsAndBlankLines(): void
    {
        $this->remember('ENVTEST_REAL');
        $this->writeEnv("# megjegyzés\n\n   \nENVTEST_REAL=igen\n# ENVTEST_FAKE=nem\n");

        Env::load($this->tempFile);

        $this->assertSame('igen', Env::get('ENVTEST_REAL'));
        $this->assertNull(Env::get('ENVTEST_FAKE'));
    }

    public function testValueMayContainEqualsSign(): void
    {
        $this->remember('ENVTEST_KEY');
        // API kulcsok és jelszavak tartalmazhatnak egyenlőségjelet
        $this->writeEnv("ENVTEST_KEY=abc==def=ghi\n");

        Env::load($this->tempFile);

        $this->assertSame('abc==def=ghi', Env::get('ENVTEST_KEY'));
    }

    public function testDoesNotOverwriteExistingValue(): void
    {
        $this->remember('ENVTEST_EXISTING');

        // Előre beállított érték, mint a phpunit.xml vagy az Apache SetEnv esetén
        $_ENV['ENVTEST_EXISTING'] = 'kiszolgalotol';
        $this->writeEnv("ENVTEST_EXISTING=envfajlbol\n");

        Env::load($this->tempFile);

        $this->assertSame('kiszolgalotol', Env::get('ENVTEST_EXISTING'));
    }

    public function testMissingFileIsNotAnError(): void
    {
        Env::load(sys_get_temp_dir() . '/nem_letezik_' . bin2hex(random_bytes(4)) . '.env');

        // Nem dob kivételt, és a hívás után is használható
        $this->assertNull(Env::get('ENVTEST_NOTHING'));
    }

    public function testLoadRunsOnlyOnce(): void
    {
        $this->remember('ENVTEST_FIRST', 'ENVTEST_SECOND');

        $this->writeEnv("ENVTEST_FIRST=egy\n");
        Env::load($this->tempFile);

        // Második hívás más fájllal: a betöltés idempotens, nem fut újra
        $second = sys_get_temp_dir() . '/envtest2_' . bin2hex(random_bytes(4)) . '.env';
        file_put_contents($second, "ENVTEST_SECOND=ketto\n");
        Env::load($second);
        @unlink($second);

        $this->assertSame('egy', Env::get('ENVTEST_FIRST'));
        $this->assertNull(Env::get('ENVTEST_SECOND'));
    }

    // =================================================================
    // Kiolvasás
    // =================================================================

    public function testGetReturnsDefaultWhenMissing(): void
    {
        $this->assertSame('alapertelmezes', Env::get('ENVTEST_MISSING', 'alapertelmezes'));
    }

    public function testGetReturnsDefaultWhenEmpty(): void
    {
        $this->remember('ENVTEST_EMPTY');
        $_ENV['ENVTEST_EMPTY'] = '';

        // Az üres érték ugyanúgy kezelendő, mint a nem beállított
        $this->assertSame('alapertelmezes', Env::get('ENVTEST_EMPTY', 'alapertelmezes'));
    }

    // =================================================================
    // Logikai értékek
    // =================================================================

    public function testBoolTreatsFalseStringAsFalse(): void
    {
        $this->remember('ENVTEST_FLAG');

        foreach (['false', 'FALSE', '0', 'off', 'no', ''] as $value) {
            $_ENV['ENVTEST_FLAG'] = $value;
            $this->assertFalse(
                Env::bool('ENVTEST_FLAG', true),
                "A(z) '{$value}' értéket hamisként kell értelmezni"
            );
        }
    }

    public function testBoolTreatsTruthyStringsAsTrue(): void
    {
        $this->remember('ENVTEST_FLAG');

        foreach (['true', '1', 'on', 'yes'] as $value) {
            $_ENV['ENVTEST_FLAG'] = $value;
            $this->assertTrue(
                Env::bool('ENVTEST_FLAG', false),
                "A(z) '{$value}' értéket igazként kell értelmezni"
            );
        }
    }

    public function testBoolReturnsDefaultWhenMissing(): void
    {
        $this->assertTrue(Env::bool('ENVTEST_MISSING_FLAG', true));
        $this->assertFalse(Env::bool('ENVTEST_MISSING_FLAG', false));
    }
}
