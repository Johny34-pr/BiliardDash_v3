<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * E-mail küldési szolgáltatás PHPMailer-rel.
 * SMTP konfigurációval, retry logikával és HTML sablonnal.
 */
class EmailService
{
    private array $config;

    /**
     * @param array $smtpConfig SMTP konfiguráció (host, port, username, password, encryption, from_address, from_name)
     */
    public function __construct(array $smtpConfig)
    {
        $this->config = $smtpConfig;
    }

    /**
     * Visszaigazoló e-mail küldése a nevezőnek.
     *
     * @param string $to Címzett e-mail cím
     * @param array $data Nevezési adatok: competitionName, competitionDate, competitionVenue, fullName, email, phone
     * @return bool Sikeres küldés esetén true, egyébként false
     */
    public function sendRegistrationConfirmation(string $to, array $data): bool
    {
        $maxRetries = 3;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $mail = new PHPMailer(true);

                // SMTP konfiguráció
                $mail->isSMTP();
                $mail->Host = $this->config['host'];
                $mail->Port = $this->config['port'];
                $mail->Username = $this->config['username'];
                $mail->Password = $this->config['password'];
                $mail->SMTPAuth = true;
                $mail->SMTPSecure = $this->config['encryption'];

                // Feladó és címzett
                $mail->setFrom($this->config['from_address'], $this->config['from_name']);
                $mail->addAddress($to);

                // Karakter kódolás
                $mail->CharSet = 'UTF-8';

                // Tárgy
                $mail->Subject = "Nevezés visszaigazolás - {$data['competitionName']}";

                // HTML tartalom
                $mail->isHTML(true);
                $mail->Body = $this->buildHtmlBody($data);

                $mail->send();

                return true;
            } catch (Exception $e) {
                error_log("EmailService: Attempt {$attempt}/{$maxRetries} failed - " . $e->getMessage());

                if ($attempt < $maxRetries) {
                    sleep(1);
                }
            }
        }

        return false;
    }

    /**
     * HTML e-mail sablon összeállítása.
     */
    private function buildHtmlBody(array $data): string
    {
        $competitionName = htmlspecialchars($data['competitionName'], ENT_QUOTES, 'UTF-8');
        $competitionDate = htmlspecialchars($data['competitionDate'], ENT_QUOTES, 'UTF-8');
        $competitionVenue = htmlspecialchars($data['competitionVenue'], ENT_QUOTES, 'UTF-8');
        $fullName = htmlspecialchars($data['fullName'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Nevezés visszaigazolás</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #166534; color: #fff; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 24px; color: #facc15;">Magyar Biliárd</h1>
        <p style="margin: 5px 0 0; font-size: 14px;">Nevezés visszaigazolás</p>
    </div>

    <div style="background-color: #ffffff; padding: 30px; border: 1px solid #e5e7eb; border-top: none;">
        <p style="font-size: 16px;">Kedves <strong>{$fullName}</strong>,</p>

        <p>Sikeresen neveztél az alábbi versenyre. Köszönjük a jelentkezésedet!</p>

        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h2 style="margin: 0 0 10px; font-size: 18px; color: #166534;">Verseny adatai</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Verseny neve:</td>
                    <td style="padding: 5px 0;">{$competitionName}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Dátum:</td>
                    <td style="padding: 5px 0;">{$competitionDate}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Helyszín:</td>
                    <td style="padding: 5px 0;">{$competitionVenue}</td>
                </tr>
            </table>
        </div>

        <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h2 style="margin: 0 0 10px; font-size: 18px; color: #374151;">Nevezési adataid</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Név:</td>
                    <td style="padding: 5px 0;">{$fullName}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">E-mail:</td>
                    <td style="padding: 5px 0;">{$email}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 10px 5px 0; font-weight: bold; vertical-align: top;">Telefon:</td>
                    <td style="padding: 5px 0;">{$phone}</td>
                </tr>
            </table>
        </div>

        <p style="color: #6b7280; font-size: 14px;">Ha kérdésed van, kérjük vedd fel velünk a kapcsolatot.</p>
    </div>

    <div style="background-color: #f9fafb; padding: 15px; text-align: center; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <p style="margin: 0; font-size: 12px; color: #9ca3af;">&copy; Magyar Biliárd | Ez egy automatikus üzenet, kérjük ne válaszolj rá.</p>
    </div>
</body>
</html>
HTML;
    }
}
