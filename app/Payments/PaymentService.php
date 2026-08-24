<?php

declare(strict_types=1);

namespace App\Payments;

use PDO;
use App\Repositories\PaymentRepository;
use App\Repositories\BookingRepository;
use App\Security\Logger;
use App\Payments\CurrencyService;

/**
 * PaymentService — Orchestrateur des paiements
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 *
 * Flux de paiement :
 * 1. initiatePayment()  → status: initiated → pending_customer
 * 2. webhook / polling  → confirmPayment() ou failPayment()
 * 3. Expiration cron    → expirePayment()
 */
class PaymentService
{
    private PDO               $pdo;
    private PaymentRepository $payments;
    private BookingRepository $bookings;
    private Logger            $logger;
    private CurrencyService   $currency;

    public function __construct(PDO $pdo)
    {
        $this->pdo      = $pdo;
        $this->payments = new PaymentRepository($pdo);
        $this->bookings = new BookingRepository($pdo);
        $this->logger   = new Logger($pdo);
        $this->currency = new CurrencyService($pdo);
    }

    // ── Initiation ────────────────────────────────────────────────────

    /**
     * Initier un paiement pour une réservation.
     * Idempotent : si la clé existe déjà, retourner l'existant.
     */
    public function initiatePayment(
        int    $bookingId,
        string $provider,
        string $paymentMethod,
        array  $params = []
    ): array {
        // Vérifier que la réservation existe et est en attente de paiement
        $booking = $this->bookings->findById($bookingId);
        if (!$booking) {
            return ['success' => false, 'error' => 'Réservation introuvable.'];
        }
        if (!in_array($booking['payment_status'], ['unpaid', 'partial'], true)) {
            return ['success' => false, 'error' => 'Cette réservation est déjà payée ou remboursée.'];
        }

        // Générer la clé d'idempotence
        $idempotencyKey = env('PAYMENT_IDEMPOTENCY_PREFIX', 'LZB') . '-'
                         . $bookingId . '-' . $provider . '-' . date('Ymd');

        // Vérifier idempotence
        $existing = $this->payments->findByIdempotencyKey($idempotencyKey);
        if ($existing && in_array($existing['payment_status'], ['initiated','pending_customer','processing'], true)) {
            return ['success' => true, 'payment_id' => $existing['id'], 'idempotent' => true, 'error' => null];
        }

        // Durée d'expiration
        $expMinutes = (int)env('PAYMENT_EXPIRY_MINUTES', '30');
        $expiresAt  = gmdate('Y-m-d H:i:s', time() + $expMinutes * 60);

        // Taux figé au moment du paiement
        $rate     = $this->currency->getActiveRate();
        $totalBif = (int)$booking['total_bif'];
        $usdCents = $this->currency->bifToUsdCents($totalBif, $rate);

        // Appeler le gateway
        $gateway = $this->resolveGateway($provider);
        $result  = $gateway->initiatePayment(array_merge($params, [
            'amount_bif'  => $totalBif,
            'reference'   => $booking['reference'],
            'description' => 'Séjour Hôtel Le Lézard Bleu — Réf. ' . $booking['reference'],
        ]));

        // Enregistrer le paiement
        $paymentId = (int)$this->payments->insert([
            'booking_id'         => $bookingId,
            'idempotency_key'    => $idempotencyKey,
            'provider'           => $provider,
            'payment_method'     => $paymentMethod,
            'amount_bif'         => $totalBif,
            'amount_usd_cents'   => $usdCents,
            'exchange_rate'      => number_format($rate, 6, '.', ''),
            'currency_charged'   => $booking['currency_chosen'] ?? 'BIF',
            'payment_status'     => $result['success'] ? 'pending_customer' : 'failed',
            'provider_reference' => $result['provider_reference'],
            'mobile_number'      => isset($params['phone_number'])
                                    ? $this->maskPhone($params['phone_number']) : null,
            'expires_at'         => $result['success'] ? $expiresAt : null,
            'failure_reason'     => $result['error'],
        ]);

        $this->logger->audit(
            Logger::ACTION_PAYMENT_INITIATED, 'payment', $paymentId,
            null, ['provider' => $provider, 'booking_id' => $bookingId]
        );

        return [
            'success'       => $result['success'],
            'payment_id'    => $paymentId,
            'redirect_url'  => $result['redirect_url'],
            'expires_at'    => $expiresAt,
            'error'         => $result['error'],
            'idempotent'    => false,
        ];
    }

    // ── Confirmation webhook ──────────────────────────────────────────

    /**
     * Traiter un webhook entrant.
     * Inclut anti-rejeu, vérification montant, vérification devise.
     */
    public function processWebhook(string $provider, string $rawPayload, array $headers): array
    {
        // 1. Vérifier la signature
        $gateway = $this->resolveGateway($provider);
        if (!$gateway->verifyWebhook($rawPayload, $headers)) {
            $this->logger->audit(Logger::ACTION_WEBHOOK_INVALID, 'webhook', null,
                null, ['provider' => $provider]);
            return ['success' => false, 'error' => 'Signature invalide.'];
        }

        $payload = json_decode($rawPayload, true);
        if (!is_array($payload)) {
            return ['success' => false, 'error' => 'Payload JSON invalide.'];
        }

        // 2. Extraire l'event_id pour l'anti-rejeu
        $eventId = $payload['event_id'] ?? $payload['transaction_id'] ?? bin2hex(random_bytes(8));
        $payloadHash = hash('sha256', $rawPayload);

        // 3. Enregistrer et vérifier l'unicité (anti-rejeu)
        $isNew = $this->payments->registerWebhookEvent($provider, $eventId, $payloadHash);
        if (!$isNew) {
            return ['success' => true, 'idempotent' => true, 'error' => null]; // Déjà traité
        }

        // 4. Trouver le paiement par référence fournisseur
        $providerRef = $payload['reference'] ?? $payload['transaction_id'] ?? '';
        if (!$providerRef) {
            return ['success' => false, 'error' => 'Référence manquante dans le webhook.'];
        }

        $payment = $this->payments->findByProviderReference($providerRef);
        if (!$payment) {
            return ['success' => false, 'error' => 'Paiement introuvable.'];
        }

        // 5. Vérifier le montant (ne jamais faire confiance au montant du webhook)
        $webhookAmount = (int)($payload['amount'] ?? 0);
        if ($webhookAmount > 0 && abs($webhookAmount - $payment['amount_bif']) > 100) {
            $this->logger->critical('Webhook montant incorrect', [
                'expected' => $payment['amount_bif'],
                'received' => $webhookAmount,
                'provider' => $provider,
                'ref'      => $providerRef,
            ]);
            $this->payments->markWebhookProcessed($provider, $eventId);
            return ['success' => false, 'error' => 'Montant du webhook ne correspond pas.'];
        }

        // 6. Normaliser et appliquer le statut
        $rawStatus        = $payload['status'] ?? 'unknown';
        $normalizedStatus = $gateway->normalizeStatus($rawStatus);

        $this->payments->updateStatus((int)$payment['id'], $normalizedStatus, [
            'webhook_received_at' => gmdate('Y-m-d H:i:s'),
            'provider_event_id'   => $eventId,
            'confirmed_at'        => $normalizedStatus === 'successful' ? gmdate('Y-m-d H:i:s') : null,
        ]);

        // 7. Si paiement réussi → confirmer la réservation
        if ($normalizedStatus === 'successful') {
            $this->pdo->prepare("
                UPDATE bookings SET payment_status = 'paid', statut = 'confirmed',
                       updated_at = UTC_TIMESTAMP()
                WHERE id = :id AND statut IN ('provisional','confirmed')
            ")->execute([':id' => $payment['booking_id']]);

            $this->logger->audit(Logger::ACTION_PAYMENT_CONFIRMED, 'payment', (int)$payment['id'],
                null, ['provider' => $provider, 'ref' => $providerRef]);
        }

        // 8. Marquer le webhook comme traité
        $this->payments->markWebhookProcessed($provider, $eventId);

        return ['success' => true, 'status' => $normalizedStatus, 'error' => null];
    }

    // ── Confirmation manuelle ─────────────────────────────────────────

    /**
     * Confirmer manuellement un paiement (réceptionniste).
     */
    public function confirmManual(int $paymentId, int $adminUserId, string $notes = ''): bool
    {
        $payment = $this->payments->findById($paymentId);
        if (!$payment) return false;

        $this->pdo->beginTransaction();
        try {
            $this->payments->updateStatus($paymentId, 'successful', [
                'confirmed_at' => gmdate('Y-m-d H:i:s'),
            ]);
            $this->pdo->prepare("
                UPDATE bookings SET payment_status = 'paid', statut = 'confirmed',
                       updated_at = UTC_TIMESTAMP()
                WHERE id = :id
            ")->execute([':id' => $payment['booking_id']]);

            $this->pdo->commit();

            $this->logger->audit(Logger::ACTION_PAYMENT_CONFIRMED, 'payment', $paymentId,
                ['payment_status' => 'pending_customer'],
                ['payment_status' => 'successful', 'notes' => $notes],
                $adminUserId);
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[PaymentService] confirmManual: ' . $e->getMessage());
            return false;
        }
    }

    // ── Remboursement ─────────────────────────────────────────────────

    /**
     * Initier un remboursement. Nécessite la permission payments.refund.
     */
    public function initiateRefund(int $paymentId, int $amountBif, string $reason, int $adminUserId): array
    {
        $payment = $this->payments->findById($paymentId);
        if (!$payment || $payment['payment_status'] !== 'successful') {
            return ['success' => false, 'error' => 'Paiement introuvable ou non confirmé.'];
        }
        if ($amountBif > (int)$payment['amount_bif']) {
            return ['success' => false, 'error' => 'Montant de remboursement supérieur au paiement initial.'];
        }

        $rate     = (float)$payment['exchange_rate']; // Taux CONTRACTUEL figé
        $usdCents = $this->currency->bifToUsdCents($amountBif, $rate);

        try {
            $this->pdo->prepare("
                INSERT INTO refunds
                  (payment_id, amount_bif, amount_usd_cents, exchange_rate, reason, status, initiated_by)
                VALUES (:pid, :bif, :usd, :rate, :reason, 'pending', :admin)
            ")->execute([
                ':pid'    => $paymentId,
                ':bif'    => $amountBif,
                ':usd'    => $usdCents,
                ':rate'   => number_format($rate, 6, '.', ''),
                ':reason' => $reason,
                ':admin'  => $adminUserId,
            ]);

            $this->payments->updateStatus($paymentId, 'refunded');

            $this->logger->audit(Logger::ACTION_REFUND_INITIATED, 'payment', $paymentId,
                null, ['amount_bif' => $amountBif, 'reason' => $reason], $adminUserId);

            return ['success' => true, 'error' => null];
        } catch (\PDOException $e) {
            error_log('[PaymentService] initiateRefund: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors du remboursement.'];
        }
    }

    // ── Expiration (cron) ─────────────────────────────────────────────

    /**
     * Expirer les paiements dont le délai est dépassé.
     * Appelé par le cron toutes les 5 minutes.
     */
    public function expireStalePayments(): int
    {
        $expired = $this->payments->findExpired();
        $count   = 0;
        foreach ($expired as $p) {
            $this->payments->updateStatus((int)$p['id'], 'expired');
            // Libérer la réservation provisoire si aucun autre paiement actif
            $this->pdo->prepare("
                UPDATE bookings
                SET statut = 'cancelled', cancelled_by = 'system',
                    cancellation_reason = 'Paiement expiré',
                    cancelled_at = UTC_TIMESTAMP()
                WHERE id = :id AND statut = 'provisional'
                  AND id NOT IN (
                    SELECT booking_id FROM payments
                    WHERE payment_status IN ('pending_customer','processing','successful')
                    AND id != :pid
                  )
            ")->execute([':id' => $p['booking_id'], ':pid' => $p['id']]);
            $count++;
        }
        return $count;
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function resolveGateway(string $provider): PaymentGatewayInterface
    {
        return match ($provider) {
            'lumicash' => new LumicashGateway(),
            default    => new ManualGateway(),
        };
    }

    private function maskPhone(string $phone): string
    {
        // Masquer les 4 chiffres du milieu : +257 79 XX XX 00 → +257 79 ** ** 00
        return preg_replace('/(\d{2})\d{4}(\d{2})$/', '$1****$2', $phone) ?? $phone;
    }
}
