<?php

declare(strict_types=1);

/**
 * Bootstrap des tests PHPUnit — Hôtel Le Lézard Bleu & Spa
 */

// Charger l'autoloader Composer
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Charger le bootstrap en mode test
putenv('APP_ENV=testing');
putenv('APP_DEBUG=true');

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

// Helpers pour les tests
function getTestPdo(): ?\PDO
{
    // En CI/CD, une DB de test séparée est utilisée
    return \Config\Database::getInstance();
}
