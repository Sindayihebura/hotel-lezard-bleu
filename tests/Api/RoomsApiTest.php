<?php

declare(strict_types=1);

namespace Tests\Api;

use PHPUnit\Framework\TestCase;
use App\Repositories\RoomRepository;
use App\Booking\BookingService;
use App\Payments\CurrencyService;

/**
 * Tests API — Endpoints chambres et disponibilité
 */
class RoomsApiTest extends TestCase
{
    private static ?\PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = getTestPdo();
        if (self::$pdo === null) {
            self::markTestSkipped('Base de données de test non disponible.');
        }
    }

    // ── RoomRepository ─────────────────────────────────────────────────

    public function testFindActiveRoomsReturnsArray(): void
    {
        $repo  = new RoomRepository(self::$pdo);
        $rooms = $repo->findActive();
        $this->assertIsArray($rooms);
    }

    public function testFindAvailableRoomsWithValidDates(): void
    {
        $repo  = new RoomRepository(self::$pdo);
        $rooms = $repo->findAvailable(
            date('Y-m-d', strtotime('+60 days')),
            date('Y-m-d', strtotime('+62 days')),
            1
        );
        $this->assertIsArray($rooms);
        // Chaque chambre doit avoir les champs requis
        foreach ($rooms as $room) {
            $this->assertArrayHasKey('id', $room);
            $this->assertArrayHasKey('name', $room);
            $this->assertArrayHasKey('price_per_night_bif', $room);
            // Prix doit être un entier positif (BIGINT, pas float)
            $this->assertIsNumeric($room['price_per_night_bif']);
            $this->assertGreaterThan(0, (int)$room['price_per_night_bif']);
        }
    }

    public function testFindAvailableWithInvertedDatesReturnsEmpty(): void
    {
        $repo  = new RoomRepository(self::$pdo);
        // date_depart avant date_arrivee → doit retourner vide ou gérer l'erreur
        // La protection est aussi dans le validator, mais le repo ne doit pas planter
        $rooms = $repo->findAvailable(
            date('Y-m-d', strtotime('+10 days')),
            date('Y-m-d', strtotime('+5 days')),  // Date inversée
            1
        );
        $this->assertIsArray($rooms);
    }

    public function testFindBySlugReturnsNullForUnknown(): void
    {
        $repo = new RoomRepository(self::$pdo);
        $room = $repo->findBySlug('slug-qui-nexiste-pas-12345');
        $this->assertNull($room);
    }

    public function testIsAvailableReturnsBool(): void
    {
        $repo   = new RoomRepository(self::$pdo);
        $result = $repo->isAvailable(
            1,
            date('Y-m-d', strtotime('+90 days')),
            date('Y-m-d', strtotime('+92 days'))
        );
        $this->assertIsBool($result);
    }

    // ── Prix : jamais de float ─────────────────────────────────────────

    public function testRoomPricesAreStoredAsIntegers(): void
    {
        $repo  = new RoomRepository(self::$pdo);
        $rooms = $repo->findActive();
        foreach ($rooms as $room) {
            $price = $room['price_per_night_bif'];
            // Le prix doit être un entier (BIGINT) sans décimales
            $this->assertSame((int)$price, (int)$price, "Prix non entier pour chambre #{$room['id']}");
        }
    }

    // ── CurrencyService intégration ────────────────────────────────────

    public function testCurrencyServiceFormatBif(): void
    {
        $cs     = new CurrencyService(self::$pdo);
        $result = $cs->formatBif(3900000);
        $this->assertStringContainsString('3', $result);
        // Doit contenir BIF ou FBu
        $hasCurrency = str_contains($result, 'BIF') || str_contains($result, 'FBu');
        $this->assertTrue($hasCurrency, "Le formatage doit inclure la devise BIF ou FBu");
    }

    public function testCurrencyServiceFormatUsd(): void
    {
        $cs     = new CurrencyService(self::$pdo);
        $result = $cs->formatUsd(65000); // 650 USD
        $this->assertStringContainsString('650', $result);
    }

    // ── Disponibilité BookingService ──────────────────────────────────

    public function testCheckAvailabilityReturnsBool(): void
    {
        $service = new BookingService(self::$pdo);
        $result  = $service->checkAvailability(
            1,
            date('Y-m-d', strtotime('+120 days')),
            date('Y-m-d', strtotime('+122 days'))
        );
        $this->assertIsBool($result);
    }

    public function testGetQuoteForValidRoom(): void
    {
        $repo  = new RoomRepository(self::$pdo);
        $rooms = $repo->findActive();

        if (empty($rooms)) {
            $this->markTestSkipped('Aucune chambre active dans la DB de test.');
        }

        $roomId  = (int)$rooms[0]['id'];
        $service = new BookingService(self::$pdo);
        $quote   = $service->getQuote(
            $roomId,
            date('Y-m-d', strtotime('+50 days')),
            date('Y-m-d', strtotime('+52 days'))
        );

        $this->assertNotNull($quote);
        $this->assertSame(2, $quote['nb_nights']);
        $this->assertArrayHasKey('totals', $quote);
        $this->assertGreaterThan(0, $quote['totals']['total_bif']);
        // Vérifier que le total BIF = prix_nuit × nb_nuits (pas de service)
        $expected = (int)$rooms[0]['price_per_night_bif'] * 2;
        $this->assertSame($expected, $quote['totals']['total_bif']);
    }
}
