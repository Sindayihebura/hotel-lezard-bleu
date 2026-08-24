<?php

declare(strict_types=1);

namespace App\Payments;

/**
 * LumicashGateway — Adaptateur Lumicash (Lumitel Burundi)
 *
 * @todo Les endpoints, paramètres et signatures doivent être confirmés
 *       avec la documentation officielle Lumitel avant tout test réel.
 *       Ce fichier est un squelette structurel conforme à l'interface.
 *       Ne jamais appeler ces méthodes en production sans intégration validée.
 */
class LumicashGateway implements PaymentGatewayInterface
{
    private string $merchantId;
    private string $apiKey;
    private string $apiSecret;
    private string $webhookSecret;
    private string $baseUrl;
    private int    $timeoutSeconds = 15;

    public function __construct()
    {
        $this->merchantId    = env('LUMICASH_MERCHANT_ID', '');
        $this->apiKey        = env('LUMICASH_API_KEY', '');
        $this->apiSecret     = env('LUMICASH_API_SECRET', '');
        $this->webhookSecret = env('LUMICASH_WEBHOOK_SECRET', '');
        $this->baseUrl       = env('LUMICASH_API_BASE_URL', '');
    }

    public function initiatePayment(array $params): array
    {
        // @todo Implémenter selon documentation officielle Lumitel
        // Paramètres attendus : amount_bif, phone_number, reference, description
        if (empty($this->baseUrl) || empty($this->merchantId)) {
            return [
                'success'            => false,
                'provider_reference' => null,
                'redirect_url'       => null,
                'error'              => 'Lumicash non configuré. Contactez l\'administrateur.',
                'raw'                => [],
            ];
        }

        // Structure de requête @todo à adapter selon doc officielle
        $payload = [
            'merchant_id' => $this->merchantId,
            'amount'      => $params['amount_bif'] ?? 0,
            'phone'       => $params['phone_number'] ?? '',
            'reference'   => $params['reference'] ?? '',
            'description' => $params['description'] ?? 'Paiement Hôtel Le Lézard Bleu',
            'timestamp'   => time(),
            'nonce'       => bin2hex(random_bytes(8)),
        ];

        // Signature HMAC @todo vérifier l'algorithme exact avec Lumitel
        $payload['signature'] = hash_hmac('sha256', json_encode($payload), $this->apiSecret);

        return $this->httpPost('/initiate', $payload); // @todo endpoint à confirmer
    }

    public function getPaymentStatus(string $providerReference): array
    {
        // @todo Implémenter selon documentation officielle Lumitel
        return ['status' => 'manual_review', 'provider_reference' => $providerReference, 'raw' => []];
    }

    public function verifyWebhook(string $rawPayload, array $headers): bool
    {
        // @todo Vérifier la méthode de signature HMAC officielle Lumitel
        $receivedSig = $headers['X-Lumicash-Signature'] ?? $headers['x-lumicash-signature'] ?? '';
        if (empty($receivedSig) || empty($this->webhookSecret)) return false;

        $expectedSig = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
        return hash_equals($expectedSig, $receivedSig);
    }

    public function refundPayment(string $providerReference, int $amountBif, string $reason): array
    {
        // @todo Implémenter selon documentation officielle Lumitel
        return ['success' => false, 'provider_reference' => null,
                'error' => 'Remboursement Lumicash non encore implémenté.'];
    }

    public function normalizeStatus(string $providerStatus): string
    {
        // @todo Mapper les statuts officiels Lumitel
        return match (strtolower($providerStatus)) {
            'success', 'successful', 'completed' => 'successful',
            'pending', 'initiated'               => 'pending_customer',
            'processing'                         => 'processing',
            'failed', 'error'                    => 'failed',
            'expired', 'timeout'                 => 'expired',
            'cancelled'                          => 'cancelled',
            default                              => 'manual_review',
        };
    }

    private function httpPost(string $endpoint, array $payload): array
    {
        $url = rtrim($this->baseUrl, '/') . $endpoint;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-Api-Key: ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw    = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($error || $raw === false) {
            error_log('[Lumicash] HTTP error: ' . $error);
            return ['success' => false, 'provider_reference' => null,
                    'redirect_url' => null, 'error' => 'Fournisseur indisponible.', 'raw' => []];
        }

        $decoded = json_decode($raw, true) ?? [];
        // @todo Adapter selon la structure de réponse réelle Lumitel
        return [
            'success'            => $status === 200,
            'provider_reference' => $decoded['reference'] ?? $decoded['transaction_id'] ?? null,
            'redirect_url'       => $decoded['redirect_url'] ?? null,
            'error'              => $status !== 200 ? ($decoded['message'] ?? 'Erreur fournisseur') : null,
            'raw'                => $decoded,
        ];
    }
}
