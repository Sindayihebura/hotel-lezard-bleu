<?php
declare(strict_types=1);
/**
 * API Legacy — Création de réservation
 * Utilise le BookingService modernisé.
 */
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_error('METHOD','Méthode non autorisée.',405); exit;
}

use App\Booking\BookingService;
use App\Security\CsrfGuard;

$pdo = getDB();
if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); exit; }

// Vérification CSRF
if (!CsrfGuard::verifyRequest()) {
    echo json_error('CSRF_INVALID','Jeton de sécurité invalide.',403); exit;
}

$body = [];
$raw  = file_get_contents('php://input');
if ($raw && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    $body = json_decode($raw, true) ?? [];
} else {
    $body = $_POST;
}

$customerId = !empty($_SESSION['customer_auth']) ? (int)$_SESSION['customer_id'] : null;

$service = new BookingService($pdo);
$result  = $service->create($body, $customerId);

if (!$result['success']) {
    echo json_error('BOOKING_FAILED', $result['error'] ?? 'Erreur.', 422); exit;
}

http_response_code(201);
echo json_success([
    'booking_id' => $result['booking_id'],
    'reference'  => $result['reference'],
    'total_bif'  => $result['totals']['total_bif'],
    'exchange_rate' => $result['totals']['rate'],
    'status'     => 'provisional',
    'message'    => 'Réservation créée. Procédez au paiement pour confirmer.',
]);
