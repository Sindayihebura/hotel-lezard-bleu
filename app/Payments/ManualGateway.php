<?php

declare(strict_types=1);

namespace App\Payments;

/**
 * ManualGateway — Paiement manuel (espèces, virement, mobile money manuel)
 * Hôtel Le Lézard Bleu & Spa
 *
 * Utilisé pour :
 * - Espèces BIF / USD à l'arrivée
 * - Virement bancaire local Burundi
 * - Mobile Money confirmé manuellement par la réception
 */
class ManualGateway implements PaymentGatewayInterface
{
    public function initiatePayment(array $params): array
    {
        // Paiement manuel : on retourne simplement un statut pending_customer
        // La confirmation se fait manuellement par l'équipe de réception
        return [
            'success'            => true,
            'provider_reference' => 'MANUAL-' . strtoupper(bin2hex(random_bytes(6))),
            'redirect_url'       => null,
            'error'              => null,
            'raw'                => ['method' => $params['method'] ?? 'manual'],
        ];
    }

    public function getPaymentStatus(string $providerReference): array
    {
        // Pour les paiements manuels, le statut est géré dans la DB
        return [
            'status'             => 'pending_customer',
            'provider_reference' => $providerReference,
            'raw'                => [],
        ];
    }

    public function verifyWebhook(string $rawPayload, array $headers): bool
    {
        // Pas de webhook pour les paiements manuels
        return false;
    }

    public function refundPayment(string $providerReference, int $amountBif, string $reason): array
    {
        // Remboursement manuel : enregistré dans la DB, traité par la comptabilité
        return [
            'success'            => true,
            'provider_reference' => 'REFUND-' . strtoupper(bin2hex(random_bytes(4))),
            'error'              => null,
        ];
    }

    public function normalizeStatus(string $providerStatus): string
    {
        return match ($providerStatus) {
            'confirmed', 'paid'    => 'successful',
            'pending', 'waiting'   => 'pending_customer',
            'cancelled'            => 'cancelled',
            'refunded'             => 'refunded',
            default                => 'pending_customer',
        };
    }
}
