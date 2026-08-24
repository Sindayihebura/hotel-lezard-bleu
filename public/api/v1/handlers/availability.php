<?php
declare(strict_types=1);

use App\Booking\BookingService;
use App\Http\Request;
use App\Payments\CurrencyService;

/**
 * Handler GET /api/v1/availability
 */
function handleAvailability(?PDO $pdo, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    $checkin  = trim($req->query('checkin', ''));
    $checkout = trim($req->query('checkout', ''));
    $adults   = max(1, min(10, (int)$req->query('adults', '1')));

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkin) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkout)) {
        echo json_error('INVALID_DATES','Dates invalides. Format : YYYY-MM-DD.',422); return;
    }
    if ($checkout <= $checkin) {
        echo json_error('INVALID_DATES','Le départ doit être après l\'arrivée.',422); return;
    }
    if ($checkin < date('Y-m-d')) {
        echo json_error('INVALID_DATES','La date d\'arrivée ne peut pas être dans le passé.',422); return;
    }

    $service  = new BookingService($pdo);
    $currency = new CurrencyService($pdo);
    $rooms    = $service->getAvailableRooms($checkin, $checkout, $adults);
    $rate     = $currency->getActiveRate();
    $nights   = calculate_nights($checkin, $checkout);

    $result = array_map(function ($r) use ($currency, $rate, $nights) {
        $priceNight = (int)$r['price_per_night_bif'];
        $totalBif   = $priceNight * $nights;
        return [
            'id'                   => (int)$r['id'],
            'slug'                 => $r['slug'],
            'name'                 => $r['name'],
            'category'             => $r['category_name'],
            'description'          => $r['description'],
            'capacity_adults'      => (int)$r['capacity_adults'],
            'surface_m2'           => (int)$r['surface_m2'],
            'view'                 => $r['view'],
            'photo_main'           => $r['photo_main'],
            'amenities'            => json_decode($r['amenities_json'] ?? '[]', true) ?? [],
            'price_per_night_bif'  => $priceNight,
            'price_per_night_usd'  => $currency->formatUsd($currency->bifToUsdCents($priceNight, $rate)),
            'total_bif'            => $totalBif,
            'price_formatted_bif'  => $currency->formatBif($priceNight),
        ];
    }, $rooms);

    echo json_success($result, [
        'checkin'       => $checkin,
        'checkout'      => $checkout,
        'nights'        => $nights,
        'adults'        => $adults,
        'exchange_rate' => $rate,
        'count'         => count($result),
    ]);
}
