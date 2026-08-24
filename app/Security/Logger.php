<?php

declare(strict_types=1);

namespace App\Security;

use PDO;

/**
 * Logger applicatif et audit trail — Hôtel Le Lézard Bleu & Spa
 *
 * Journalise :
 * - Les événements applicatifs (fichier log rotatif)
 * - Les actions sensibles dans audit_logs (base de données)
 *
 * Ne journalise JAMAIS :
 * - Mots de passe, PIN, OTP, CVV, clés API, tokens complets, numéros de carte.
 */
class Logger
{
    // Niveaux de log
    public const DEBUG   = 'DEBUG';
    public const INFO    = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR   = 'ERROR';
    public const CRITICAL = 'CRITICAL';

    // Actions auditables
    public const ACTION_LOGIN              = 'auth.login';
    public const ACTION_LOGIN_FAILED       = 'auth.login_failed';
    public const ACTION_LOGOUT             = 'auth.logout';
    public const ACTION_REGISTER           = 'auth.register';
    public const ACTION_PASSWORD_RESET     = 'auth.password_reset';
    public const ACTION_PASSWORD_CHANGED   = 'auth.password_changed';
    public const ACTION_EMAIL_VERIFIED     = 'auth.email_verified';
    public const ACTION_ROLE_CHANGED       = 'admin.role_changed';
    public const ACTION_BOOKING_CREATED    = 'booking.created';
    public const ACTION_BOOKING_UPDATED    = 'booking.updated';
    public const ACTION_BOOKING_CANCELLED  = 'booking.cancelled';
    public const ACTION_BOOKING_CHECKIN    = 'booking.checkin';
    public const ACTION_BOOKING_CHECKOUT   = 'booking.checkout';
    public const ACTION_PAYMENT_INITIATED  = 'payment.initiated';
    public const ACTION_PAYMENT_CONFIRMED  = 'payment.confirmed';
    public const ACTION_PAYMENT_FAILED     = 'payment.failed';
    public const ACTION_REFUND_INITIATED   = 'payment.refund_initiated';
    public const ACTION_REFUND_CONFIRMED   = 'payment.refund_confirmed';
    public const ACTION_RATE_CHANGED       = 'settings.exchange_rate_changed';
    public const ACTION_PRICE_CHANGED      = 'rooms.price_changed';
    public const ACTION_EXPORT             = 'data.export';
    public const ACTION_ACCESS_DENIED      = 'security.access_denied';
    public const ACTION_BRUTE_FORCE        = 'security.brute_force';
    public const ACTION_SETTINGS_CHANGED   = 'settings.changed';
    public const ACTION_USER_CREATED       = 'admin.user_created';
    public const ACTION_USER_DISABLED      = 'admin.user_disabled';
    public const ACTION_WEBHOOK_RECEIVED   = 'payment.webhook_received';
    public const ACTION_WEBHOOK_INVALID    = 'payment.webhook_invalid';

    private ?PDO $pdo;
    private string $logPath;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo     = $pdo;
        $this->logPath = storage_path('logs/app.log');
    }

    // ── Log fichier ───────────────────────────────────────────────────────

    public function log(string $level, string $message, array $context = []): void
    {
        // Masquer les champs sensibles avant d'écrire
        $context = $this->sanitizeContext($context);

        $line = sprintf(
            "[%s] [%s] [req:%s] [ip:%s] %s %s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $level,
            request_id(),
            client_ip(),
            $message,
            !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );

        // Écriture append-only, rotation gérée par logrotate côté serveur
        @file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
        $this->triggerAlert($message, $context);
    }

    // ── Audit DB ──────────────────────────────────────────────────────────

    /**
     * Enregistrer une action auditée dans la table audit_logs.
     *
     * @param string      $action        Constante ACTION_* définie ci-dessus
     * @param string|null $resourceType  Type de ressource (booking, user, room…)
     * @param int|null    $resourceId    ID de la ressource
     * @param array|null  $oldValues     Valeurs avant modification (sensibles masquées)
     * @param array|null  $newValues     Valeurs après modification (sensibles masquées)
     * @param int|null    $adminUserId   ID de l'admin (null si action cliente)
     * @param string      $result        'success' | 'failure'
     * @param string|null $failureReason Raison en cas d'échec
     */
    public function audit(
        string  $action,
        ?string $resourceType  = null,
        ?int    $resourceId    = null,
        ?array  $oldValues     = null,
        ?array  $newValues     = null,
        ?int    $adminUserId   = null,
        string  $result        = 'success',
        ?string $failureReason = null,
        ?int    $customerId    = null   // BUG FIX : customer_id manquant
    ): void {
        // Masquer les champs sensibles
        $oldValues = $oldValues !== null ? $this->sanitizeContext($oldValues) : null;
        $newValues = $newValues !== null ? $this->sanitizeContext($newValues) : null;

        // Déduire le customer_id depuis la session si non fourni
        if ($customerId === null && !empty($_SESSION['customer_id'])) {
            $customerId = (int) $_SESSION['customer_id'];
        }

        // Log fichier aussi
        $this->log(self::INFO, "AUDIT:{$action}", [
            'resource'      => $resourceType,
            'resource_id'   => $resourceId,
            'result'        => $result,
            'admin_user_id' => $adminUserId,
            'customer_id'   => $customerId,
        ]);

        // Insertion en DB
        if ($this->pdo === null) {
            return;
        }
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs
                    (admin_user_id, customer_id, action, resource_type, resource_id,
                     old_values, new_values, ip_address, user_agent,
                     request_id, result, failure_reason, created_at)
                VALUES
                    (:admin_user_id, :customer_id, :action, :resource_type, :resource_id,
                     :old_values, :new_values, :ip_address, :user_agent,
                     :request_id, :result, :failure_reason, UTC_TIMESTAMP())
            ");
            $stmt->execute([
                ':admin_user_id'  => $adminUserId,
                ':customer_id'    => $customerId,
                ':action'         => $action,
                ':resource_type'  => $resourceType,
                ':resource_id'    => $resourceId,
                ':old_values'     => $oldValues !== null ? json_encode($oldValues) : null,
                ':new_values'     => $newValues !== null ? json_encode($newValues) : null,
                ':ip_address'     => client_ip(),
                ':user_agent'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512),
                ':request_id'     => request_id(),
                ':result'         => $result,
                ':failure_reason' => $failureReason,
            ]);
        } catch (\PDOException $e) {
            // Ne jamais bloquer l'application si l'audit échoue
            error_log('[Audit] Insert failed: ' . $e->getMessage());
        }
    }

    // ── Alertes ───────────────────────────────────────────────────────────

    /**
     * Détecter et alerter sur des patterns suspects.
     */
    public function checkBruteForce(string $identifier, int $attempts, int $max): void
    {
        if ($attempts >= $max) {
            $this->critical('Brute force détecté', [
                'identifier' => $identifier,
                'attempts'   => $attempts,
            ]);
            $this->audit(
                self::ACTION_BRUTE_FORCE,
                'auth',
                null,
                null,
                ['identifier' => $identifier, 'attempts' => $attempts],
                null,
                'failure',
                "Brute force : {$attempts} tentatives"
            );
        }
    }

    private function triggerAlert(string $message, array $context): void
    {
        // En production : envoyer un email/SMS à l'équipe de sécurité.
        // Implémentation de notification à compléter selon le fournisseur.
        $alertFile = storage_path('logs/security_alerts.log');
        $line = sprintf("[%s] ALERT %s %s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE)
        );
        @file_put_contents($alertFile, $line, FILE_APPEND | LOCK_EX);
    }

    // ── Sanitisation ─────────────────────────────────────────────────────

    /**
     * Masquer les champs sensibles dans un tableau de contexte.
     * Ne jamais logger : password, pin, otp, cvv, api_key, token, card_number…
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveKeys = [
            'password', 'passwd', 'pass', 'pwd',
            'pin', 'otp', 'cvv', 'cvc',
            'card_number', 'pan', 'card_full',
            'api_key', 'api_secret', 'secret', 'token',
            'access_token', 'refresh_token', 'bearer',
            'webhook_secret', 'hmac', 'signature_raw',
            'credit_card', 'bank_account',
        ];

        $result = [];
        foreach ($context as $key => $value) {
            $keyLower = strtolower((string) $key);
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($keyLower, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }
            if ($isSensitive) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $result[$key] = $this->sanitizeContext($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
