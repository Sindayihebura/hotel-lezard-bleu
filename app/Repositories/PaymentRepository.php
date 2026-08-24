<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * PaymentRepository — Accès aux données de paiement
 * Hôtel Le Lézard Bleu & Spa
 *
 * Garanties :
 * - idempotency_key unique (anti-double paiement)
 * - provider_event_id unique (anti-rejeu webhook)
 * - Toutes les recherches par payment_id vérifient le booking_id (BOLA)
 */
class PaymentRepository extends BaseRepository
{
    protected string $table = 'payments';

    protected function getAllowedColumns(): array
    {
        return [
            'booking_id','idempotency_key','provider','payment_method',
            'amount_bif','amount_usd_cents','exchange_rate','currency_charged',
            'payment_status','provider_reference','provider_event_id',
            'mobile_number','bank_name','expires_at',
            'initiated_at','confirmed_at','failed_at','failure_reason',
            'webhook_received_at','metadata_json',
        ];
    }

    /** Trouver un paiement par ID avec vérification booking (BOLA). */
    public function findByIdForBooking(int $paymentId, int $bookingId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments
            WHERE id = :id AND booking_id = :booking_id
            LIMIT 1
        ");
        $stmt->execute([':id' => $paymentId, ':booking_id' => $bookingId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Trouver tous les paiements d'une réservation. */
    public function findByBooking(int $bookingId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments
            WHERE booking_id = :booking_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetchAll();
    }

    /** Trouver par référence fournisseur. */
    public function findByProviderReference(string $ref): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments WHERE provider_reference = :ref LIMIT 1
        ");
        $stmt->execute([':ref' => $ref]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Vérifier si une idempotency_key a déjà été utilisée. */
    public function findByIdempotencyKey(string $key): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments WHERE idempotency_key = :key LIMIT 1
        ");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Mettre à jour le statut d'un paiement. */
    public function updateStatus(int $paymentId, string $status, array $extra = []): bool
    {
        $allowed = [
            'initiated','pending_customer','processing','successful',
            'failed','expired','cancelled','provider_unavailable','manual_review','refunded',
        ];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $sets   = ['payment_status = :status', 'updated_at = UTC_TIMESTAMP()'];
        $params = [':status' => $status, ':id' => $paymentId];

        // Champs additionnels autorisés
        $allowedExtra = ['confirmed_at','failed_at','failure_reason','webhook_received_at',
                         'provider_reference','provider_event_id','metadata_json'];
        foreach ($allowedExtra as $field) {
            if (array_key_exists($field, $extra)) {
                $sets[]           = "`{$field}` = :{$field}";
                $params[":{$field}"] = $extra[$field];
            }
        }

        $sql  = "UPDATE payments SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    /** Anti-rejeu webhook : vérifier et enregistrer un event_id. */
    public function registerWebhookEvent(string $provider, string $eventId, string $payloadHash): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO webhook_events (provider, event_id, payload_hash, processed)
                VALUES (:provider, :event_id, :hash, 0)
            ");
            $stmt->execute([
                ':provider' => $provider,
                ':event_id' => $eventId,
                ':hash'     => $payloadHash,
            ]);
            return true;
        } catch (\PDOException $e) {
            // Duplicate key = event déjà traité
            if (str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate')) {
                return false;
            }
            throw $e;
        }
    }

    /** Marquer un webhook event comme traité. */
    public function markWebhookProcessed(string $provider, string $eventId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE webhook_events
            SET processed = 1, processed_at = UTC_TIMESTAMP()
            WHERE provider = :p AND event_id = :e
        ");
        $stmt->execute([':p' => $provider, ':e' => $eventId]);
    }

    /** Paiements expirés à traiter (cron). */
    public function findExpired(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments
            WHERE payment_status IN ('initiated', 'pending_customer')
              AND expires_at IS NOT NULL
              AND expires_at < UTC_TIMESTAMP()
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Paiements en attente (pour le dashboard admin). */
    public function countPending(): int
    {
        $stmt = $this->pdo->query("
            SELECT COUNT(*) FROM payments
            WHERE payment_status IN ('pending_customer', 'initiated', 'processing')
        ");
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }
}
