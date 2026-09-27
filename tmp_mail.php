<?php

declare(strict_types=1);

/** SMTP diagnosztika: mi a beállítás, és mi történik kapcsolódáskor. */

require __DIR__ . '/vendor/autoload.php';

App\Core\Env::load(__DIR__ . '/.env');

$config = require __DIR__ . '/config/mail.php';

echo "=== A BETÖLTÖTT KONFIGURÁCIÓ ===\n";
foreach ($config as $key => $value) {
    $shown = match (true) {
        $key === 'password' => $value === '' ? "''  <-- ÜRES" : '(' . strlen((string) $value) . ' karakter)',
        $value === '' => "''  <-- ÜRES",
        default => var_export($value, true),
    };

    printf("  %-14s %s\n", $key, $shown);
}

echo "\n=== PHPMAILER VERZIÓ ===\n";
echo '  ' . PHPMailer\PHPMailer\PHPMailer::VERSION . "\n";

echo "\n=== OPENSSL ===\n";
echo '  extension_loaded(openssl): ' . var_export(extension_loaded('openssl'), true) . "\n";
echo '  elérhető átvitelek: ' . implode(', ', stream_get_transports()) . "\n";

echo "\n=== KAPCSOLÓDÁSI PRÓBA (nem küld levelet) ===\n";

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
$mail->SMTPDebug = 2;
$mail->Debugoutput = static function (string $str, int $level): void {
    echo '  [' . $level . '] ' . rtrim($str) . "\n";
};

try {
    $mail->isSMTP();
    $mail->Host = $config['host'];
    $mail->Port = $config['port'];
    $mail->Username = $config['username'];
    $mail->Password = $config['password'];
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = $config['encryption'];
    $mail->Timeout = 15;

    if ($mail->smtpConnect()) {
        echo "\n  EREDMÉNY: a kapcsolat FELÁLLT.\n";
        $mail->smtpClose();
    } else {
        echo "\n  EREDMÉNY: a kapcsolat NEM állt fel.\n";
    }
} catch (Throwable $e) {
    echo "\n  KIVÉTEL: " . $e->getMessage() . "\n";
}
