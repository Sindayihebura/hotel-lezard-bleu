<?php

declare(strict_types=1);

/**
 * Configuration e-mail — Hôtel Le Lézard Bleu & Spa
 */

return [
    'driver'       => env('MAIL_DRIVER', 'smtp'),
    'host'         => env('MAIL_HOST', ''),
    'port'         => (int) env('MAIL_PORT', '587'),
    'username'     => env('MAIL_USERNAME', ''),
    'password'     => env('MAIL_PASSWORD', ''),
    'encryption'   => env('MAIL_ENCRYPTION', 'tls'),
    'from_address' => env('MAIL_FROM_ADDRESS', 'reservation@lezardbleu-hotel.bi'),
    'from_name'    => env('MAIL_FROM_NAME', 'Le Lézard Bleu Hôtel & Spa'),

    // Templates e-mail disponibles
    'templates' => [
        'booking_confirmation'  => 'resources/emails/booking_confirmation.html',
        'booking_cancellation'  => 'resources/emails/booking_cancellation.html',
        'payment_receipt'       => 'resources/emails/payment_receipt.html',
        'password_reset'        => 'resources/emails/password_reset.html',
        'email_verification'    => 'resources/emails/email_verification.html',
        'welcome'               => 'resources/emails/welcome.html',
        'unusual_login_alert'   => 'resources/emails/unusual_login_alert.html',
    ],

    // Timeout de connexion SMTP
    'timeout_seconds' => 10,

    // Retry
    'max_attempts' => 3,
];
