<?php

declare(strict_types=1);

/**
 * Configuration internationalisation — Hôtel Le Lézard Bleu & Spa
 * Langues : Français (fr) | English (en) | Kirundi (rn)
 */

return [
    'default_locale'    => env('DEFAULT_LOCALE', 'fr'),
    'fallback_locale'   => 'fr',
    'supported_locales' => ['fr', 'en', 'rn'],

    'locale_names' => [
        'fr' => 'Français',
        'en' => 'English',
        'rn' => 'Kirundi',
    ],

    // Codes ISO 639-1 ou BCP 47 pour hreflang
    'hreflang' => [
        'fr' => 'fr',
        'en' => 'en',
        'rn' => 'rn',  // Kirundi
    ],

    // Locale PHP intl pour chaque langue
    'intl_locale' => [
        'fr' => 'fr_BI',   // Français Burundi
        'en' => 'en_US',
        'rn' => 'rn_BI',   // Kirundi Burundi
    ],

    // Format de date par locale
    'date_format' => [
        'fr' => 'd/m/Y',
        'en' => 'Y-m-d',
        'rn' => 'd/m/Y',
    ],

    // Ordre de résolution de la langue :
    // 1. Paramètre GET ?lang=xx (choix manuel)
    // 2. Session ($_SESSION['locale'])
    // 3. Cookie (hotel_lang)
    // 4. Préférence du navigateur (Accept-Language)
    // 5. Langue par défaut (fr)
    'resolution_order' => ['get', 'session', 'cookie', 'browser', 'default'],

    // Durée du cookie de langue (30 jours)
    'cookie_lifetime_days' => 30,
];
