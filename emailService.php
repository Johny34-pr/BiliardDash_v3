<?php
/**
 * Email Service - Általános email küldő rendszer
 * 
 * Ez az osztály központosítja az email küldést és template kezelést.
 * Könnyen bővíthető új email típusokkal.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/init.php';

/**
 * EmailService - Alapvető email küldési szolgáltatás
 */
class EmailService {
    
    /**
     * Email konfiguráció
     */
    private static $config = [
        'from_email' => 'noreply@{host}',
        'from_name' => 'Magyar Bábus Biliárd Egyesület',
        'reply_to' => 'info@{host}',
        'charset' => 'UTF-8'
    ];
    
    /**
     * Általános email küldő metódus
     * 
     * @param string $to Email címzett
     * @param string $subject Email tárgy
     * @param string $htmlBody HTML tartalom
     * @param array $options Opcionális beállítások (from_name, reply_to, stb.)
     * @return bool Sikeres volt-e a küldés
     */
    public static function send($to, $subject, $htmlBody, $options = []) {
        $host = $_SERVER['HTTP_HOST'];
        
        // Fejlécek összeállítása
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=" . self::$config['charset'] . "\r\n";
        
        $fromName = isset($options['from_name']) ? $options['from_name'] : self::$config['from_name'];
        $fromEmail = isset($options['from_email']) ? $options['from_email'] : str_replace('{host}', $host, self::$config['from_email']);
        $replyTo = isset($options['reply_to']) ? $options['reply_to'] : str_replace('{host}', $host, self::$config['reply_to']);
        
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$replyTo}\r\n";
        
        // Email küldés
        $result = mail($to, $subject, $htmlBody, $headers);
        
        // Hiba esetén logolás
        if (!$result) {
            error_log("Email send failed to: {$to}, subject: {$subject}");
        }
        
        return $result;
    }
    
    /**
     * Tömeges email küldés spam védelem késleltetéssel
     * 
     * @param array $recipients Címzettek tömbje [['email' => '', 'name' => '', ...], ...]
     * @param callable $emailBuilder Függvény ami az email tartalmát generálja minden címzetthez
     * @param int $delayMicroseconds Késleltetés mikroszekundumban (alapértelmezett: 100000 = 0.1s)
     * @return array Statisztika [success, failed, total]
     */
    public static function sendBulk($recipients, callable $emailBuilder, $delayMicroseconds = 100000) {
        $successCount = 0;
        $failCount = 0;
        
        foreach ($recipients as $recipient) {
            try {
                $emailData = $emailBuilder($recipient);
                
                $sent = self::send(
                    $recipient['email'],
                    $emailData['subject'],
                    $emailData['body'],
                    isset($emailData['options']) ? $emailData['options'] : []
                );
                
                if ($sent) {
                    $successCount++;
                } else {
                    $failCount++;
                }
                
                // Késleltetés spam védelem miatt
                if ($delayMicroseconds > 0) {
                    usleep($delayMicroseconds);
                }
                
            } catch (Exception $e) {
                error_log("Bulk email error for {$recipient['email']}: " . $e->getMessage());
                $failCount++;
            }
        }
        
        return [
            'success' => $successCount,
            'failed' => $failCount,
            'total' => count($recipients)
        ];
    }
    
    /**
     * Alap URL generálás (http/https figyelembevételével)
     * 
     * @param string $path Útvonal
     * @return string Teljes URL
     */
    public static function getUrl($path = '') {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        return "{$protocol}://{$host}{$path}";
    }
    
    /**
     * Szöveg átalakítása SEO-barát slug-gá (URL-ben használható formátum)
     * 
     * @param string $string Eredeti szöveg
     * @return string URL-barát slug
     */
    public static function convertToSlug($string) {
        // Kisbetűsítés
        $slug = strtolower($string);
        
        // Magyar ékezetes karakterek cseréje
        $slug = strtr($slug, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o', 'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ö' => 'o', 'Ő' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ű' => 'u'
        ]);
        
        // Szóközök cseréje kötőjelre
        $slug = str_replace(' ', '-', $slug);
        
        // Csak betű, szám és kötőjel maradhat
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        // Felesleges kötőjelek eltávolítása (dupla, elejéről, végéről)
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
}

/**
 * EmailTemplate - Email sablonok kezelése
 */
class EmailTemplate {
    
    /**
     * Alap HTML email sablon CSS stílusokkal
     * 
     * @param string $headerTitle Fejléc cím
     * @param string $content Tartalom HTML
     * @param string $footerText Lábléc szöveg (opcionális)
     * @param array $styles Egyedi stílusok (opcionális)
     * @return string Teljes HTML email
     */
    public static function base($headerTitle, $content, $footerText = '', $styles = []) {
        $defaultStyles = [
            'body' => 'font-family: Arial, sans-serif; line-height: 1.6; color: #333;',
            'container' => 'max-width: 600px; margin: 0 auto; padding: 20px;',
            'header' => 'background: linear-gradient(135deg, #01d099 0%, #00a878 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;',
            'content' => 'background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;',
            'button' => 'display: inline-block; padding: 12px 30px; background: #01d099; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0;',
            'footer' => 'text-align: center; margin-top: 30px; font-size: 12px; color: #666;'
        ];
        
        $styles = array_merge($defaultStyles, $styles);
        
        if (empty($footerText)) {
            $footerText = "&copy; " . date('Y') . " Magyar Bábus Biliárd Egyesület. Minden jog fenntartva.";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { {$styles['body']} }
                .container { {$styles['container']} }
                .header { {$styles['header']} }
                .content { {$styles['content']} }
                .button { {$styles['button']} }
                .footer { {$styles['footer']} }
                .small-text { color: #999; font-size: 11px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$headerTitle}</h1>
                </div>
                <div class='content'>
                    {$content}
                </div>
                <div class='footer'>
                    <p>{$footerText}</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Generikus gomb HTML elem
     * 
     * @param string $url Link
     * @param string $text Gomb szöveg
     * @param string $color Háttérszín (opcionális)
     * @return string HTML gomb
     */
    public static function button($url, $text, $color = '#01d099') {
        return "<a href='{$url}' class='button' style='background: {$color}; color: white !important;'>{$text}</a>";
    }
}

/**
 * NewsletterEmails - Hírlevél specifikus email sablonok
 */
class NewsletterEmails {
    
    /**
     * Üdvözlő email új feliratkozónak
     * 
     * @param string $name Feliratkozó neve
     * @param string $email Email cím
     * @param string $unsubscribeToken Leiratkozási token
     * @return bool Sikeres volt-e a küldés
     */
    public static function sendWelcome($name, $email, $unsubscribeToken) {
        $subject = "Üdvözlünk a Magyar Bábus Biliárd Egyesület hírlevelében!";
        
        $content = "
            <h2>Kedves " . htmlspecialchars($name) . "!</h2>
            <p>Köszönjük, hogy feliratkoztál a <strong>Magyar Bábus Biliárd Egyesület</strong> hírlevelére!</p>
            <p>Mostantól értesítést kapsz minden új hírről, cikkről és eseményről, amit megosztunk.</p>
            <p>Amit kapsz tőlünk:</p>
            <ul>
                <li>Friss hírek a biliárd világából</li>
                <li>Versenyek és események bejelentései</li>
                <li>Játékosok és csapatok eredményei</li>
                <li>Exkluzív tartalmak és interjúk</li>
            </ul>
            <p>Ha bármilyen kérdésed van, látogass el a weboldalunkra vagy írj nekünk!</p>
            " . EmailTemplate::button(EmailService::getUrl('/'), 'Látogass el a weboldalra') . "
        ";
        
        $unsubscribeUrl = EmailService::getUrl('/newsletter-unsubscribe.php?token=' . $unsubscribeToken);
        $footer = "
            &copy; " . date('Y') . " Magyar Bábus Biliárd Egyesület. Minden jog fenntartva.
            <p class='small-text'>
                Ha le szeretnél iratkozni a hírlevélről, <a href='{$unsubscribeUrl}'>kattints ide</a>.
            </p>
        ";
        
        $htmlBody = EmailTemplate::base('Üdvözlünk!', $content, $footer);
        
        return EmailService::send($email, $subject, $htmlBody);
    }
    
    /**
     * Új cikk értesítő email
     * 
     * @param string $name Feliratkozó neve
     * @param string $email Email cím
     * @param string $unsubscribeToken Leiratkozási token
     * @param string $articleTitle Cikk címe
     * @param int $articleId Cikk azonosító
     * @return bool Sikeres volt-e a küldés
     */
    public static function sendNewArticle($name, $email, $unsubscribeToken, $articleTitle, $articleId) {
        $subject = "Új cikk: " . $articleTitle;
        // SEO-barát URL generálás slug-gal
        $slug = EmailService::convertToSlug($articleTitle);
        $articleUrl = EmailService::getUrl('/hir/' . $articleId . '/' . $slug);
        
        $content = "
            <h2>Kedves " . htmlspecialchars($name) . "!</h2>
            <p>Új cikket tettünk közzé a Magyar Bábus Biliárd Egyesület weboldalán:</p>
            <div style='font-size: 24px; color: #01d099; margin: 20px 0;'>" . htmlspecialchars($articleTitle) . "</div>
            <p>Kattints az alábbi gombra a teljes cikk elolvasásához:</p>
            " . EmailTemplate::button($articleUrl, 'Cikk elolvasása') . "
        ";
        
        $unsubscribeUrl = EmailService::getUrl('/newsletter-unsubscribe.php?token=' . $unsubscribeToken);
        $footer = "
            &copy; " . date('Y') . " Magyar Bábus Biliárd Egyesület. Minden jog fenntartva.
            <p class='small-text'>
                Ha le szeretnél iratkozni a hírlevélről, <a href='{$unsubscribeUrl}'>kattints ide</a>.
            </p>
        ";
        
        $htmlBody = EmailTemplate::base('📰 Új cikk jelent meg!', $content, $footer);
        
        return EmailService::send($email, $subject, $htmlBody);
    }
    
    /**
     * Tömeges értesítés küldése új cikkről minden feliratkozónak
     * 
     * @param string $articleTitle Cikk címe
     * @param int $articleId Cikk azonosító
     * @return array Statisztika [success, failed, total]
     */
    public static function notifySubscribersAboutNewArticle($articleTitle, $articleId) {
        $db = new MySql();
        $subscribers = $db->getActiveNewsletterSubscribers();
        
        if (empty($subscribers)) {
            return ['success' => 0, 'failed' => 0, 'total' => 0];
        }
        
        // SEO-barát URL generálás slug-gal
        $slug = EmailService::convertToSlug($articleTitle);
        
        return EmailService::sendBulk($subscribers, function($subscriber) use ($articleTitle, $articleId, $slug) {
            $articleUrl = EmailService::getUrl('/hir/' . $articleId . '/' . $slug);
            $unsubscribeUrl = EmailService::getUrl('/newsletter-unsubscribe.php?token=' . $subscriber['unsubscribe_token']);
            
            $content = "
                <h2>Kedves " . htmlspecialchars($subscriber['name']) . "!</h2>
                <p>Új cikket tettünk közzé a Magyar Bábus Biliárd Egyesület weboldalán:</p>
                <div style='font-size: 24px; color: #01d099; margin: 20px 0;'>" . htmlspecialchars($articleTitle) . "</div>
                <p>Kattints az alábbi gombra a teljes cikk elolvasásához:</p>
                " . EmailTemplate::button($articleUrl, 'Cikk elolvasása') . "
            ";
            
            $footer = "
                &copy; " . date('Y') . " Magyar Bábus Biliárd Egyesület. Minden jog fenntartva.
                <p class='small-text'>
                    Ha le szeretnél iratkozni a hírlevélről, <a href='{$unsubscribeUrl}'>kattints ide</a>.
                </p>
            ";
            
            return [
                'subject' => "Új cikk: " . $articleTitle,
                'body' => EmailTemplate::base('📰 Új cikk jelent meg!', $content, $footer)
            ];
        });
    }
}

/**
 * MatchScheduleEmails - Mérkőzés időpont egyeztetés értesítések
 */
class MatchScheduleEmails {
    /**
     * Időpont javaslat értesítés a másik csapat kapitányának
     *
     * @param array $schedule getScheduleById eredménye (team nevek, id-k)
     * @param string $proposedDate Y-m-d H:i:s
     * @param int $agreementId match_date_agreements.agreement_id
     * @param string $proposedBy 'team1'|'team2'
     * @param array $captainUsers címzettek tömbje ['email'=>, 'name'=>]
     */
    public static function sendProposalNotification($schedule, $proposedDate, $agreementId, $proposedBy, $captainUsers) {
        if (empty($captainUsers)) {
            return;
        }

        $team1 = $schedule['team1_name'] ?? 'Hazai csapat';
        $team2 = $schedule['team2_name'] ?? 'Vendég csapat';
        $proposerName = $proposedBy === 'team1' ? $team1 : $team2;
        $respondentName = $proposedBy === 'team1' ? $team2 : $team1;
        $prettyDate = date('Y.m.d', strtotime($proposedDate));
        $url = EmailService::getUrl('/respond-date-proposal.php?agreement_id=' . urlencode($agreementId));

        foreach ($captainUsers as $user) {
            $name = $user['name'] ?? 'Kapitány';
            $subject = "Új mérkőzésidőpont javaslat - {$team1} vs {$team2}";
            $content = "
                <h2>Kedves " . htmlspecialchars($name) . "!</h2>
                <p>A(z) <strong>" . htmlspecialchars($proposerName) . "</strong> csapat új időpontot javasolt a mérkőzésre:</p>
                <p style='font-size:18px;'><strong>" . htmlspecialchars($prettyDate) . "</strong></p>
                <p>Kérlek, kattints a gombra az elfogadáshoz vagy elutasításhoz:</p>
                " . EmailTemplate::button($url, 'Időpont javaslat megnyitása') . "
                <p>Ellenfél: <strong>" . htmlspecialchars($respondentName) . "</strong></p>
            ";

            $body = EmailTemplate::base('Új időpont javaslat', $content);
            EmailService::send($user['email'], $subject, $body);
        }
    }

    /**
     * Elfogadás/elutasítás értesítés a javaslatot tevő kapitánynak
     *
     * @param array $schedule getScheduleById eredménye
     * @param string $proposedDate
     * @param string $decision 'accepted'|'rejected'
     * @param string $respondingTeam 'team1'|'team2'
     * @param array $proposerUser ['email'=>, 'name'=>]
     */
    public static function sendResponseNotification($schedule, $proposedDate, $decision, $respondingTeam, $proposerUser) {
        if (empty($proposerUser['email'])) {
            return;
        }

        $team1 = $schedule['team1_name'] ?? 'Hazai csapat';
        $team2 = $schedule['team2_name'] ?? 'Vendég csapat';
        $responderName = $respondingTeam === 'team1' ? $team1 : $team2;
        $prettyDate = date('Y.m.d', strtotime($proposedDate));
        $title = $decision === 'accepted' ? 'Időpont javaslat elfogadva' : 'Időpont javaslat elutasítva';
        $subject = "{$title} - {$team1} vs {$team2}";
        $content = "
            <h2>Kedves " . htmlspecialchars($proposerUser['name'] ?? 'Kapitány') . "!</h2>
            <p>A(z) <strong>" . htmlspecialchars($responderName) . "</strong> csapat " . ($decision === 'accepted' ? 'elfogadta' : 'elutasította') . " az általad javasolt időpontot.</p>
            <p style='font-size:18px;'><strong>" . htmlspecialchars($prettyDate) . "</strong></p>
            <p>További részleteket a rendszerben találsz.</p>
        ";
        $body = EmailTemplate::base($title, $content);
        EmailService::send($proposerUser['email'], $subject, $body);
    }
}
