<?php

declare(strict_types=1);

namespace App\Payments;

/**
 * Interface commune pour tous les adaptateurs de paiement.
 * Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi
 *
 * IMPORTANT : Les endpoints et signatures des fournisseurs locaux
 * (Lumicash/Lumitel, EcoCash/Econet Leo, EasyPay) sont marqués @todo
 * car ils doivent être confirmés avec la documentation officielle
 * de chaque opérateur avant toute mise en production.
 */
interface PaymentGatewayInterface
{
    /**
     * Initier un paiement.
     * @return array{success: bool, provider_reference: string|null, redirect_url: string|null, error: string|null, raw: array}
     */
    public function initiatePayment(array $params): array;

    /**
     * Vérifier le statut d'un paiement côté fournisseur.
     * @return array{status: string, provider_reference: string|null, raw: array}
     */
    public function getPaymentStatus(string $providerReference): array;

    /**
     * Vérifier la signature d'un webhook entrant.
     * Retourne true seulement si la signature est valide.
     */
    public function verifyWebhook(string $rawPayload, array $headers): bool;

    /**
     * Émettre un remboursement.
     * @return array{success: bool, provider_reference: string|null, error: string|null}
     */
    public function refundPayment(string $providerReference, int $amountBif, string $reason): array;

    /**
     * Normaliser un statut fournisseur vers les statuts internes.
     * @param string $providerStatus Statut brut du fournisseur
     * @return string Statut normalisé : initiated|pending_customer|processing|successful|failed|expired|cancelled|manual_review
     */
    public function normalizeStatus(string $providerStatus): string;
}
