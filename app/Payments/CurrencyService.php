<?php

declare(strict_types=1);

namespace App\Payments;

use PDO;

/**
 * CurrencyService — Gestion des devises BIF/USD
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 *
 * Règles absolues :
 * - BIF stocké en BIGINT (entiers, 0 décimales)
 * - USD stocké en BIGINT centimes (× 100)
 * - Taux en DECIMAL(18,6)
 * - JAMAIS de float pour les calculs monétaires
 * - Le taux est figé au moment de la réservation (snapshot)
 */
class CurrencyService
{
    private PDO $pdo;
    private ?float $cachedRate = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── Taux de change ────────────────────────────────────────────────

    /**
     * Obtenir le taux actif depuis la DB (1 USD = X BIF).
     * Cache en mémoire pour la durée de la requête.
     */
    public function getActiveRate(): float
    {
        if ($this->cachedRate !== null) {
            return $this->cachedRate;
        }

        // Source 1 : table exchange_rates (la plus récente)
        try {
            $stmt = $this->pdo->prepare("
                SELECT CAST(rate AS DECIMAL(18,6)) AS rate
                FROM exchange_rates
                WHERE base_currency = 'USD'
                  AND quote_currency = 'BIF'
                  AND is_active = 1
                  AND (effective_to IS NULL OR effective_to > UTC_TIMESTAMP())
                ORDER BY effective_from DESC
                LIMIT 1
            ");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            if ($val !== false && (float)$val > 0) {
                $this->cachedRate = (float)$val;
                return $this->cachedRate;
            }
        } catch (\PDOException $e) {
            error_log('[CurrencyService] getActiveRate DB error: ' . $e->getMessage());
        }

        // Source 2 : table parametres (legacy)
        try {
            $stmt = $this->pdo->prepare(
                "SELECT valeur FROM parametres WHERE cle = 'taux_usd_bif' LIMIT 1"
            );
            $stmt->execute();
            $val = $stmt->fetchColumn();
            if ($val !== false && is_numeric($val) && (float)$val > 0) {
                $this->cachedRate = (float)$val;
                return $this->cachedRate;
            }
        } catch (\PDOException $e) {
            error_log('[CurrencyService] getActiveRate parametres error: ' . $e->getMessage());
        }

        // Fallback .env
        $this->cachedRate = (float)env('DEFAULT_EXCHANGE_RATE', '6000');
        return $this->cachedRate;
    }

    /**
     * Mettre à jour le taux de change.
     * Historise l'ancien taux avant de désactiver.
     */
    public function updateRate(float $newRate, int $adminUserId, string $reason = ''): bool
    {
        if ($newRate <= 0) {
            return false;
        }

        $oldRate = $this->getActiveRate();

        try {
            $this->pdo->beginTransaction();

            // Désactiver l'ancien taux
            $this->pdo->prepare("
                UPDATE exchange_rates
                SET is_active = 0, effective_to = UTC_TIMESTAMP()
                WHERE base_currency = 'USD' AND quote_currency = 'BIF' AND is_active = 1
            ")->execute();

            // Insérer le nouveau taux
            $this->pdo->prepare("
                INSERT INTO exchange_rates
                  (base_currency, quote_currency, rate, source, effective_from, is_active, created_by)
                VALUES ('USD', 'BIF', :rate, 'admin', UTC_TIMESTAMP(), 1, :admin)
            ")->execute([':rate' => number_format($newRate, 6, '.', ''), ':admin' => $adminUserId]);

            // Historiser
            $this->pdo->prepare("
                INSERT INTO exchange_rate_history
                  (old_rate, new_rate, source, reason, changed_by)
                VALUES (:old, :new, 'admin', :reason, :admin)
            ")->execute([
                ':old'    => number_format($oldRate, 6, '.', ''),
                ':new'    => number_format($newRate, 6, '.', ''),
                ':reason' => $reason,
                ':admin'  => $adminUserId,
            ]);

            // Synchroniser parametres legacy
            $this->pdo->prepare("
                UPDATE parametres SET valeur = :rate WHERE cle = 'taux_usd_bif'
            ")->execute([':rate' => number_format($newRate, 6, '.', '')]);

            $this->pdo->commit();
            $this->cachedRate = $newRate;
            return true;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log('[CurrencyService] updateRate error: ' . $e->getMessage());
            return false;
        }
    }

    // ── Conversions (entiers uniquement) ──────────────────────────────

    /**
     * Convertir BIF → USD cents (BIGINT).
     * Arrondi mathématique (0.5 → haut).
     */
    public function bifToUsdCents(int $amountBif, float $rate): int
    {
        if ($rate <= 0) return 0;
        return (int) round(($amountBif * 100) / $rate);
    }

    /**
     * Convertir USD cents → BIF (BIGINT).
     */
    public function usdCentsToBif(int $usdCents, float $rate): int
    {
        return (int) round(($usdCents * $rate) / 100);
    }

    // ── Calcul total réservation ──────────────────────────────────────

    /**
     * Calculer le total d'une réservation.
     * Retourne un snapshot complet figé avec le taux courant.
     *
     * @param int   $pricePerNightBif   Prix par nuit en BIF (BIGINT)
     * @param int   $nbNights           Nombre de nuits
     * @param int   $servicesBif        Total services additionnels en BIF
     * @param int   $discountBif        Remise en BIF
     * @return array{
     *   rate: float,
     *   price_per_night_bif: int,
     *   nb_nights: int,
     *   subtotal_bif: int,
     *   services_total_bif: int,
     *   discount_bif: int,
     *   total_bif: int,
     *   total_usd_cents: int,
     *   snapshot_json: string
     * }
     */
    public function calculateBookingTotal(
        int $pricePerNightBif,
        int $nbNights,
        int $servicesBif  = 0,
        int $discountBif  = 0
    ): array {
        $rate        = $this->getActiveRate();
        $subtotal    = $pricePerNightBif * $nbNights;
        $total       = max(0, $subtotal + $servicesBif - $discountBif);
        $usdCents    = $this->bifToUsdCents($total, $rate);

        $snapshot = [
            'rate'                => $rate,
            'price_per_night_bif' => $pricePerNightBif,
            'nb_nights'           => $nbNights,
            'subtotal_bif'        => $subtotal,
            'services_total_bif'  => $servicesBif,
            'discount_bif'        => $discountBif,
            'total_bif'           => $total,
            'total_usd_cents'     => $usdCents,
            'calculated_at'       => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        return array_merge($snapshot, ['snapshot_json' => json_encode($snapshot)]);
    }

    /**
     * Appliquer un code promo et retourner le montant de remise en BIF.
     */
    public function applyOfferCode(string $code, int $subtotalBif, int $nbNights): int
    {
        if (empty($code)) return 0;

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

            if (!$offer) return 0;

            if ($offer['discount_type'] === 'percent') {
                $pct = min(100, (int)$offer['discount_value']);
                return (int) round($subtotalBif * $pct / 100);
            }
            if ($offer['discount_type'] === 'fixed_bif') {
                return min($subtotalBif, (int)$offer['discount_value']);
            }
            if ($offer['discount_type'] === 'fixed_usd') {
                $rate = $this->getActiveRate();
                $bifEquiv = $this->usdCentsToBif((int)$offer['discount_value'] * 100, $rate);
                return min($subtotalBif, $bifEquiv);
            }
        } catch (\PDOException $e) {
            error_log('[CurrencyService] applyOfferCode error: ' . $e->getMessage());
        }

        return 0;
    }

    // ── Formatage d'affichage ─────────────────────────────────────────

    public function formatBif(int $amountBif, string $locale = 'fr'): string
    {
        if (extension_loaded('intl')) {
            $intlLocale = $locale === 'en' ? 'en_US' : 'fr_BI';
            $fmt = new \NumberFormatter($intlLocale, \NumberFormatter::DECIMAL);
            $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
            return $fmt->format($amountBif) . ' FBu';
        }
        return number_format($amountBif, 0, ',', ' ') . ' FBu';
    }

    public function formatUsd(int $usdCents, string $locale = 'en'): string
    {
        $amount = $usdCents / 100;
        if (extension_loaded('intl')) {
            $fmt = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
            return $fmt->formatCurrency($amount, 'USD');
        }
        return '$ ' . number_format($amount, 2, '.', ',');
    }

    public function format(int $amountBif, string $currency = 'BIF', ?float $rate = null, string $locale = 'fr'): string
    {
        if ($currency === 'USD') {
            $r    = $rate ?? $this->getActiveRate();
            $cents = $this->bifToUsdCents($amountBif, $r);
            return $this->formatUsd($cents, $locale);
        }
        return $this->formatBif($amountBif, $locale);
    }
}
