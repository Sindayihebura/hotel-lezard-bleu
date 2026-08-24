<?php
declare(strict_types=1);
/**
 * Cron job — Envoi des notifications (emails, SMS, WhatsApp)
 * Hôtel Le Lézard Bleu & Spa
 *
 * Crontab :
 *   * /5 * * * * php /var/www/lezardbleu/cron/send_notifications.php >> /var/log/cron_notif.log 2>&1
 */
if (PHP_SAPI !== 'cli' && !defined('CRON_ALLOWED')) {
    http_response_code(403); exit('Accès refusé.');
}

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Security\Logger;

$pdo = getDB();
if (!$pdo) { echo 'DB indisponible.' . PHP_EOL; exit(1); }

$logger = new Logger($pdo);
$maxBatch = 20; // Traiter 20 notifications par cycle
$sent = 0; $failed = 0;

$stmt = $pdo->prepare("
    SELECT * FROM notification_queue
    WHERE status = 'pending'
      AND scheduled_at <= UTC_TIMESTAMP()
      AND attempts < max_attempts
    ORDER BY scheduled_at ASC
    LIMIT :limit
");
$stmt->bindValue(':limit', $maxBatch, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();

echo '[' . date('Y-m-d H:i:s') . '] ' . count($notifications) . ' notification(s) à traiter.' . PHP_EOL;

foreach ($notifications as $notif) {
    // Marquer en cours
    $pdo->prepare("UPDATE notification_queue SET status='sending', attempts=attempts+1 WHERE id=:id")
        ->execute([':id' => $notif['id']]);

    $success = false;
    $errorMsg = '';

    try {
        $data = json_decode($notif['data_json'] ?? '{}', true) ?? [];

        if ($notif['type'] === 'email') {
            $success = sendEmailNotification(
                $notif['recipient'],
                $notif['subject'] ?? 'Notification — Le Lézard Bleu',
                $notif['template'],
                $data,
                $notif['locale']
            );
        }
        // SMS et WhatsApp : @todo intégrer selon l'opérateur disponible au Burundi
    } catch (\Throwable $e) {
        $errorMsg = $e->getMessage();
        $logger->error('Cron notification error', ['id' => $notif['id'], 'error' => $errorMsg]);
    }

    $status = $success ? 'sent' : (($notif['attempts'] + 1) >= $notif['max_attempts'] ? 'failed' : 'pending');
    $pdo->prepare("UPDATE notification_queue SET status=:s, sent_at=IF(:ok, UTC_TIMESTAMP(), NULL), error_msg=:e WHERE id=:id")
        ->execute([':s' => $status, ':ok' => $success ? 1 : 0, ':e' => $errorMsg ?: null, ':id' => $notif['id']]);

    if ($success) $sent++; else $failed++;
}

echo '[' . date('Y-m-d H:i:s') . "] Résultat : {$sent} envoyée(s), {$failed} échouée(s)." . PHP_EOL;

function sendEmailNotification(string $to, string $subject, string $template, array $data, string $locale): bool
{
    // Implémentation simple via mail() PHP natif
    // En production : utiliser PHPMailer ou Symfony Mailer avec SMTP
    $from    = env('MAIL_FROM_ADDRESS', 'reservation@lezardbleu-hotel.bi');
    $fromName = env('MAIL_FROM_NAME', 'Le Lézard Bleu Hôtel & Spa');

    $templateFile = base_path("resources/emails/{$template}.html");
    if (!file_exists($templateFile)) {
        // Template simple par défaut
        $body = "<p>Bonjour,</p><p>Message de l'Hôtel Le Lézard Bleu & Spa.</p>";
    } else {
        $body = file_get_contents($templateFile);
        // Substituer les variables
        foreach ($data as $k => $v) {
            $body = str_replace('{{' . $k . '}}', htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'), $body);
        }
    }

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
    $headers .= "X-Mailer: LezardBleu/1.0\r\n";

    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}
