<?php
declare(strict_types=1);

use App\Payments\PaymentService;
use App\Http\Request;

/**
 * Handler POST /api/v1/payments/webhooks/{provider}
 * Traite les callbacks de paiement des opérateurs mobiles.
 * Sécurité : vérification de signature HMAC avant tout traitement.
 */
function handleWebhook(?PDO $pdo, Request $req, string $provider): void
{
    if (!$pdo) { http_response_code(503); echo '{"error":"db"}'; return; }

    $allowedProviders = ['lumicash','ecocash','easypay','paypal'];
    $provider = strtolower(trim($provider));

    if (!in_array($provider, $allowedProviders, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Unknown provider']);
        return;
    }

    // Lire le payload brut AVANT toute décodification
    $rawPayload = file_get_contents('php://input') ?: '';

    // Extraire les headers pertinents
    $headers = [];
    foreach ($_SERVER as $k => $v) {
        if (str_starts_with($k, 'HTTP_')) {
            $headerName = str_replace('_', '-', substr($k, 5));
            $headers[$headerName] = $v;
        }
    }

    $service = new PaymentService($pdo);
    $result  = $service->processWebhook($provider, $rawPayload, $headers);

    // Toujours répondre 200 au fournisseur même en cas d'erreur interne
    // pour éviter les re-tentatives infinies sur des erreurs logiques
    if ($result['success'] || ($result['error'] ?? '') === 'Signature invalide.') {
        http_response_code($result['success'] ? 200 : 400);
    } else {
        http_response_code(200); // ACK au fournisseur
    }

    echo json_encode(['received' => true]);
}
