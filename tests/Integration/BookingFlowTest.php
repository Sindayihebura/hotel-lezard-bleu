<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Booking\BookingService;
use App\Payments\CurrencyService;
use App\Repositories\BookingRepository;

/**
 * Tests d'intégration — Flux de réservation complet
 * Nécessite une base de données de test configurée dans phpunit.xml
 */
class BookingFlowTest extends TestCase
{
    private static ?\PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = getTestPdo();
        if (self::$pdo === null) {
            self::markTestSkipped('Base de données de test non disponible.');
        }
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('Base de données non disponible.');
        }
        // Démarrer une transaction pour isoler chaque test
        self::$pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback après chaque test — ne pas polluer la DB de test
        if (self::$pdo && self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
    }

    // ── Tests CurrencyService ─────────────────────────────────────────

    public function testCurrencyServiceGetRate(): void
    {
        $cs   = new CurrencyService(self::$pdo);
        $rate = $cs->getActiveRate();
        $this->assertGreaterThan(0.0, $rate, 'Le taux de change doit être positif.');
        $this->assertLessThan(1000000.0, $rate, 'Le taux de change semble anormal.');
    }

    public function testCurrencyConversionRoundTrip(): void
    {
        $cs       = new CurrencyService(self::$pdo);
        $rate     = $cs->getActiveRate();
        $original = 3900000; // BIF

        $usdCents = $cs->bifToUsdCents($original, $rate);
        $backToBif = $cs->usdCentsToBif($usdCents, $rate);

        // Tolérance de 1 BIF (arrondi)
        $this->assertEqualsWithDelta($original, $backToBif, 1.0);
    }

    public function testCalculateBookingTotal(): void
    {
        $cs = new CurrencyService(self::$pdo);

        // Suite Présidentielle : 3 900 000 BIF × 2 nuits
        $result = $cs->calculateBookingTotal(3900000, 2);

        $this->assertSame(7800000, $result['total_bif']);
        $this->assertSame(3900000, $result['price_per_night_bif']);
        $this->assertSame(2, $result['nb_nights']);
        $this->assertSame(7800000, $result['subtotal_bif']);
        $this->assertSame(0, $result['discount_bif']);
        $this->assertGreaterThan(0, $result['total_usd_cents']);
        $this->assertArrayHasKey('rate', $result);
        $this->assertArrayHasKey('snapshot_json', $result);
    }

    public function testCalculateBookingTotalWithDiscount(): void
    {
        $cs = new CurrencyService(self::$pdo);
        // 10% de remise sur 2 000 000 BIF
        $discount = (int)round(2000000 * 0.10);
        $result   = $cs->calculateBookingTotal(1000000, 2, 0, $discount);

        $this->assertSame(2000000, $result['subtotal_bif']);
        $this->assertSame($discount, $result['discount_bif']);
        $this->assertSame(2000000 - $discount, $result['total_bif']);
    }

    // ── Tests BookingRepository ───────────────────────────────────────

    public function testBookingRepositoryFindByReferenceReturnsNullForUnknown(): void
    {
        $repo = new BookingRepository(self::$pdo);
        $result = $repo->findByReference('RES-BDI-XXXXXXXX');
        $this->assertNull($result);
    }

    public function testBookingRepositoryFindByCustomerReturnsArray(): void
    {
        $repo  = new BookingRepository(self::$pdo);
        $items = $repo->findByCustomer(999999999); // ID inexistant
        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }

    // ── Tests BookingService ─────────────────────────────────────────

    public function testBookingServiceGetAvailableRoomsReturnsArray(): void
    {
        $service = new BookingService(self::$pdo);
        $rooms   = $service->getAvailableRooms(
            date('Y-m-d', strtotime('+30 days')),
            date('Y-m-d', strtotime('+32 days')),
            1
        );
        $this->assertIsArray($rooms);
    }

    public function testBookingServiceCreateFailsWithInvalidData(): void
    {
        $service = new BookingService(self::$pdo);
        $result  = $service->create([
            'date_arrivee' => '', // Invalide
            'date_depart'  => '',
            'room_id'      => 0,
            'nb_adults'    => 1,
            'guest_first_name' => '',
            'guest_last_name'  => '',
            'guest_email'      => 'bad-email',
            'guest_phone'      => '',
            'payment_method'   => 'invalid',
            'currency_chosen'  => 'BIF',
        ]);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['error']);
        $this->assertNull($result['booking_id']);
    }

    public function testBookingServiceQuoteReturnsNullForInvalidRoom(): void
    {
        $service = new BookingService(self::$pdo);
        $quote   = $service->getQuote(
            999999,
            date('Y-m-d', strtotime('+7 days')),
            date('Y-m-d', strtotime('+9 days'))
        );
        $this->assertNull($quote);
    }

    // ── Tests de sécurité SQL (via repository) ────────────────────────

    public function testSqlInjectionInSearchIsNeutralized(): void
    {
        $repo  = new BookingRepository(self::$pdo);
        // Tenter une injection dans le filtre search
        $items = $repo->searchAdmin(['search' => "'; DROP TABLE bookings;--"], 5, 0);
        // Si on arrive ici sans exception, l'injection est neutralisée
        $this->assertIsArray($items);
    }

    public function testSqlInjectionInReferenceIsNeutralized(): void
    {
        $repo   = new BookingRepository(self::$pdo);
        $result = $repo->findByReference("' OR '1'='1");
        // Doit retourner null (pas de résultat), pas d'erreur SQL
        $this->assertNull($result);
    }
}
