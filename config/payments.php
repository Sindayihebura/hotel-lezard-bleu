<?php

declare(strict_types=1);

/**
 * Configuration paiements — Hôtel Le Lézard Bleu & Spa
 *
 * IMPORTANT :
 * - Les clés API réelles sont lues depuis .env uniquement.
 * - Ce fichier ne contient AUCUNE clé, secret ou credential en dur.
 * - Les endpoints réels de Lumicash/EcoCash doivent être confirmés
 *   avec la documentation officielle de chaque opérateur.
 *   Ils sont marqués @todo ci-dessous.
 */

return [

    // ── Fournisseur actif ─────────────────────────────────────────────────
    'default'     => env('PAYMENT_PROVIDER', 'manual'),
    'environment' => env('PAYMENT_ENV', 'sandbox'),  // sandbox | live

    // ── Devise des montants ───────────────────────────────────────────────
    'currency' => [
        'primary'   => 'BIF',
        'secondary' => 'USD',
    ],

    // ── Délai d'attente paiement ──────────────────────────────────────────
    'expiry' => [
        // Minutes avant expiration d'un paiement initié
        'mobile_money_minutes' => 30,
        'bank_transfer_hours'  => 48,
        'card_minutes'         => 15,
    ],

    // ── Idempotence ───────────────────────────────────────────────────────
    'idempotency' => [
        'key_prefix'    => 'LZB',
        'ttl_seconds'   => 86400, // 24h de mémorisation des idempotency keys
    ],

    // ── Timeout HTTP vers fournisseurs externes ───────────────────────────
    'http_timeout_seconds' => 15,

    // ── Lumicash (Lumitel Burundi) ────────────────────────────────────────
    // @todo : Vérifier endpoints et flux exacts avec la documentation Lumitel.
    // Ces URLs sont des placeholders — NE PAS utiliser en production sans confirmation.
    'lumicash' => [
        'merchant_id'    => env('LUMICASH_MERCHANT_ID', ''),
        'api_key'        => env('LUMICASH_API_KEY', ''),
        'api_secret'     => env('LUMICASH_API_SECRET', ''),
        'webhook_secret' => env('LUMICASH_WEBHOOK_SECRET', ''),
        'api_base_url'   => env('LUMICASH_API_BASE_URL', ''), // @todo remplir depuis doc officielle
        'supported_currency' => 'BIF',
        'min_amount_bif' => 1000,
        'max_amount_bif' => 50000000,
    ],

    // ── EcoCash / PesaFlash (Econet Leo Burundi) ─────────────────────────
    // @todo : Vérifier endpoints et flux exacts avec la documentation Econet Leo.
    'ecocash' => [
        'merchant_id'    => env('ECOCASH_MERCHANT_ID', ''),
        'api_key'        => env('ECOCASH_API_KEY', ''),
        'api_secret'     => env('ECOCASH_API_SECRET', ''),
        'webhook_secret' => env('ECOCASH_WEBHOOK_SECRET', ''),
        'api_base_url'   => env('ECOCASH_API_BASE_URL', ''), // @todo remplir depuis doc officielle
        'supported_currency' => 'BIF',
        'min_amount_bif' => 1000,
        'max_amount_bif' => 10000000,
    ],

    // ── EasyPay ───────────────────────────────────────────────────────────
    // @todo : Vérifier endpoints avec la documentation EasyPay Burundi.
    'easypay' => [
        'merchant_id'    => env('EASYPAY_MERCHANT_ID', ''),
        'api_key'        => env('EASYPAY_API_KEY', ''),
        'api_secret'     => env('EASYPAY_API_SECRET', ''),
        'webhook_secret' => env('EASYPAY_WEBHOOK_SECRET', ''),
        'api_base_url'   => env('EASYPAY_API_BASE_URL', ''), // @todo remplir depuis doc officielle
        'supported_currencies' => ['BIF', 'USD'],
    ],

    // ── PayPal (international) ────────────────────────────────────────────
    'paypal' => [
        'client_id'     => env('PAYPAL_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),
        'webhook_id'    => env('PAYPAL_WEBHOOK_ID', ''),
        'mode'          => env('PAYPAL_MODE', 'sandbox'), // sandbox | live
        'supported_currency' => 'USD',
        // Redirection après paiement
        'return_url'  => env('APP_URL', '') . '/public/reservation-confirmee.php',
        'cancel_url'  => env('APP_URL', '') . '/public/reservation.php',
    ],

    // ── Paiement manuel / espèces ─────────────────────────────────────────
    'manual' => [
        'methods' => [
            'cash_bif'    => 'Espèces BIF (à l\'arrivée)',
            'cash_usd'    => 'Espèces USD (à l\'arrivée)',
            'bank_local'  => 'Virement bancaire local Burundi',
        ],
        'local_banks' => [
            'BANCOBU' => 'Banque Commerciale du Burundi',
            'BCB'     => 'Banque de Crédit de Bujumbura',
            'IBB'     => 'Interbank Burundi',
            'ECOBANK' => 'Ecobank Burundi',
            'CRDB'    => 'CRDB Bank Burundi',
            'FINBANK' => 'Finbank Burundi',
            'BGF'     => 'Banque de Gestion et de Financement',
            'BHB'     => 'Banque de l\'Habitat du Burundi',
        ],
    ],

    // ── Statuts unifiés ───────────────────────────────────────────────────
    'statuses' => [
        'initiated',
        'pending_customer',
        'processing',
        'successful',
        'failed',
        'expired',
        'cancelled',
        'provider_unavailable',
        'manual_review',
        'refunded',
    ],
];
