<?php

declare(strict_types=1);

/**
 * Configuration base de données et singleton PDO.
 * Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi
 */

namespace Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    /** Retourne l'instance PDO unique (singleton). */
    public static function getInstance(): ?PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }
        return self::$instance;
    }

    private static function connect(): ?PDO
    {
        $host    = env('DB_HOST', 'localhost');
        $port    = env('DB_PORT', '3306');
        $db      = env('DB_DATABASE', 'hotel_lezardbleu');
        $user    = env('DB_USERNAME', '');
        $pass    = env('DB_PASSWORD', '');
        $charset = env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ]);
            // Définir le fuseau horaire SQL
            $tz = env('APP_TIMEZONE', 'Africa/Bujumbura');
            // Convertir en offset UTC pour MySQL (Africa/Bujumbura = UTC+2)
            $pdo->exec("SET time_zone = '+02:00'");
            $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
            return $pdo;
        } catch (PDOException $e) {
            // Ne jamais exposer les détails de connexion
            error_log('[DB] Connection failed: ' . $e->getMessage());
            return null;
        }
    }

    /** Ferme la connexion (utile pour les tests). */
    public static function reset(): void
    {
        self::$instance = null;
    }
}

// ── Compatibilité avec l'ancien getDB() procédural ────────────────────────
// Permet de migrer progressivement sans casser les fichiers existants.
if (!function_exists('getDB')) {
    function getDB(): ?PDO
    {
        return \Config\Database::getInstance();
    }
}
