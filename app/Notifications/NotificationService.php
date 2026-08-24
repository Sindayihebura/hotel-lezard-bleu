<?php

declare(strict_types=1);

namespace App\Notifications;

use PDO;
use App\Security\Logger;

/**
 * NotificationService — Envoi d'emails, SMS et WhatsApp
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 *
 * Stratégie :
 * - Tous les envois passent par la file notification_queue (async)
 * - Le cron send_notifications.php traite la file toutes les 5 min
 * - Les envois urgents (alerte sécurité) peuvent être synchrones
 */
class NotificationService
{
    private PDO    $pdo;
    private Logger $logger;
    private string $fromEmail;
    private string $fromName;

    public function __construct(PDO $pdo)
    {
        $this->pdo       = $pdo;
        $this->logger    = new Logger($pdo);
        $this->fromEmail = env('MAIL_FROM_ADDRESS', 'reservation@lezardbleu-hotel.bi');
        $this->fromName  = env('MAIL_FROM_NAME', 'Le Lézard Bleu Hôtel & Spa');
    }

    // ── File d'attente (asynchrone) ───────────────────────────────────

    /**
     * Mettre en file d'attente un email de confirmation de réservation.
     */
    public function queueBookingConfirmation(
        string $toEmail,
        string $firstName,
        array  $bookingData,
        string $locale = 'fr'
    ): void {
        $this->enqueue('email', $toEmail, 'booking_confirmation', [
            'first_name'          => $firstName,
            'reference'           => $bookingData['reference']           ?? '',
            'room_name'           => $bookingData['room_name']           ?? '',
            'checkin'             => $bookingData['date_arrivee']        ?? '',
            'checkout'            => $bookingData['date_depart']         ?? '',
            'nb_nights'           => $bookingData['nb_nights']           ?? '',
            'nb_adults'           => $bookingData['nb_adults']           ?? '',
            'payment_method'      => $bookingData['payment_method']      ?? '',
            'total_bif'           => format_bif((int)($bookingData['total_bif'] ?? 0)),
            'exchange_rate'       => number_format((float)($bookingData['exchange_rate_used'] ?? 6000), 0),
            'cancellation_policy' => 'Annulation gratuite jusqu\'à 48h avant l\'arrivée.',
            'booking_url'         => env('APP_URL') . '/public/mes-reservations.php',
        ], 'Confirmation de réservation — ' . ($bookingData['reference'] ?? ''), $locale);
    }

    /**
     * Mettre en file un email d'annulation.
     */
    public function queueBookingCancellation(
        string $toEmail,
        string $firstName,
        array  $bookingData,
        string $locale = 'fr'
    ): void {
        $this->enqueue('email', $toEmail, 'booking_cancellation', [
            'first_name'           => $firstName,
            'reference'            => $bookingData['reference']           ?? '',
            'room_name'            => $bookingData['room_name']           ?? '',
            'checkin'              => $bookingData['date_arrivee']        ?? '',
            'checkout'             => $bookingData['date_depart']         ?? '',
            'cancellation_reason'  => $bookingData['cancellation_reason'] ?? 'Annulation client',
            'app_url'              => env('APP_URL'),
        ], 'Annulation de réservation — ' . ($bookingData['reference'] ?? ''), $locale);
    }

    /**
     * Mettre en file un reçu de paiement.
     */
    public function queuePaymentReceipt(
        string $toEmail,
        string $firstName,
        array  $paymentData,
        string $locale = 'fr'
    ): void {
        $this->enqueue('email', $toEmail, 'payment_receipt', [
            'first_name'      => $firstName,
            'reference'       => $paymentData['reference']    ?? '',
            'payment_method'  => $paymentData['provider']     ?? 'Manuel',
            'amount_bif'      => format_bif((int)($paymentData['amount_bif'] ?? 0)),
            'exchange_rate'   => number_format((float)($paymentData['exchange_rate'] ?? 6000), 0),
            'payment_date'    => date('d/m/Y H:i'),
            'room_name'       => $paymentData['room_name']    ?? '',
            'checkin'         => $paymentData['date_arrivee'] ?? '',
            'checkout'        => $paymentData['date_depart']  ?? '',
            'nb_nights'       => $paymentData['nb_nights']    ?? '',
            'booking_url'     => env('APP_URL') . '/public/mes-reservations.php',
        ], 'Reçu de paiement — ' . ($paymentData['reference'] ?? ''), $locale);
    }

    /**
     * Mettre en file un email de réinitialisation de mot de passe.
     */
    public function queuePasswordReset(
        string $toEmail,
        string $firstName,
        string $token,
        string $locale = 'fr'
    ): void {
        $resetUrl = env('APP_URL') . '/public/reinitialiser-mot-de-passe.php?token=' . urlencode($token);
        $this->enqueue('email', $toEmail, 'password_reset', [
            'first_name' => $firstName,
            'reset_url'  => $resetUrl,
        ], 'Réinitialisation de votre mot de passe', $locale);
    }

    /**
     * Mettre en file un email de vérification d'adresse.
     */
    public function queueEmailVerification(
        string $toEmail,
        string $firstName,
        string $token,
        string $locale = 'fr'
    ): void {
        $verifyUrl = env('APP_URL') . '/public/verification-email.php?token=' . urlencode($token);
        $this->enqueue('email', $toEmail, 'email_verification', [
            'first_name' => $firstName,
            'verify_url' => $verifyUrl,
        ], 'Confirmez votre adresse email', $locale);
    }

    /**
     * Mettre en file un email de bienvenue.
     */
    public function queueWelcome(
        string $toEmail,
        string $firstName,
        string $locale = 'fr'
    ): void {
        $this->enqueue('email', $toEmail, 'welcome', [
            'first_name' => $firstName,
            'app_url'    => env('APP_URL'),
        ], 'Bienvenue au Lézard Bleu !', $locale);
    }

    // ── Envoi synchrone (urgences sécurité) ──────────────────────────

    /**
     * Envoyer immédiatement une alerte de connexion inhabituelle.
     */
    public function sendLoginAlert(
        string $toEmail,
        string $firstName,
        string $ip,
        string $userAgent,
        string $locale = 'fr'
    ): void {
        $this->enqueue('email', $toEmail, 'unusual_login_alert', [
            'first_name' => $firstName,
            'login_date' => date('d/m/Y à H:i:s') . ' UTC',
            'ip_address' => $ip,
            'user_agent' => mb_substr($userAgent, 0, 100),
            'app_url'    => env('APP_URL'),
        ], 'Nouvelle connexion détectée sur votre compte', $locale, true);
    }

    // ── Envoi email direct (utilisé par cron) ────────────────────────

    /**
     * Envoyer un email en utilisant un template HTML.
     * Utilisé par le cron send_notifications.php.
     */
    public function sendEmail(
        string $toEmail,
        string $subject,
        string $template,
        array  $data,
        string $locale = 'fr'
    ): bool {
        $templateFile = resource_path("emails/{$template}.html");
        if (!file_exists($templateFile)) {
            $this->logger->error("Template email introuvable : {$template}");
            return false;
        }

        $body = file_get_contents($templateFile);
        if ($body === false) {
            return false;
        }

        // Substituer toutes les variables {{key}}
        foreach ($data as $key => $value) {
            $body = str_replace(
                '{{' . $key . '}}',
                htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $body
            );
        }

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= 'From: =?UTF-8?B?' . base64_encode($this->fromName) . "?= <{$this->fromEmail}>\r\n";
        $headers .= "X-Mailer: LezardBleu/1.0\r\n";
        $headers .= "X-Priority: 3\r\n";

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        try {
            return @mail($toEmail, $encodedSubject, $body, $headers);
        } catch (\Throwable $e) {
            $this->logger->error('Email send error', ['to' => $toEmail, 'template' => $template]);
            return false;
        }
    }

    // ── Helpers privés ────────────────────────────────────────────────

    private function enqueue(
        string $type,
        string $recipient,
        string $template,
        array  $data,
        string $subject,
        string $locale,
        bool   $immediate = false
    ): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_queue
                    (type, recipient, subject, template, data_json, locale, status, scheduled_at)
                VALUES
                    (:type, :recipient, :subject, :template, :data, :locale, 'pending', UTC_TIMESTAMP())
            ");
            $stmt->execute([
                ':type'      => $type,
                ':recipient' => $recipient,
                ':subject'   => $subject,
                ':template'  => $template,
                ':data'      => json_encode($data, JSON_UNESCAPED_UNICODE),
                ':locale'    => $locale,
            ]);

            // Si envoi immédiat demandé, traiter maintenant
            if ($immediate) {
                $id = (int)$this->pdo->lastInsertId();
                $this->processNow($id, $type, $recipient, $subject, $template, $data, $locale);
            }
        } catch (\PDOException $e) {
            error_log('[NotificationService] enqueue error: ' . $e->getMessage());
        }
    }

    private function processNow(
        int    $id,
        string $type,
        string $recipient,
        string $subject,
        string $template,
        array  $data,
        string $locale
    ): void {
        if ($type === 'email') {
            $success = $this->sendEmail($recipient, $subject, $template, $data, $locale);
            $status  = $success ? 'sent' : 'failed';
            $this->pdo->prepare(
                "UPDATE notification_queue SET status = :s, sent_at = IF(:ok, UTC_TIMESTAMP(), NULL) WHERE id = :id"
            )->execute([':s' => $status, ':ok' => $success ? 1 : 0, ':id' => $id]);
        }
    }
}
