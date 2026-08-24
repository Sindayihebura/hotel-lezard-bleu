<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * HotelService — Données de configuration de l'hôtel
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 */
class HotelService
{
    private PDO   $pdo;
    private array $cache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtenir un paramètre hôtel.
     */
    public function get(string $key, string $default = ''): string
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        try {
            $stmt = $this->pdo->prepare("SELECT valeur FROM parametres WHERE cle = :k LIMIT 1");
            $stmt->execute([':k' => $key]);
            $val = $stmt->fetchColumn();
            $this->cache[$key] = $val !== false ? (string)$val : $default;
        } catch (\PDOException $e) {
            $this->cache[$key] = $default;
        }

        return $this->cache[$key];
    }

    /**
     * Mettre à jour un paramètre.
     */
    public function set(string $key, string $value, int $adminId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE parametres SET valeur = :v, updated_by = :a, updated_at = UTC_TIMESTAMP() WHERE cle = :k"
            );
            $stmt->execute([':v' => $value, ':a' => $adminId, ':k' => $key]);
            unset($this->cache[$key]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log('[HotelService] set error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir tous les paramètres publics (pour les pages client).
     */
    public function getPublicSettings(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT cle, valeur FROM parametres WHERE is_public = 1");
            $rows = $stmt ? $stmt->fetchAll() : [];
            $result = [];
            foreach ($rows as $r) {
                $result[$r['cle']] = $r['valeur'];
            }
            return $result;
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Vérifier si un code promo est valide.
     */
    public function validateOfferCode(string $code, int $nbNights = 1): ?array
    {
        if (empty($code)) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM offers
                WHERE code = :code
                  AND is_active = 1
                  AND (valid_from IS NULL OR valid_from <= CURDATE())
                  AND (valid_to   IS NULL OR valid_to   >= CURDATE())
                  AND (max_uses   IS NULL OR uses_count < max_uses)
                  AND min_nights <= :nights
                LIMIT 1
            ");
            $stmt->execute([':code' => strtoupper($code), ':nights' => $nbNights]);
            $offer = $stmt->fetch();
            return $offer ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }
}
