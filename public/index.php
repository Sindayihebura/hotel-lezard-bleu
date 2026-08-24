<?php

declare(strict_types=1);

/**
 * Point d'entrée public — Hôtel Le Lézard Bleu & Spa
 * Redirige vers la page d'accueil principale.
 */

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

// Pour la compatibilité avec l'existant, inclure les helpers
// (getDB, format_currency, sanitize_input, generate_csrf_token…)

define('PAGE_TITLE', 'Le Lézard Bleu | Hôtel 5 Étoiles — Bujumbura, Lac Tanganyika');
define('PUBLIC_ROOT', __DIR__);
define('APP_ROOT', dirname(__DIR__));

// Charger la page d'accueil depuis la vue principale
// Durant la migration, on redirige vers le fichier PHP existant à la racine
header('Location: /index_main.php', true, 302);
exit;
