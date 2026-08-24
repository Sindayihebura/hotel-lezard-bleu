<?php
declare(strict_types=1);

use App\Booking\BookingService;
use App\Http\Request;

/** POST /api/v1/booking-quotes */
function handleBookingQuote(?PDO $pdo, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    $body     = $req->jsonBody();
    $roomId   = (int)($body['room_id']      ?? 0);
    $checkin  = trim($body['checkin']        ?? '');
    $checkout = trim($body['checkout']       ?? '');
    $code     = strtoupper(trim($body['offer_code'] ?? ''));

    if (!$roomId || !$checkin || !$checkout) {
        echo json_error('MISSING_PARAMS','room_id, checkin et checkout sont obligatoires.',422); return;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$checkin) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$checkout)) {
        echo json_error('INVALID_DATES','Format de date invalide. Utilisez YYYY-MM-DD.',422); return;
    }
    if ($checkout <= $checkin) {
        echo json_error('INVALID_DATES','La date de départ doit être après l\'arrivée.',422); return;
    }

    $service = new BookingService($pdo);
    $quote   = $service->getQuote($roomId, $checkin, $checkout, $code);

    if (!$quote) {
        echo json_error('ROOM_NOT_FOUND','Chambre introuvable ou inactive.',404); return;
    }

    $available = $service->checkAvailability($roomId, $checkin, $checkout);

    echo json_success([
        'room_id'      => $roomId,
        'checkin'      => $checkin,
        'checkout'     => $checkout,
        'nb_nights'    => $quote['nb_nights'],
        'available'    => $available,
        'offer_code'   => $code ?: null,
        'discount_bif' => $quote['discount_bif'],
        'price_per_night_bif' => $quote['totals']['price_per_night_bif'],
        'subtotal_bif'        => $quote['totals']['subtotal_bif'],
        'total_bif'           => $quote['totals']['total_bif'],
        'total_usd_cents'     => $quote['totals']['total_usd_cents'],
        'exchange_rate'       => $quote['totals']['rate'],
    ]);
}
