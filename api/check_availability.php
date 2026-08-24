<?php
declare(strict_types=1);
/**
 * API Legacy — Vérification disponibilité
 * Redirige vers le service BookingService modernisé.
 */
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

use App\Booking\BookingService;
use App\Payments\CurrencyService;

$pdo      = getDB();
$checkin  = trim($_GET['checkin']  ?? '');
$checkout = trim($_GET['checkout'] ?? '');
$adults   = max(1, (int)($_GET['adults'] ?? 1));

if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); exit; }

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkin)
 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkout)
 || $checkout <= $checkin) {
    echo json_error('INVALID_DATES','Dates invalides.',422); exit;
}

$service  = new BookingService($pdo);
$currency = new CurrencyService($pdo);
$rooms    = $service->getAvailableRooms($checkin, $checkout, $adults);
$rate     = $currency->getActiveRate();
$nights   = calculate_nights($checkin, $checkout);

$result = array_map(function ($r) use ($currency, $rate, $nights) {
    $priceBif = (int)$r['price_per_night_bif'];
    $totalBif = $priceBif * $nights;
    return [
        'id'              => (int)$r['id'],
        'name'            => $r['name'],
        'description'     => $r['description'],
        'price_per_night' => $priceBif,
        'price_formatted' => $currency->formatBif($priceBif),
        'total_bif'       => $totalBif,
        'total_formatted' => $currency->formatBif($totalBif),
        'capacity'        => (int)$r['capacity_adults'],
        'surface'         => (int)$r['surface_m2'],
        'view'            => $r['view'],
        'photo'           => $r['photo_main'],
        'amenities'       => json_decode($r['amenities_json'] ?? '[]', true) ?? [],
        'category'        => $r['category_name'] ?? '',
    ];
}, $rooms);

echo json_success($result, [
    'checkin'  => $checkin,
    'checkout' => $checkout,
    'nights'   => $nights,
    'rate'     => $rate,
]);
