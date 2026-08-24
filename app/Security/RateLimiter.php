<?php

declare(strict_types=1);

namespace App\Security;

use PDO;

/**
 * Rate limiter par IP/identifiant — stockage en DB.
 * Utilisé pour : login, API, réservation, contact, reset password.
 */
class RateLimiter
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Vérifier si la limite est dépassée.
     *
     * @param string $key        Clé unique (ex: "login:127.0.0.1", "api:/api/v1/bookings")
     * @param int    $maxHits    Nombre max de requêtes
     * @param int    $windowSecs Fenêtre en secondes
     * @return bool true = limite dépassée (bloquer), false = OK
     */
    public function tooManyAttempts(string $key, int $maxHits, int $windowSecs = 60): bool
    {
        $this->purgeExpired();
        $count = $this->getCount($key, $windowSecs);
        return $count >= $maxHits;
    }

    /**
     * Enregistrer une tentative.
     */
    public function hit(string $key, int $windowSecs = 60): int
    {
        $expiresAt = gmdate('Y-m-d H:i:s', time() + $windowSecs);
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits (rate_key, expires_at)
                VALUES (:key, :expires_at)
            ");
            $stmt->execute([':key' => $key, ':expires_at' => $expiresAt]);
        } catch (\PDOException $e) {
            error_log('[RateLimiter] hit error: ' . $e->getMessage());
        }
        return $this->getCount($key, $windowSecs);
    }

    /**
     * Réinitialiser les tentatives pour une clé (après succès login).
     */
    public function reset(string $key): void
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM rate_limits WHERE rate_key = :key");
            $stmt->execute([':key' => $key]);
        } catch (\PDOException $e) {
            error_log('[RateLimiter] reset error: ' . $e->getMessage());
        }
    }

    /**
     * Obtenir le nombre de tentatives restantes.
     */
    public function remaining(string $key, int $maxHits, int $windowSecs = 60): int
    {
        return max(0, $maxHits - $this->getCount($key, $windowSecs));
    }

    /**
     * Obtenir la date d'expiration du blocage.
     */
    public function availableAt(string $key, int $windowSecs = 60): ?string
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT MIN(expires_at) FROM rate_limits
                WHERE rate_key = :key
                AND expires_at > UTC_TIMESTAMP()
            ");
            $stmt->execute([':key' => $key]);
            $result = $stmt->fetchColumn();
            return $result ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    private function getCount(string $key, int $windowSecs): int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM rate_limits
                WHERE rate_key = :key
                AND expires_at > UTC_TIMESTAMP()
            ");
            $stmt->execute([':key' => $key]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    private function purgeExpired(): void
    {
        // Purge probabiliste (1/100) pour ne pas purger à chaque requête
        if (random_int(1, 100) === 1) {
            try {
                $this->pdo->exec("DELETE FROM rate_limits WHERE expires_at <= UTC_TIMESTAMP()");
            } catch (\PDOException $e) {
                error_log('[RateLimiter] purge error: ' . $e->getMessage());
            }
        }
    }
}
