<?php

declare(strict_types=1);

/**
 * Configuration sécurité — Hôtel Le Lézard Bleu & Spa
 * Centralise tous les paramètres de sécurité applicative.
 */

return [
    // ── Mots de passe ──────────────────────────────────────────────────────
    'password' => [
        'algorithm'  => PASSWORD_DEFAULT,   // bcrypt par défaut, argon2id si disponible
        'cost'       => (int) env('BCRYPT_COST', '12'),
        'min_length' => (int) env('PASSWORD_MIN_LENGTH', '10'),
        'max_length' => 128,
    ],

    // ── Tentatives de connexion ───────────────────────────────────────────
    'login' => [
        'max_attempts'           => (int) env('MAX_LOGIN_ATTEMPTS', '5'),
        'lockout_minutes'        => (int) env('LOCKOUT_DURATION_MINUTES', '15'),
        // Fenêtre de comptage des tentatives (en secondes)
        'window_seconds'         => 600,
        // MFA obligatoire pour les comptes admin
        'admin_mfa_required'     => filter_var(env('ADMIN_MFA_REQUIRED', 'true'), FILTER_VALIDATE_BOOLEAN),
    ],

    // ── Sessions ──────────────────────────────────────────────────────────
    'session' => [
        'name'            => env('SESSION_NAME', '__Host-lezard_session'),
        'lifetime_seconds'=> (int) env('SESSION_LIFETIME', '7200'),
        'secure'          => filter_var(env('SESSION_SECURE', 'true'), FILTER_VALIDATE_BOOLEAN),
        'samesite'        => env('SESSION_SAMESITE', 'Lax'),
    ],

    // ── CSRF ──────────────────────────────────────────────────────────────
    'csrf' => [
        'token_length'   => 32,   // bytes → 64 hex chars
        'rotate_on_use'  => false, // true = rotation après chaque usage sensible
    ],

    // ── Rate limiting ─────────────────────────────────────────────────────
    'rate_limit' => [
        'default_per_minute'    => (int) env('RATE_LIMIT_PER_MINUTE', '60'),
        'booking_per_hour'      => 10,
        'auth_per_minute'       => 5,
        'api_per_minute'        => 120,
        'password_reset_per_hour' => 3,
        'contact_per_hour'      => 5,
    ],

    // ── Headers HTTP ─────────────────────────────────────────────────────
    'headers' => [
        'hsts'              => 'max-age=31536000; includeSubDomains; preload',
        'x_frame_options'   => 'DENY',
        'x_content_type'    => 'nosniff',
        'referrer_policy'   => 'strict-origin-when-cross-origin',
        'permissions_policy'=> 'geolocation=(), microphone=(), camera=(), payment=()',
    ],

    // ── IP et accès ───────────────────────────────────────────────────────
    'ip' => [
        // IPs privées bloquées pour SSRF
        'blocked_ranges' => [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            '169.254.0.0/16',
            '::1',
            'fc00::/7',
        ],
    ],

    // ── Webhooks paiement ─────────────────────────────────────────────────
    'webhook' => [
        // Durée max acceptée pour un timestamp de webhook (anti-rejeu)
        'timestamp_tolerance_seconds' => 300,
    ],

    // ── Export ────────────────────────────────────────────────────────────
    'export' => [
        'max_rows_csv'    => 5000,
        'max_rows_report' => 10000,
    ],
];
