<?php

declare(strict_types=1);

namespace App\Booking;

use PDO;
use App\Repositories\BookingRepository;
use App\Repositories\RoomRepository;
use App\Repositories\CustomerRepository;
use App\Payments\CurrencyService;
use App\Security\Logger;
use App\Validators\BookingValidator;

/**
 * BookingService — Moteur de réservation
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 *
 * Flux :
 * 1. Vérifier disponibilité (double-lock via transaction)
 * 2. Calculer total (taux figé)
 * 3. Créer réservation provisoire
 * 4. Initier paiement
 * 5. Confirmer sur succès paiement
 */
class BookingService
{
    private PDO                $pdo;
    private BookingRepository  $bookings;
    private RoomRepository     $rooms;
    private CustomerRepository $customers;
    private CurrencyService    $currency;
    private Logger             $logger;

    public function __construct(PDO $pdo)
    {
        $this->pdo       = $pdo;
        $this->bookings  = new BookingRepository($pdo);
        $this->rooms     = new RoomRepository($pdo);
        $this->customers = new CustomerRepository($pdo);
        $this->currency  = new CurrencyService($pdo);
        $this->logger    = new Logger($pdo);
    }

    // ── Disponibilité ─────────────────────────────────────────────────

    /**
     * Retourner les chambres disponibles pour les critères donnés.
     */
    public function getAvailableRooms(string $checkin, string $checkout, int $nbAdults = 1): array
    {
        return $this->rooms->findAvailable($checkin, $checkout, $nbAdults);
    }

    /**
     * Vérifier la disponibilité d'une chambre spécifique (pour l'API).
     */
    public function checkAvailability(int $roomId, string $checkin, string $checkout): bool
    {
        return $this->rooms->isAvailable($roomId, $checkin, $checkout);
    }

    // ── Devis ─────────────────────────────────────────────────────────

    /**
     * Calculer un devis sans créer la réservation.
     */
    public function getQuote(int $roomId, string $checkin, string $checkout, string $offerCode = ''): ?array
    {
        $room = $this->rooms->findById($roomId);
        if (!$room || !(bool)$room['is_active']) {
            return null;
        }

        $nbNights    = calculate_nights($checkin, $checkout);
        $priceNight  = (int)$room['price_per_night_bif'];
        $subtotal    = $priceNight * $nbNights;
        $discountBif = $this->currency->applyOfferCode($offerCode, $subtotal, $nbNights);
        $totals      = $this->currency->calculateBookingTotal($priceNight, $nbNights, 0, $discountBif);

        return [
            'room'         => $room,
            'checkin'      => $checkin,
            'checkout'     => $checkout,
            'nb_nights'    => $nbNights,
            'offer_code'   => $offerCode,
            'discount_bif' => $discountBif,
            'totals'       => $totals,
        ];
    }

    // ── Création réservation ──────────────────────────────────────────

    /**
     * Créer une réservation provisoire.
     * Toutes les validations sont faites ici côté serveur.
     *
     * @return array{success: bool, booking_id: int|null, reference: string|null, error: string|null}
     */
    public function create(array $data, ?int $customerId = null): array
    {
        // 1. Valider les données
        $validator = new BookingValidator();
        if (!$validator->validate($data)) {
            return ['success' => false, 'booking_id' => null, 'reference' => null,
                    'error' => $validator->firstError()];
        }

        $roomId   = (int)$data['room_id'];
        $checkin  = $data['date_arrivee'];
        $checkout = $data['date_depart'];

        // 2. Vérifier la chambre
        $room = $this->rooms->findById($roomId);
        if (!$room || !(bool)$room['is_active']) {
            return ['success' => false, 'booking_id' => null, 'reference' => null,
                    'error' => 'Chambre introuvable ou inactive.'];
        }

        // 3. Vérifier capacité
        if ((int)$data['nb_adults'] > (int)$room['capacity_adults']) {
            return ['success' => false, 'booking_id' => null, 'reference' => null,
                    'error' => 'Capacité adultes dépassée pour cette chambre.'];
        }

        // 4. Vérifier disponibilité + créer en transaction (anti double-booking)
        $this->pdo->beginTransaction();
        try {
            // Verrouiller la ligne chambre pendant le check
            $this->pdo->prepare(
                "SELECT id FROM rooms WHERE id = :id FOR UPDATE"
            )->execute([':id' => $roomId]);

            if (!$this->rooms->isAvailable($roomId, $checkin, $checkout)) {
                $this->pdo->rollBack();
                return ['success' => false, 'booking_id' => null, 'reference' => null,
                        'error' => 'Cette chambre n\'est plus disponible pour ces dates.'];
            }

            // 5. Calculer le total (taux figé)
            $nbNights    = calculate_nights($checkin, $checkout);
            $priceNight  = (int)$room['price_per_night_bif'];
            $offerCode   = strtoupper(trim($data['offer_code'] ?? ''));
            $subtotal    = $priceNight * $nbNights;

            // Services additionnels
            $servicesBif = 0;
            $servicesJson = null;
            if (!empty($data['services']) && is_array($data['services'])) {
                [$servicesBif, $servicesJson] = $this->calculateServices($data['services'], $nbNights);
            }

            $discountBif = $this->currency->applyOfferCode($offerCode, $subtotal, $nbNights);
            $totals      = $this->currency->calculateBookingTotal($priceNight, $nbNights, $servicesBif, $discountBif);

            // 6. Générer la référence unique
            $reference = $this->generateReference();

            // 7. Insérer la réservation
            $currency = in_array($data['currency_chosen'] ?? 'BIF', ['BIF', 'USD']) ? $data['currency_chosen'] : 'BIF';

            $bookingId = (int)$this->bookings->insert([
                'reference'           => $reference,
                'room_id'             => $roomId,
                'customer_id'         => $customerId,
                'guest_first_name'    => trim($data['guest_first_name']),
                'guest_last_name'     => trim($data['guest_last_name']),
                'guest_email'         => strtolower(trim($data['guest_email'])),
                'guest_phone'         => trim($data['guest_phone']),
                'guest_country'       => strtoupper(substr(trim($data['guest_country'] ?? ''), 0, 2)) ?: null,
                'date_arrivee'        => $checkin,
                'date_depart'         => $checkout,
                'nb_adults'           => (int)$data['nb_adults'],
                'nb_children'         => (int)($data['nb_children'] ?? 0),
                'currency_chosen'     => $currency,
                'exchange_rate_used'  => number_format($totals['rate'], 6, '.', ''),
                'price_per_night_bif' => $priceNight,
                'nb_nights'           => $nbNights,
                'subtotal_bif'        => $totals['subtotal_bif'],
                'services_total_bif'  => $totals['services_total_bif'],
                'discount_bif'        => $totals['discount_bif'],
                'total_bif'           => $totals['total_bif'],
                'total_usd_cents'     => $totals['total_usd_cents'],
                'price_snapshot_json' => $totals['snapshot_json'],
                'payment_method'      => $data['payment_method'] ?? 'manual',
                'payment_status'      => 'unpaid',
                'statut'              => 'provisional',
                'services_json'       => $servicesJson,
                'offer_code'          => $offerCode ?: null,
                'special_requests'    => substr(trim($data['special_requests'] ?? ''), 0, 1000) ?: null,
                'source'              => 'web',
            ]);

            // 8. Incrémenter le compteur d'utilisation du code promo
            if ($offerCode && $discountBif > 0) {
                $this->pdo->prepare(
                    "UPDATE offers SET uses_count = uses_count + 1 WHERE code = :code"
                )->execute([':code' => $offerCode]);
            }

            $this->pdo->commit();

            $this->logger->audit(
                Logger::ACTION_BOOKING_CREATED,
                'booking', $bookingId,
                null,
                ['reference' => $reference, 'room_id' => $roomId,
                 'total_bif' => $totals['total_bif'], 'nights' => $nbNights],
                null, 'success', null, $customerId
            );

            return [
                'success'    => true,
                'booking_id' => $bookingId,
                'reference'  => $reference,
                'totals'     => $totals,
                'error'      => null,
            ];

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[BookingService] create error: ' . $e->getMessage());
            return ['success' => false, 'booking_id' => null, 'reference' => null,
                    'error' => 'Erreur lors de la création de la réservation.'];
        }
    }

    // ── Confirmation / Statuts ────────────────────────────────────────

    public function confirm(int $bookingId): bool
    {
        $ok = $this->bookings->updateStatus($bookingId, 'confirmed');
        if ($ok) {
            $this->logger->audit(Logger::ACTION_BOOKING_UPDATED, 'booking', $bookingId,
                ['statut' => 'provisional'], ['statut' => 'confirmed']);
        }
        return $ok;
    }

    public function checkin(int $bookingId, int $adminUserId): bool
    {
        $ok = $this->bookings->updateStatus($bookingId, 'checked_in');
        if ($ok) {
            $this->logger->audit(Logger::ACTION_BOOKING_CHECKIN, 'booking', $bookingId,
                null, ['statut' => 'checked_in'], $adminUserId);
        }
        return $ok;
    }

    public function checkout(int $bookingId, int $adminUserId): bool
    {
        $ok = $this->bookings->updateStatus($bookingId, 'checked_out');
        if ($ok) {
            $this->logger->audit(Logger::ACTION_BOOKING_CHECKOUT, 'booking', $bookingId,
                null, ['statut' => 'checked_out'], $adminUserId);
        }
        return $ok;
    }

    public function cancel(int $bookingId, string $reason, string $cancelledBy, ?int $adminUserId = null): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE bookings
                SET statut = 'cancelled',
                    cancelled_at = UTC_TIMESTAMP(),
                    cancellation_reason = :reason,
                    cancelled_by = :by,
                    updated_at = UTC_TIMESTAMP()
                WHERE id = :id
                  AND statut NOT IN ('checked_out','cancelled')
            ");
            $stmt->execute([':reason' => $reason, ':by' => $cancelledBy, ':id' => $bookingId]);
            $ok = $stmt->rowCount() > 0;
            if ($ok) {
                $this->logger->audit(Logger::ACTION_BOOKING_CANCELLED, 'booking', $bookingId,
                    null, ['reason' => $reason, 'by' => $cancelledBy], $adminUserId);
            }
            return $ok;
        } catch (\PDOException $e) {
            error_log('[BookingService] cancel error: ' . $e->getMessage());
            return false;
        }
    }

    // ── Helpers privés ────────────────────────────────────────────────

    private function generateReference(): string
    {
        $max = 5;
        for ($i = 0; $i < $max; $i++) {
            $ref = 'RES-BDI-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $this->pdo->prepare("SELECT id FROM bookings WHERE reference = :ref LIMIT 1");
            $stmt->execute([':ref' => $ref]);
            if (!$stmt->fetchColumn()) {
                return $ref;
            }
        }
        return 'RES-BDI-' . date('ymdHis') . random_int(10, 99);
    }

    private function calculateServices(array $serviceIds, int $nbNights): array
    {
        if (empty($serviceIds)) return [0, null];

        $total    = 0;
        $details  = [];
        foreach ($serviceIds as $sid) {
            $sid  = (int)$sid;
            if ($sid <= 0) continue;
            $stmt = $this->pdo->prepare("SELECT id, title, price_bif, price_unit FROM services WHERE id = :id AND is_active = 1 LIMIT 1");
            $stmt->execute([':id' => $sid]);
            $svc = $stmt->fetch();
            if (!$svc) continue;
            $price    = (int)$svc['price_bif'];
            $total   += $price;
            $details[] = ['id' => $sid, 'title' => $svc['title'], 'price_bif' => $price];
        }
        return [$total, !empty($details) ? json_encode($details) : null];
    }
}
