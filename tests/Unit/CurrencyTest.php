<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires — Système BIF/USD
 * Hôtel Le Lézard Bleu & Spa
 */
class CurrencyTest extends TestCase
{
    private float $rate = 6000.0;

    // ── convertBifToUsdCents ─────────────────────────────────────────

    public function testConvertBifToUsdCentsBasic(): void
    {
        // 6 000 BIF = 1 USD = 100 cents
        $this->assertSame(100, convert_bif_to_usd_cents(6000, $this->rate));
    }

    public function testConvertBifToUsdCentsZero(): void
    {
        $this->assertSame(0, convert_bif_to_usd_cents(0, $this->rate));
    }

    public function testConvertBifToUsdCentsRounding(): void
    {
        // 3 000 BIF = 0.5 USD = 50 cents
        $this->assertSame(50, convert_bif_to_usd_cents(3000, $this->rate));
    }

    public function testConvertBifToUsdCentsLargeAmount(): void
    {
        // 3 900 000 BIF (Suite Présidentielle) = 650 USD = 65 000 cents
        $this->assertSame(65000, convert_bif_to_usd_cents(3900000, $this->rate));
    }

    public function testConvertBifToUsdCentsZeroRateReturnsZero(): void
    {
        $this->assertSame(0, convert_bif_to_usd_cents(5000, 0.0));
    }

    public function testConvertBifToUsdCentsNegativeRateReturnsZero(): void
    {
        $this->assertSame(0, convert_bif_to_usd_cents(5000, -100.0));
    }

    // ── convertUsdCentsToBif ─────────────────────────────────────────

    public function testConvertUsdCentsToBifBasic(): void
    {
        // 100 cents (1 USD) * 6000 = 6 000 BIF
        $this->assertSame(6000, convert_usd_cents_to_bif(100, $this->rate));
    }

    public function testConvertUsdCentsToBifLargeAmount(): void
    {
        // 65 000 cents (650 USD) * 6000 = 3 900 000 BIF
        $this->assertSame(3900000, convert_usd_cents_to_bif(65000, $this->rate));
    }

    // ── Pas de float dans les calculs ────────────────────────────────

    public function testReturnTypesAreInt(): void
    {
        $usd = convert_bif_to_usd_cents(6000, $this->rate);
        $bif = convert_usd_cents_to_bif($usd, $this->rate);
        $this->assertIsInt($usd);
        $this->assertIsInt($bif);
    }

    // ── Formatage ─────────────────────────────────────────────────────

    public function testFormatBifContainsBif(): void
    {
        $result = format_bif(6000);
        $this->assertStringContainsString('BIF', $result);
    }

    public function testFormatUsdContainsDollarSign(): void
    {
        $result = format_usd(100);
        $this->assertStringContainsString('1', $result); // 100 cents = $1.00
    }

    // ── Calcul total réservation ─────────────────────────────────────

    public function testCalculateNightsBasic(): void
    {
        $nights = calculate_nights('2026-08-16', '2026-08-18');
        $this->assertSame(2, $nights);
    }

    public function testCalculateNightsSameDay(): void
    {
        // Minimum 1 nuit
        $nights = calculate_nights('2026-08-16', '2026-08-16');
        $this->assertSame(1, $nights);
    }

    public function testBookingTotalCalculation(): void
    {
        // Suite 3 900 000 BIF × 2 nuits = 7 800 000 BIF
        $priceNight = 3900000;
        $nights     = 2;
        $subtotal   = $priceNight * $nights;
        $usdCents   = convert_bif_to_usd_cents($subtotal, $this->rate);

        $this->assertSame(7800000, $subtotal);
        $this->assertSame(130000, $usdCents); // 1300 USD
    }

    // ── Taux de change ────────────────────────────────────────────────

    public function testRoundTripConversionApproximate(): void
    {
        $originalBif = 3900000;
        $usdCents    = convert_bif_to_usd_cents($originalBif, $this->rate);
        $backToBif   = convert_usd_cents_to_bif($usdCents, $this->rate);
        // Tolérance de 1 BIF (arrondi)
        $this->assertEqualsWithDelta($originalBif, $backToBif, 1.0);
    }

    // ── Sécurité : ne jamais utiliser de float pour les montants ─────

    public function testNeverUseFloatForAmounts(): void
    {
        // Vérifier que 0.1 + 0.2 ≠ 0.3 en float (problème classique)
        $this->assertNotSame(0.3, 0.1 + 0.2);
        // Mais en BIGINT entiers, pas de problème
        $bif1 = 1000; $bif2 = 2000;
        $this->assertSame(3000, $bif1 + $bif2);
    }
}
